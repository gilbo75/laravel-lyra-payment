<?php

namespace LyraPayment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use LyraPayment\Events\PaymentNotified;
use LyraPayment\LyraPayment;

class IpnController
{
    public function __invoke(Request $request, LyraPayment $lyra): Response
    {
        $payment = $lyra->verifyRequest($request);
        $log = logger()->channel($lyra->config('log_channel'));

        if (! $payment->isAuthentic()) {
            $log->warning('Lyra IPN rejected: invalid signature. Check that the key, mode and signature algorithm match the Back Office.', $payment->logContext());

            return $this->reply(false, 'Invalid signature.', 400);
        }

        $log->info('Lyra IPN received.', $payment->logContext());

        PaymentNotified::dispatch($payment);

        return $this->reply(true, 'Notification processed.');
    }

    private function reply(bool $ok, string $message, int $status = 200): Response
    {
        return new Response(($ok ? 'OK-' : 'KO-') . $message . "\n", $status, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
