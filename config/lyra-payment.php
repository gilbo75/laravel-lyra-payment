<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Shop credentials
    |--------------------------------------------------------------------------
    |
    | Found in your gateway Back Office (Settings > Shop > Keys).
    | Never commit real keys: keep them in your .env file.
    |
    */

    'site_id' => env('LYRA_SITE_ID'),

    'key_test' => env('LYRA_KEY_TEST'),

    'key_prod' => env('LYRA_KEY_PROD'),

    /*
    |--------------------------------------------------------------------------
    | Mode
    |--------------------------------------------------------------------------
    |
    | "TEST" or "PRODUCTION". Switch to PRODUCTION only once your test
    | payments and IPN handling have been validated in the Back Office.
    |
    */

    'mode' => env('LYRA_MODE', 'TEST'),

    /*
    |--------------------------------------------------------------------------
    | Payment page URL
    |--------------------------------------------------------------------------
    |
    | Depends on your gateway / country, for example:
    |   France:  https://secure.payzen.eu/vads-payment/
    |   Brazil:  https://secure.payzen.com.br/vads-payment/
    |   Germany: https://de.payzen.eu/vads-payment/
    |   Chile:   https://secure.payzen.cl/vads-payment/
    |
    */

    'platform_url' => env('LYRA_PLATFORM_URL', 'https://secure.payzen.eu/vads-payment/'),

    /*
    |--------------------------------------------------------------------------
    | Signature algorithm
    |--------------------------------------------------------------------------
    |
    | Must match the algorithm selected in the Back Office: "SHA-256"
    | (HMAC-SHA-256, recommended) or "SHA-1" (legacy).
    |
    */

    'sign_algo' => env('LYRA_SIGN_ALGO', 'SHA-256'),

    /*
    |--------------------------------------------------------------------------
    | Customer return
    |--------------------------------------------------------------------------
    |
    | return_mode: how the gateway sends the result back with the customer
    | (GET, POST or NONE). GET is recommended: a cross-site POST does not
    | carry the customer's session cookie (SameSite=Lax), so a POST return
    | can make Laravel start a new session and log the customer out.
    |
    | url_return: where the customer is sent after paying. Leave null to use
    | this package's return route (lyra-payment.return).
    |
    | redirect_after_return: if set (URL or path), the package return route
    | verifies the signature and then redirects here, flashing a summary to
    | the session under the "lyra_payment" key. If null, a simple built-in
    | result page is shown.
    |
    */

    'return_mode' => env('LYRA_RETURN_MODE', 'GET'),

    'url_return' => env('LYRA_URL_RETURN'),

    'redirect_after_return' => env('LYRA_REDIRECT_AFTER_RETURN'),

    /*
    |--------------------------------------------------------------------------
    | IPN (Instant Payment Notification) URL
    |--------------------------------------------------------------------------
    |
    | send_ipn_url: when true, every request carries vads_url_check pointing
    | at this package's IPN route (or ipn_url if set), overriding the URL
    | configured in the Back Office. Leave false if you configure the IPN
    | URL in the Back Office (recommended for production).
    |
    */

    'send_ipn_url' => env('LYRA_SEND_IPN_URL', false),

    'ipn_url' => env('LYRA_IPN_URL'),

    /*
    |--------------------------------------------------------------------------
    | Transaction ID generator
    |--------------------------------------------------------------------------
    |
    | vads_trans_id must be unique per shop and per day (000000-899999).
    |   time:  tenths of a second since UTC midnight (stateless, the original
    |          SDK behaviour; may collide for two payments in the same 0.1 s).
    |   cache: an atomic daily counter in your cache store (use redis,
    |          memcached or database in production).
    | Or the class name of your own LyraPayment\Contracts\TransactionIdGenerator.
    |
    */

    'trans_id_generator' => env('LYRA_TRANS_ID_GENERATOR', 'time'),

    'trans_id_cache_store' => env('LYRA_TRANS_ID_CACHE_STORE'),

    /*
    |--------------------------------------------------------------------------
    | Debug
    |--------------------------------------------------------------------------
    |
    | When true, the redirect page lists the signed fields and waits for a
    | click instead of auto-submitting. Never enable in production.
    |
    */

    'debug' => env('LYRA_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Defaults applied to every payment request
    |--------------------------------------------------------------------------
    |
    | Any vads_* field (with or without the "vads_" prefix), e.g.
    | 'capture_delay' => 0, 'validation_mode' => 0, 'payment_cards' => 'CB;VISA',
    | 'shop_name' => 'My shop'.
    |
    */

    'defaults' => [
        // 'capture_delay' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | The IPN route is registered without the "web" middleware group (no
    | session, no CSRF): it is called server-to-server by the gateway.
    | The return route uses return_middleware, with CSRF verification removed
    | so that POST returns work.
    |
    */

    'routes' => [
        'enabled' => true,
        'prefix' => 'lyra-payment',
        'ipn_path' => 'ipn',
        'return_path' => 'return',
        'ipn_middleware' => [],
        'return_middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Log channel used by the package (null = default channel). Only
    | identifiers and result codes are logged, never customer data or keys.
    |
    */

    'log_channel' => env('LYRA_LOG_CHANNEL'),

];
