<?php

/**
 * Sözleşme tipleri katalogu — K3 çoklu sözleşme desteği.
 * Her tip: etiket, açıklama, zorunlu alanlar, varsayılan süreler.
 */
return [

    'standard' => [
        'label'       => 'Standart Danışmanlık Sözleşmesi',
        'description' => 'Tek ülke başvurusu için standart hizmet sözleşmesi.',
        'required_fields' => [
            'student_name', 'student_email', 'target_program',
            'advisor_company_name', 'advisor_authorized_person',
            'service_fee', 'jurisdiction_city',
        ],
        'default_duration_days' => 365,
        'supports_addendum'     => true,
    ],

    'multi_country' => [
        'label'       => 'Çok Ülkeli Başvuru Sözleşmesi',
        'description' => 'Birden fazla ülkeye başvuru hizmeti için genişletilmiş sözleşme.',
        'required_fields' => [
            'student_name', 'student_email', 'target_countries',
            'advisor_company_name', 'advisor_authorized_person',
            'service_fee', 'jurisdiction_city',
        ],
        'default_duration_days' => 540,
        'supports_addendum'     => true,
    ],

    'basic' => [
        'label'       => 'Temel Bilgi Hizmet Sözleşmesi',
        'description' => 'Üniversite araştırma ve bilgi hizmeti için temel sözleşme.',
        'required_fields' => [
            'student_name', 'student_email',
            'advisor_company_name', 'service_fee',
        ],
        'default_duration_days' => 180,
        'supports_addendum'     => false,
    ],

    'renewal' => [
        'label'       => 'Yenileme / Uzatma Sözleşmesi',
        'description' => 'Mevcut sözleşmenin süre uzatımı için ek sözleşme.',
        'required_fields' => [
            'student_name', 'parent_contract_ref',
            'new_end_date', 'extension_fee',
        ],
        'default_duration_days' => 180,
        'supports_addendum'     => false,
    ],

];
