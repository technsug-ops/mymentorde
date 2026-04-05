<?php

/**
 * Üniversite Katalogu — v5.1
 * 5 Alman üniversitesi (TU Berlin, Bremen, Marburg, Dortmund, Duisburg-Essen)
 * Başlangıç bölümü: Informatik (Master)
 *
 * Model: App\Models\StudentUniversityApplication
 * university_code alanı bu katalogdaki anahtarlarla eşleşir.
 */
return [

    'universities' => [

        'TU_BERLIN' => [
            'name_de'        => 'Technische Universität Berlin',
            'name_en'        => 'Technical University of Berlin',
            'name_tr'        => 'Berlin Teknik Üniversitesi',
            'city'           => 'Berlin',
            'state'          => 'Berlin',
            'type'           => 'tu',
            'default_portal' => 'uni_assist',
            'website'        => 'https://www.tu.berlin',
            'departments'    => [
                'INFORMATIK' => [
                    'name_de'      => 'Informatik',
                    'name_en'      => 'Computer Science',
                    'name_tr'      => 'Bilgisayar Bilimi',
                    'degree_types' => ['bachelor', 'master'],
                    'nc'           => false,
                    'language'     => 'de',
                ],
            ],
        ],

        'UNI_BREMEN' => [
            'name_de'        => 'Universität Bremen',
            'name_en'        => 'University of Bremen',
            'name_tr'        => 'Bremen Üniversitesi',
            'city'           => 'Bremen',
            'state'          => 'Bremen',
            'type'           => 'university',
            'default_portal' => 'uni_assist',
            'website'        => 'https://www.uni-bremen.de',
            'departments'    => [
                'INFORMATIK' => [
                    'name_de'      => 'Informatik',
                    'name_en'      => 'Computer Science',
                    'name_tr'      => 'Bilgisayar Bilimi',
                    'degree_types' => ['bachelor', 'master'],
                    'nc'           => false,
                    'language'     => 'de',
                ],
            ],
        ],

        'UNI_MARBURG' => [
            'name_de'        => 'Philipps-Universität Marburg',
            'name_en'        => 'Philipps University Marburg',
            'name_tr'        => 'Marburg Philipps Üniversitesi',
            'city'           => 'Marburg',
            'state'          => 'Hessen',
            'type'           => 'university',
            'default_portal' => 'uni_assist',
            'website'        => 'https://www.uni-marburg.de',
            'departments'    => [
                'INFORMATIK' => [
                    'name_de'      => 'Informatik',
                    'name_en'      => 'Computer Science',
                    'name_tr'      => 'Bilgisayar Bilimi',
                    'degree_types' => ['bachelor', 'master'],
                    'nc'           => false,
                    'language'     => 'de',
                ],
            ],
        ],

        'TU_DORTMUND' => [
            'name_de'        => 'Technische Universität Dortmund',
            'name_en'        => 'TU Dortmund University',
            'name_tr'        => 'Dortmund Teknik Üniversitesi',
            'city'           => 'Dortmund',
            'state'          => 'Nordrhein-Westfalen',
            'type'           => 'tu',
            'default_portal' => 'direct',
            'website'        => 'https://www.tu-dortmund.de',
            'departments'    => [
                'INFORMATIK' => [
                    'name_de'      => 'Informatik',
                    'name_en'      => 'Computer Science',
                    'name_tr'      => 'Bilgisayar Bilimi',
                    'degree_types' => ['bachelor', 'master'],
                    'nc'           => false,
                    'language'     => 'de',
                ],
            ],
        ],

        'UNI_DUE' => [
            'name_de'        => 'Universität Duisburg-Essen',
            'name_en'        => 'University of Duisburg-Essen',
            'name_tr'        => 'Duisburg-Essen Üniversitesi',
            'city'           => 'Duisburg / Essen',
            'state'          => 'Nordrhein-Westfalen',
            'type'           => 'university',
            'default_portal' => 'uni_assist',
            'website'        => 'https://www.uni-due.de',
            'departments'    => [
                'INFORMATIK' => [
                    'name_de'      => 'Informatik',
                    'name_en'      => 'Computer Science',
                    'name_tr'      => 'Bilgisayar Bilimi',
                    'degree_types' => ['bachelor', 'master'],
                    'nc'           => false,
                    'language'     => 'de',
                ],
            ],
        ],

    ],

];
