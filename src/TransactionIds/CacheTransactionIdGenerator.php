<?php

namespace LyraPayment\TransactionIds;

use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository;
use LyraPayment\Contracts\TransactionIdGenerator;

/**
 * Atomic per-day counter kept in the cache (use an atomic store such as
 * redis, memcached or database). Wraps after 900000 ids in one day.
 */
final class CacheTransactionIdGenerator implements TransactionIdGenerator
{
    public function __construct(private readonly Repository $cache)
    {
    }

    public function generate(DateTimeInterface $date): string
    {
        $key = 'lyra-payment:trans-id:' . gmdate('Ymd', (int) $date->format('U'));

        $this->cache->add($key, 0, 60 * 60 * 48);
        $next = (int) $this->cache->increment($key);

        return sprintf('%06d', ($next - 1) % 900000);
    }
}
