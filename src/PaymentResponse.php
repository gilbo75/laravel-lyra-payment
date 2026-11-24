<?php

namespace LyraPayment;

use Illuminate\Contracts\Support\Arrayable;

/**
 * A payment result received on the IPN URL or the return URL.
 *
 * Always check isAuthentic() before trusting any value. Update orders only
 * from the IPN (isFromIpn()): the return URL depends on the customer's
 * browser and may never be called, or be replayed.
 */
class PaymentResponse implements Arrayable
{
    public const TYPE_RESULT = 'result';
    public const TYPE_AUTH_RESULT = 'auth_result';
    public const TYPE_WARRANTY_RESULT = 'warranty_result';

    private const CONFIRMED = ['AUTHORISED', 'AUTHORISED_TO_VALIDATE', 'CAPTURED', 'CAPTURE_FAILED', 'ACCEPTED'];
    private const PENDING = ['INITIAL', 'WAITING_AUTHORISATION', 'WAITING_AUTHORISATION_TO_VALIDATE', 'UNDER_VERIFICATION', 'WAITING_FOR_PAYMENT'];
    private const CANCELLED = ['NOT_CREATED', 'ABANDONED'];
    private const TO_VALIDATE = ['WAITING_AUTHORISATION_TO_VALIDATE', 'AUTHORISED_TO_VALIDATE'];

    /** extra_result code => invalid field, when result is "30" (form error). */
    private const FORM_ERRORS = [
        '00' => 'SIGNATURE', '01' => 'VERSION', '02' => 'SITE_ID', '03' => 'TRANS_ID', '04' => 'TRANS_DATE',
        '05' => 'VALIDATION_MODE', '06' => 'CAPTURE_DELAY', '07' => 'PAYMENT_CONFIG', '08' => 'PAYMENT_CARDS', '09' => 'AMOUNT',
        '10' => 'CURRENCY', '11' => 'CTX_MODE', '12' => 'LANGUAGE', '13' => 'ORDER_ID', '14' => 'ORDER_INFO',
        '15' => 'CUST_EMAIL', '16' => 'CUST_ID', '17' => 'CUST_TITLE', '18' => 'CUST_NAME', '19' => 'CUST_ADDRESS',
        '20' => 'CUST_ZIP', '21' => 'CUST_CITY', '22' => 'CUST_COUNTRY', '23' => 'CUST_PHONE', '24' => 'URL_SUCCESS',
        '25' => 'URL_REFUSED', '26' => 'URL_REFERRAL', '27' => 'URL_CANCEL', '28' => 'URL_RETURN', '29' => 'URL_ERROR',
        '30' => 'IDENTIFIER', '31' => 'CONTRIB', '32' => 'THEME_CONFIG', '33' => 'URL_CHECK', '34' => 'REDIRECT_SUCCESS_TIMEOUT',
        '35' => 'REDIRECT_SUCCESS_MESSAGE', '36' => 'REDIRECT_ERROR_TIMEOUT', '37' => 'REDIRECT_ERROR_MESSAGE', '38' => 'RETURN_POST_PARAMS', '39' => 'RETURN_GET_PARAMS',
        '40' => 'CARD_NUMBER', '41' => 'CARD_EXP_MONTH', '42' => 'CARD_EXP_YEAR', '43' => 'CARD_CVV', '44' => 'CARD_CVV_AND_BIRTH',
        '46' => 'PAGE_ACTION', '47' => 'ACTION_MODE', '48' => 'RETURN_MODE', '49' => 'ABSTRACT_INFO', '50' => 'SECURE_MPI',
        '51' => 'SECURE_ENROLLED', '52' => 'SECURE_CAVV', '53' => 'SECURE_ECI', '54' => 'SECURE_XID', '55' => 'SECURE_CAVV_ALG',
        '56' => 'SECURE_STATUS', '60' => 'PAYMENT_SRC', '61' => 'USER_INFO', '62' => 'CONTRACTS', '63' => 'RECURRENCE',
        '64' => 'RECURRENCE_DESC', '65' => 'RECURRENCE_AMOUNT', '66' => 'RECURRENCE_REDUCED_AMOUNT', '67' => 'RECURRENCE_CURRENCY', '68' => 'RECURRENCE_REDUCED_AMOUNT_NUMBER',
        '69' => 'RECURRENCE_EFFECT_DATE', '70' => 'EMPTY_PARAMS', '71' => 'AVAILABLE_LANGUAGES', '72' => 'SHOP_NAME', '73' => 'SHOP_URL',
        '74' => 'OP_COFINOGA', '75' => 'OP_CETELEM', '76' => 'BIRTH_DATE', '77' => 'CUST_CELL_PHONE', '79' => 'TOKEN_ID',
        '80' => 'SHIP_TO_NAME', '81' => 'SHIP_TO_STREET', '82' => 'SHIP_TO_STREET2', '83' => 'SHIP_TO_CITY', '84' => 'SHIP_TO_STATE',
        '85' => 'SHIP_TO_ZIP', '86' => 'SHIP_TO_COUNTRY', '87' => 'SHIP_TO_PHONE_NUM', '88' => 'CUST_STATE', '89' => 'REQUESTOR',
        '90' => 'PAYMENT_TYPE', '91' => 'EXT_INFO', '92' => 'CUST_STATUS', '93' => 'SHIP_TO_STATUS', '94' => 'SHIP_TO_TYPE',
        '95' => 'SHIP_TO_SPEED', '96' => 'SHIP_TO_DELIVERY_COMPANY_NAME', '97' => 'PRODUCT_LABEL', '98' => 'PRODUCT_TYPE', '100' => 'PRODUCT_REF',
        '101' => 'PRODUCT_QTY', '102' => 'PRODUCT_AMOUNT', '103' => 'PAYMENT_OPTION_CODE', '104' => 'CUST_FIRST_NAME', '105' => 'CUST_LAST_NAME',
        '106' => 'SHIP_TO_FIRST_NAME', '107' => 'SHIP_TO_LAST_NAME', '108' => 'TAX_AMOUNT', '109' => 'SHIPPING_AMOUNT', '110' => 'INSURANCE_AMOUNT',
        '111' => 'PAYMENT_ENTRY', '112' => 'CUST_ADDRESS_NUMBER', '113' => 'CUST_DISTRICT', '114' => 'SHIP_TO_STREET_NUMBER', '115' => 'SHIP_TO_DISTRICT',
        '116' => 'SHIP_TO_USER_INFO', '117' => 'DONATION', '99' => 'OTHER', '118' => 'STEP_UP_DATA', '201' => 'PAYMENT_AUTH_CODE',
        '202' => 'PAYMENT_CUST_CONTRACT_NUM', '888' => 'ROBOT_REQUEST', '999' => 'SENSITIVE_DATA',
    ];

