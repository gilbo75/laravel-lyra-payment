<?php

// Generated from the Lyra PHP payment form example (Apache-2.0), Response::$RESPONSE_TRANS.

return [
    'UNKNOWN' => 'Unbekannt',
    'result' => [
        'empty' => '',
        '00' => 'Aktion erfolgreich ausgeführt',
        '02' => 'Der Händler muss die Bank des Karteninhabers kontaktieren',
        '05' => 'Aktion abgelehnt',
        '17' => 'Aktion abgebrochen',
        '30' => 'Fehler im Format der Anfrage',
        '96' => 'Technischer Fehler bei der Zahlung',
    ],
    'auth_result' => [
        'empty' => '',
        '00' => 'Zahlung durchgeführt oder mit Erfolg bearbeitet',
        'UNKNOWN' => 'Weitere Informationen finden Sie in den Transaktionsdetails',
    ],
    'warranty_result' => [
        'empty' => 'Zahlungsgarantie nicht anwendbar',
        'YES' => 'Die Zahlung ist garantiert',
        'NO' => 'Die Zahlung ist nicht garantiert',
        'UNKNOWN' => 'Die Zahlung kann aufgrund eines technischen Fehlers nicht gewährleistet werden',
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
