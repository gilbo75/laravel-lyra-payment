<?php

namespace LyraPayment\TransactionIds;

use DateTimeInterface;
use LyraPayment\Contracts\TransactionIdGenerator;

/**
 * Tenths of a second elapsed since UTC midnight (0 - 863999).
 *
 * Stateless, as in the original SDK, but computed against UTC midnight so it
 * matches vads_trans_date (which is UTC). Two payments started within the
 * same tenth of a second get the same id: use the cache generator if that
 * can happen on your shop.
 */
final class TimeTransactionIdGenerator implements TransactionIdGenerator
{
    public function generate(DateTimeInterface $date): string
    {
        $seconds = ((int) $date->format('U')) % 86400;
        $tenths = intdiv((int) $date->format('u'), 100000);

        return sprintf('%06d', $seconds * 10 + $tenths);
    }
}
