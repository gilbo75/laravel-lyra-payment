<?php

namespace LyraPayment\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \LyraPayment\PaymentRequest request()
 * @method static \LyraPayment\PaymentResponse verify(array $fields)
 * @method static \LyraPayment\PaymentResponse verifyRequest(\Illuminate\Http\Request $request)
 * @method static array rawFields(\Illuminate\Http\Request $request)
 * @method static mixed config(string $key, mixed $default = null)
 *
 * @see \LyraPayment\LyraPayment
 */
class LyraPayment extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \LyraPayment\LyraPayment::class;
    }
}
