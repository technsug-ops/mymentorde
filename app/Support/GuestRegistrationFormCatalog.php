<?php

namespace App\Support;

class GuestRegistrationFormCatalog
{
    /**
     * @return array<int,array<string,mixed>>
     */
    public static function applicationCountryOptions(): array
    {
        // value = lowercase ISO code (DB ile uyumlu — guest_applications.application_country
        // 'de' gibi kod saklıyor). Label = "Almanya (DE)" gösterim.
        return array_map(
            static fn (array $c) => [
                'value' => strtolower($c['code']),
                'label' => $c['label'] . ' (' . $c['code'] . ')',
            ],
            ApplicationCountryCatalog::options()
        );
    }

    /**
     * Eski label-tabanlı kayıtları (örn. 'Almanya') yeni code formatına ('de') çevir.
     * Form rendering sırasında $value'yu normalize etmek için.
     */
    public static function normalizeCountryValue(?string $value): string
    {
        $v = trim((string) $value);
        if ($v === '') {
            return '';
        }
        $vLower = strtolower($v);
        // Zaten code ise (2 harf, ASCII)
        if (preg_match('/^[a-z]{2}$/', $vLower)) {
            return $vLower;
        }
        // Label ise (Almanya, Avusturya, ...) → code'a çevir
        foreach (ApplicationCountryCatalog::options() as $c) {
            if (mb_strtolower((string) $c['label']) === mb_strtolower($v)) {
                return strtolower((string) $c['code']);
            }
        }
        return $v;
    }

    /**
     * Level filtreli grup listesi.
     *
     * Level 2 = mevcut 8 wizard yapısı (kişisel/eş/adres/eğitim/dil/finans/aile/ek/motivasyon)
     * Level 1 = User'ın istediği 6 wizard yapısı:
     *           1) KİŞİSEL BİLGİLER
     *           2) AKADEMİK PROFİL
     *           3) HEDEF VE PLANLAR
     *           4) DİL YETERLİLİĞİ
     *           5) MALİ DURUM VE LOJİSTİK
     *           6) MOTİVASYON VE HAZIRLIK
     *
     * @return array<int,array<string,mixed>>
     */
    public static function groupsByLevel(int $level = 2): array
    {
        $allGroups = self::allGroups();
        if ($level >= 2) {
            return $allGroups;
        }

        // Level 1: 6 wizard mapping'i — mevcut grup section_key'leriyle yeni başlıkları
        // eşleştirir. Field'lar mevcut allGroups'tan level=1 işaretli olanlarla doldurulur.
        $level1Wizards = [
            ['key' => 'personal_info',         'title' => 'Kişisel Bilgiler'],
            ['key' => 'education_history',     'title' => 'Akademik Profil'],
            ['key' => 'address_application',   'title' => 'Hedef ve Planlar'],
            ['key' => 'language_skills',       'title' => 'Dil Yeterliliği'],
            ['key' => 'finance_visa',          'title' => 'Mali Durum ve Lojistik'],
            ['key' => 'motivation_preparation','title' => 'Motivasyon ve Hazırlık'],
        ];

        $output = [];
        $order = 10;
        foreach ($level1Wizards as $wizard) {
            $sourceGroup = collect($allGroups)->firstWhere('section_key', $wizard['key']);
            if (!$sourceGroup) {
                continue;
            }

            $level1Fields = collect($sourceGroup['fields'] ?? [])
                ->filter(fn (array $f) => (int) ($f['level'] ?? 2) <= $level)
                ->values()
                ->all();

            if (empty($level1Fields)) {
                continue;
            }

            $output[] = [
                'section_key'   => $wizard['key'],
                'section_order' => $order,
                'title'         => $wizard['title'],
                'fields'        => $level1Fields,
            ];
            $order += 10;
        }

        return $output;
    }

    /**
     * Geriye dönük uyumluluk — mevcut Level 2 davranışı.
     * @return array<int,array<string,mixed>>
     */
    public static function groups(): array
    {
        return self::groupsByLevel(2);
    }

