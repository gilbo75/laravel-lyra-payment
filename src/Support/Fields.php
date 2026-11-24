<?php

namespace LyraPayment\Support;

/**
 * Format rules for the vads_* request fields, ported from the original SDK
 * with fixes: alternations are now anchored as a group (the original
 * "#^A|B$#" matched almost anything), names/addresses accept spaces and
 * accented letters, page_action accepts the subscription/token actions,
 * and vads_url_check (IPN URL override) is supported.
 */
final class Fields
{
    private const ANS = '[^<>]';

    /** Any field matching one of these prefixes is accepted (format: ANS, max 255). */
    public const DYNAMIC_PREFIXES = ['vads_product_', 'vads_ext_info_'];

    /**
     * @return array<string, array{0: string, 1: int}> field => [regex, max length]
     */
    public static function rules(): array
    {
        static $rules = null;

        if ($rules !== null) {
            return $rules;
        }

        $ans = fn (int $max) => ['#^' . self::ANS . '{0,' . $max . '}$#u', $max];
        $positive = ['#^[1-9]\d*$#', 12];
        $positiveOrZero = ['#^\d+$#', 12];
        $country = ['#^[A-Za-z]{2}$#', 2];
        $url = $ans(127);
        $pageActions = 'PAYMENT|REGISTER|REGISTER_UPDATE|REGISTER_PAY|REGISTER_SUBSCRIBE|REGISTER_PAY_SUBSCRIBE'
            . '|SUBSCRIBE|REGISTER_UPDATE_PAY|ASK_REGISTER_PAY';

        return $rules = [
            'vads_action_mode' => ['#^(INTERACTIVE|SILENT)$#', 11],
            'vads_amount' => $positiveOrZero,
            'vads_available_languages' => ['#^([A-Za-z]{2}(;[A-Za-z]{2})*)?$#', 255],
            'vads_capture_delay' => ['#^\d{0,3}$#', 3],
            'vads_contracts' => $ans(255),
            'vads_contrib' => $ans(255),
            'vads_ctx_mode' => ['#^(TEST|PRODUCTION)$#', 10],
            'vads_currency' => ['#^\d{3}$#', 3],
            'vads_cust_address' => $ans(255),
            'vads_cust_address_number' => $ans(64),
            'vads_cust_antecedents' => ['#^(NONE|NO_INCIDENT|INCIDENT)$#', 11],
            'vads_cust_cell_phone' => $ans(32),
            'vads_cust_city' => $ans(128),
            'vads_cust_country' => $country,
            'vads_cust_district' => $ans(127),
            'vads_cust_email' => ['#^[^@\s<>]+@[^@\s<>]+\.[^@\s<>]+$#u', 150],
            'vads_cust_first_name' => $ans(63),
            'vads_cust_id' => $ans(63),
            'vads_cust_last_name' => $ans(63),
            'vads_cust_legal_name' => $ans(100),
            'vads_cust_name' => $ans(127),
            'vads_cust_national_id' => $ans(255),
            'vads_cust_phone' => $ans(32),
            'vads_cust_state' => $ans(127),
            'vads_cust_status' => ['#^(PRIVATE|COMPANY)$#', 7],
            'vads_cust_title' => $ans(63),
            'vads_cust_zip' => $ans(64),
            'vads_identifier' => $ans(50),
            'vads_insurance_amount' => $positive,
            'vads_language' => ['#^[A-Za-z]{2}$#', 2],
            'vads_nb_products' => $positive,
            'vads_order_id' => ['#^[A-Za-z0-9_-]{0,64}$#', 64],
            'vads_order_info' => $ans(255),
            'vads_order_info2' => $ans(255),
            'vads_order_info3' => $ans(255),
            'vads_page_action' => ['#^(' . $pageActions . ')$#', 22],
            'vads_payment_cards' => ['#^([A-Za-z0-9_-]+;)*[A-Za-z0-9_-]*$#', 127],
            'vads_payment_config' => ['#^(SINGLE|MULTI:first=\d+;count=[1-9]\d*;period=[1-9]\d*)$#', 255],
            'vads_payment_option_code' => $ans(255),
            'vads_redirect_error_message' => $ans(255),
            'vads_redirect_error_timeout' => ['#^\d{1,3}$#', 3],
            'vads_redirect_success_message' => $ans(255),
            'vads_redirect_success_timeout' => ['#^\d{1,3}$#', 3],
            'vads_return_get_params' => ['#^([^&=]+=[^&=]*)?(&[^&=]+=[^&=]*)*$#u', 255],
            'vads_return_mode' => ['#^(NONE|GET|POST)$#', 4],
            'vads_return_post_params' => ['#^([^&=]+=[^&=]*)?(&[^&=]+=[^&=]*)*$#u', 255],
            'vads_ship_to_city' => $ans(128),
            'vads_ship_to_country' => $country,
            'vads_ship_to_delay' => ['#^(INFERIOR_EQUALS|SUPERIOR|IMMEDIATE|ALWAYS)$#', 15],
            'vads_ship_to_delivery_company_name' => $ans(127),
            'vads_ship_to_first_name' => $ans(63),
            'vads_ship_to_last_name' => $ans(63),
            'vads_ship_to_legal_name' => $ans(100),
            'vads_ship_to_name' => $ans(127),
            'vads_ship_to_phone_num' => $ans(32),
            'vads_ship_to_speed' => ['#^(STANDARD|EXPRESS|PRIORITY)$#', 8],
            'vads_ship_to_state' => $ans(127),
            'vads_ship_to_status' => ['#^(PRIVATE|COMPANY)$#', 7],
            'vads_ship_to_street' => $ans(255),
            'vads_ship_to_street2' => $ans(255),
            'vads_ship_to_type' => ['#^(RECLAIM_IN_SHOP|RELAY_POINT|RECLAIM_IN_STATION|PACKAGE_DELIVERY_COMPANY|ETICKET)$#', 24],
            'vads_ship_to_zip' => $ans(64),
            'vads_shipping_amount' => $positiveOrZero,
            'vads_shop_name' => $ans(127),
            'vads_shop_url' => ['#^https?://\S+$#', 1024],
            'vads_site_id' => ['#^\d{8}$#', 8],
            'vads_tax_amount' => $positiveOrZero,
            'vads_tax_rate' => ['#^\d{1,2}(\.\d{1,4})?$#', 7],
            'vads_theme_config' => ['#^[^;=]+=[^;=]*(;[^;=]+=[^;=]*)*;?$#u', 255],
            'vads_threeds_mpi' => ['#^[0-2]$#', 1],
            'vads_totalamount_vat' => $positiveOrZero,
            'vads_trans_date' => ['#^\d{4}(1[0-2]|0[1-9])(3[01]|[12]\d|0[1-9])(2[0-3]|[01]\d)([0-5]\d){2}$#', 14],
            'vads_trans_id' => ['#^[0-8]\d{5}$#', 6],
            'vads_url_cancel' => $url,
            'vads_url_check' => $url,
            'vads_url_error' => $url,
            'vads_url_referral' => $url,
            'vads_url_refused' => $url,
            'vads_url_return' => $url,
            'vads_url_success' => $url,
            'vads_user_info' => $ans(255),
            'vads_validation_mode' => ['#^[01]?$#', 1],
            'vads_version' => ['#^V2$#', 2],

            // Subscriptions.
            'vads_sub_amount' => $positive,
            'vads_sub_currency' => ['#^\d{3}$#', 3],
            'vads_sub_desc' => $ans(255),
            'vads_sub_effect_date' => ['#^\d{4}(1[0-2]|0[1-9])(3[01]|[12]\d|0[1-9])$#', 8],
            'vads_sub_init_amount' => $positive,
            'vads_sub_init_amount_number' => ['#^\d+$#', 3],
        ];
    }

