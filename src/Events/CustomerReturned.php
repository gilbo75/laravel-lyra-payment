<?php

namespace LyraPayment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use LyraPayment\PaymentResponse;

/**
 * Fired when the customer comes back from the payment page with a valid
 * signature. Use it for display or analytics only; update orders from
 * PaymentNotified (the IPN), which does not depend on the customer's browser.
 */
class CustomerReturned
{
    use Dispatchable;

    public function __construct(public readonly PaymentResponse $payment)
    {
    }
}
