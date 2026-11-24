<?php

namespace LyraPayment\Tests\Unit;

use DateTimeImmutable;
use LyraPayment\TransactionIds\TimeTransactionIdGenerator;
use PHPUnit\Framework\TestCase;

class TimeTransactionIdGeneratorTest extends TestCase
{
    public function test_tenths_of_seconds_since_utc_midnight(): void
    {
        $generator = new TimeTransactionIdGenerator();

        $this->assertSame('000000', $generator->generate(new DateTimeImmutable('2026-01-01 00:00:00.000 UTC')));
        $this->assertSame('000015', $generator->generate(new DateTimeImmutable('2026-01-01 00:00:01.500 UTC')));
        $this->assertSame('863999', $generator->generate(new DateTimeImmutable('2026-01-01 23:59:59.999 UTC')));
        // Same instant in another timezone gives the same id.
        $this->assertSame('000000', $generator->generate(new DateTimeImmutable('2026-01-01 01:00:00.000 +01:00')));
    }
}
