<?php

namespace LyraPayment\Exceptions;

class InvalidPaymentRequest extends LyraPaymentException
{
    /**
     * @param  array<string, string>  $errors  field name => reason
     */
    public function __construct(public readonly array $errors)
    {
        $list = implode(', ', array_map(
            fn ($field, $reason) => "{$field} ({$reason})",
            array_keys($errors),
            $errors
        ));

        parent::__construct("Invalid payment request fields: {$list}.");
    }
}
