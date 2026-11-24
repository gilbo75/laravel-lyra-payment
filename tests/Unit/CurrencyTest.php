<?php

namespace LyraPayment\Tests\Unit;

use LyraPayment\Exceptions\LyraPaymentException;
use LyraPayment\Support\Currency;
use PHPUnit\Framework\TestCase;

class CurrencyTest extends TestCase
{
    public function test_lookup_by_alpha_or_numeric_code(): void
    {
        $this->assertSame('978', Currency::of('eur')->numeric);
        $this->assertSame('USD', Currency::of(840)->alpha3);
        $this->assertSame('ARS', Currency::of('32')->alpha3);
    }

    public function test_to_minor_units_without_float_errors(): void
    {
        $eur = Currency::of('EUR');

        $this->assertSame(1999, $eur->toMinorUnits('19.99'));
        $this->assertSame(1050, $eur->toMinorUnits(10.5));
        $this->assertSame(30, $eur->toMinorUnits(0.1 + 0.2));
        $this->assertSame(2000, $eur->toMinorUnits(20));
        $this->assertSame(1990, $eur->toMinorUnits('19.900'));
        $this->assertSame(1500, Currency::of('JPY')->toMinorUnits('1500'));
    }

    public function test_too_many_decimals_is_rejected(): void
    {
        $this->expectException(LyraPaymentException::class);
        Currency::of('EUR')->toMinorUnits('19.999');
    }

    public function test_from_minor_units(): void
    {
        $this->assertSame('19.99', Currency::of('EUR')->fromMinorUnits(1999));
        $this->assertSame('0.05', Currency::of('EUR')->fromMinorUnits(5));
        $this->assertSame('1500', Currency::of('JPY')->fromMinorUnits(1500));
    }

    public function test_unknown_currency(): void
    {
        $this->expectException(LyraPaymentException::class);
        Currency::of('XXX');
    }
}
