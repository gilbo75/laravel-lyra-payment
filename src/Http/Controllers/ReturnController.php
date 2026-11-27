<?php

namespace LyraPayment\Http\Controllers;

use Illuminate\Http\Request;
use LyraPayment\Events\CustomerReturned;
use LyraPayment\LyraPayment;

class ReturnController
{
    public function __invoke(Request $request, LyraPayment $lyra)
    {
        $payment = $lyra->verifyRequest($request);
        $log = logger()->channel($lyra->config('log_channel'));

        if ($payment->isAuthentic()) {
            $log->info('Lyra customer return.', $payment->logContext());
            CustomerReturned::dispatch($payment);
        } else {
            $log->warning('Lyra return rejected: invalid signature.', $payment->logContext());
        }

        $fieldPrefix = 'vads_ext_info';
        $extInfo = collect($payment->toArray())
            ->filter(fn($value, $field) => substr($field, 0, strlen($fieldPrefix)) == $fieldPrefix)
            ->mapWithKeys(function ($value, $field) use ($fieldPrefix) {
                return [str_replace($fieldPrefix.'_', '', $field) => $value];
            })->toArray();

        if ($target = $lyra->config('redirect_after_return')) {
            $authentic = $payment->isAuthentic();
            $summary = [
                'authentic' => $authentic,
                'outcome' => $payment->outcome(),
                'order_id' => $authentic ? $payment->orderId() : null,
                'trans_id' => $authentic ? $payment->transId() : null,
                'trans_uuid' => $authentic ? $payment->transUuid() : null,
                'message' => $authentic ? $payment->message() : null,
                'trans_date' => $authentic ? $payment->transDate() : null,
                'ext_info' => $authentic ? $extInfo : null,
            ];

            $redirect = redirect()->to($target);

            if ($lyra->config('return_storage', 'session') === 'cookie') {
                return $redirect->cookie(
                    $lyra->config('return_cookie_name', 'lyra_payment'),
                    json_encode($summary),
                    (int) $lyra->config('return_cookie_lifetime', 1)
                );
            }

            return $redirect->with('lyra_payment', $summary);
        }

        return response()->view('lyra-payment::return', ['payment' => $payment])
            ->header('Cache-Control', 'no-store');
    }
}
