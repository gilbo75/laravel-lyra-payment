<?php

namespace LyraPayment\Tests\Feature;

use Illuminate\Support\Facades\Event;
use LyraPayment\Events\CustomerReturned;
use LyraPayment\Tests\TestCase;

class ReturnTest extends TestCase
{
    public function test_get_return_shows_result_page(): void
    {
        Event::fake([CustomerReturned::class]);

        $this->get('/lyra-payment/return?' . http_build_query($this->gatewayFields()))
            ->assertOk()
            ->assertSee('Payment accepted')
            ->assertSee('Action successfully completed (00)')
            ->assertSee('ORDER-42');

        Event::assertDispatched(CustomerReturned::class);
    }

    public function test_post_return_works_without_csrf_token(): void
    {
        $this->gatewayPost('/lyra-payment/return', $this->gatewayFields())
            ->assertOk()
            ->assertSee('Payment accepted');
    }

    public function test_redirect_after_return_flashes_summary(): void
    {
        config(['lyra-payment.redirect_after_return' => '/orders/thanks']);
        $this->app->forgetInstance(\LyraPayment\LyraPayment::class);

        $this->get('/lyra-payment/return?' . http_build_query($this->gatewayFields(['vads_trans_status' => 'ABANDONED', 'vads_result' => '17'])))
            ->assertRedirect('/orders/thanks')
            ->assertSessionHas('lyra_payment', fn ($summary) => $summary['outcome'] === 'cancelled'
                && $summary['authentic'] === true
                && $summary['order_id'] === 'ORDER-42');
    }

    public function test_invalid_signature_is_not_trusted(): void
    {
        Event::fake([CustomerReturned::class]);
        $fields = $this->gatewayFields();
        $fields['vads_order_id'] = '<script>alert(1)</script>';

        $this->get('/lyra-payment/return?' . http_build_query($fields))
            ->assertOk()
            ->assertSee('could not be verified')
            ->assertDontSee('<script>alert(1)</script>', false);

        Event::assertNotDispatched(CustomerReturned::class);
    }

    public function test_unsigned_extra_parameters_are_never_rendered(): void
    {
        $fields = $this->gatewayFields() + ['extra' => '<script>alert(1)</script>'];

        $this->get('/lyra-payment/return?' . http_build_query($fields))
            ->assertOk()
            ->assertDontSee('alert(1)', false);
    }

    public function test_messages_use_payment_language(): void
    {
        $this->get('/lyra-payment/return?' . http_build_query($this->gatewayFields(['vads_language' => 'fr'])))
            ->assertSee('Action réalisée avec succès (00)');
    }
}