    /**
     * Tüm groups (filtre uygulanmamış ham hali).
     * @return array<int,array<string,mixed>>
     */
    private static function allGroups(): array
    {
        return [
            [
                'section_key' => 'personal_info',
                'section_order' => 10,
                'title' => 'Kişisel Bilgiler',
                'fields' => [
                    // Level 0 (apply'dan) — Level 1+2'de readonly görünür
                    self::f('first_name', 'İsim *', 'text', true, 120, level: 1),
                    self::f('last_name', 'Soyisim *', 'text', true, 120, level: 1),
                    self::f('gender', 'Cinsiyetiniz *', 'select', true, 40, options: self::yesNoGenderOptions(), level: 1),
                    self::f('email', 'E-Mail *', 'email', true, 180, level: 1),
                    self::f('phone', 'Telefon *', 'text', true, 64, level: 1),
                    // Level 1 yeni
                    self::f('birth_date', 'Doğum Tarihiniz *', 'date', true, 20, level: 1),
                    // Sadece Level 2
                    self::f('marital_status', 'Medeni Hali *', 'select', true, 40, options: self::maritalOptions()),
                    self::f('reference_text', 'Referans', 'text', false, 180),
                    self::f('birth_place', 'Doğum yeriniz *', 'text', true, 120),
                ],
            ],
            [
                'section_key' => 'address_application',
                'section_order' => 20,
                'title' => 'Adres ve Başvuru',
                'fields' => [
                    // Sadece Level 2 (adres detayı)
                    self::f('application_city', 'Başvuru şehri *', 'text', true, 120),
                    // Level 0 (apply'dan)
                    self::f('application_country', 'Başvuru ülkesi *', 'select', true, 120, options: self::applicationCountryOptions(), level: 1),
                    // Sadece Level 2
                    self::f('address_line', 'Açık Adresiniz *', 'text', true, 255),
                    self::f('postal_code', 'Posta kodu *', 'text', true, 32),
                    self::f('district', 'İlçe *', 'text', true, 120),
                    self::f('province', 'İl *', 'text', true, 120),
                    // Level 0 (apply'dan)
                    self::f('application_type', 'Başvuru Tipi *', 'select', true, 40, options: self::applicationTypeOptions(), level: 1),
                    // Level 1 (User listesinde "Hedef bölüm")
                    self::f('target_program', 'Okumayı hedeflediğiniz bölüm/program *', 'text', true, 255, level: 1),
                    // Sadece Level 2
                    self::f('university_start_target_date', 'Üniversite başlangıç tarihi hedefiniz *', 'date', true, 20),
                ],
            ],
            [
                'section_key' => 'education_history',
                'section_order' => 30,
                'title' => 'Eğitim Geçmişi',
                'fields' => [
                    // Sadece Level 2 (en yüksek eğitim seviyesi — tüm ilk-orta-lise tarihleri)
                    self::f('education_level', 'Eğitim seviyeniz *', 'select', true, 60, options: self::educationLevelOptions()),
                    self::f('primary_start_date', 'İlkokul başlama tarihi *', 'date', true, 20),
                    self::f('primary_end_date', 'İlkokul bitirme tarihi *', 'date', true, 20),
                    self::f('primary_grade', 'İlkokul mezuniyet ortalaması', 'text', false, 32),
                    self::f('middle_start_date', 'Ortaokul başlama tarihi *', 'date', true, 20),
                    self::f('middle_end_date', 'Ortaokul bitirme tarihi *', 'date', true, 20),
                    self::f('middle_grade', 'Ortaokul mezuniyet ortalaması', 'text', false, 32),
                    self::f('middle_school_name', 'Mezun olduğunuz ortaokul *', 'text', true, 180),
                    self::f('high_start_date', 'Lise başlama tarihi *', 'date', true, 20),
                    self::f('high_end_date', 'Lise mezuniyet tarihi *', 'date', true, 20),
                    self::f('high_school_name', 'Mezun olduğunuz lise *', 'text', true, 180),
                    // Level 1 (User: Lise türü + diploma notu)
                    self::f(
                        'high_school_type',
                        'Lise türünüz *',
                        'select',
                        true,
                        32,
                        options: self::highSchoolTypeOptions(),
                        help_text: '● Anadolu ve Fen Liseleri: Anadolu Lisesi, Fen Lisesi, Sosyal Bilimler Lisesi. ● Meslek Lisesi: Mesleki ve Teknik Anadolu Lisesi (MTAL), Mesleki Eğitim Merkezi (MESEM), Anadolu Teknik Programı (ATP), İmam Hatip Lisesi, Anadolu İmam Hatip Lisesi, Güzel Sanatlar ve Spor Lisesi, Çok Programlı Anadolu Lisesi, Sağlık Meslek Lisesi, Ticaret/Adalet/Turizm/Spor Meslek Lisesi. ● Açık Lise: Mesleki Açık Öğretim Lisesi (MAÖL), Açık Öğretim İmam Hatip Lisesi (AÖİHL), Açık Öğretim Lisesi (AOL).',
                        level: 1
                    ),
                    self::f('high_school_grade', 'Lise mezuniyet ortalamanız (100 üzerinden) *', 'text', true, 32, level: 1),
                    // Level 1 yeni — sadece yıl bilgisi (Level 2'de high_end_date tam tarih ile birlikte yaşar)
                    self::f('high_school_grad_year', 'Lise mezuniyet yılınız *', 'text', true, 4, placeholder: 'Örn: 2024', level: 1),
                    // Level 1 yeni — yükseköğretim DURUMU (mevcut education_level "en yüksek seviye"den farklı concept)
                    self::f(
                        'higher_education_status',
                        'Yükseköğretim durumunuz *',
                        'select',
                        true,
                        20,
                        options: self::higherEducationStatusOptions(),
                        help_text: 'Lise mezuniyetinden sonraki durumunuz.',
                        level: 1
                    ),
                    // Level 1 (mevcut field — devam ederse zorunlu, opsiyonel kalır client-side)
                    self::f('university_name', 'Üniversite adı (devam ediyorsanız)', 'text', false, 180, level: 1),
                    self::f('university_department', 'Bölüm adı (devam ediyorsanız)', 'text', false, 180, level: 1),
                    // Level 1 yeni — sınıf
                    self::f(
                        'university_year',
                        'Şu an hangi sınıftasınız?',
                        'select',
                        false,
                        20,
                        options: self::universityYearOptions(),
                        help_text: 'Yükseköğretime devam ediyorsanız.',
                        level: 1
                    ),
                ],
            ],
            [
                'section_key' => 'language_skills',
                'section_order' => 40,
                'title' => 'Dil Bilgisi',
                'fields' => [
                    // Sadece Level 2
                    self::f('is_enrolled_german_course', 'Herhangi bir Almanca kursuna kayıtlı mısınız? *', 'select', true, 10, options: self::yesNoOptions()),
                    self::f('german_course_name', 'Almanca kursuna gidiyorsanız ismini yazın *', 'text', false, 180),
                    // Level 1
                    self::f('german_level', 'Almanca seviyeniz *', 'select', true, 20, options: self::languageLevelOptions(includeNone: true), level: 1),
                    // Level 1 yeni — sertifika
                    self::f('german_certificate_held', 'Almanca dil sertifikanız var mı?', 'select', false, 10, options: self::yesNoOptions(), level: 1),
                    self::f('german_certificate_type', 'Sertifika türü', 'select', false, 40, options: self::germanCertificateTypeOptions(), help_text: 'Sertifikanız varsa.', level: 1),
                    self::f('german_certificate_score', 'Sertifika puanı / sonucu', 'text', false, 20, placeholder: 'Örn: TDN 4 / B2', level: 1),
                    // Sadece Level 2
                    self::f('is_enrolled_english_course', 'İngilizce kursuna gidiyor musunuz? *', 'select', true, 10, options: self::yesNoOptions()),
                    // Level 1
                    self::f('english_level', 'İngilizce dil seviyeniz *', 'select', true, 20, options: self::languageLevelOptions(includeNone: true), level: 1),
                    // Level 1 yeni — opsiyonel IELTS/TOEFL
                    self::f('english_certificate_score', 'İngilizce sertifika puanı (varsa)', 'text', false, 40, placeholder: 'Örn: IELTS 7.0 / TOEFL 95', level: 1),
                    // Sadece Level 2
                    self::f('other_language', 'Bildiğiniz diğer dil (varsa)', 'text', false, 120, placeholder: 'Örn: Fransızca, Rusça, Arapça'),
                    self::f('other_language_level', 'Bu dilin seviyesi', 'select', false, 20, options: self::languageLevelOptions(includeNone: true), help_text: 'Yukarıda bir dil yazdıysanız seviyesini seçiniz.'),
                ],
            ],
            [
                'section_key' => 'finance_visa',
                'section_order' => 50,
                'title' => 'Finans / Vize / Geçmiş',
                'fields' => [
                    // Level 1
                    self::f('finance_method', 'Almanya\'da yaşam masraflarınızı kanıtlamak için hangi yöntemi düşünüyorsunuz? *', 'select', true, 60, options: self::financeMethodOptions(), level: 1),
                    // Level 1 yeni — konaklama tanıdığı
                    self::f('accommodation_contact_status', 'Almanya\'da konaklama için size destek olabilecek bir tanıdığınız var mı?', 'select', false, 20, options: self::accommodationContactOptions(), level: 1),
                    self::f('accommodation_contact_city', 'Tanıdığınızın bulunduğu şehir', 'text', false, 100, placeholder: 'Örn: München', help_text: 'Sadece tanıdığınız varsa doldurun.', level: 1),
                    // Sadece Level 2
                    self::f('estimated_monthly_budget_eur', 'Aylık bütçe planı (EUR)', 'text', false, 32),
                    self::f('knows_blocked_account', 'Bloke hesap gerekliliği hakkında bilginiz var mı? *', 'select', true, 10, options: self::yesNoOptions()),
                    self::f('has_passport', 'Pasaportunuz var mı? *', 'select', true, 10, options: self::yesNoOptions()),
                    self::f('passport_number', 'Pasaport seri numarası (pasaport varsa) *', 'text', false, 64),
                    self::f('has_visa_history', 'Daha önce Almanya/başka ülkeye vize başvurusu yaptınız mı? *', 'select', true, 10, options: self::yesNoOptions()),
                    self::f('has_abroad_experience', 'Daha önce yurt dışında eğitim/iş deneyiminiz oldu mu? *', 'select', true, 10, options: self::yesNoOptions()),
                    self::f('has_health_condition', 'Özel bir sağlık durumu/gereksiniminiz var mı? *', 'select', true, 10, options: self::yesNoOptions()),
                    self::f('considered_other_country', 'Başka bir ülkede üniversite okumayı düşündünüz mü?', 'select', false, 10, options: self::yesNoOptions()),
                    self::f('lived_in_germany_before', 'Daha önce Almanya\'da yaşadınız mı? *', 'select', true, 10, options: self::yesNoOptions()),
                    self::f('germany_stay_from', 'Almanya\'da kalış başlangıç (ay/yıl)', 'month', false, 10, placeholder: 'YYYY-MM'),
                    self::f('germany_stay_to', 'Almanya\'da kalış bitiş (ay/yıl)', 'month', false, 10, placeholder: 'YYYY-MM'),
                    self::f('germany_last_residences', 'Almanya\'daki son üç ikamet yeri/tarih bilgisi', 'textarea', false, 2000),
                    self::f('germany_references', 'Varsa Almanya\'daki referanslarınız', 'textarea', false, 2000),
                ],
            ],
            [
                'section_key' => 'family_info',
                'section_order' => 60,
                'title' => 'Aile Bilgileri',
                'fields' => [
                    self::f('father_full_name', 'Babanızın Adı Soyadı *', 'text', true, 180),
                    self::f('father_birth_date', 'Babanızın doğum tarihi *', 'date', true, 20),
                    self::f('father_job', 'Babanızın mesleği *', 'text', true, 120),
                    self::f('father_birth_place', 'Babanızın doğum yeri *', 'text', true, 120),
                    self::f('father_address', 'Babanızın açık adresi *', 'text', true, 255),
                    self::f('mother_full_name', 'Annenizin adı soyadı *', 'text', true, 180),
                    self::f('mother_birth_date', 'Annenizin doğum tarihi *', 'date', true, 20),
                    self::f('mother_birth_place', 'Annenizin doğum yeri *', 'text', true, 120),
                    self::f('mother_job', 'Annenizin mesleği *', 'text', true, 120),
                    self::f('mother_address', 'Annenizin açık adresi *', 'text', true, 255),
                ],
            ],
            [
                // Sadece marital_status=='married' seçildiğinde görünür.
                // Adım 2 (personal_info hemen sonrası) konumunda — Evli seçilirse
                // eş bilgileri derhal sorulur, değilse client-side JS bu adımı atlar.
                'section_key' => 'spouse_info',
                'section_order' => 15,
                'title' => 'Eşinizle İlgili Bilgiler',
                'fields' => [
                    self::f('spouse_full_name', 'Eşinizin adı soyadı *', 'text', true, 180),
                    self::f('spouse_birth_date', 'Eşinizin doğum tarihi *', 'date', true, 20),
                    self::f('spouse_nationality', 'Eşinizin uyruğu *', 'text', true, 120),
                    self::f('spouse_occupation', 'Eşinizin mesleği *', 'text', true, 120),
                    self::f('marriage_date', 'Nikah tarihi *', 'date', true, 20),
                    self::f('marriage_place', 'Nikah yeri (şehir/ülke) *', 'text', true, 180),
                    self::f(
                        'spouse_currently_in_germany',
                        'Eşiniz şu an Almanya\'da mı? *',
                        'select',
                        true,
                        10,
                        options: self::yesNoOptions()
                    ),
                    self::f(
                        'has_children',
                        'Çocuğunuz var mı?',
                        'select',
                        false,
                        10,
                        options: self::yesNoOptions()
                    ),
                    // has_children === 'yes' seçildiğinde görünür (client-side JS).
                    self::f('children_count', 'Kaç çocuğunuz var?', 'text', false, 2, placeholder: 'Örn: 2'),
                ],
            ],
            [
                'section_key' => 'reference_extra',
                'section_order' => 70,
                'title' => 'Proje / Referans / Ek Bilgi',
                'fields' => [
                    self::f('social_project_text', 'Bilimsel/sosyal projede yer aldınız mı?', 'text', false, 255),
                    self::f('has_teacher_reference', 'Referans olabilecek öğretmeniniz var mı?', 'select', false, 10, options: self::yesNoOptions()),
                    self::f('teacher_reference_contact', 'Referans isim-soyisim ve iletişim', 'text', false, 255),
                    self::f('additional_note', 'Eklemek istediğiniz açıklama var mı?', 'textarea', false, 2500),
                ],
            ],
            [
                // Level 1 yeni section — User'ın 6. wizard'ı (Motivasyon ve Hazırlık).
                // Mevcut Level 2 form'da yok; Level 1'den geldiği için Level 2'de de prefill görünür.
                'section_key' => 'motivation_preparation',
                'section_order' => 80,
                'title' => 'Motivasyon ve Hazırlık',
                'fields' => [
                    self::f(
                        'motivation_thinking_duration',
                        'Almanya\'da eğitim fikri sizde ne kadar süredir var? *',
                        'select',
                        true,
                        20,
                        options: self::motivationDurationOptions(),
                        level: 1
                    ),
                    self::f(
                        'biggest_concerns',
                        'Bu süreçteki en büyük endişeniz nedir? (birden fazla seçebilirsiniz)',
                        'checkbox_group',
                        false,
                        500,
                        options: self::biggestConcernOptions(),
                        help_text: 'Sizi en çok zorladığını düşündüğünüz konuları seçin. Diğer durumunda lütfen ek not bölümünde belirtin.',
                        level: 1
                    ),
                ],
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public static function sanitizePayload(array $input): array
    {
        return self::sanitizePayloadByFields($input, self::flatFields());
    }

    /**
     * @param array<int,array<string,mixed>> $fields
     * @return array<string,mixed>
     */
    public static function sanitizePayloadByFields(array $input, array $fields): array
    {
        $out = [];
        foreach ($fields as $field) {
            $key = (string) $field['key'];
            $type = (string) $field['type'];
            $max = (int) ($field['max'] ?? 255);
            $val = $input[$key] ?? null;

            if ($type === 'date') {
                $txt = trim((string) $val);
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $txt)) {
                    $out[$key] = null;
                    continue;
                }

                // Per-field tarih kısıtı (server-side koruma — HTML5 max/min bypass için)
                $today = now()->format('Y-m-d');

                if ($key === 'birth_date') {
                    // En az 15 yaşında olmalı — gelecek tarih + son 15 yıl reddedilir
                    $maxBirth = now()->subYears(15)->format('Y-m-d');
                    if ($txt > $maxBirth) {
                        $out[$key] = null;
                        continue;
                    }
                } elseif (in_array($key, [
                    'father_birth_date', 'mother_birth_date', 'spouse_birth_date', 'marriage_date',
                    'primary_start_date', 'primary_end_date',
                    'middle_start_date', 'middle_end_date',
                    'high_start_date', 'high_end_date',
                ], true)) {
                    // Geçmiş tarih field'ları — gelecek olamaz
                    if ($txt > $today) {
                        $out[$key] = null;
                        continue;
                    }
                } elseif (in_array($key, [
                    'university_start_target_date', 'planned_start_date', 'target_start_date',
                ], true)) {
                    // Hedef tarih field'ları — geçmişte olamaz
                    if ($txt < $today) {
                        $out[$key] = null;
                        continue;
                    }
                }

                $out[$key] = $txt;
                continue;
            }

            if ($type === 'select') {
                $txt = trim((string) $val);
                if ($txt === '') {
                    $out[$key] = null;
                    continue;
                }
                $options = array_column((array) ($field['options'] ?? []), 'value');
                $out[$key] = in_array($txt, $options, true) ? $txt : null;
                continue;
            }

            if ($type === 'checkbox_group') {
                // Multi-select — array kabul, valid option'lara filtre
                $arr = is_array($val) ? $val : [];
                $options = array_column((array) ($field['options'] ?? []), 'value');
                $out[$key] = collect($arr)
                    ->map(fn ($v) => trim((string) $v))
                    ->filter(fn ($v) => in_array($v, $options, true))
                    ->values()
                    ->all();
                continue;
            }

            $txt = trim((string) $val);
            $out[$key] = $txt === '' ? null : mb_substr($txt, 0, $max);
        }

        return $out;
    }

    /**
     * @return array<int,string>
     */
    public static function requiredKeys(): array
    {
        return self::requiredKeysByFields(self::flatFields());
    }

    /**
     * @param array<int,array<string,mixed>> $fields
     * @return array<int,string>
     */
    public static function requiredKeysByFields(array $fields): array
    {
        return collect($fields)
            ->filter(fn (array $f) => (bool) ($f['required'] ?? false))
            ->map(fn (array $f) => (string) $f['key'])
            ->values()
            ->all();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function flatFields(): array
    {
        return self::flatFieldsByLevel(2);
    }

    /**
     * Level filtreli düz field listesi.
     * @return array<int,array<string,mixed>>
     */
    public static function flatFieldsByLevel(int $level = 2): array
    {
        return collect(self::groupsByLevel($level))
            ->flatMap(fn (array $g) => (array) ($g['fields'] ?? []))
            ->values()
            ->all();
    }

    /**
     * @return array<int,string>
     */
    public static function requiredKeysByLevel(int $level = 2): array
    {
        return self::requiredKeysByFields(self::flatFieldsByLevel($level));
    }

    /**
     * @return array<string,mixed>
     */
    public static function sanitizePayloadByLevel(array $input, int $level = 2): array
    {
        return self::sanitizePayloadByFields($input, self::flatFieldsByLevel($level));
    }

    /**
     * Field tanımı.
     *
     * @param int $level 1 = Level 1+2'de görünür (cumulative subset),
     *                   2 = sadece Level 2'de görünür (default — mevcut field'lar).
     *                   Apply'dan gelen field'lar (first_name, gender, vs.) level=1
     *                   işaretlenir; view tarafında readonly badge ile gösterilir.
     */
    private static function f(
        string $key,
        string $label,
        string $type,
        bool $required,
        int $max = 255,
        string $placeholder = '',
        array $options = [],
        string $help_text = '',
        int $level = 2
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'type' => $type,
            'required' => $required,
            'max' => $max,
            'placeholder' => $placeholder,
            'options' => $options,
            'help_text' => $help_text,
            'level' => $level,
        ];
    }

    private static function yesNoGenderOptions(): array
    {
        return [
            ['value' => 'male', 'label' => 'Erkek'],
            ['value' => 'female', 'label' => 'Kadın'],
            ['value' => 'other', 'label' => 'Diğer'],
        ];
    }

    private static function maritalOptions(): array
    {
        return [
            ['value' => 'single', 'label' => 'Bekar'],
            ['value' => 'married', 'label' => 'Evli'],
            ['value' => 'divorced', 'label' => 'Boşanmış'],
            ['value' => 'widowed', 'label' => 'Dul'],
        ];
    }

    private static function yesNoOptions(): array
    {
        return [
            ['value' => 'yes', 'label' => 'Evet'],
            ['value' => 'no', 'label' => 'Hayır'],
        ];
    }

    private static function languageLevelOptions(bool $includeNone = false): array
    {
        $rows = [
            ['value' => 'a1', 'label' => 'A1'],
            ['value' => 'a2', 'label' => 'A2'],
            ['value' => 'b1', 'label' => 'B1'],
            ['value' => 'b2', 'label' => 'B2'],
            ['value' => 'c1', 'label' => 'C1'],
            ['value' => 'c2', 'label' => 'C2'],
        ];
        if ($includeNone) {
            array_unshift($rows, ['value' => 'none', 'label' => 'Yok']);
        }
        return $rows;
    }

    private static function educationLevelOptions(): array
    {
        return [
            ['value' => 'middle_school', 'label' => 'Ortaokul'],
            ['value' => 'high_school', 'label' => 'Lise'],
            ['value' => 'bachelor', 'label' => 'Lisans'],
            ['value' => 'master', 'label' => 'Yüksek Lisans'],
        ];
    }

    private static function applicationTypeOptions(): array
    {
        return [
            ['value' => 'bachelor', 'label' => 'Bachelor (Lisans)'],
            ['value' => 'master', 'label' => 'Master (Yüksek Lisans)'],
            ['value' => 'ausbildung', 'label' => 'Ausbildung (Mesleki Eğitim)'],
            ['value' => 'language_course', 'label' => 'Dil Kursu'],
            ['value' => 'residence', 'label' => 'İkamet'],
        ];
    }

    private static function highSchoolTypeOptions(): array
    {
        return [
            ['value' => 'anadolu_fen', 'label' => 'Anadolu ve Fen Liseleri'],
            ['value' => 'meslek', 'label' => 'Meslek Lisesi'],
            ['value' => 'acik_lise', 'label' => 'Açık Lise'],
        ];
    }

    private static function financeMethodOptions(): array
    {
        return [
            ['value' => 'blocked_account', 'label' => 'Bloke Hesap (Sperrkonto)'],
            ['value' => 'sponsor', 'label' => 'Garantör (Verpflichtungserklärung)'],
            ['value' => 'self_funded', 'label' => 'Kendi Birikimi'],
            ['value' => 'scholarship', 'label' => 'Burs'],
            ['value' => 'undecided', 'label' => 'Henüz Karar Vermedim'],
        ];
    }

    // ─── Level 1 yeni opsiyon listeleri ──────────────────────────────────────

    private static function higherEducationStatusOptions(): array
    {
        return [
            ['value' => 'not_started', 'label' => 'Lise mezunuyum, henüz üniversiteye başlamadım'],
            ['value' => 'enrolled', 'label' => 'Üniversiteye devam ediyorum'],
            ['value' => 'dropped', 'label' => 'Üniversiteyi bıraktım / ayrıldım'],
            ['value' => 'graduated', 'label' => 'Üniversite mezunuyum'],
        ];
    }

    private static function universityYearOptions(): array
    {
        return [
            ['value' => 'prep', 'label' => 'Hazırlık'],
            ['value' => '1', 'label' => '1. sınıf'],
            ['value' => '2', 'label' => '2. sınıf'],
            ['value' => '3', 'label' => '3. sınıf'],
            ['value' => '4', 'label' => '4. sınıf'],
            ['value' => '5plus', 'label' => '5. sınıf ve üzeri'],
        ];
    }

    private static function germanCertificateTypeOptions(): array
    {
        return [
            ['value' => 'testdaf', 'label' => 'TestDaF'],
            ['value' => 'dsh', 'label' => 'DSH'],
            ['value' => 'goethe', 'label' => 'Goethe (B1/B2/C1/C2)'],
            ['value' => 'telc', 'label' => 'telc'],
            ['value' => 'oesd', 'label' => 'ÖSD'],
            ['value' => 'other', 'label' => 'Diğer'],
        ];
    }

    private static function accommodationContactOptions(): array
    {
        return [
            ['value' => 'yes', 'label' => 'Evet, var'],
            ['value' => 'no', 'label' => 'Hayır, yok'],
            ['value' => 'maybe', 'label' => 'Emin değilim / belki'],
        ];
    }

    private static function motivationDurationOptions(): array
    {
        return [
            ['value' => 'under_1y', 'label' => '1 yıldan az'],
            ['value' => '1_2y', 'label' => '1-2 yıl'],
            ['value' => 'over_2y', 'label' => '2 yıldan fazla'],
        ];
    }

    private static function biggestConcernOptions(): array
    {
        return [
            ['value' => 'language', 'label' => 'Almanca dil yeterliliği'],
            ['value' => 'cost', 'label' => 'Maliyet / mali yük'],
            ['value' => 'loneliness', 'label' => 'Yalnızlık / uyum'],
            ['value' => 'academic', 'label' => 'Akademik zorluk'],
            ['value' => 'visa', 'label' => 'Vize süreci'],
            ['value' => 'housing', 'label' => 'Konaklama / barınma'],
            ['value' => 'other', 'label' => 'Diğer'],
        ];
    }
}
