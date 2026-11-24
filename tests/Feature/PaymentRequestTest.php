<?php

namespace LyraPayment\Tests\Feature;

use Illuminate\Support\Facades\Route;
use LyraPayment\Exceptions\InvalidPaymentRequest;
use LyraPayment\Exceptions\LyraPaymentException;
use LyraPayment\Facades\LyraPayment;
use LyraPayment\Signature;
use LyraPayment\Tests\TestCase;

class PaymentRequestTest extends TestCase
{
    private function standard()
    {
        return LyraPayment::request()->amount(1999)->currency('EUR')->orderId('ORDER-42');
    }

    public function test_builds_signed_fields_from_config(): void
    {
        $fields = $this->standard()->customer(['email' => 'jane@example.com', 'first_name' => 'Hélène Marie'])->toArray();

        $this->assertSame('12345678', $fields['vads_site_id']);
        $this->assertSame('TEST', $fields['vads_ctx_mode']);
        $this->assertSame('PAYMENT', $fields['vads_page_action']);
        $this->assertSame('SINGLE', $fields['vads_payment_config']);
        $this->assertSame('V2', $fields['vads_version']);
        $this->assertSame('1999', $fields['vads_amount']);
        $this->assertSame('978', $fields['vads_currency']);
        $this->assertSame('GET', $fields['vads_return_mode']);
        $this->assertSame(route('lyra-payment.return'), $fields['vads_url_return']);
        $this->assertSame('Hélène Marie', $fields['vads_cust_first_name']);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $fields['vads_trans_id']);
        $this->assertMatchesRegularExpression('/^\d{14}$/', $fields['vads_trans_date']);
        $this->assertArrayNotHasKey('vads_url_check', $fields);

        $this->assertTrue((new Signature(self::KEY_TEST))->verify($fields));
    }

    public function test_production_mode_signs_with_production_key(): void
    {
        config(['lyra-payment.mode' => 'PRODUCTION']);
        $this->app->forgetInstance(\LyraPayment\LyraPayment::class);
        LyraPayment::clearResolvedInstances();

        $fields = $this->standard()->toArray();

        $this->assertSame('PRODUCTION', $fields['vads_ctx_mode']);
        $this->assertTrue((new Signature(self::KEY_PROD))->verify($fields));
    }

    public function test_ipn_url_is_sent_when_enabled(): void
    {
        config(['lyra-payment.send_ipn_url' => true]);
        $this->app->forgetInstance(\LyraPayment\LyraPayment::class);
        LyraPayment::clearResolvedInstances();

        $this->assertSame(route('lyra-payment.ipn'), $this->standard()->toArray()['vads_url_check']);
    }

    public function test_decimal_amount(): void
    {
        $this->assertSame('1050', LyraPayment::request()->amountDecimal('10.5', 'EUR')->get('amount'));
    }

    public function test_missing_amount_is_reported(): void
    {
        try {
            LyraPayment::request()->currency('EUR')->toArray();
            $this->fail('Expected InvalidPaymentRequest');
        } catch (InvalidPaymentRequest $e) {
            $this->assertSame(['vads_amount' => 'required'], $e->errors);
        }
    }

    public function test_invalid_email_is_reported(): void
    {
        $this->expectException(InvalidPaymentRequest::class);
        $this->standard()->customer(['email' => 'not-an-email'])->toArray();
    }

    public function test_unknown_field_is_rejected(): void
    {
        $this->expectException(LyraPaymentException::class);
        LyraPayment::request()->set('amonut', 10);
    }

    public function test_installments(): void
    {
        $this->assertSame('MULTI:first=666;count=3;period=30', $this->standard()->installments(3)->get('payment_config'));
        $this->assertSame('MULTI:first=1000;count=2;period=15', $this->standard()->installmentsWithFirstPercent(2, 15, 50.03)->get('payment_config'));
    }

    public function test_subscription_does_not_need_an_immediate_amount(): void
    {
        $fields = LyraPayment::request()
            ->subscription(350, 'RRULE:FREQ=MONTHLY;BYMONTHDAY=1', new \DateTimeImmutable('2026-12-01'), 'EUR')
            ->customer(['email' => 'customer@example.com'])
            ->toArray();

        $this->assertSame('REGISTER_SUBSCRIBE', $fields['vads_page_action']);
        $this->assertSame('350', $fields['vads_sub_amount']);
        $this->assertSame('978', $fields['vads_sub_currency']);
        $this->assertSame('20261201', $fields['vads_sub_effect_date']);
        $this->assertArrayNotHasKey('vads_amount', $fields);
    }

    public function test_products_cards_and_contracts(): void
    {
        $request = $this->standard()
            ->paymentCards(['VISA', 'MASTERCARD', 'E_CV'])
            ->contracts(['ANCV' => '123459-1-1'])
            ->addProduct('T-shirt', 999, 1, 'TS01')
            ->addProduct('Mug', 1000, 1, 'MG01');

        $fields = $request->toArray();
        $this->assertSame('VISA;MASTERCARD;E_CV', $fields['vads_payment_cards']);
        $this->assertSame('ANCV=123459-1-1', $fields['vads_contracts']);
        $this->assertSame('2', $fields['vads_nb_products']);
        $this->assertSame('Mug', $fields['vads_product_label1']);
    }

    public function test_redirect_page_auto_submits_escaped_fields(): void
    {
        Route::get('/pay', fn () => $this->standard()->orderInfo('Tom & "Jerry"'));

        $this->get('/pay')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('action="https://secure.payzen.eu/vads-payment/"', false)
            ->assertSee('name="vads_order_info" value="Tom &amp; &quot;Jerry&quot;"', false)
            ->assertSee("document.getElementById('lyra-payment-form').submit()", false);
    }

    public function test_debug_page_lists_fields_and_does_not_auto_submit(): void
    {
        config(['lyra-payment.debug' => true]);
        $this->app->forgetInstance(\LyraPayment\LyraPayment::class);
        LyraPayment::clearResolvedInstances();

        Route::get('/pay', fn () => $this->standard());

        $this->get('/pay')
            ->assertOk()
            ->assertSee('Debug mode')
            ->assertSee('vads_order_id')
            ->assertDontSee('.submit()', false);
    }
}
