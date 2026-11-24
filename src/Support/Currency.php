<?php

namespace LyraPayment\Support;

use LyraPayment\Exceptions\LyraPaymentException;

/**
 * ISO 4217 currencies: alpha-3 code <-> numeric code, and decimal amount
 * <-> amount in the smallest unit (cents), without float rounding errors.
 */
final class Currency
{
    /** alpha3 => [numeric, decimals] */
    private const CURRENCIES = [
        'ARS' => ['032', 2], 'AUD' => ['036', 2], 'BRL' => ['986', 2], 'CAD' => ['124', 2],
        'CHF' => ['756', 2], 'CLP' => ['152', 0], 'CNY' => ['156', 2], 'COP' => ['170', 2],
        'CZK' => ['203', 2], 'DKK' => ['208', 2], 'EUR' => ['978', 2], 'GBP' => ['826', 2],
        'HKD' => ['344', 2], 'HUF' => ['348', 2], 'IDR' => ['360', 2], 'INR' => ['356', 2],
        'JPY' => ['392', 0], 'KRW' => ['410', 0], 'MAD' => ['504', 2], 'MXN' => ['484', 2],
        'MYR' => ['458', 2], 'NOK' => ['578', 2], 'NZD' => ['554', 2], 'PEN' => ['604', 2],
        'PHP' => ['608', 2], 'PLN' => ['985', 2], 'RUB' => ['643', 2], 'SEK' => ['752', 2],
        'SGD' => ['702', 2], 'THB' => ['764', 2], 'TND' => ['788', 3], 'TRY' => ['949', 2],
        'TWD' => ['901', 2], 'USD' => ['840', 2], 'UYU' => ['858', 2], 'XOF' => ['952', 0],
        'XPF' => ['953', 0], 'ZAR' => ['710', 2],
    ];

    private function __construct(
        public readonly string $alpha3,
        public readonly string $numeric,
        public readonly int $decimals,
    ) {
    }

    /**
     * @param  string|int  $code  alpha-3 ("EUR") or numeric ("978" / 978)
     */
    public static function of(string|int $code): self
    {
        $code = strtoupper(trim((string) $code));

        if (ctype_digit($code)) {
            $code = str_pad($code, 3, '0', STR_PAD_LEFT);
            foreach (self::CURRENCIES as $alpha3 => [$numeric, $decimals]) {
                if ($numeric === $code) {
                    return new self($alpha3, $numeric, $decimals);
                }
            }
        } elseif (isset(self::CURRENCIES[$code])) {
            [$numeric, $decimals] = self::CURRENCIES[$code];

            return new self($code, $numeric, $decimals);
        }

        throw new LyraPaymentException("Unknown currency [{$code}].");
    }

    /**
     * Convert a decimal amount ("10.5", 10.50, "1 234,56" is NOT accepted) to minor units.
     */
    public function toMinorUnits(string|int|float $amount): int
    {
        if (is_float($amount)) {
            $amount = number_format($amount, $this->decimals, '.', '');
        }

        $amount = trim((string) $amount);

        if (! preg_match('/^(\d+)(?:\.(\d+))?$/', $amount, $m)) {
            throw new LyraPaymentException("Invalid amount [{$amount}].");
        }

        $fraction = $m[2] ?? '';

        if (strlen(rtrim($fraction, '0')) > $this->decimals) {
            throw new LyraPaymentException(
                "Amount [{$amount}] has more than {$this->decimals} decimals for {$this->alpha3}."
            );
        }

        $fraction = str_pad(substr($fraction, 0, $this->decimals), $this->decimals, '0');

        return (int) ($m[1] . $fraction);
    }

    /**
     * Convert minor units back to a decimal string ("1050" -> "10.50").
     */
    public function fromMinorUnits(int|string $minor): string
    {
        $minor = (string) (int) $minor;

        if ($this->decimals === 0) {
            return $minor;
        }

        $minor = str_pad($minor, $this->decimals + 1, '0', STR_PAD_LEFT);

        return substr($minor, 0, -$this->decimals) . '.' . substr($minor, -$this->decimals);
    }
}
