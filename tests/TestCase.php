<?php

namespace LyraPayment\Tests;

use LyraPayment\LyraPaymentServiceProvider;
use LyraPayment\Signature;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    public const KEY_TEST = 'test-key-1111111111111111';
    public const KEY_PROD = 'prod-key-2222222222222222';

    protected function getPackageProviders($app): array
    {
        return [LyraPaymentServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        $app['config']->set('lyra-payment.site_id', '12345678');
        $app['config']->set('lyra-payment.key_test', self::KEY_TEST);
        $app['config']->set('lyra-payment.key_prod', self::KEY_PROD);
        $app['config']->set('lyra-payment.mode', 'TEST');
        $app['config']->set('lyra-payment.sign_algo', 'SHA-256');
    }

    /**
     * A gateway-style response, signed with the test key.
     *
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    protected function gatewayFields(array $overrides = [], string $key = self::KEY_TEST): array
    {
        $fields = array_merge([
            'vads_amount' => '1999',
            'vads_auth_result' => '00',
            'vads_ctx_mode' => 'TEST',
            'vads_currency' => '978',
            'vads_extra_result' => '',
            'vads_language' => 'en',
            'vads_order_id' => 'ORDER-42',
            'vads_result' => '00',
            'vads_site_id' => '12345678',
            'vads_trans_id' => '123456',
            'vads_trans_status' => 'AUTHORISED',
            'vads_trans_uuid' => 'a1b2c3d4e5f6',
            'vads_warranty_result' => 'YES',
        ], $overrides);

        $fields['signature'] = (new Signature($key, Signature::SHA256))->compute($fields);

        return $fields;
    }

    /**
     * Send a form-urlencoded POST exactly as the gateway does (raw body).
     */
    protected function gatewayPost(string $uri, array $fields)
    {
        return $this->call('POST', $uri, [], [], [], [
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        ], http_build_query($fields));
    }
}
