<?php

namespace App\Services;

use App\Models\GuestApplication;

/**
 * Uni-Assist "Mein Konto" form'unun 4 sekmesini (PERSÖNLICHE DATEN /
 * KONTAKTDATEN / HOCHSCHULSTART.DE / BILDUNGSHISTORIE) bizim öğrenci
 * verisine map'ler. Manager guide sayfasında her field copy edilebilir.
 *
 * Field yapısı:
 *  - key:         tab içi unique key
 *  - label:       Uni-Assist'teki birebir Almanca etiket
 *  - label_tr:    Türkçe karşılık (manager için açıklayıcı)
 *  - value:       öğrenci datasından çekilmiş hali (null = eksik)
 *  - source:      veri kaynağı (db column / form draft / meta / manual)
 *  - hint:        Uni-Assist form'undaki yardım metni
 *  - required:    zorunlu mu
 *  - missing:     value boş mu (empty alarmı)
 *  - copyable:    kopyala butonu görünür mü
 */
class UniAssistFieldMapperService
{
    /**
     * Uni-Assist'in tanıdığı 8 Türk lise diploma türü (Bildungshistorie sekmesi).
     * Manager bu listeden seçer. Default: 12-jährige (1997 sonrası en yaygın).
     */
    public const SCHULABSCHLUSS_OPTIONS = [
        'lise_12'         => 'Lise Diplomasi einer 12-jährigen allgemeinbildenden Sekundarschule',
        'lise_11'         => 'Lise Diplomasi einer 11-jährigen allgemeinbildenden Sekundarschule',
        'anadolu_imam'    => 'Anadolu Imam Hatip Lisesi Diplomasi',
        'anadolu_meslek'  => 'Anadolu Mesleki ve Teknik Lisesi Diplomasi',
        'anadolu_teknik'  => 'Anadolu Teknik Lisesi Diplomasi',
        'acik_lise'       => 'Lise Diplomasi eines türkischen Ferngymnasiums (Acik Ögretim Lisesi)',
        'lise_onlisans'   => 'Lise Diplomasi in Verbindung mit Önlisans',
        'meslek_lisesi'   => 'Meslek Lisesi Diplomasi einer 11- oder 12-jährigen berufsbildenden Sekundarschule',
    ];

    /** Geschlecht dropdown options */
    public const GESCHLECHT_OPTIONS = [
        'Männlich'   => 'Männlich (Erkek)',
        'Weiblich'   => 'Weiblich (Kadın)',
        'Divers'     => 'Divers (Diğer)',
        'Unbestimmt' => 'Unbestimmt (Belirtilmemiş)',
    ];

