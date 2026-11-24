<?php

namespace LyraPayment\Contracts;

use DateTimeInterface;

interface TransactionIdGenerator
{
    /**
     * Return a 6-digit id (000000-899999), unique for the shop on the UTC day of $date.
     */
    public function generate(DateTimeInterface $date): string;
}
