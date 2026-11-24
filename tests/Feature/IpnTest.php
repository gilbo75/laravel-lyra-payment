<?php

namespace LyraPayment\Tests\Feature;

use Illuminate\Support\Facades\Event;
use LyraPayment\Events\PaymentNotified;
use LyraPayment\Tests\TestCase;

class IpnTest extends TestCase
{
    public function test_valid_notification_dispatches_event(): void
    {
        Event::fake([PaymentNotified::class]);

        $this->gatewayPost('/lyra-payment/ipn', $this->gatewayFields(['vads_hash' => 'abc']))
            ->assertOk()
            ->assertSee('OK-');

        Event::assertDispatched(PaymentNotified::class, function (PaymentNotified $event) {
            return $event->payment->isAuthentic()
                && $event->payment->isFromIpn()
                && $event->payment->isAccepted()
                && $event->payment->orderId() === 'ORDER-42'
                && $event->payment->amount() === 1999;
        });
    }

    public function test_values_altered_by_laravel_middleware_still_verify(): void
    {
        // TrimStrings / ConvertEmptyStringsToNull would change these values
        // and break the signature if the parsed input were used.
        Event::fake([PaymentNotified::class]);

        $fields = $this->gatewayFields([
            'vads_hash' => 'abc',
            'vads_cust_last_name' => 'Durand ',
            'vads_order_info' => '',
        ]);

        $this->gatewayPost('/lyra-payment/ipn', $fields)->assertOk();

        Event::assertDispatched(PaymentNotified::class);
    }

    public function test_tampered_notification_is_rejected(): void
    {
        Event::fake([PaymentNotified::class]);

        $fields = $this->gatewayFields(['vads_hash' => 'abc']);
        $fields['vads_amount'] = '1';

        $this->gatewayPost('/lyra-payment/ipn', $fields)
            ->assertStatus(400)
            ->assertSee('KO-');

        Event::assertNotDispatched(PaymentNotified::class);
    }

    public function test_test_payment_is_not_accepted_by_production_shop(): void
    {
        config(['lyra-payment.mode' => 'PRODUCTION']);
        $this->app->forgetInstance(\LyraPayment\LyraPayment::class);
        Event::fake([PaymentNotified::class]);

        $this->gatewayPost('/lyra-payment/ipn', $this->gatewayFields(['vads_hash' => 'abc']))
            ->assertStatus(400);

        Event::assertNotDispatched(PaymentNotified::class);
    }

    public function test_ipn_route_needs_no_csrf_token_or_session(): void
    {
        $route = app('router')->getRoutes()->getByName('lyra-payment.ipn');

        $this->assertNotContains('web', $route->gatherMiddleware());
    }
}
