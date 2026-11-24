<?php

// Generated from the Lyra PHP payment form example (Apache-2.0), Response::$RESPONSE_TRANS.

return [
    'UNKNOWN' => 'Desconocido',
    'result' => [
        'empty' => '',
        '00' => 'Accion procesada con exito',
        '02' => 'El mercante debe contactar el banco del portador',
        '05' => 'Accion rechazada',
        '17' => 'Accion cancelada',
        '30' => 'Error de formato de solicitutd',
        '96' => 'Problema technico',
    ],
    'auth_result' => [
        'empty' => '',
        '00' => 'Transacción aceptada o procesada con exito',
        'UNKNOWN' => 'Vea los detalles de la transacción para más información',
    ],
    'warranty_result' => [
        'empty' => 'Garantia de pago no aplicable',
        'YES' => 'El pago es garantizado',
        'NO' => 'El pago no es garantizado',
        'UNKNOWN' => 'Debido a un problema tecnico, el pago no puede ser garantizado',
    ],
    'risk_control' => [
        'CARD_FRAUD' => 'Control de numero de tarjeta',
        'SUSPECT_COUNTRY' => 'Control de pais de tarjeta',
        'IP_FRAUD' => 'Control de direccion IP',
        'CREDIT_LIMIT' => 'Control de saldo de vivo de tarjeta',
        'BIN_FRAUD' => 'Control de codigo BIN',
        'ECB' => 'Control de E-carte bleue',
        'COMMERCIAL_CARD' => 'Control de tarjeta comercial',
        'SYSTEMATIC_AUTO' => 'Control de tarjeta a autorizacion sistematica',
        'INCONSISTENT_COUNTRIES' => 'Control de coherencia de pais (IP, tarjeta, direccion de envio)',
        'NON_WARRANTY_PAYMENT' => 'Control de transferencia de responsabilidad',
        'SUSPECT_IP_COUNTRY' => 'Control del pais de la IP',
    ],
    'risk_assessment' => [
        'ENABLE_3DS' => '3D Secure activado',
        'DISABLE_3DS' => '3D Secure desactivado',
        'MANUAL_VALIDATION' => 'La transaccion ha sido creada con validacion manual',
        'REFUSE' => 'La transaccion ha sido rechazada',
        'RUN_RISK_ANALYSIS' => 'Llamada a un analisador de riesgos exterior',
        'INFORM' => 'Un mensaje de advertencia aparece',
    ],
];
