<?php

// Generated from the Lyra PHP payment form example (Apache-2.0), Response::$RESPONSE_TRANS.

return [
    'UNKNOWN' => 'Inconnu',
    'result' => [
        'empty' => '',
        '00' => 'Action réalisée avec succès',
        '02' => 'Le marchand doit contacter la banque du porteur',
        '05' => 'Action refusée',
        '17' => 'Action annulée',
        '30' => 'Erreur de format de la requête',
        '96' => 'Erreur technique',
    ],
    'auth_result' => [
        'empty' => '',
        '00' => 'Transaction approuvée ou traitée avec succès',
        'UNKNOWN' => 'Voir le détail de la transaction pour plus d\'information',
    ],
    'warranty_result' => [
        'empty' => 'Garantie de paiement non applicable',
        'YES' => 'Le paiement est garanti',
        'NO' => 'Le paiement n\'est pas garanti',
        'UNKNOWN' => 'Suite à une erreur technique, le paiment ne peut pas être garanti',
    ],
    'risk_control' => [
        'CARD_FRAUD' => 'Contrôle du numéro de carte',
        'SUSPECT_COUNTRY' => 'Contrôle du pays émetteur de la carte',
        'IP_FRAUD' => 'Contrôle de l\'adresse IP',
        'CREDIT_LIMIT' => 'Contrôle de l\'encours',
        'BIN_FRAUD' => 'Contrôle du code BIN',
        'ECB' => 'Contrôle e-carte bleue',
        'COMMERCIAL_CARD' => 'Contrôle carte commerciale',
        'SYSTEMATIC_AUTO' => 'Contrôle carte à autorisation systématique',
        'INCONSISTENT_COUNTRIES' => 'Contrôle de cohérence des pays (IP, carte, adresse de facturation)',
        'NON_WARRANTY_PAYMENT' => 'Contrôle le transfert de responsabilité',
        'SUSPECT_IP_COUNTRY' => 'Contrôle Pays de l\'IP',
    ],
    'risk_assessment' => [
        'ENABLE_3DS' => '3D Secure activé',
        'DISABLE_3DS' => '3D Secure désactivé',
        'MANUAL_VALIDATION' => 'La transaction est créée en validation manuelle',
        'REFUSE' => 'La transaction est refusée',
        'RUN_RISK_ANALYSIS' => 'Appel à un analyseur de risques externes',
        'INFORM' => 'Une alerte est remontée',
    ],
];