    /**
     * Fields always sent and checked before signing.
     */
    public const REQUIRED = [
        'vads_action_mode', 'vads_ctx_mode', 'vads_page_action', 'vads_payment_config',
        'vads_site_id', 'vads_trans_date', 'vads_trans_id', 'vads_version',
    ];

    /**
     * Page actions that charge the customer now and therefore need amount + currency.
     */
    public const PAYING_ACTIONS = [
        'PAYMENT', 'REGISTER_PAY', 'REGISTER_PAY_SUBSCRIBE', 'REGISTER_UPDATE_PAY', 'ASK_REGISTER_PAY',
    ];

    /**
     * Page actions that create a subscription.
     */
    public const SUBSCRIBE_ACTIONS = ['REGISTER_SUBSCRIBE', 'REGISTER_PAY_SUBSCRIBE', 'SUBSCRIBE'];

    public static function normalizeName(string $name): string
    {
        return str_starts_with($name, 'vads_') ? $name : 'vads_' . $name;
    }

    public static function isKnown(string $name): bool
    {
        if (isset(self::rules()[$name])) {
            return true;
        }

        foreach (self::DYNAMIC_PREFIXES as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return null if valid, otherwise a short reason.
     */
    public static function check(string $name, string $value): ?string
    {
        [$regex, $max] = self::rules()[$name] ?? ['#^' . self::ANS . '{0,255}$#u', 255];

        if (mb_strlen($value) > $max) {
            return "max {$max} characters";
        }

        if (! preg_match($regex, $value)) {
            return 'invalid format';
        }

        return null;
    }
}
