<?php

namespace App\Services;

/**
 * OCR çıkarımı için kategori → şema haritası.
 *
 * Her şema, Gemini Vision'a hangi field'ları çıkarması gerektiğini söyler:
 *   - key:     JSON içinde dönecek alan adı
 *   - label:   UI'da gösterilecek Türkçe etiket
 *   - type:    string | date | int | enum (UI render hint)
 *   - format:  beklenen format hint'i (örn. "YYYY-MM-DD", "ISO-3166 alpha-3")
 *   - required: model'in mutlaka denemesi gereken field (eksikse null bırak)
 *
 * Desteklenen kategoriler — code'lar DocumentCategory.code ile eşleşmeli.
 * Listede olmayan kategoride OCR job dispatch edilmez (skip + tag).
 */
class DocumentOcrSchemas
{
    /**
     * Public — kategori code → schema array | null
     */
    public function getSchemaForCategory(string $categoryCode): ?array
    {
        $code = strtolower(trim($categoryCode));
        $code = $this->normalizeCode($code);

        return self::SCHEMAS[$code] ?? null;
    }

    /**
     * UI / dispatch logic için: OCR destekleyen tüm kategori kodları.
     *
     * @return array<int,string>
     */
    public function supportedCategoryCodes(): array
    {
        return array_keys(self::SCHEMAS);
    }

    /**
     * Kategori OCR'a uygun mu? (controller dispatch kararı)
     */
    public function isSupported(string $categoryCode): bool
    {
        return $this->getSchemaForCategory($categoryCode) !== null;
    }

    /**
     * Eski/varyant kategori kod'larını canonical halka indir.
     * Mevcut DocumentCategory koleksiyonunda bazı kodlar TR ekli olabilir
     * (örn. "pasaport"), bunları İngilizce canonical'a map'le.
     */
    private function normalizeCode(string $code): string
    {
        return match ($code) {
            'pasaport', 'passaport', 'passport_copy' => 'passport',
            'kimlik', 'id', 'national_id', 'tc_kimlik' => 'id_card',
            'diploma_belgesi', 'lise_diplomasi', 'mezuniyet_belgesi' => 'diploma',
            'transkript', 'not_dokumu', 'osym_sonuc' => 'transcript',
            'oturum_izni', 'oturma_izni', 'aufenthaltstitel' => 'residence_permit',
            'vize', 'visa_sticker' => 'visa',
            'dil_sertifikasi', 'dil_belgesi', 'language_cert', 'telc', 'goethe', 'testdaf', 'dsh' => 'language_certificate',
            default => $code,
        };
    }

