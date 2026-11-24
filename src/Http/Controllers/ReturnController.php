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
            return redirect()->to($target)->with('lyra_payment', [
                'authentic' => $payment->isAuthentic(),
                'outcome' => $payment->outcome(),
                'order_id' => $payment->isAuthentic() ? $payment->orderId() : null,
                'trans_id' => $payment->isAuthentic() ? $payment->transId() : null,
                'trans_uuid' => $payment->isAuthentic() ? $payment->transUuid() : null,
                'message' => $payment->isAuthentic() ? $payment->message() : null,
                'trans_date' => $payment->isAuthentic() ? $payment->transDate() : null,
                'ext_info' => $payment->isAuthentic() ? $extInfo : null,
            ]);
        }

        return response()->view('lyra-payment::return', ['payment' => $payment])
            ->header('Cache-Control', 'no-store');
    }
}