    /**
     * @param  array<string, string|null>  $fields  raw fields as sent by the gateway
     */
    public function __construct(
        private readonly array $fields,
        private readonly bool $authentic,
    ) {
    }

    // ---------------------------------------------------------------------
    // Trust
    // ---------------------------------------------------------------------

    public function isAuthentic(): bool
    {
        return $this->authentic;
    }

    /**
     * True for a server-to-server notification (the gateway adds vads_hash).
     */
    public function isFromIpn(): bool
    {
        return $this->get('hash') !== null;
    }

    // ---------------------------------------------------------------------
    // Status
    // ---------------------------------------------------------------------

    /**
     * Payment successful, including payments still pending capture/authorisation
     * (same meaning as the original SDK). Use isPending() to tell them apart.
     */
    public function isAccepted(): bool
    {
        return in_array($this->transStatus(), self::CONFIRMED, true) || $this->isPending();
    }

    public function isPending(): bool
    {
        return in_array($this->transStatus(), self::PENDING, true);
    }

    /** Payment interrupted by the customer. */
    public function isCancelled(): bool
    {
        return in_array($this->transStatus(), self::CANCELLED, true);
    }

    /** Payment to be validated manually in the Back Office. */
    public function isToValidate(): bool
    {
        return in_array($this->transStatus(), self::TO_VALIDATE, true);
    }

    public function isRefused(): bool
    {
        return $this->transStatus() === 'REFUSED';
    }

    /**
     * One word summary: invalid, pending, accepted, cancelled, refused or failed.
     */
    public function outcome(): string
    {
        return match (true) {
            ! $this->authentic => 'invalid',
            $this->isPending() => 'pending',
            $this->isAccepted() => 'accepted',
            $this->isCancelled() => 'cancelled',
            $this->isRefused() => 'refused',
            default => 'failed',
        };
    }

    public function isSuspectedFraud(): bool
    {
        $controls = $this->riskControl();

        return in_array('WARNING', $controls, true)
            || in_array('ERROR', $controls, true)
            || in_array('INFORM', $this->riskAssessment(), true);
    }

    /**
     * @return array<string, string> control => OK|WARNING|ERROR
     */
    public function riskControl(): array
    {
        $raw = trim((string) $this->get('risk_control'));

        if ($raw === '') {
            return [];
        }

        $result = [];
        foreach (explode(';', $raw) as $pair) {
            [$control, $value] = array_pad(explode('=', $pair, 2), 2, '');
            if ($control !== '') {
                $result[$control] = $value;
            }
        }

        return $result;
    }

