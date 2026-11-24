<?php

namespace LyraPayment;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Contracts\Support\Responsable;
use LyraPayment\Contracts\TransactionIdGenerator;
use LyraPayment\Exceptions\InvalidPaymentRequest;
use LyraPayment\Exceptions\LyraPaymentException;
use LyraPayment\Support\Currency;
use LyraPayment\Support\Fields;

/**
 * Fluent builder for a signed hosted-payment-form request.
 *
 * Return it from a controller to send the customer to the payment page:
 *
 *     return LyraPayment::request()
 *         ->amount(1999)->currency('EUR')
 *         ->orderId($order->reference)
 *         ->customer(['email' => $user->email]);
 */
class PaymentRequest implements Responsable
{
    /** @var array<string, string> */
    private array $fields = [];

    private readonly DateTimeImmutable $createdAt;

    /**
     * @param  array{site_id: ?string, mode: string, key_test: ?string, key_prod: ?string, platform_url: string, sign_algo: string, debug?: bool}  $config
     */
    public function __construct(
        private readonly array $config,
        TransactionIdGenerator $transactionIds,
        ?DateTimeInterface $now = null,
    ) {
        $this->createdAt = DateTimeImmutable::createFromInterface($now ?? new DateTimeImmutable())
            ->setTimezone(new DateTimeZone('UTC'));

        $this->set('version', 'V2');
        $this->set('page_action', 'PAYMENT');
        $this->set('action_mode', 'INTERACTIVE');
        $this->set('payment_config', 'SINGLE');
        $this->set('site_id', (string) ($config['site_id'] ?? ''));
        $this->set('ctx_mode', strtoupper((string) ($config['mode'] ?? 'TEST')));
        $this->set('trans_date', $this->createdAt->format('YmdHis'));
        $this->set('trans_id', $transactionIds->generate($this->createdAt));
    }

    // ---------------------------------------------------------------------
    // Generic access
    // ---------------------------------------------------------------------

    /**
     * Set any vads_* field ("vads_" prefix optional). Null removes the field.
     */
    public function set(string $name, string|int|float|bool|null $value): static
    {
        $name = Fields::normalizeName($name);

        if (! Fields::isKnown($name)) {
            throw new LyraPaymentException("Unknown payment field [{$name}].");
        }

        if ($value === null) {
            unset($this->fields[$name]);

            return $this;
        }

        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }

        // Same clean-up as the original SDK: trim, drop "<" / ">", collapse whitespace.
        $value = preg_replace(['#[<>]+#u', '#\s+#u'], ['', ' '], trim((string) $value));

        $this->fields[$name] = $value;

