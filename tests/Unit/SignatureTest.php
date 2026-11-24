<?php

namespace LyraPayment\Tests\Unit;

use LyraPayment\Exceptions\LyraPaymentException;
use LyraPayment\Signature;
use PHPUnit\Framework\TestCase;

class SignatureTest extends TestCase
{
    private array $fields = [
        'vads_trans_id' => '000001',
        'vads_amount' => '1000',
        'vads_cust_name' => '',
        'not_vads' => 'ignored',
        'signature' => 'whatever',
    ];

    public function test_signed_string_sorts_vads_fields_and_appends_key(): void
    {
        $this->assertSame('1000++000001+KEY', Signature::signedString($this->fields, 'KEY'));
    }

    public function test_sha256_is_base64_hmac_of_signed_string(): void
    {
        $expected = base64_encode(hash_hmac('sha256', '1000++000001+KEY', 'KEY', true));

        $this->assertSame($expected, (new Signature('KEY', Signature::SHA256))->compute($this->fields));
    }

    public function test_sha1_is_hex_sha1_of_signed_string(): void
    {
        $this->assertSame(sha1('1000++000001+KEY'), (new Signature('KEY', Signature::SHA1))->compute($this->fields));
    }

    public function test_verify(): void
    {
        $signer = new Signature('KEY');
        $fields = $this->fields;
        $fields['signature'] = $signer->compute($fields);

        $this->assertTrue($signer->verify($fields));

        $fields['vads_amount'] = '1';
        $this->assertFalse($signer->verify($fields));

        unset($fields['signature']);
        $this->assertFalse($signer->verify($fields));
    }

    public function test_empty_key_and_unknown_algorithm_are_rejected(): void
    {
        $this->expectException(LyraPaymentException::class);
        new Signature('');
    }

    public function test_unknown_algorithm_is_rejected(): void
    {
        $this->expectException(LyraPaymentException::class);
        new Signature('KEY', 'MD5');
    }
}
