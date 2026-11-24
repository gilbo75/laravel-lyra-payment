<?php

namespace LyraPayment;

use LyraPayment\Exceptions\LyraPaymentException;

/**
 * Computes and verifies the gateway form signature.
 *
 * The signed string is every vads_* value, sorted by field name, joined
 * with "+", followed by "+" and the shop key. It is then hashed with SHA-1
 * (legacy) or HMAC-SHA-256 keyed with the shop key (base64 encoded).
 */
final class Signature
{
    public const SHA1 = 'SHA-1';

    public const SHA256 = 'SHA-256';

    public const ALGORITHMS = [self::SHA1, self::SHA256];

    public function __construct(
        private readonly string $key,
        private readonly string $algorithm = self::SHA256,
    ) {
        if ($key === '') {
            throw new LyraPaymentException('The shop key for the current mode is not configured.');
        }

        if (! in_array($algorithm, self::ALGORITHMS, true)) {
            throw new LyraPaymentException("Unsupported signature algorithm [{$algorithm}].");
        }
    }

    /**
     * @param  array<string, scalar|null>  $fields
     */
    public function compute(array $fields): string
    {
        $string = self::signedString($fields, $this->key);

        return match ($this->algorithm) {
            self::SHA1 => sha1($string),
            self::SHA256 => base64_encode(hash_hmac('sha256', $string, $this->key, true)),
        };
    }

    /**
     * Constant-time check of the "signature" field against the vads_* fields.
     *
     * @param  array<string, scalar|null>  $fields
     */
    public function verify(array $fields): bool
    {
        $received = $fields['signature'] ?? null;

        if (! is_string($received) || $received === '') {
            return false;
        }

        return hash_equals($this->compute($fields), $received);
    }

    /**
     * @param  array<string, scalar|null>  $fields
     */
    public static function signedString(array $fields, string $key): string
    {
        $vads = array_filter(
            $fields,
            fn ($name) => is_string($name) && str_starts_with($name, 'vads_'),
            ARRAY_FILTER_USE_KEY
        );

        ksort($vads, SORT_STRING);

        $string = '';
        foreach ($vads as $value) {
            $string .= (string) $value . '+';
        }

        return $string . $key;
    }
}
