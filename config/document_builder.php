<?php

/**
 * Document Builder konfigürasyonu.
 * DocumentBuilderService bu config'i kullanarak tip kontrolü ve rendering yapar.
 */
return [

    'types' => [
        'cv' => [
            'title_de'   => 'Lebenslauf',
            'title_tr'   => 'Özgeçmiş',
            'force_lang' => 'de',
            'ai_modes'   => ['template', 'ai_assist', 'final_text'],
            'description'=> 'Almanca özgeçmiş (Lebenslauf)',
        ],
        'motivation' => [
            'title_de'   => 'Motivationsschreiben',
            'title_tr'   => 'Motivasyon Mektubu',
            'force_lang' => 'de',
            'ai_modes'   => ['template', 'ai_assist', 'final_text'],
            'description'=> 'Üniversiteye başvuru motivasyon mektubu',
        ],
        'reference' => [
            'title_de'   => 'Empfehlungsschreiben',
            'title_tr'   => 'Referans Mektubu',
            'force_lang' => 'de',
            'ai_modes'   => ['template', 'ai_assist'],
            'description'=> 'İşveren veya hoca tarafından yazılan referans mektubu',
        ],
        'cover_letter' => [
            'title_de'   => 'Anschreiben',
            'title_tr'   => 'Başvuru Mektubu',
            'force_lang' => 'de',
            'ai_modes'   => ['template', 'ai_assist'],
            'description'=> 'Genel başvuru mektubu (iş veya program başvurusu)',
        ],
        'sperrkonto' => [
            'title_de'   => 'Sperrkonto-Antrag',
            'title_tr'   => 'Bloke Hesap Başvurusu',
            'force_lang' => 'de',
            'ai_modes'   => ['template'],
            'description'=> 'Alman bloke hesabı (Sperrkonto) açma başvuru mektubu',
        ],
        'housing' => [
            'title_de'   => 'Wohnheimsantrag',
            'title_tr'   => 'Yurt Başvurusu',
            'force_lang' => 'de',
            'ai_modes'   => ['template'],
            'description'=> 'Üniversite yurdu başvuru mektubu',
        ],
    ],

    'output_formats'   => ['docx', 'md'],
    'max_notes_length' => 5000,
    'max_field_length' => 10000,

    'translations' => [
        'education' => [
            'Lise'         => 'Gymnasium',
            'Üniversite'   => 'Universität',
            'Bölüm'        => 'Abteilung / Fachbereich',
            'Fakülte'      => 'Fakultät',
            'Yüksek Lisans'=> 'Master',
            'Lisans'       => 'Bachelor',
            'Doktora'      => 'Promotion',
        ],
        'cities' => [
            'İstanbul'     => 'Istanbul',
            'Ankara'       => 'Ankara',
            'İzmir'        => 'Izmir',
            'Bursa'        => 'Bursa',
            'Antalya'      => 'Antalya',
            'Adana'        => 'Adana',
        ],
        'countries' => [
            'Türkiye'      => 'Türkei',
            'Almanya'      => 'Deutschland',
            'Avusturya'    => 'Österreich',
            'İsviçre'      => 'Schweiz',
        ],
        'terms' => [
            'Staj'         => 'Praktikum',
            'Proje'        => 'Projekt',
            'Sertifika'    => 'Zertifikat',
            'Hobileri'     => 'Hobbys',
            'Hobi'         => 'Hobby',
            'Dil'          => 'Sprache',
            'Deneyim'      => 'Erfahrung',
            'Eğitim'       => 'Ausbildung',
            'Beceri'       => 'Fähigkeit',
            'Başarı'       => 'Leistung',
        ],
    ],

];