        return $this;
    }

    /**
     * @param  array<string, string|int|float|bool|null>  $fields
     */
    public function fill(array $fields): static
    {
        foreach ($fields as $name => $value) {
            $this->set($name, $value);
        }

        return $this;
    }

    public function get(string $name): ?string
    {
        return $this->fields[Fields::normalizeName($name)] ?? null;
    }

    // ---------------------------------------------------------------------
    // Order
    // ---------------------------------------------------------------------

    /**
     * Amount in the currency's smallest unit (e.g. cents): 1999 = 19.99 EUR.
     */
    public function amount(int $minorUnits): static
    {
        if ($minorUnits < 0) {
            throw new LyraPaymentException('The amount cannot be negative.');
        }

        return $this->set('amount', $minorUnits);
    }

    /**
     * Amount as a decimal ("19.99"), converted with the currency's decimals.
     */
    public function amountDecimal(string|int|float $amount, string|int|null $currency = null): static
    {
        if ($currency !== null) {
            $this->currency($currency);
        }

        $code = $this->get('currency') ?? throw new LyraPaymentException('Set the currency before a decimal amount.');

        return $this->amount(Currency::of($code)->toMinorUnits($amount));
    }

    /**
     * ISO 4217 alpha ("EUR") or numeric ("978") code.
     */
    public function currency(string|int $code): static
    {
        return $this->set('currency', Currency::of($code)->numeric);
    }

    public function orderId(string|int $orderId): static
    {
        return $this->set('order_id', (string) $orderId);
    }

    /**
     * Free text returned untouched in the response (vads_order_info, info2, info3).
     */
    public function orderInfo(string $info, int $slot = 1): static
    {
        return $this->set($slot === 1 ? 'order_info' : "order_info{$slot}", $info);
    }

    public function language(string $isoCode): static
    {
        return $this->set('language', strtolower($isoCode));
    }

    /**
     * Customer details, keys without the "cust_" prefix:
     * id, email, first_name, last_name, name, title, status, legal_name,
     * address, address_number, district, zip, city, state, country, phone, cell_phone.
     *
     * @param  array<string, string|int|null>  $customer
     */
    public function customer(array $customer): static
    {
        foreach ($customer as $key => $value) {
            $this->set('cust_' . $key, $value);
        }

        return $this;
    }

    /**
     * Shipping details, keys without the "ship_to_" prefix:
     * first_name, last_name, name, legal_name, street, street2, zip, city,
     * state, country, phone_num, status, type, speed, delay, delivery_company_name.
     *
     * @param  array<string, string|int|null>  $shipping
     */
    public function shipping(array $shipping): static
    {
        foreach ($shipping as $key => $value) {
            $this->set('ship_to_' . $key, $value);
        }

        return $this;
    }

    /**
     * Add an order line (vads_product_*). Amount in minor units.
     */
    public function addProduct(
        string $label,
        int $amount,
        int $quantity,
        string $reference,
        ?string $type = null,
        string|float|null $vat = null,
    ): static {
        $index = (int) ($this->get('nb_products') ?? 0);

        $this->set("product_label{$index}", $label);
        $this->set("product_amount{$index}", $amount);
        $this->set("product_qty{$index}", $quantity);
        $this->set("product_ref{$index}", $reference);
        $this->set("product_type{$index}", $type);
        $this->set("product_vat{$index}", $vat === null ? null : (string) $vat);

        return $this->set('nb_products', $index + 1);
    }

    /**
     * Custom data returned in the response (vads_ext_info_{key}).
     */
    public function extInfo(string $key, string $value): static
    {
        if (! preg_match('/^[A-Za-z0-9_]+$/', $key)) {
            throw new LyraPaymentException("Invalid ext_info key [{$key}].");
        }

        return $this->set("ext_info_{$key}", $value);
    }

    // ---------------------------------------------------------------------
    // Payment options
    // ---------------------------------------------------------------------

    /**
     * Restrict the payment means offered, e.g. ['VISA', 'MASTERCARD'] or 'CB'.
     *
     * @param  string|array<int, string>  $cards
     */
    public function paymentCards(string|array $cards): static
    {
        return $this->set('payment_cards', is_array($cards) ? implode(';', $cards) : $cards);
    }

    /**
     * Merchant contract per acceptance network, e.g. ['ANCV' => '123459-1-1'].
     *
     * @param  string|array<string, string>  $contracts
     */
    public function contracts(string|array $contracts): static
    {
        if (is_array($contracts)) {
            $contracts = implode(';', array_map(
                fn ($network, $mid) => "{$network}={$mid}",
                array_keys($contracts),
                $contracts
            ));
        }

        return $this->set('contracts', $contracts);
    }

    public function captureDelay(int $days): static
    {
        return $this->set('capture_delay', $days);
    }

    /**
     * 0 = automatic capture, 1 = manual validation in the Back Office.
     */
    public function validationMode(int $mode): static
    {
        return $this->set('validation_mode', $mode);
    }

    public function pageAction(string $action): static
    {
        return $this->set('page_action', strtoupper($action));
    }

    /**
     * Pay in installments. Call after amount().
     *
     * @param  int  $count  number of installments (>= 2)
     * @param  int  $period  days between installments
     * @param  int|null  $first  first installment in minor units (default: total / count)
     */
    public function installments(int $count, int $period = 30, ?int $first = null): static
    {
        $total = (int) ($this->get('amount') ?? 0);

        if ($count < 2 || $period < 1) {
            throw new LyraPaymentException('Installments need count >= 2 and period >= 1.');
        }

        if ($total <= 0) {
            throw new LyraPaymentException('Set a positive amount before installments().');
        }

        $first ??= (int) round($total / $count);

        if ($first <= 0 || $first >= $total) {
            throw new LyraPaymentException('The first installment must be > 0 and less than the total.');
        }

        return $this->set('payment_config', "MULTI:first={$first};count={$count};period={$period}");
    }

    /**
     * Pay in installments, first installment given as a percentage of the total.
     */
    public function installmentsWithFirstPercent(int $count, int $period, int|float $firstPercent): static
    {
        $total = (int) ($this->get('amount') ?? 0);

        return $this->installments($count, $period, (int) round($total * $firstPercent / 100));
    }

    /**
     * Create a subscription (recurring payment) with a token.
     *
     * @param  int  $amount  amount of each payment in minor units
     * @param  string  $rule  iCalendar RRULE (RFC 5545), e.g. "RRULE:FREQ=MONTHLY;BYMONTHDAY=1"
     * @param  DateTimeInterface|string  $effectDate  first payment date (YYYYMMDD string or date)
     */
    public function subscription(
        int $amount,
        string $rule,
        DateTimeInterface|string $effectDate,
        string|int|null $currency = null,
        ?int $initialAmount = null,
        ?int $initialAmountCount = null,
    ): static {
        if (! in_array($this->get('page_action'), Fields::SUBSCRIBE_ACTIONS, true)) {
            $this->pageAction('REGISTER_SUBSCRIBE');
        }

        $currency ??= $this->get('currency') ?? throw new LyraPaymentException('Set a currency for the subscription.');

        return $this
            ->set('sub_amount', $amount)
            ->set('sub_currency', Currency::of($currency)->numeric)
            ->set('sub_desc', $rule)
            ->set('sub_effect_date', $effectDate instanceof DateTimeInterface ? $effectDate->format('Ymd') : $effectDate)
            ->set('sub_init_amount', $initialAmount)
            ->set('sub_init_amount_number', $initialAmountCount);
    }

    /**
     * Settings for displaying the payment page in an iframe.
     */
    public function forIframe(): static
    {
        return $this
            ->set('theme_config', '3DS_LOGOS=false;')
            ->set('redirect_success_timeout', 0)
            ->set('redirect_error_timeout', 0);
    }

    // ---------------------------------------------------------------------
    // URLs
    // ---------------------------------------------------------------------

    public function returnUrl(string $url, ?string $mode = null): static
    {
        $this->set('url_return', $url);

        return $mode === null ? $this : $this->set('return_mode', strtoupper($mode));
    }

    /**
     * Override the IPN URL configured in the Back Office for this payment.
     */
    public function ipnUrl(string $url): static
    {
        return $this->set('url_check', $url);
    }

    public function transId(): string
    {
        return $this->fields['vads_trans_id'];
    }

    public function platformUrl(): string
    {
        return (string) $this->config['platform_url'];
    }

    /**
     * @return array<string, string> field => reason (empty when valid)
     */
    public function errors(): array
    {
        $errors = [];
        $action = $this->get('page_action');

        $required = Fields::REQUIRED;

        if (in_array($action, Fields::PAYING_ACTIONS, true)) {
            $required[] = 'vads_amount';
            $required[] = 'vads_currency';

            if (($this->fields['vads_amount'] ?? null) === '0') {
                $errors['vads_amount'] = 'must be greater than 0';
            }
        }

        if (in_array($action, Fields::SUBSCRIBE_ACTIONS, true)) {
            array_push($required, 'vads_sub_amount', 'vads_sub_currency', 'vads_sub_desc', 'vads_sub_effect_date');
        }

        foreach ($required as $name) {
            if (($this->fields[$name] ?? '') === '') {
                $errors[$name] = 'required';
            }
        }

        foreach ($this->fields as $name => $value) {
            if (! isset($errors[$name]) && $value !== '' && ($reason = Fields::check($name, $value)) !== null) {
                $errors[$name] = $reason;
            }
        }

        if (! preg_match('#^https://\S+/$#', $this->platformUrl())) {
            $errors['platform_url'] = 'must be an https URL ending with "/"';
        }

        return $errors;
    }

    /**
     * The signed fields to POST to the payment page.
     *
     * @return array<string, string>
     *
     * @throws InvalidPaymentRequest
     */
    public function toArray(): array
    {
        if ($errors = $this->errors()) {
            throw new InvalidPaymentRequest($errors);
        }

        $fields = array_filter($this->fields, fn ($value) => $value !== '');
        ksort($fields, SORT_STRING);

        $fields['signature'] = $this->signature()->compute($fields);

        return $fields;
    }

    public function signature(): Signature
    {
        $key = $this->get('ctx_mode') === 'PRODUCTION' ? $this->config['key_prod'] : $this->config['key_test'];

        return new Signature((string) $key, (string) $this->config['sign_algo']);
    }

    /**
     * Page that posts the form to the gateway (auto-submit, or field list in debug mode).
     */
    public function toResponse($request)
    {
        $fields = $this->toArray();

        logger()->channel(config('lyra-payment.log_channel'))->info('Lyra payment form generated.', [
            'order_id' => $fields['vads_order_id'] ?? null,
            'trans_id' => $fields['vads_trans_id'],
            'amount' => $fields['vads_amount'] ?? null,
            'mode' => $fields['vads_ctx_mode'],
        ]);

        return response()->view('lyra-payment::redirect', [
            'action' => $this->platformUrl(),
            'fields' => $fields,
            'debug' => (bool) ($this->config['debug'] ?? false),
        ])->header('Cache-Control', 'no-store');
    }
}