    /**
     * @return array<int, string>
     */
    public function riskAssessment(): array
    {
        $raw = trim((string) $this->get('risk_assessment_result'));

        return $raw === '' ? [] : explode(';', $raw);
    }

    // ---------------------------------------------------------------------
    // Values
    // ---------------------------------------------------------------------

    /**
     * Any vads_* field ("vads_" prefix optional); null when absent.
     */
    public function get(string $name): ?string
    {
        $name = str_starts_with($name, 'vads_') ? $name : 'vads_' . $name;
        $value = $this->fields[$name] ?? null;

        return $value === null ? null : (string) $value;
    }

    public function extInfo(string $key): ?string
    {
        return $this->get("ext_info_{$key}");
    }

    public function orderId(): ?string
    {
        return $this->get('order_id');
    }

    public function transId(): ?string
    {
        return $this->get('trans_id');
    }

    public function transDate(): ?string
    {
        return $this->get('trans_date');
    }

    /** Unique transaction reference (vads_trans_uuid). */
    public function transUuid(): ?string
    {
        return $this->get('trans_uuid');
    }

    public function transStatus(): ?string
    {
        return $this->get('trans_status');
    }

    /** Amount in minor units (cents). */
    public function amount(): ?int
    {
        $amount = $this->get('amount');

        return $amount === null || $amount === '' ? null : (int) $amount;
    }

    /** Numeric ISO 4217 currency code. */
    public function currency(): ?string
    {
        return $this->get('currency');
    }

    public function result(): ?string
    {
        return $this->get('result');
    }

    public function extraResult(): ?string
    {
        return $this->get('extra_result');
    }

    public function authResult(): ?string
    {
        return $this->get('auth_result');
    }

    public function warrantyResult(): ?string
    {
        return $this->get('warranty_result');
    }

    /**
     * When result is "30" (request format error), the name of the rejected field.
     */
    public function formError(): ?string
    {
        if ($this->result() !== '30') {
            return null;
        }

        return self::FORM_ERRORS[(string) $this->extraResult()] ?? 'OTHER';
    }

    // ---------------------------------------------------------------------
    // Messages
    // ---------------------------------------------------------------------

    /**
     * Human-readable message for a result type, in $locale (default: the
     * payment language, then the app locale, then English).
     */
    public function message(string $type = self::TYPE_RESULT, ?string $locale = null): string
    {
        $locale ??= $this->get('language') ?: app()->getLocale();
        $code = (string) $this->get($type);
        $key = $code === '' ? 'empty' : $code;
        $translator = trans();

        $line = "lyra-payment::results.{$type}.{$key}";
        if ($translator->has($line, $locale, false)) {
            $text = $translator->get($line, [], $locale, false);
        } elseif ($translator->has($line, 'en', false)) {
            $text = $translator->get($line, [], 'en', false);
        } elseif ($translator->has("lyra-payment::results.{$type}.UNKNOWN", $locale)) {
            $text = $translator->get("lyra-payment::results.{$type}.UNKNOWN", [], $locale);
        } else {
            $text = $translator->get('lyra-payment::results.UNKNOWN', [], $locale);
        }

        if ($text !== '' && $code !== '') {
            $text .= " ({$code})";
        }

        if ($type === self::TYPE_RESULT && $code === '30') {
            $text .= ' ' . $this->formError() . ' (' . $this->extraResult() . ')';
        }

        return $text;
    }

    public function completeMessage(string $separator = ' ', ?string $locale = null): string
    {
        return implode($separator, array_filter([
            $this->message(self::TYPE_RESULT, $locale),
            $this->message(self::TYPE_AUTH_RESULT, $locale),
            $this->message(self::TYPE_WARRANTY_RESULT, $locale),
        ]));
    }

    /**
     * Identifiers and result codes only: safe to log.
     *
     * @return array<string, mixed>
     */
    public function logContext(): array
    {
        return [
            'authentic' => $this->authentic,
            'ipn' => $this->isFromIpn(),
            'order_id' => $this->orderId(),
            'trans_id' => $this->transId(),
            'trans_uuid' => $this->transUuid(),
            'trans_status' => $this->transStatus(),
            'result' => $this->result(),
            'extra_result' => $this->extraResult(),
            'auth_result' => $this->authResult(),
            'amount' => $this->amount(),
            'currency' => $this->currency(),
            'mode' => $this->get('ctx_mode'),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return $this->fields;
    }
}
