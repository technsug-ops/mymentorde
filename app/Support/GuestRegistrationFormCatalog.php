<?php

namespace App\Support;

class GuestRegistrationFormCatalog
{
    /**
     * @return array<int,array<string,mixed>>
     */
    public static function applicationCountryOptions(): array
    {
        return array_map(
            static fn (array $c) => ['value' => $c['label'], 'label' => $c['label'] . ' (' . $c['code'] . ')'],
            ApplicationCountryCatalog::options()
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function groups(): array
    {
        return [
            [
                'section_key' => 'personal_info',
                'section_order' => 10,
                'title' => 'Kişisel Bilgiler',
                'fields' => [
                    self::f('first_name', 'İsim *', 'text', true, 120),
                    self::f('last_name', 'Soyisim *', 'text', true, 120),
                    self::f('gender', 'Cinsiyetiniz *', 'select', true, 40, options: self::yesNoGenderOptions()),
                    self::f('marital_status', 'Medeni Hali *', 'select', true, 40, options: self::maritalOptions()),
                    self::f('email', 'E-Mail *', 'email', true, 180),
                    self::f('phone', 'Telefon *', 'text', true, 64),
                    self::f('reference_text', 'Referans', 'text', false, 180),
                    self::f('birth_date', 'Doğum Tarihiniz *', 'date', true, 20),
                    self::f('birth_place', 'Doğum yeriniz *', 'text', true, 120),
                ],
            ],
            [
                'section_key' => 'address_application',
                'section_order' => 20,
                'title' => 'Adres ve Başvuru',
                'fields' => [
                    self::f('application_city', 'Başvuru şehri *', 'text', true, 120),
                    self::f('application_country', 'Başvuru ülkesi *', 'select', true, 120, options: self::applicationCountryOptions()),
                    self::f('address_line', 'Açık Adresiniz *', 'text', true, 255),
                    self::f('postal_code', 'Posta kodu *', 'text', true, 32),
                    self::f('district', 'İlçe *', 'text', true, 120),
                    self::f('province', 'İl *', 'text', true, 120),
                    self::f('application_type', 'Başvuru Tipi *', 'select', true, 40, options: self::applicationTypeOptions()),
                    self::f('target_program', 'Okumayı hedeflediğiniz bölüm/program *', 'text', true, 255),
                    self::f('university_start_target_date', 'Üniversite başlangıç tarihi hedefiniz *', 'date', true, 20),
                ],
            ],
            [
                'section_key' => 'education_history',
                'section_order' => 30,
                'title' => 'Eğitim Geçmişi',
                'fields' => [
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
                    self::f(
                        'high_school_type',
                        'Lise türünüz *',
                        'select',
                        true,
                        32,
                        options: self::highSchoolTypeOptions(),
                        help_text: '● Anadolu ve Fen Liseleri: Anadolu Lisesi, Fen Lisesi, Sosyal Bilimler Lisesi. ● Meslek Lisesi: Mesleki ve Teknik Anadolu Lisesi (MTAL), Mesleki Eğitim Merkezi (MESEM), Anadolu Teknik Programı (ATP), İmam Hatip Lisesi, Anadolu İmam Hatip Lisesi, Güzel Sanatlar ve Spor Lisesi, Çok Programlı Anadolu Lisesi, Sağlık Meslek Lisesi, Ticaret/Adalet/Turizm/Spor Meslek Lisesi. ● Açık Lise: Mesleki Açık Öğretim Lisesi (MAÖL), Açık Öğretim İmam Hatip Lisesi (AÖİHL), Açık Öğretim Lisesi (AOL).'
                    ),
                    self::f('high_school_grade', 'Lise mezuniyet ortalamanız *', 'text', true, 32),
                    self::f('university_name', 'Üniversite adı (eğer öğrenciyseniz)', 'text', false, 180),
                    self::f('university_department', 'Bölüm adı (eğer öğrenciyseniz)', 'text', false, 180),
                ],
            ],
            [
                'section_key' => 'language_skills',
                'section_order' => 40,
                'title' => 'Dil Bilgisi',
                'fields' => [
                    self::f('is_enrolled_german_course', 'Herhangi bir Almanca kursuna kayıtlı mısınız? *', 'select', true, 10, options: self::yesNoOptions()),
                    self::f('german_course_name', 'Almanca kursuna gidiyorsanız ismini yazın *', 'text', false, 180),
                    self::f('german_level', 'Almanca seviyeniz *', 'select', true, 20, options: self::languageLevelOptions()),
                    self::f('is_enrolled_english_course', 'İngilizce kursuna gidiyor musunuz? *', 'select', true, 10, options: self::yesNoOptions()),
                    self::f('english_level', 'İngilizce dil seviyeniz *', 'select', true, 20, options: self::languageLevelOptions()),
                    self::f('other_language', 'Bildiğiniz diğer dil (varsa)', 'text', false, 120, placeholder: 'Örn: Fransızca, Rusça, Arapça'),
                    self::f('other_language_level', 'Bu dilin seviyesi', 'select', false, 20, options: self::languageLevelOptions(includeNone: true), help_text: 'Yukarıda bir dil yazdıysanız seviyesini seçiniz.'),
                ],
            ],
            [
                'section_key' => 'finance_visa',
                'section_order' => 50,
                'title' => 'Finans / Vize / Geçmiş',
                'fields' => [
                    self::f('finance_method', 'Üniversite başvurusunda hangi finansal yöntemi seçeceksiniz? *', 'select', true, 60, options: self::financeMethodOptions()),
                    self::f('estimated_monthly_budget_eur', 'Aylık bütçe planı (EUR)', 'text', false, 32),
                    self::f('knows_blocked_account', 'Bloke hesap gerekliliği hakkında bilginiz var mı? *', 'select', true, 10, options: self::yesNoOptions()),
                    self::f('has_passport', 'Pasaportunuz var mı? *', 'select', true, 10, options: self::yesNoOptions()),
                    self::f('passport_number', 'Pasaport seri numarası (pasaport varsa) *', 'text', false, 64),
                    self::f('has_visa_history', 'Daha önce Almanya/başka ülkeye vize başvurusu yaptınız mı? *', 'select', true, 10, options: self::yesNoOptions()),
                    self::f('has_abroad_experience', 'Daha önce yurt dışında eğitim/iş deneyiminiz oldu mu? *', 'select', true, 10, options: self::yesNoOptions()),
                    self::f('has_health_condition', 'Özel bir sağlık durumu/gereksiniminiz var mı? *', 'select', true, 10, options: self::yesNoOptions()),
                    self::f('considered_other_country', 'Başka bir ülkede üniversite okumayı düşündünüz mü?', 'select', false, 10, options: self::yesNoOptions()),
                    self::f('lived_in_germany_before', 'Daha önce Almanya\'da yaşadınız mı? *', 'select', true, 10, options: self::yesNoOptions()),
                    self::f('germany_stay_date_range', 'Hangi tarihler arasında Almanya\'da kaldınız?', 'text', false, 180),
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
                // Sadece marital_status=='married' seçildiğinde görünür
                // (client-side JS + server-side conditionalRequired ile kontrol edilir)
                'section_key' => 'spouse_info',
                'section_order' => 65,
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
                $out[$key] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $txt) ? $txt : null;
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
        return collect(self::groups())
            ->flatMap(fn (array $g) => (array) ($g['fields'] ?? []))
            ->values()
            ->all();
    }

    private static function f(
        string $key,
        string $label,
        string $type,
        bool $required,
        int $max = 255,
        string $placeholder = '',
        array $options = [],
        string $help_text = ''
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
            ['value' => 'blocked_account', 'label' => 'Bloke Hesap'],
            ['value' => 'sponsor', 'label' => 'Sponsorluk'],
            ['value' => 'self_funded', 'label' => 'Kendi Birikimi'],
            ['value' => 'scholarship', 'label' => 'Burs'],
        ];
    }
}
