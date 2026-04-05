<?php

/**
 * Üniversite Başvuru Belgeleri Katalogu — APP-* kodları
 *
 * Bu kodlar university_requirement_maps.required_document_codes (JSON) içinde kullanılır.
 * Türk öğrencilerin Almanya'ya yüksek lisans başvurusu için gerekli belgeler.
 */
return [

    'documents' => [

        // ── Kimlik & Temel ─────────────────────────────────────────────────────
        'APP-PASSPORT' => [
            'label_tr' => 'Pasaport (geçerli, fotokopi)',
            'label_de' => 'Reisepass (gültig, Kopie)',
            'category' => 'kimlik',
            'notes'    => 'Tüm üniversiteler için zorunlu.',
        ],
        'APP-PHOTO' => [
            'label_tr' => 'Biyometrik Fotoğraf',
            'label_de' => 'Biometrisches Foto',
            'category' => 'kimlik',
            'notes'    => 'Dijital + baskı formatında (35x45mm).',
        ],

        // ── Akademik Belgeler ───────────────────────────────────────────────────
        'APP-DIPLOMA' => [
            'label_tr' => 'Lisans Diploması (onaylı fotokopi)',
            'label_de' => 'Bachelor-Abschlusszeugnis (beglaubigte Kopie)',
            'category' => 'akademik',
            'notes'    => 'Türkçe + Almanca/İngilizce çeviri ile.',
        ],
        'APP-DIPLOMA-NOTARIZED' => [
            'label_tr' => 'Noterli Lisans Diploması',
            'label_de' => 'Notariell beglaubigte Diplomkopie',
            'category' => 'akademik',
            'notes'    => 'Uni-Assist başvurularında zorunlu olabilir.',
        ],
        'APP-TRANS-DE' => [
            'label_tr' => 'Transkript — Almanca Onaylı Çeviri',
            'label_de' => 'Notenspiegel (beglaubigte deutsche Übersetzung)',
            'category' => 'akademik',
            'notes'    => 'Yeminli tercüman tarafından yapılmış olmalı.',
        ],
        'APP-TRANS-EN' => [
            'label_tr' => 'Transkript — İngilizce',
            'label_de' => 'Transcript of Records (englisch)',
            'category' => 'akademik',
            'notes'    => 'Üniversite resmi mühürlü.',
        ],
        'APP-GPA-CALC' => [
            'label_tr' => 'GPA Hesaplama Belgesi',
            'label_de' => 'GPA-Berechnungsblatt',
            'category' => 'akademik',
            'notes'    => 'Bazı programlar belirli bir GPA eşiği ister (ör. min 2.5/4.0).',
        ],

        // ── Başvuru Sertifikaları ────────────────────────────────────────────────
        'APP-APS' => [
            'label_tr' => 'APS Sertifikası',
            'label_de' => 'APS-Zertifikat (Akademische Prüfstelle)',
            'category' => 'sertifika',
            'notes'    => 'Türk lisans mezunları için zorunlu. Süreç 6-8 hafta sürer.',
        ],
        'APP-VPD' => [
            'label_tr' => 'VPD (Uni-Assist Ön Değerlendirme)',
            'label_de' => 'Vorprüfungsdokumentation (VPD)',
            'category' => 'sertifika',
            'notes'    => 'Uni-Assist üzerinden başvuruda APS yerine kullanılır (bazı üniversiteler).',
        ],

        // ── Dil Sertifikaları ────────────────────────────────────────────────────
        'APP-LANG-DE-DSH' => [
            'label_tr' => 'Almanca Dil Sertifikası — DSH',
            'label_de' => 'Deutschkenntnisse — DSH (mind. DSH-2)',
            'category' => 'dil',
            'notes'    => 'DSH-2 veya üzeri. Alternatif: TestDaF 4x4 veya Goethe C1/C2.',
        ],
        'APP-LANG-DE-TESTDAF' => [
            'label_tr' => 'Almanca Dil Sertifikası — TestDaF',
            'label_de' => 'Deutschkenntnisse — TestDaF (mind. 4x16)',
            'category' => 'dil',
            'notes'    => 'Her alt testten min. 4 puan (toplam 16).',
        ],
        'APP-LANG-EN-TOEFL' => [
            'label_tr' => 'İngilizce Dil Sertifikası — TOEFL iBT',
            'label_de' => 'Englischkenntnisse — TOEFL iBT',
            'category' => 'dil',
            'notes'    => 'Min. 79-80 puan (programa göre değişir).',
        ],
        'APP-LANG-EN-IELTS' => [
            'label_tr' => 'İngilizce Dil Sertifikası — IELTS',
            'label_de' => 'Englischkenntnisse — IELTS',
            'category' => 'dil',
            'notes'    => 'Min. 6.0-6.5 band (programa göre değişir).',
        ],

        // ── Motivasyon & Referans ────────────────────────────────────────────────
        'APP-MOT' => [
            'label_tr' => 'Motivasyon Mektubu (Almanca)',
            'label_de' => 'Motivationsschreiben (auf Deutsch)',
            'category' => 'kisisel',
            'notes'    => 'Program spesifik, genellikle 1-2 sayfa. Bazı programlar İngilizce kabul eder.',
        ],
        'APP-MOT-EN' => [
            'label_tr' => 'Motivasyon Mektubu (İngilizce)',
            'label_de' => 'Motivationsschreiben (auf Englisch)',
            'category' => 'kisisel',
            'notes'    => 'İngilizce eğitim veren programlar için.',
        ],
        'APP-CV' => [
            'label_tr' => 'Özgeçmiş (Europass veya Almanca format)',
            'label_de' => 'Lebenslauf (Europass oder deutsches Format)',
            'category' => 'kisisel',
            'notes'    => 'Almanca yazılmış, Europass formatı tercih edilir.',
        ],
        'APP-REF1' => [
            'label_tr' => 'Referans Mektubu 1 (Akademik)',
            'label_de' => 'Empfehlungsschreiben 1 (akademisch)',
            'category' => 'referans',
            'notes'    => 'Akademisyen veya danışman tarafından.',
        ],
        'APP-REF2' => [
            'label_tr' => 'Referans Mektubu 2 (Akademik/Profesyonel)',
            'label_de' => 'Empfehlungsschreiben 2',
            'category' => 'referans',
            'notes'    => 'İkinci referans (isteğe bağlı veya zorunlu).',
        ],

        // ── Araştırma & Proje ───────────────────────────────────────────────────
        'APP-RESEARCH-STMT' => [
            'label_tr' => 'Araştırma Planı / Niyet Mektubu',
            'label_de' => 'Forschungsplan / Exposé',
            'category' => 'proje',
            'notes'    => 'Doktora başvuruları için zorunlu.',
        ],
        'APP-PORTFOLIO' => [
            'label_tr' => 'Portfolyo',
            'label_de' => 'Portfolio',
            'category' => 'proje',
            'notes'    => 'Tasarım, mimarlık veya sanat programları için.',
        ],
        'APP-WORK-CERT' => [
            'label_tr' => 'İş Deneyimi / Staj Belgesi',
            'label_de' => 'Berufserfahrungsnachweis / Praktikumszeugnis',
            'category' => 'is',
            'notes'    => 'Bazı programlar min. 6 ay iş deneyimi ister.',
        ],

    ],

    'categories' => [
        'kimlik'   => 'Kimlik & Temel Belgeler',
        'akademik' => 'Akademik Belgeler',
        'sertifika'=> 'Sertifikalar',
        'dil'      => 'Dil Sertifikaları',
        'kisisel'  => 'Motivasyon & Özgeçmiş',
        'referans' => 'Referans Mektupları',
        'proje'    => 'Araştırma & Proje',
        'is'       => 'İş Deneyimi',
    ],

];
