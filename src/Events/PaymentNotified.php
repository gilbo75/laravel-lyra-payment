<?php

namespace LyraPayment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use LyraPayment\PaymentResponse;

/**
 * Fired when the gateway calls the IPN URL with a valid signature.
 *
 * This is where orders should be updated. Make your listener idempotent
 * (the gateway retries failed notifications) and compare the amount,
 * currency and order id with your own order before marking it paid.
 */
class PaymentNotified
{
    use Dispatchable;

    public function __construct(public readonly PaymentResponse $payment)
    {
    }
}
