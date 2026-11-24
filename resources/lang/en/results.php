<?php

// Generated from the Lyra PHP payment form example (Apache-2.0), Response::$RESPONSE_TRANS.

return [
    'UNKNOWN' => 'Unknown',
    'result' => [
        'empty' => '',
        '00' => 'Action successfully completed',
        '02' => 'The merchant must contact the cardholder\'s bank',
        '05' => 'Action rejected',
        '17' => 'Action canceled',
        '30' => 'Request format error',
        '96' => 'Technical issue',
    ],
    'auth_result' => [
        'empty' => '',
        '00' => 'Approved or successfully processed transaction',
        'UNKNOWN' => 'See the transaction details for more information',
    ],
    'warranty_result' => [
        'empty' => 'Payment guarantee not applicable',
        'YES' => 'The payment is guaranteed',
        'NO' => 'The payment is not guaranteed',
        'UNKNOWN' => 'Due to a technical error, the payment cannot be guaranteed',
    ],
    'risk_control' => [
        'CARD_FRAUD' => 'Card number control',
        'SUSPECT_COUNTRY' => 'Card country control',
        'IP_FRAUD' => 'IP address control',
        'CREDIT_LIMIT' => 'Card outstanding control',
        'BIN_FRAUD' => 'BIN code control',
        'ECB' => 'E-carte bleue control',
        'COMMERCIAL_CARD' => 'Commercial card control',
        'SYSTEMATIC_AUTO' => 'Systematic authorization card control',
        'INCONSISTENT_COUNTRIES' => 'Countries consistency control (IP, card, shipping address)',
        'NON_WARRANTY_PAYMENT' => 'Transfer of responsibility control',
        'SUSPECT_IP_COUNTRY' => 'IP country control',
    ],
    'risk_assessment' => [
        'ENABLE_3DS' => '3D Secure enabled',
        'DISABLE_3DS' => '3D Secure disabled',
        'MANUAL_VALIDATION' => 'The transaction has been created via manual validation',
        'REFUSE' => 'The transaction is refused',
        'RUN_RISK_ANALYSIS' => 'Call for an external risk analyser',
        'INFORM' => 'A warning message appears',
    ],
];
