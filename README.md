# Laravel Lyra Payment

A Laravel integration for the Lyra / PayZen **hosted payment form**. It covers the redirect form, the IPN (instant payment notification) and the customer return URL.

It is a Laravel rewrite of the *Lyra PHP payment form example* (Apache-2.0, see `NOTICE`). It needs PHP 8.2+ and Laravel 12 or 13.

## What it does

- Builds and signs the `vads_*` form fields with a fluent API, checks them against the gateway formats, and sends the customer to the payment page.
- Verifies gateway calls with a constant-time signature check (`hash_equals`), using the **raw** request body. Laravel's `TrimStrings` and `ConvertEmptyStringsToNull` middleware change input values, which would otherwise make valid signatures fail.
- Registers two routes:
  - `POST /lyra-payment/ipn`: no session and no CSRF check. Fires the `PaymentNotified` event.
  - `GET|POST /lyra-payment/return`: fires the `CustomerReturned` event, then shows a result page or redirects to your own page.
- Refuses TEST-mode payments when the shop is configured for PRODUCTION.
- Logs only identifiers and result codes, never customer data or keys.

## Installation

```bash
composer require gilbo75/laravel-lyra-payment
php artisan vendor:publish --tag=lyra-payment-config   # optional
```

Add your credentials to `.env` (Back Office → Settings → Shop → Keys):

```dotenv
LYRA_SITE_ID=12345678
LYRA_KEY_TEST=your-test-key
LYRA_KEY_PROD=your-production-key
LYRA_MODE=TEST
LYRA_PLATFORM_URL=https://secure.payzen.eu/vads-payment/
LYRA_SIGN_ALGO=SHA-256
```

In the Back Office, set the **IPN URL** (Settings → Notification rules) to `https://your-shop.example/lyra-payment/ipn`. During development, you can instead set `LYRA_SEND_IPN_URL=true` to send that URL with each payment.

## Taking a payment

Return the request from a controller. The browser posts the signed form to the gateway automatically.

```php
use LyraPayment\Facades\LyraPayment;

public function pay(Order $order)
{
    return LyraPayment::request()
        ->amount($order->total_cents)          // smallest unit: 1999 = 19.99 EUR
        ->currency('EUR')
        ->orderId($order->reference)           // letters, digits, - and _ (max 64)
        ->language(app()->getLocale())
        ->customer([
            'id' => $order->user_id,
            'email' => $order->email,
            'first_name' => $order->first_name,
            'last_name' => $order->last_name,
            'address' => $order->address,
            'zip' => $order->zip,
            'city' => $order->city,
            'country' => 'FR',
            'phone' => $order->phone,
        ]);
}
```

Other options, mapped to the original examples:

```php
->amountDecimal('19.99', 'EUR')                    // decimal amount, no float rounding
->installments(count: 3, period: 30)               // 4installment-payment.php
->installmentsWithFirstPercent(2, 30, 50)          // 2installment-payment.php (first = 50%)
->paymentCards('CB')                               // preselected-card-payment.php
->paymentCards(['VISA', 'MASTERCARD', 'E_CV'])     // e-cheques-vacances-payment.php
    ->contracts(['ANCV' => '123459-1-1'])
->subscription(350, 'RRULE:FREQ=MONTHLY;BYMONTHDAY=1', now()->addMonth(), 'EUR')  // subscription.php
->addProduct('T-shirt', 999, 1, 'TS01')
->extInfo('cart_id', '987')                        // sent back as vads_ext_info_cart_id
->orderInfo('free text')
->captureDelay(0)->validationMode(0)
->set('shop_name', 'My shop')                      // any vads_* field
->forIframe()                                      // settings for iframe display
```

An invalid or missing field throws `LyraPayment\Exceptions\InvalidPaymentRequest`; `$e->errors` lists each field and the reason. Call `->toArray()` to get the signed fields if you'd rather render the form yourself.

## Updating orders: listen to the IPN

The gateway calls the IPN server-to-server for every payment, even when the customer closes the browser. **This is the only place where orders should be updated.**

```php
// app/Listeners/HandleLyraPayment.php
use LyraPayment\Events\PaymentNotified;

class HandleLyraPayment
{
    public function handle(PaymentNotified $event): void
    {
        $payment = $event->payment;            // signature already verified
        $order = Order::where('reference', $payment->orderId())->firstOrFail();

        // Always compare with your own data.
        if ($payment->amount() !== $order->total_cents || $payment->currency() !== '978') {
            Log::alert('Lyra amount mismatch', $payment->logContext());
            return;
        }

        // The gateway can notify more than once: keep this idempotent.
        if ($order->isPaid()) {
            return;
        }

        match (true) {
            $payment->isPending() => $order->markPending($payment->transUuid()),
            $payment->isAccepted() => $order->markPaid($payment->transUuid()),
            $payment->isCancelled(), $payment->isRefused() => $order->markFailed(),
            default => null,
        };
    }
}
```

Laravel discovers listeners in `app/Listeners` automatically; otherwise register one with `Event::listen(PaymentNotified::class, HandleLyraPayment::class)`. If a synchronous listener throws, the IPN answers HTTP 500 and the gateway retries. Note that `isAccepted()` also returns true for pending payments, as in the original SDK, so check `isPending()` first.

`PaymentResponse` also offers `transStatus()`, `result()`, `authResult()`, `warrantyResult()`, `isToValidate()`, `isSuspectedFraud()`, `riskControl()`, `riskAssessment()`, `formError()`, `outcome()`, `message()` (in en, fr, es, pt or de), `extInfo()`, `get('any_field')`.

## Customer return

By default the return route shows a simple result page; publish the views with `--tag=lyra-payment-views` to restyle it. To use your own page instead:

```dotenv
LYRA_REDIRECT_AFTER_RETURN=/checkout/complete
```

The route then redirects there and flashes `session('lyra_payment')`, which contains `authentic`, `outcome`, `order_id`, `trans_id` and `message`. Use it for display only.

Keep `LYRA_RETURN_MODE=GET` (the default). A POST from the gateway is a cross-site request, so the browser does not send the `SameSite=Lax` session cookie. Laravel would then start a fresh session and the customer would appear logged out.

## Configuration reference

See `config/lyra-payment.php`: modes, keys, platform URL, signature algorithm, return/IPN URLs, default fields for every request (`defaults`), route prefix and middleware, log channel, and the transaction-ID generator. The generator is either `time`, which is stateless and follows the original algorithm, or `cache`, an atomic daily counter that avoids collisions between payments started in the same tenth of a second.

## Testing

```bash
composer install
vendor/bin/phpunit
```

35 tests, passing on Laravel 12 and 13 with PHP 8.3.

## Before going to production

- Some field limits were widened from the original SDK based on the gateway documentation (for example `order_id` up to 64 characters from `[A-Za-z0-9_-]`, phone numbers up to 32 characters, URLs up to 127). Check them against the current *Implementation guide – hosted payment form* for your gateway.
- Run a full payment cycle in TEST mode and check the IPN status in the Back Office before switching `LYRA_MODE` to `PRODUCTION`.