    public function buildAllTabs(GuestApplication $guest): array
    {
        return [
            'persoenliche' => [
                'title' => 'PERSÖNLICHE DATEN',
                'title_tr' => 'Kişisel Bilgiler',
                'fields' => $this->personalFields($guest),
            ],
            'kontakt' => [
                'title' => 'KONTAKTDATEN',
                'title_tr' => 'İletişim Bilgileri',
                'fields' => $this->contactFields($guest),
            ],
            'hochschulstart' => [
                'title' => 'HOCHSCHULSTART.DE',
                'title_tr' => 'Hochschulstart Hesabı',
                'fields' => $this->hochschulstartFields($guest),
            ],
            'bildungs' => [
                'title' => 'BILDUNGSHISTORIE',
                'title_tr' => 'Eğitim Geçmişi',
                'fields' => $this->educationFields($guest),
            ],
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function personalFields(GuestApplication $guest): array
    {
        $draft = is_array($guest->registration_form_draft) ? $guest->registration_form_draft : [];

        // Geschlecht: önce manager override (meta.geschlecht_de), yoksa guest.gender'dan map
        $meta = is_array($guest->application_meta) ? $guest->application_meta : [];
        $managerGender = (string) ($meta['geschlecht_de'] ?? '');
        if ($managerGender !== '' && isset(self::GESCHLECHT_OPTIONS[$managerGender])) {
            $resolvedGender = $managerGender;
        } else {
            $genderMap = ['m' => 'Männlich', 'male' => 'Männlich', 'f' => 'Weiblich', 'female' => 'Weiblich', 'erkek' => 'Männlich', 'kadın' => 'Weiblich', 'kadin' => 'Weiblich'];
            $rawGender = strtolower((string) ($guest->gender ?? ''));
            $resolvedGender = $genderMap[$rawGender] ?? null;
        }

        $birthDate = $draft['birth_date'] ?? null;

        return [
            $this->field('geschlecht', 'Geschlecht', 'Cinsiyet', $resolvedGender, 'guest.gender|meta.geschlecht_de', true, 'Manager dropdown\'dan da seçebilir: Männlich/Weiblich/Divers/Unbestimmt'),
            $this->field('vorname', 'Vorname', 'Ad', $guest->first_name, 'guest.first_name', true),
            $this->field('nachname', 'Nachname', 'Soyad', $guest->last_name, 'guest.last_name', true),
            $this->field('namenszusatz', 'Namenszusatz', 'Soyad eki', null, 'manual', false, 'Türkiye\'de kullanılmıyorsa boş bırak'),
            $this->field('geburtsname', 'Geburtsname', 'Doğum soyadı', null, 'manual', false, 'Evlilik öncesi soyadı varsa yaz'),
            $this->field('geburtsdatum', 'Geburtsdatum', 'Doğum Tarihi', $birthDate ? $this->formatDate($birthDate) : null, 'draft.birth_date', true),
            $this->field('geburtsort', 'Geburtsort', 'Doğum Yeri', $draft['birth_place'] ?? null, 'draft.birth_place', true),
            $this->field('staatsangehoerigkeit', 'Staatsangehörigkeit', 'Uyruk', 'Türkei', 'static', true, 'Çoklu uyruk varsa hepsini ekle'),
            $this->field('eu_marriage', 'EU/EWR vatandaşı ile evli misiniz?', '(EU/EWR Familienangehöriger)', $this->metaVal($guest, 'eu_marriage', 'Nein'), 'meta.eu_marriage', false, 'Genelde Nein'),
            $this->field('is_refugee', 'Mülteci olarak başvuran mı?', '(Geflüchtete*r)', $this->metaBool($guest, 'is_refugee') ? 'Ja' : 'Nein', 'meta.is_refugee', false, 'Genelde Nein'),
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function contactFields(GuestApplication $guest): array
    {
        $draft = is_array($guest->registration_form_draft) ? $guest->registration_form_draft : [];

        return [
            $this->field('co', 'c/o', 'c/o adresi', $this->metaVal($guest, 'co_address'), 'meta.co_address', false, 'Format: "c/o İsim". Genelde boş.'),
            $this->field('strasse', 'Straße, Hausnummer', 'Sokak ve numara', $draft['address_line'] ?? null, 'draft.address_line', true),
            $this->field('adresszusatz', 'Adresszusatz', 'Apartman/Site adı', $this->metaVal($guest, 'address_extra'), 'meta.address_extra', false),
            $this->field('plz', 'Postleitzahl', 'Posta kodu', $draft['postal_code'] ?? null, 'draft.postal_code', false),
            $this->field('stadt', 'Stadt/Provinz/Region', 'Şehir/İlçe', $this->resolveStadtFromDraft($draft) ?? $this->metaVal($guest, 'home_city'), 'draft.district+province|meta.home_city', true, 'Form\'da il+ilçe seçildiyse "İLÇE/İL" formatında otomatik üretilir, yoksa manager elle girer.'),
            $this->field('land', 'Land', 'Ülke', 'Türkei', 'static', true),
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function hochschulstartFields(GuestApplication $guest): array
    {
        return [
            $this->field('bid', 'BID (Benutzer-ID)', 'BID — Hochschulstart kullanıcı ID', $this->metaVal($guest, 'hochschulstart_bid'), 'meta.hochschulstart_bid', false, '"B" + 12 hane. Sadece DoSV (Tıp/Diş/Veteriner) için. Aday hochschulstart.de\'den alır.', editable: true, format: 'B + 12 hane'),
            $this->field('ban', 'BAN (Bewerber-Authentifizierungs-Nummer)', 'BAN — Hochschulstart şifre', $this->metaVal($guest, 'hochschulstart_ban'), 'meta.hochschulstart_ban', false, '6 hane. BID ile birlikte gelir.', editable: true, format: '6 hane'),
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function educationFields(GuestApplication $guest): array
    {
        $draft = is_array($guest->registration_form_draft) ? $guest->registration_form_draft : [];

        return [
            $this->field('schulabschluss_done', 'Haben Sie einen Schulabschluss gemacht?', 'Lise bitirme yaptın mı?', 'Ja', 'static', true, 'Lise diplomanız var → Ja'),
            $this->field('schulabschluss_land', 'In welchem Land?', 'Hangi ülkede lise bitti?', 'Türkei', 'static', true),
            $this->field('schulabschluss_typ', 'Name des höchsten Schulabschlusszeugnisses', 'Lise diploması tipi', $this->resolveSchulabschlussType($guest, $draft), 'derived', true, '8 Türk lise türü tanınır. Default: 12-jährige (1997 sonrası en yaygın). Açık lise/imam hatip/meslek için manager dropdown\'dan seçer.'),
            $this->field('feststellungspruefung', 'Feststellungsprüfung yaptı mı?', '(Studienkolleg sınavı)', $this->metaVal($guest, 'feststellungspruefung_done', 'Nein'), 'meta.feststellungspruefung_done', false, 'Lise yetersizse Studienkolleg + FSP. Genelde Nein.'),
            $this->field('studienabschluss', 'Studienabschluss var mı?', 'Üniversite diploması var mı? (en az 3 yıllık)', $this->resolveStudienabschluss($draft), 'derived', false, 'Yüksek lisans/Master için → Ja, Bachelor için → Nein'),
            $this->field('high_school_grad_year', 'Lise Mezuniyet Yılı', '(yardımcı bilgi)', $draft['high_school_grad_year'] ?? null, 'draft.high_school_grad_year', false),
            $this->field('passport_number', 'Pasaport Numarası', '(yardımcı — başka tab\'larda istenebilir)', $draft['passport_number'] ?? null, 'draft.passport_number', false),
        ];
    }

    private function field(
        string $key, string $label, string $labelTr, ?string $value, string $source,
        bool $required = false, ?string $hint = null, bool $editable = false, ?string $format = null
    ): array {
        $val = $value !== null ? trim((string) $value) : null;
        return [
            'key'      => $key,
            'label'    => $label,
            'label_tr' => $labelTr,
            'value'    => $val !== '' ? $val : null,
            'source'   => $source,
            'required' => $required,
            'missing'  => $val === null || $val === '',
            'hint'     => $hint,
            'editable' => $editable,
            'format'   => $format,
            'copyable' => $val !== null && $val !== '',
        ];
    }

    private function metaVal(GuestApplication $guest, string $key, ?string $default = null): ?string
    {
        $meta = is_array($guest->application_meta) ? $guest->application_meta : [];
        $v = $meta[$key] ?? null;
        if ($v === null || $v === '') return $default;
        return (string) $v;
    }

    /**
     * Form draft'tan il+ilçe slug'larını "İLÇE/İL" formatında label'a çevirir.
     * Uni-Assist Stadt alanı için.
     */
    private function resolveStadtFromDraft(array $draft): ?string
    {
        $provinceSlug = trim((string) ($draft['province'] ?? ''));
        $districtSlug = trim((string) ($draft['district'] ?? ''));
        if ($provinceSlug === '' && $districtSlug === '') return null;

        try {
            $province = $provinceSlug !== '' ? \App\Models\TrProvince::where('slug', $provinceSlug)->first() : null;
            $district = $districtSlug !== '' ? \App\Models\TrDistrict::where('slug', $districtSlug)->first() : null;

            $provinceName = $province->name ?? null;
            $districtName = $district->name ?? null;

            if ($districtName && $provinceName) return mb_strtoupper("{$districtName}/{$provinceName}");
            if ($provinceName) return mb_strtoupper($provinceName);
            if ($districtName) return mb_strtoupper($districtName);
        } catch (\Throwable $e) {}
        return null;
    }

    private function metaBool(GuestApplication $guest, string $key): bool
    {
        $meta = is_array($guest->application_meta) ? $guest->application_meta : [];
        return ! empty($meta[$key]);
    }

    private function formatDate(string $raw): string
    {
        try {
            return \Carbon\Carbon::parse($raw)->format('d.m.Y');
        } catch (\Throwable $e) {
            return $raw;
        }
    }

    /**
     * Schulabschluss tipi:
     * 1) Manager elle seçtiyse meta.schulabschluss_type'tan oku
     * 2) Yoksa birth_date / high_school_grad_year'dan auto-detect
     *    - 1997 sonrası doğum → 12-jährige (8+4 sistemi)
     *    - öncesi → 11-jährige
     *    Default: 12-jährige
     */
    public function resolveSchulabschlussType(GuestApplication $guest, array $draft): string
    {
        $meta = is_array($guest->application_meta) ? $guest->application_meta : [];
        $key = trim((string) ($meta['schulabschluss_type'] ?? ''));
        if ($key !== '' && isset(self::SCHULABSCHLUSS_OPTIONS[$key])) {
            return self::SCHULABSCHLUSS_OPTIONS[$key];
        }

        // Auto-detect — 1997 sonrası 12-jährige (8+4 sistemi)
        $birthDate = $draft['birth_date'] ?? null;
        if ($birthDate) {
            try {
                $birthYear = (int) \Carbon\Carbon::parse($birthDate)->format('Y');
                if ($birthYear >= 1997) return self::SCHULABSCHLUSS_OPTIONS['lise_12'];
                if ($birthYear < 1997 && $birthYear > 1900) return self::SCHULABSCHLUSS_OPTIONS['lise_11'];
            } catch (\Throwable $e) {}
        }

        // Default: 12-jährige (en yaygın)
        return self::SCHULABSCHLUSS_OPTIONS['lise_12'];
    }

    private function resolveStudienabschluss(array $draft): ?string
    {
        $status = strtolower((string) ($draft['higher_education_status'] ?? ''));
        if (str_contains($status, 'mezun') || str_contains($status, 'graduate') || str_contains($status, 'bitir')) return 'Ja';
        if ($status === '') return null;
        return 'Nein';
    }

    /**
     * Tüm field'ları düzleştir, eksik olanları döndür (mail tetikleme için).
     * @return array<int,array<string,mixed>>
     */
    public function missingFields(GuestApplication $guest): array
    {
        $missing = [];
        foreach ($this->buildAllTabs($guest) as $tab) {
            foreach ($tab['fields'] as $f) {
                if ($f['missing'] && $f['required'] && $f['source'] !== 'static') {
                    $missing[] = $f + ['tab' => $tab['title']];
                }
            }
        }
        return $missing;
    }
}