    /**
     * Şemalar tek kaynak — yeni kategori eklemek için buraya ekle.
     */
    private const SCHEMAS = [
        'passport' => [
            'category_label' => 'Pasaport',
            'doc_type_hint'  => 'Türkiye Cumhuriyeti veya başka ülke pasaportu (kimlik bilgisi sayfası).',
            'fields' => [
                ['key' => 'full_name',       'label' => 'Ad Soyad',            'type' => 'string', 'required' => true,  'format' => null],
                ['key' => 'document_number', 'label' => 'Pasaport No',         'type' => 'string', 'required' => true,  'format' => 'alphanumeric'],
                ['key' => 'birth_date',      'label' => 'Doğum Tarihi',        'type' => 'date',   'required' => true,  'format' => 'YYYY-MM-DD'],
                ['key' => 'birth_place',     'label' => 'Doğum Yeri',          'type' => 'string', 'required' => false, 'format' => null],
                ['key' => 'nationality',     'label' => 'Uyruk',               'type' => 'string', 'required' => true,  'format' => 'ISO-3166 alpha-3 (örn. TUR)'],
                ['key' => 'gender',          'label' => 'Cinsiyet',            'type' => 'enum',   'required' => false, 'format' => 'M | F | X'],
                ['key' => 'issue_date',      'label' => 'Veriliş Tarihi',      'type' => 'date',   'required' => false, 'format' => 'YYYY-MM-DD'],
                ['key' => 'expiry_date',     'label' => 'Geçerlilik Sonu',     'type' => 'date',   'required' => true,  'format' => 'YYYY-MM-DD'],
                ['key' => 'issuing_country', 'label' => 'Veren Ülke',          'type' => 'string', 'required' => false, 'format' => 'ISO-3166 alpha-3'],
                ['key' => 'issuing_authority', 'label' => 'Veren Makam',       'type' => 'string', 'required' => false, 'format' => null],
            ],
        ],

        'id_card' => [
            'category_label' => 'Kimlik Kartı',
            'doc_type_hint'  => 'T.C. Nüfus Cüzdanı / Yeni Kimlik Kartı veya yabancı kimlik.',
            'fields' => [
                ['key' => 'full_name',       'label' => 'Ad Soyad',            'type' => 'string', 'required' => true,  'format' => null],
                ['key' => 'document_number', 'label' => 'Kimlik / Belge No',   'type' => 'string', 'required' => true,  'format' => 'numeric (11 hane T.C.)'],
                ['key' => 'birth_date',      'label' => 'Doğum Tarihi',        'type' => 'date',   'required' => true,  'format' => 'YYYY-MM-DD'],
                ['key' => 'nationality',     'label' => 'Uyruk',               'type' => 'string', 'required' => false, 'format' => null],
                ['key' => 'gender',          'label' => 'Cinsiyet',            'type' => 'enum',   'required' => false, 'format' => 'M | F'],
                ['key' => 'father_name',     'label' => 'Baba Adı',            'type' => 'string', 'required' => false, 'format' => null],
                ['key' => 'mother_name',     'label' => 'Anne Adı',            'type' => 'string', 'required' => false, 'format' => null],
                ['key' => 'issue_date',      'label' => 'Veriliş Tarihi',      'type' => 'date',   'required' => false, 'format' => 'YYYY-MM-DD'],
                ['key' => 'expiry_date',     'label' => 'Geçerlilik Sonu',     'type' => 'date',   'required' => false, 'format' => 'YYYY-MM-DD'],
            ],
        ],

        'diploma' => [
            'category_label' => 'Diploma',
            'doc_type_hint'  => 'Lise diploması (MEB), önlisans / lisans diploması.',
            'fields' => [
                ['key' => 'full_name',       'label' => 'Mezun Adı',           'type' => 'string', 'required' => true,  'format' => null],
                ['key' => 'school_name',     'label' => 'Okul / Kurum',        'type' => 'string', 'required' => true,  'format' => null],
                ['key' => 'school_type',     'label' => 'Okul Türü',           'type' => 'string', 'required' => false, 'format' => 'lise / lisans / önlisans / yüksek lisans'],
                ['key' => 'department',      'label' => 'Bölüm / Alan',        'type' => 'string', 'required' => false, 'format' => null],
                ['key' => 'graduation_date', 'label' => 'Mezuniyet Tarihi',    'type' => 'date',   'required' => true,  'format' => 'YYYY-MM-DD'],
                ['key' => 'graduation_year', 'label' => 'Mezuniyet Yılı',      'type' => 'int',    'required' => true,  'format' => 'YYYY'],
                ['key' => 'diploma_number',  'label' => 'Diploma No',          'type' => 'string', 'required' => false, 'format' => null],
                ['key' => 'gpa',             'label' => 'Diploma Notu / GPA',  'type' => 'string', 'required' => false, 'format' => '0-100 veya 0.00-4.00'],
                ['key' => 'gpa_scale',       'label' => 'Not Sistemi',         'type' => 'enum',   'required' => false, 'format' => '100 | 4 | 5'],
                ['key' => 'country',         'label' => 'Ülke',                'type' => 'string', 'required' => false, 'format' => null],
            ],
        ],

        'transcript' => [
            'category_label' => 'Transkript / Not Dökümü',
            'doc_type_hint'  => 'Üniversite transkripti veya ÖSYM (YKS/TYT) sonuç belgesi.',
            'fields' => [
                ['key' => 'full_name',        'label' => 'Öğrenci Adı',         'type' => 'string', 'required' => true,  'format' => null],
                ['key' => 'school_name',      'label' => 'Okul / Üniversite',   'type' => 'string', 'required' => true,  'format' => null],
                ['key' => 'department',       'label' => 'Bölüm',               'type' => 'string', 'required' => false, 'format' => null],
                ['key' => 'student_number',   'label' => 'Öğrenci No',          'type' => 'string', 'required' => false, 'format' => null],
                ['key' => 'gpa',              'label' => 'Genel Not Ortalaması','type' => 'string', 'required' => true,  'format' => '0-100 veya 0.00-4.00'],
                ['key' => 'gpa_scale',        'label' => 'Not Sistemi',         'type' => 'enum',   'required' => true,  'format' => '100 | 4 | 5'],
                ['key' => 'total_credits',    'label' => 'Toplam Kredi',        'type' => 'string', 'required' => false, 'format' => 'AKTS / ECTS'],
                ['key' => 'graduation_year',  'label' => 'Mezuniyet Yılı',      'type' => 'int',    'required' => false, 'format' => 'YYYY'],
                ['key' => 'subjects',         'label' => 'Dersler (özet)',      'type' => 'array',  'required' => false, 'format' => 'array<string>'],
                ['key' => 'osym_score',       'label' => 'ÖSYM / YKS Puanı',    'type' => 'string', 'required' => false, 'format' => 'sadece ÖSYM belgesi ise'],
                ['key' => 'osym_score_type',  'label' => 'Puan Türü',           'type' => 'string', 'required' => false, 'format' => 'SAY / EA / SÖZ / DİL'],
            ],
        ],

        'residence_permit' => [
            'category_label' => 'Oturum İzni / Aufenthaltstitel',
            'doc_type_hint'  => 'Almanya Aufenthaltstitel kartı veya başka ülke oturma izni.',
            'fields' => [
                ['key' => 'full_name',         'label' => 'Ad Soyad',           'type' => 'string', 'required' => true,  'format' => null],
                ['key' => 'document_number',   'label' => 'Belge No',           'type' => 'string', 'required' => true,  'format' => null],
                ['key' => 'permit_type',       'label' => 'İzin Türü',          'type' => 'string', 'required' => true,  'format' => '§16b StudienAufenthG vb.'],
                ['key' => 'issue_date',        'label' => 'Veriliş Tarihi',     'type' => 'date',   'required' => true,  'format' => 'YYYY-MM-DD'],
                ['key' => 'expiry_date',       'label' => 'Geçerlilik Sonu',    'type' => 'date',   'required' => true,  'format' => 'YYYY-MM-DD'],
                ['key' => 'issuing_authority', 'label' => 'Veren Makam',        'type' => 'string', 'required' => false, 'format' => 'Ausländerbehörde şehri'],
                ['key' => 'nationality',       'label' => 'Uyruk',              'type' => 'string', 'required' => false, 'format' => null],
                ['key' => 'remarks',           'label' => 'Notlar / Kısıtlama', 'type' => 'string', 'required' => false, 'format' => 'çalışma izni durumu vb.'],
            ],
        ],

        'visa' => [
            'category_label' => 'Vize',
            'doc_type_hint'  => 'Schengen / ulusal D vizesi sticker veya elektronik vize.',
            'fields' => [
                ['key' => 'full_name',        'label' => 'Ad Soyad',           'type' => 'string', 'required' => true,  'format' => null],
                ['key' => 'visa_number',      'label' => 'Vize No',            'type' => 'string', 'required' => true,  'format' => null],
                ['key' => 'visa_type',        'label' => 'Vize Tipi',          'type' => 'string', 'required' => true,  'format' => 'C (kısa süreli) | D (uzun)'],
                ['key' => 'visa_category',    'label' => 'Vize Kategorisi',    'type' => 'string', 'required' => false, 'format' => 'Studium / Sprachkurs / Studienbewerber...'],
                ['key' => 'issuing_country',  'label' => 'Veren Ülke',         'type' => 'string', 'required' => true,  'format' => 'ISO-3166 alpha-3'],
                ['key' => 'issue_date',       'label' => 'Veriliş Tarihi',     'type' => 'date',   'required' => true,  'format' => 'YYYY-MM-DD'],
                ['key' => 'valid_from',       'label' => 'Geçerlilik Başlangıç','type' => 'date',  'required' => true,  'format' => 'YYYY-MM-DD'],
                ['key' => 'valid_until',      'label' => 'Geçerlilik Sonu',    'type' => 'date',   'required' => true,  'format' => 'YYYY-MM-DD'],
                ['key' => 'duration_days',    'label' => 'Süre (gün)',         'type' => 'int',    'required' => false, 'format' => null],
                ['key' => 'number_of_entries','label' => 'Giriş Sayısı',       'type' => 'string', 'required' => false, 'format' => '1 | 2 | MULT'],
            ],
        ],

        'language_certificate' => [
            'category_label' => 'Dil Sertifikası',
            'doc_type_hint'  => 'Almanca (telc, Goethe, TestDaF, DSH, TELC), İngilizce (TOEFL, IELTS) veya Türkçe YDS sertifikası.',
            'fields' => [
                ['key' => 'full_name',         'label' => 'Ad Soyad',            'type' => 'string', 'required' => true,  'format' => null],
                ['key' => 'language',          'label' => 'Dil',                 'type' => 'string', 'required' => true,  'format' => 'Almanca / İngilizce / Türkçe'],
                ['key' => 'exam_name',         'label' => 'Sınav Adı',           'type' => 'string', 'required' => true,  'format' => 'telc B2 / Goethe C1 / TestDaF / TOEFL iBT vb.'],
                ['key' => 'level',             'label' => 'CEFR Seviye',         'type' => 'enum',   'required' => false, 'format' => 'A1 | A2 | B1 | B2 | C1 | C2'],
                ['key' => 'overall_score',     'label' => 'Toplam Puan',         'type' => 'string', 'required' => false, 'format' => 'TestDaF TDN, TOEFL 0-120, IELTS 0-9'],
                ['key' => 'reading_score',     'label' => 'Okuma Puanı',         'type' => 'string', 'required' => false, 'format' => null],
                ['key' => 'listening_score',   'label' => 'Dinleme Puanı',       'type' => 'string', 'required' => false, 'format' => null],
                ['key' => 'writing_score',     'label' => 'Yazma Puanı',         'type' => 'string', 'required' => false, 'format' => null],
                ['key' => 'speaking_score',    'label' => 'Konuşma Puanı',       'type' => 'string', 'required' => false, 'format' => null],
                ['key' => 'exam_date',         'label' => 'Sınav Tarihi',        'type' => 'date',   'required' => true,  'format' => 'YYYY-MM-DD'],
                ['key' => 'certificate_number','label' => 'Sertifika No',        'type' => 'string', 'required' => false, 'format' => null],
                ['key' => 'issuing_institution','label' => 'Sertifika Veren',    'type' => 'string', 'required' => false, 'format' => null],
            ],
        ],
    ];
}
