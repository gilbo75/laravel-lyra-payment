<?php

namespace LyraPayment;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use LyraPayment\Contracts\TransactionIdGenerator;
use LyraPayment\Exceptions\LyraPaymentException;

class LyraPayment
{
    /**
     * @param  array<string, mixed>  $config  the "lyra-payment" config array
     */
    public function __construct(
        private readonly array $config,
        private readonly TransactionIdGenerator $transactionIds,
        private readonly Router $router,
        private readonly UrlGenerator $url,
    ) {
    }

    /**
     * A new payment request, pre-filled from the configuration.
     */
    public function request(): PaymentRequest
    {
        $request = new PaymentRequest($this->config, $this->transactionIds);

        $request->fill($this->config['defaults'] ?? []);

        if ($returnMode = $this->config['return_mode'] ?? null) {
            $request->set('return_mode', strtoupper($returnMode));
        }

        if ($returnUrl = $this->config['url_return'] ?? $this->routeUrl('lyra-payment.return')) {
            $request->set('url_return', $returnUrl);
        }

        if (filter_var($this->config['send_ipn_url'] ?? false, FILTER_VALIDATE_BOOL)) {
            $ipnUrl = $this->config['ipn_url'] ?? $this->routeUrl('lyra-payment.ipn')
                ?? throw new LyraPaymentException('send_ipn_url is enabled but no IPN URL is available.');
            $request->set('url_check', $ipnUrl);
        }

        return $request;
    }

    /**
     * Verify a set of fields received from the gateway.
     *
     * @param  array<string, mixed>  $fields
     */
    public function verify(array $fields): PaymentResponse
    {
        $fields = array_filter($fields, fn ($value) => is_scalar($value) || $value === null);

        $mode = strtoupper((string) ($fields['vads_ctx_mode'] ?? $this->config['mode'] ?? 'TEST'));
        $key = (string) ($mode === 'PRODUCTION' ? $this->config['key_prod'] : $this->config['key_test']);

        $authentic = (new Signature($key, (string) $this->config['sign_algo']))->verify($fields);

        // Never accept a TEST payment as genuine on a shop configured for PRODUCTION.
        if ($authentic && strtoupper((string) $this->config['mode']) === 'PRODUCTION' && $mode !== 'PRODUCTION') {
            $authentic = false;
        }

        return new PaymentResponse($fields, $authentic);
    }

    /**
     * Verify the gateway call in an HTTP request.
     *
     * Uses the raw query string / body: Laravel's TrimStrings and
     * ConvertEmptyStringsToNull middleware alter input values, which would
     * break the signature check.
     */
    public function verifyRequest(Request $request): PaymentResponse
    {
        return $this->verify($this->rawFields($request));
    }

    /**
     * @return array<string, string>
     */
    public function rawFields(Request $request): array
    {
        $fields = [];

        parse_str((string) $request->server('QUERY_STRING', ''), $query);
        $fields = array_merge($fields, $query);

        if ($request->isMethod('POST')) {
            $content = (string) $request->getContent();

            if ($content !== '' && str_contains((string) $request->header('Content-Type'), 'application/x-www-form-urlencoded')) {
                parse_str($content, $body);
            } else {
                $body = $request->request->all();
            }

            $fields = array_merge($fields, $body);
        }

        return array_filter($fields, fn ($value) => is_string($value));
    }

    public function config(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    private function routeUrl(string $name): ?string
    {
        return $this->router->has($name) ? $this->url->route($name) : null;
    }
}
