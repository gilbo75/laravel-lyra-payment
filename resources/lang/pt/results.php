<?php

// Generated from the Lyra PHP payment form example (Apache-2.0), Response::$RESPONSE_TRANS.

return [
    'UNKNOWN' => 'Desconhecido',
    'result' => [
        'empty' => '',
        '00' => 'Ação realizada com sucesso',
        '02' => 'O comerciante deve contactar o banco do portador',
        '05' => 'Ação recusada',
        '17' => 'Ação cancelada',
        '30' => 'Erro no formato dos dados',
        '96' => 'Erro técnico durante o pagamento',
    ],
    'auth_result' => [
        'empty' => '',
        '00' => 'Transação aprovada ou tratada com sucesso',
        'UNKNOWN' => 'Veja os detalhes da transação para mais informações',
    ],
    'warranty_result' => [
        'empty' => 'Garantia de pagamento não aplicável',
        'YES' => 'O pagamento foi garantido',
        'NO' => 'O pagamento não foi garantido',
        'UNKNOWN' => 'Devido à un erro técnico, o pagamento não pôde ser garantido',
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
