<?php

namespace App\Services;

use App\Models\GuestApplication;

/**
 * Auswärtiges Amt VIDEX vize başvuru formunun 7 bölümünü
 * (Vertretung / Person / Kontaktdaten / Ausweispapiere / Reisedaten /
 * Referenz / Lebensunterhalt) bizim öğrenci verisine map'ler.
 *
 * Manager guide sayfasında her field copy edilebilir. Manager-girişi
 * alanlar `application_meta.visa.*` JSON namespace'inde tutulur (Uni-Assist
 * flat keys'iyle çakışmasın diye nested visa.*).
 *
 * Field yapısı (UniAssist mapper ile aynı):
 *  - key / label / label_tr / value / source / hint / required / missing /
 *    copyable / editable / format
 */
class VisaFieldMapperService
{
    /** Geschlecht — VIDEX 4 seçenek (Uni-Assist ile aynı) */
    public const GESCHLECHT_OPTIONS = [
        'männlich'    => 'männlich (Erkek)',
        'weiblich'    => 'weiblich (Kadın)',
        'divers'      => 'divers (Diğer)',
        'unbestimmt'  => 'unbestimmt (Belirtilmemiş)',
    ];

    /** Familienstand — VIDEX 4 seçenek */
    public const FAMILIENSTAND_OPTIONS = [
        'Ledig'       => 'Ledig (Bekâr)',
        'Verheiratet' => 'Verheiratet (Evli)',
        'Geschieden'  => 'Geschieden (Boşanmış)',
        'Verwitwet'   => 'Verwitwet (Dul)',
    ];

    /** Vertretung tipleri */
    public const VERTRETUNG_OPTIONS = [
        'self'      => 'Ich besitze das Konto und stelle diesen Visaantrag für mich.',
        'adult'     => 'Ich besitze das Konto und stelle den Visaantrag für eine erwachsene Person.',
        'minor'     => 'Ich besitze das Konto und stelle den Visaantrag für eine minderjährige Person.',
    ];

    /** Aufenthaltszweck (seyahat amacı) */
    public const ZWECK_OPTIONS = [
        'Studium'                 => 'Studium (Lisans/Master öğrencisi)',
        'Sprachkurs'              => 'Sprachkurs (Almanca dil kursu)',
        'Studienbewerbung'        => 'Studienbewerbung (Henüz kabul yok)',
        'Studienkolleg'           => 'Studienkolleg',
        'Promotion'               => 'Promotion (Doktora)',
        'Erwerbstätigkeit'        => 'Erwerbstätigkeit (İş)',
        'Familienzusammenführung' => 'Familienzusammenführung (Aile birleşimi)',
        'Sonstiges'               => 'Sonstiges (Diğer)',
    ];

    /** Lebensunterhalt (geçim kaynağı) */
    public const LEBENSUNTERHALT_OPTIONS = [
        'Sperrkonto'             => 'Sperrkonto (Bloke hesap — ~12,000 €/yıl)',
        'Stipendium'             => 'Stipendium (Burs)',
        'Verpflichtungserklärung' => 'Verpflichtungserklärung (Mali sorumluluk taahhütnamesi)',
        'Eigenes Vermögen'       => 'Eigenes Vermögen / Erwerbstätigkeit (Kendi varlığı)',
    ];

    /** Unterbringung (konaklama tipi) */
    public const UNTERBRINGUNG_OPTIONS = [
        'Einzelzimmer'      => 'Einzelzimmer (Tek oda)',
        'Wohnung'           => 'Wohnung (Daire)',
        'Sammelunterkunft'  => 'Sammelunterkunft (Toplu konut)',
        'Sonstiges'         => 'Sonstiges (Diğer)',
    ];

    /** Reference type */
    public const REFERENCE_TYPE_OPTIONS = [
        'Bildungseinrichtung' => 'Bildungseinrichtung/Firma/Organisation (Üni / dil okulu / firma)',
        'Privatperson'        => 'Privatperson (Aile birleşiminde — özel kişi)',
    ];

    public function buildAllTabs(GuestApplication $guest): array
    {
        return [
            'vertretung' => [
                'title' => '1. ANGABEN ZUR VERTRETUNG',
                'title_tr' => 'Temsili',
                'fields' => $this->vertretungFields($guest),
            ],
            'person' => [
                'title' => '2. ANGABEN ZUR PERSON',
                'title_tr' => 'Kişisel Bilgiler',
                'fields' => $this->personFields($guest),
            ],
            'kontakt' => [
                'title' => '3. KONTAKTDATEN',
                'title_tr' => 'İletişim Bilgileri',
                'fields' => $this->contactFields($guest),
            ],
            'ausweis' => [
                'title' => '4. AUSWEISPAPIERE',
                'title_tr' => 'Pasaport',
                'fields' => $this->passportFields($guest),
            ],
            'reise' => [
                'title' => '5. REISEDATEN',
                'title_tr' => 'Seyahat Bilgileri',
                'fields' => $this->travelFields($guest),
            ],
            'referenz' => [
                'title' => '6. REFERENZ',
                'title_tr' => 'Almanya\'daki Referans',
                'fields' => $this->referenceFields($guest),
            ],
            'lebensunterhalt' => [
                'title' => '7. LEBENSUNTERHALT',
                'title_tr' => 'Geçim + Konaklama + Erklärung',
                'fields' => $this->lebensunterhaltFields($guest),
            ],
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function vertretungFields(GuestApplication $guest): array
    {
        $vertKey = $this->visaMeta($guest, 'vertretung', 'self') ?? 'self';
        $vertLabel = self::VERTRETUNG_OPTIONS[$vertKey] ?? self::VERTRETUNG_OPTIONS['self'];

        return [
            $this->field('vertretung', 'Stellen Sie den Antrag für sich selbst?', 'Başvuruyu kim için yapıyorsun?', $vertLabel, 'meta.visa.vertretung', true, 'Default: kendi adına. Manager dropdown\'dan değiştirebilir.', editable: true),
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function personFields(GuestApplication $guest): array
    {
        $draft = is_array($guest->registration_form_draft) ? $guest->registration_form_draft : [];

        // Geschlecht: önce visa override (meta.visa.geschlecht), yoksa Uni-Assist override (meta.geschlecht_de),
        // en son guest.gender'dan map
        $managerVisaGender = (string) ($this->visaMeta($guest, 'geschlecht') ?? '');
        $managerUaGender = (string) ($this->flatMeta($guest, 'geschlecht_de') ?? '');
        if ($managerVisaGender !== '' && isset(self::GESCHLECHT_OPTIONS[$managerVisaGender])) {
            $resolvedGender = $managerVisaGender;
        } elseif ($managerUaGender !== '') {
            // Uni-Assist'te Männlich (büyük harf) — VIDEX'te küçük harf "männlich"
            $resolvedGender = strtolower($managerUaGender);
        } else {
            $genderMap = ['m' => 'männlich', 'male' => 'männlich', 'f' => 'weiblich', 'female' => 'weiblich', 'erkek' => 'männlich', 'kadın' => 'weiblich', 'kadin' => 'weiblich'];
            $rawGender = strtolower((string) ($guest->gender ?? ''));
            $resolvedGender = $genderMap[$rawGender] ?? null;
        }

        $birthDate = $draft['birth_date'] ?? null;

        // Hibrit kaynak — önce form draft, yoksa meta.visa fallback
        $birthName        = $this->draftOrVisa($draft, $guest, 'birth_name', 'geburtsname');
        $previousNat      = $this->draftOrVisa($draft, $guest, 'previous_nationality', 'frueher_staatsang');
        $erlerntBeruf     = $this->draftOrVisa($draft, $guest, 'erlernter_beruf', 'beruf_erlernt') ?? 'Student/in, Praktikant/-in';

        // Anne/Baba — form'da ayrı first/last varsa kullan, yoksa full_name'i parçala, en son meta
        $faFirst = $this->splitNameOrMeta($draft, $guest, 'father_first_name', 'father_full_name', 'first', 'vater_vorname');
        $faLast  = $this->splitNameOrMeta($draft, $guest, 'father_last_name',  'father_full_name', 'last',  'vater_nachname');
        $moFirst = $this->splitNameOrMeta($draft, $guest, 'mother_first_name', 'mother_full_name', 'first', 'mutter_vorname');
        $moLast  = $this->splitNameOrMeta($draft, $guest, 'mother_last_name',  'mother_full_name', 'last',  'mutter_nachname');

        $faNat = $this->draftOrVisa($draft, $guest, 'father_nationality', 'vater_staatsang') ?? 'Türkei';
        $moNat = $this->draftOrVisa($draft, $guest, 'mother_nationality', 'mutter_staatsang') ?? 'Türkei';

        $faBirth = isset($draft['father_birth_date']) ? $this->formatDate((string) $draft['father_birth_date']) : $this->visaMeta($guest, 'vater_geburtsdatum');
        $moBirth = isset($draft['mother_birth_date']) ? $this->formatDate((string) $draft['mother_birth_date']) : $this->visaMeta($guest, 'mutter_geburtsdatum');

        $faBirthPlace = $draft['father_birth_place'] ?? $this->visaMeta($guest, 'vater_geburtsort');
        $moBirthPlace = $draft['mother_birth_place'] ?? $this->visaMeta($guest, 'mutter_geburtsort');

        $faAddress = $draft['father_address'] ?? $this->visaMeta($guest, 'vater_wohnort');
        $moAddress = $draft['mother_address'] ?? $this->visaMeta($guest, 'mutter_wohnort');

        return [
            $this->field('familienname', 'Familienname', 'Soyad', $guest->last_name, 'guest.last_name', true),
            $this->field('vorname', 'Vorname(n)', 'Ad(lar)', $guest->first_name, 'guest.first_name', true),
            $this->field('geburtsname', 'Geburtsname / frühere Familienname(n)', 'Doğum soyadı / önceki soyad(lar)', $birthName, 'draft.birth_name|meta.visa.geburtsname', false, 'Evlilik öncesi soyadı varsa yaz, yoksa boş', editable: true),
            $this->field('geburtsdatum', 'Geburtsdatum', 'Doğum Tarihi', $birthDate ? $this->formatDate($birthDate) : null, 'draft.birth_date', true, 'Format: TT.MM.JJJJ'),
            $this->field('geburtsort', 'Geburtsort', 'Doğum Yeri', $draft['birth_place'] ?? null, 'draft.birth_place', true),
            $this->field('geburtsland', 'Geburtsland', 'Doğum Ülkesi', $this->trToDeNationality($draft['birth_country'] ?? null) ?? 'Türkei', 'draft.birth_country|static', true, 'Form\'da birth_country boşsa Türkei varsayılır.'),
            $this->field('geschlecht', 'Geschlecht', 'Cinsiyet', $resolvedGender ? (self::GESCHLECHT_OPTIONS[$resolvedGender] ?? $resolvedGender) : null, 'guest.gender|meta.visa.geschlecht', true, 'VIDEX 4 seçenek: männlich / weiblich / divers / unbestimmt'),
            $this->field('familienstand', 'Familienstand', 'Medeni Durum', $this->maritalToDe($guest), 'guest.marital_status|meta.visa.familienstand', true, 'Manager dropdown\'dan seçer: Ledig / Verheiratet / Geschieden / Verwitwet', editable: true),
            $this->field('staatsangehoerigkeit', 'Derzeitige Staatsangehörigkeit', 'Mevcut Uyruk', $this->trToDeNationality($draft['nationality'] ?? null) ?? 'Türkei', 'draft.nationality|static', true, 'Form\'da nationality boşsa Türkei varsayılır.'),
            $this->field('frueher_staatsang', 'Frühere Staatsangehörigkeit', 'Önceki Uyruk', $this->trToDeNationality($previousNat), 'draft.previous_nationality|meta.visa.frueher_staatsang', false, 'Çift vatandaşlık veya değişiklik varsa', editable: true),
            $this->field('has_children', 'Haben Sie Kinder?', 'Çocuğunuz var mı?', $this->resolveHasChildren($draft, $guest), 'draft.has_children|meta.visa.has_children', false, 'Yetişkin çocuklar dahil. Default: Nein', editable: true),
            $this->field('beruf_erlernt', 'Erlernter Beruf', 'Öğrenim/Meslek Eğitimi', $erlerntBeruf, 'draft.erlernter_beruf|meta.visa.beruf_erlernt', true, 'Default: Student/in. Yetişkin çalışan için meslek yaz.', editable: true),
            $this->field('beruf_aktuell', 'Derzeitige berufliche Tätigkeit', 'Şu Anki Meslek', $this->visaMeta($guest, 'beruf_aktuell'), 'meta.visa.beruf_aktuell', false, 'Erlernter\'dan farklıysa doldur. Öğrenciler genelde boş bırakır.', editable: true),

            // Eltern — form draft öncelikli, sonra meta fallback
            $this->field('vater_nachname', '— Vater: Familienname', 'Babanın Soyadı', $faLast, 'draft.father_last_name|meta.visa.vater_nachname', true, editable: true),
            $this->field('vater_vorname', '— Vater: Vorname(n)', 'Babanın Adı', $faFirst, 'draft.father_first_name|meta.visa.vater_vorname', true, editable: true),
            $this->field('vater_staatsang', '— Vater: Staatsangehörigkeit', 'Babanın Uyruğu', $this->trToDeNationality($faNat), 'draft.father_nationality|meta.visa.vater_staatsang', false, editable: true),
            $this->field('vater_geburtsdatum', '— Vater: Geburtsdatum', 'Babanın Doğum Tarihi', $faBirth, 'draft.father_birth_date|meta.visa.vater_geburtsdatum', false, 'Format: TT.MM.JJJJ', editable: true),
            $this->field('vater_geburtsort', '— Vater: Geburtsort', 'Babanın Doğum Yeri', $faBirthPlace, 'draft.father_birth_place|meta.visa.vater_geburtsort', false, editable: true),
            $this->field('vater_wohnort', '— Vater: Wohnort', 'Babanın İkamet Yeri', $faAddress, 'draft.father_address|meta.visa.vater_wohnort', true, 'Hayattaysa şu anki adresi', editable: true),
            $this->field('mutter_nachname', '— Mutter: Familienname', 'Annenin Soyadı', $moLast, 'draft.mother_last_name|meta.visa.mutter_nachname', true, editable: true),
            $this->field('mutter_vorname', '— Mutter: Vorname(n)', 'Annenin Adı', $moFirst, 'draft.mother_first_name|meta.visa.mutter_vorname', true, editable: true),
            $this->field('mutter_staatsang', '— Mutter: Staatsangehörigkeit', 'Annenin Uyruğu', $this->trToDeNationality($moNat), 'draft.mother_nationality|meta.visa.mutter_staatsang', false, editable: true),
            $this->field('mutter_geburtsdatum', '— Mutter: Geburtsdatum', 'Annenin Doğum Tarihi', $moBirth, 'draft.mother_birth_date|meta.visa.mutter_geburtsdatum', false, 'Format: TT.MM.JJJJ', editable: true),
            $this->field('mutter_geburtsort', '— Mutter: Geburtsort', 'Annenin Doğum Yeri', $moBirthPlace, 'draft.mother_birth_place|meta.visa.mutter_geburtsort', false, editable: true),
            $this->field('mutter_wohnort', '— Mutter: Wohnort', 'Annenin İkamet Yeri', $moAddress, 'draft.mother_address|meta.visa.mutter_wohnort', true, editable: true),
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function contactFields(GuestApplication $guest): array
    {
        $draft = is_array($guest->registration_form_draft) ? $guest->registration_form_draft : [];

        // Ort (şehir/ilçe) için: önce form district+province'ten label üret, yoksa meta.home_city
        $ortFromDraft = $this->resolveOrtFromDraft($draft);

        return [
            $this->field('strasse', 'Straße', 'Sokak', $draft['address_line'] ?? null, 'draft.address_line', true, 'Hausnummer ile birlikte gönderilebilir veya ayrı tutulur'),
            $this->field('hausnummer', 'Hausnummer', 'Bina No', $this->visaMeta($guest, 'hausnummer'), 'meta.visa.hausnummer', true, 'address_line\'dan ayrılmış bina numarası', editable: true),
            $this->field('adress_extra', 'Sonstige Adressangaben', 'Apartman/Site/Blok', $this->flatMeta($guest, 'address_extra'), 'meta.address_extra', false, 'örn: 13. BLOK, Mavidogankaya Sitesi'),
            $this->field('plz', 'Postleitzahl', 'Posta Kodu', $draft['postal_code'] ?? null, 'draft.postal_code', true),
            $this->field('ort', 'Ort', 'Şehir/İlçe', $ortFromDraft ?? $this->flatMeta($guest, 'home_city'), 'draft.district+province|meta.home_city', true, 'Form\'da il+ilçe seçildiyse "İlçe / İl" formatında otomatik üretilir.'),
            $this->field('land_kontakt', 'Land', 'Ülke', 'Türkei', 'static', true),
            $this->field('telefon', 'Telefon-/Mobilfunknummer', 'Telefon', $this->formatPhone($guest->phone), 'guest.phone', true, 'Format: 0090 XXX XXX XX XX (uluslararası)'),
            $this->field('email', 'E-Mail', 'E-posta', $guest->email, 'guest.email', true),
            $this->field('wohnsitz_anderes_land', 'Wohnsitz in einem anderen Staat?', 'Başka ülkede ikamet?', $this->visaMeta($guest, 'wohnsitz_anderes_land', 'Nein'), 'meta.visa.wohnsitz_anderes_land', false, 'Genelde Nein', editable: true),
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function passportFields(GuestApplication $guest): array
    {
        $draft = is_array($guest->registration_form_draft) ? $guest->registration_form_draft : [];
        $passNumber = (string) ($draft['passport_number'] ?? '');

        // Pasaport tipi: "S" ile başlıyorsa Sondernpass, yoksa Reisepass
        $passType = null;
        if ($passNumber !== '') {
            $passType = strtoupper(substr($passNumber, 0, 1)) === 'S' ? 'Sondernpass' : 'Reisepass';
        }

        return [
            $this->field('reisedoc_art', 'Art des Reisedokuments', 'Pasaport Tipi', $passType, 'derived', true, '"S" ile başlayan numara → Sondernpass, diğerleri → Reisepass'),
            $this->field('reisedoc_nummer', 'Nummer des Reisedokuments', 'Pasaport Numarası', $passNumber !== '' ? $passNumber : null, 'draft.passport_number', true),
            $this->field('reisedoc_ausstellung', 'Ausstellungsdatum', 'Veriliş Tarihi', isset($draft['passport_issue_date']) ? $this->formatDate((string) $draft['passport_issue_date']) : null, 'draft.passport_issue_date', true, 'Format: TT.MM.JJJJ'),
            $this->field('reisedoc_aussteller_staat', 'Ausstellender Staat', 'Veren Ülke', 'Türkei', 'static', true),
            $this->field('reisedoc_gueltig_bis', 'Gültig bis', 'Geçerlilik Bitiş', isset($draft['passport_expiry_date']) ? $this->formatDate((string) $draft['passport_expiry_date']) : null, 'draft.passport_expiry_date', true, 'Vize bitişinden +3 ay sonrasına kadar geçerli olmalı'),
            $this->field('reisedoc_ausgestellt_von', 'Ausgestellt von', 'Veren Kurum', $this->draftOrVisa($draft, $guest, 'passport_authority', 'reisedoc_ausgestellt_von'), 'draft.passport_authority|meta.visa.reisedoc_ausgestellt_von', false, 'örn: ATASEHIR (kaymakamlık ilçesi)', editable: true),
            $this->field('reisedoc_ausgestellt_in', 'Ausgestellt in', 'Veriliş Şehri', $draft['passport_issue_place'] ?? null, 'draft.passport_issue_place', false, 'örn: ISTANBUL'),
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function travelFields(GuestApplication $guest): array
    {
        $draft = is_array($guest->registration_form_draft) ? $guest->registration_form_draft : [];

        // Zweck — manager seçer; application_type'tan default türet
        $zweck = $this->visaMeta($guest, 'travel_zweck') ?? $this->derivedZweckFromApplicationType($draft);
        $zweckLabel = $zweck && isset(self::ZWECK_OPTIONS[$zweck]) ? self::ZWECK_OPTIONS[$zweck] : $zweck;

        // Tarihler — form draft öncelikli, sonra visa meta
        $vonRaw = $draft['university_start_target_date'] ?? $this->visaMeta($guest, 'travel_von');
        $bisRaw = $draft['travel_planned_end_date']      ?? $this->visaMeta($guest, 'travel_bis');

        return [
            $this->field('zweck', 'Zweck des Aufenthalts in Deutschland', 'Almanya\'da Kalış Amacı', $zweckLabel, 'guest.application_type|meta.visa.travel_zweck', true, 'application_type\'tan otomatik türetilir; manager düzeltebilir', editable: true),
            $this->field('erwerb', 'Beabsichtigte Erwerbstätigkeit', 'Yan İş Niyeti', $this->visaMeta($guest, 'travel_erwerb'), 'meta.visa.travel_erwerb', false, 'Yan iş yapacaksa açıklama yaz, yoksa boş', editable: true),
            $this->field('reise_von', 'Beabsichtigte Dauer — Von', 'Planlanan Başlangıç', $vonRaw ? $this->formatDate((string) $vonRaw) : null, 'draft.university_start_target_date|meta.visa.travel_von', true, 'Format: TT.MM.JJJJ', editable: true),
            $this->field('reise_bis', 'Beabsichtigte Dauer — Bis', 'Planlanan Bitiş', $bisRaw ? $this->formatDate((string) $bisRaw) : null, 'draft.travel_planned_end_date|meta.visa.travel_bis', true, 'Format: TT.MM.JJJJ', editable: true),
            $this->field('reise_ueber12', 'Beabsichtigen Sie länger als 12 Monate?', '12 ay\'dan uzun mu?', $this->visaMeta($guest, 'travel_ueber12', 'Ja'), 'meta.visa.travel_ueber12', false, 'Öğrenci için genelde Ja', editable: true),
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function referenceFields(GuestApplication $guest): array
    {
        $refType = $this->visaMeta($guest, 'reference_type', 'Bildungseinrichtung');
        $refTypeLabel = $refType && isset(self::REFERENCE_TYPE_OPTIONS[$refType]) ? self::REFERENCE_TYPE_OPTIONS[$refType] : $refType;

        // Form'da hedef program seçildiyse (Expatrio veya HK), üniversite bilgileri otomatik gelsin
        $draft = is_array($guest->registration_form_draft) ? $guest->registration_form_draft : [];
        $expatrioProgram = $this->resolveExpatrioProgram($draft['target_program_id'] ?? null, $draft);
        $autoRefName = $expatrioProgram['universityName'] ?? null;
        $autoRefCity = $expatrioProgram['location']       ?? null;

        return [
            $this->field('ref_art', 'Art der Referenz', 'Referans Türü', $refTypeLabel, 'meta.visa.reference_type', true, 'Öğrenci için: Bildungseinrichtung. Aile birleşimi: Privatperson', editable: true),
            $this->field('ref_name', 'Name', 'Kurum/Kişi Adı', $this->visaMeta($guest, 'reference_name') ?? $autoRefName, 'meta.visa.reference_name|expatrio.target_program', true, 'Form\'da seçilen Expatrio programının üniversitesi otomatik gelir; manager override edebilir.', editable: true),
            $this->field('ref_ort', 'Sitz: Ort', 'Şehir', $this->visaMeta($guest, 'reference_city') ?? $autoRefCity, 'meta.visa.reference_city|expatrio.target_program', true, 'Form\'da seçilen Expatrio programının lokasyonu otomatik gelir.', editable: true),
            $this->field('ref_land', 'Sitz: Land', 'Ülke', 'Deutschland', 'static', true),
            $this->field('ref_aufgabe', 'Aufgabenstellung / Wirkungsbereich', 'Faaliyet Alanı', $this->visaMeta($guest, 'reference_aufgabe'), 'meta.visa.reference_aufgabe', false, 'örn: Sprachschule / Universität', editable: true),
            $this->field('ref_register_name', 'Name des Registers', 'Sicil Adı', $this->visaMeta($guest, 'reference_register_name'), 'meta.visa.reference_register_name', false, 'örn: Handelsregister', editable: true),
            $this->field('ref_register_ort', 'Register-Ort', 'Sicil Yeri', $this->visaMeta($guest, 'reference_register_ort'), 'meta.visa.reference_register_ort', false, editable: true),
            $this->field('ref_register_nr', 'Registernummer', 'Sicil Numarası', $this->visaMeta($guest, 'reference_register_nr'), 'meta.visa.reference_register_nr', false, editable: true),

            // Ansprechperson — referans kurum yetkilisi
            $this->field('ref_kontakt_nachname', '— Ansprechperson: Familienname', 'Yetkili Soyadı', $this->visaMeta($guest, 'reference_contact_lastname'), 'meta.visa.reference_contact_lastname', true, editable: true),
            $this->field('ref_kontakt_vorname', '— Ansprechperson: Vorname(n)', 'Yetkili Adı', $this->visaMeta($guest, 'reference_contact_firstname'), 'meta.visa.reference_contact_firstname', true, editable: true),
            $this->field('ref_kontakt_strasse', '— Ansprechperson: Adresse', 'Yetkili Adresi', $this->visaMeta($guest, 'reference_contact_address'), 'meta.visa.reference_contact_address', false, 'Tam sokak/ev no/PLZ/Ort', editable: true),
            $this->field('ref_kontakt_telefon', '— Ansprechperson: Telefon', 'Yetkili Telefon', $this->visaMeta($guest, 'reference_contact_phone'), 'meta.visa.reference_contact_phone', false, editable: true),
            $this->field('ref_kontakt_email', '— Ansprechperson: E-Mail', 'Yetkili E-posta', $this->visaMeta($guest, 'reference_contact_email'), 'meta.visa.reference_contact_email', false, editable: true),
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function lebensunterhaltFields(GuestApplication $guest): array
    {
        $luType = $this->visaMeta($guest, 'lebensunterhalt_type', 'Sperrkonto');
        $luLabel = $luType && isset(self::LEBENSUNTERHALT_OPTIONS[$luType]) ? self::LEBENSUNTERHALT_OPTIONS[$luType] : $luType;

        $unterbringung = $this->visaMeta($guest, 'unterbringung', 'Einzelzimmer');
        $unterbringungLabel = $unterbringung && isset(self::UNTERBRINGUNG_OPTIONS[$unterbringung]) ? self::UNTERBRINGUNG_OPTIONS[$unterbringung] : $unterbringung;

        return [
            $this->field('lu_type', 'Mittel des Lebensunterhalts', 'Geçim Kaynağı', $luLabel, 'meta.visa.lebensunterhalt_type', true, 'Default: Sperrkonto (en yaygın)', editable: true),
            $this->field('lu_verpflicht', 'Förmliche Verpflichtungserklärung?', 'Verpflichtungserklärung Var Mı?', $this->visaMeta($guest, 'lebensunterhalt_verpflicht', 'Nein'), 'meta.visa.lebensunterhalt_verpflicht', false, 'Sperrkonto kullanıyorsa Nein', editable: true),

            // Aufenthaltsort in Deutschland (Almanya'daki adres)
            $this->field('aufenthalt_strasse', 'Aufenthaltsort: Straße + Nr.', 'Almanya Adresi (Sokak)', $this->visaMeta($guest, 'aufenthalt_strasse'), 'meta.visa.aufenthalt_strasse', false, 'Konaklama yeri biliniyorsa', editable: true),
            $this->field('aufenthalt_plz_ort', 'Aufenthaltsort: PLZ + Ort', 'Almanya Adresi (PLZ + Şehir)', $this->visaMeta($guest, 'aufenthalt_plz_ort'), 'meta.visa.aufenthalt_plz_ort', true, 'örn: 60435 FRANKFURT A.M', editable: true),
            $this->field('unterbringung', 'Wie werden Sie untergebracht?', 'Konaklama Tipi', $unterbringungLabel, 'meta.visa.unterbringung', true, 'Default: Einzelzimmer', editable: true),
            $this->field('unterbringung_note', 'Anmerkung zur Unterbringung', 'Konaklama Notu', $this->visaMeta($guest, 'unterbringung_note'), 'meta.visa.unterbringung_note', false, 'örn: WOHNUNG GEHÖRT MEINER TANTE', editable: true),
            $this->field('staendig_ausland', 'Wird ständiger Wohnort außerhalb Deutschlands beibehalten?', 'TR İkametgah Devam Ediyor mu?', $this->visaMeta($guest, 'staendig_ausland', 'Ja'), 'meta.visa.staendig_ausland', false, 'Genelde Ja (Türkiye\'deki ev hâlâ var)', editable: true),
            $this->field('familie_einreise', 'Sollen Familienangehörige mit einreisen?', 'Aile de Geliyor mu?', $this->visaMeta($guest, 'familie_einreise', 'Nein'), 'meta.visa.familie_einreise', false, 'Öğrenci tek başına gelirse Nein', editable: true),
            $this->field('krankenversicherung', 'Haben Sie eine Krankenversicherung?', 'Sağlık Sigortası Var Mı?', $this->visaMeta($guest, 'krankenversicherung', 'Ja'), 'meta.visa.krankenversicherung', true, 'Mawista/Care Concept ile vize başvurusunda Ja', editable: true),
            $this->field('fruehere_aufenthalte', 'Frühere Aufenthalte in Deutschland?', 'Daha Önce Almanya\'da Bulundun mu?', $this->visaMeta($guest, 'fruehere_aufenthalte', 'Nein'), 'meta.visa.fruehere_aufenthalte', false, 'Önceden gittiyse Ja, yoksa Nein', editable: true),

            // Erklärung — 3 zorunlu Nein/Ja
            $this->field('vorbestraft', 'Sind Sie vorbestraft?', 'Adli Sicil Var Mı?', $this->visaMeta($guest, 'erkl_vorbestraft', 'Nein'), 'meta.visa.erkl_vorbestraft', true, 'Genelde Nein. Adli sicil varsa öğrenciye danış', editable: true),
            $this->field('abgeschoben', 'Aus Deutschland ausgewiesen / Antrag abgelehnt?', 'Daha Önce Sınır Dışı / Ret?', $this->visaMeta($guest, 'erkl_abgeschoben', 'Nein'), 'meta.visa.erkl_abgeschoben', true, 'Genelde Nein', editable: true),
            $this->field('krankheiten', 'Krankheiten (Pocken/Polio/Cholera/SARS vb.)?', 'Bulaşıcı Hastalık Var Mı?', $this->visaMeta($guest, 'erkl_krankheiten', 'Nein'), 'meta.visa.erkl_krankheiten', true, 'Genelde Nein', editable: true),
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

    /** application_meta.visa.* nested namespace */
    private function visaMeta(GuestApplication $guest, string $key, ?string $default = null): ?string
    {
        $meta = is_array($guest->application_meta) ? $guest->application_meta : [];
        $visa = is_array($meta['visa'] ?? null) ? $meta['visa'] : [];
        $v = $visa[$key] ?? null;
        if ($v === null || $v === '') return $default;
        return (string) $v;
    }

    /** Form draft'tan oku, yoksa visa meta'dan fallback. */
    private function draftOrVisa(array $draft, GuestApplication $guest, string $draftKey, string $metaKey): ?string
    {
        $v = $draft[$draftKey] ?? null;
        if ($v !== null && trim((string) $v) !== '') return (string) $v;
        return $this->visaMeta($guest, $metaKey);
    }

    /**
     * Anne/baba ad veya soyad: önce ayrı field (father_first_name) → sonra full_name'i parçala → en son meta.
     * @param 'first'|'last' $part
     */
    private function splitNameOrMeta(array $draft, GuestApplication $guest, string $separateKey, string $fullKey, string $part, string $metaKey): ?string
    {
        $sep = $draft[$separateKey] ?? null;
        if ($sep !== null && trim((string) $sep) !== '') return (string) $sep;

        $full = trim((string) ($draft[$fullKey] ?? ''));
        if ($full !== '') {
            $tokens = preg_split('/\s+/', $full) ?: [];
            if (count($tokens) >= 2) {
                if ($part === 'first') return implode(' ', array_slice($tokens, 0, count($tokens) - 1));
                return $tokens[count($tokens) - 1];
            }
            // Tek kelime — first'a koy
            if ($part === 'first') return $full;
            return null;
        }
        return $this->visaMeta($guest, $metaKey);
    }

    /** TR marital_status → VIDEX Familienstand */
    private function maritalToDe(GuestApplication $guest): ?string
    {
        // Önce visa meta override
        $override = $this->visaMeta($guest, 'familienstand');
        if ($override) return $override;

        $draft = is_array($guest->registration_form_draft) ? $guest->registration_form_draft : [];
        $ms = strtolower((string) ($draft['marital_status'] ?? ''));
        return match ($ms) {
            'single'    => 'Ledig',
            'married'   => 'Verheiratet',
            'divorced'  => 'Geschieden',
            'widowed'   => 'Verwitwet',
            default     => null,
        };
    }

    /** Çocuk var mı? — eş bilgileri formundaki has_children + visa meta override */
    private function resolveHasChildren(array $draft, GuestApplication $guest): string
    {
        $override = $this->visaMeta($guest, 'has_children');
        if ($override) return $override;

        $hc = strtolower((string) ($draft['has_children'] ?? ''));
        if ($hc === 'yes') return 'Ja';
        if ($hc === 'no')  return 'Nein';
        return 'Nein';
    }

    /**
     * Uyruk değerini VIDEX'in beklediği Almanca label'a çevirir.
     *
     * Form artık ISO kodu (TR/DE/SY) saklıyor — CountryCatalog runtime
     * Almanca'ya çevirir ('TR' → 'Türkei'). Eski metin değerleri için
     * (Türk, Türkiye vb.) fallback aktif.
     */
    private function trToDeNationality(?string $value): ?string
    {
        if ($value === null || trim($value) === '') return null;
        return \App\Support\CountryCatalog::toGermanLabel($value);
    }

    /**
     * Hedef program ID'den (form'da seçilen) üniversite bilgisini çek.
     * ProgramCatalogRegistry üzerinden source-aware (Expatrio veya HK).
     *
     * @param  array  $draft  registration_form_draft
     * @return array{universityName?:string, location?:string, courseName?:string}|null
     */
    private function resolveExpatrioProgram(?string $programId, array $draft = []): ?array
    {
        if ($programId === null || trim($programId) === '') return null;

        // Source default: 'expatrio'. Form'da target_program_source set edilmişse onu kullan.
        $source = trim((string) ($draft['target_program_source'] ?? '')) ?: 'expatrio';

        try {
            $registry = app(\App\Services\ProgramCatalog\ProgramCatalogRegistry::class);
            $program = $registry->find($source, $programId);
            if (! $program) return null;

            return [
                'universityName' => $program['university_name'] ?? null,
                'location'       => $program['location'] ?? null,
                'courseName'     => $program['course_name'] ?? null,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Form draft'tan il+ilçe slug'larını "İlçe / İl" formatında label'a çevirir.
     * province/district artık select dropdown — slug saklanır (istanbul, atasehir).
     * VIDEX Ort alanı için human-readable label üretilir.
     */
    private function resolveOrtFromDraft(array $draft): ?string
    {
        $provinceSlug = trim((string) ($draft['province'] ?? ''));
        $districtSlug = trim((string) ($draft['district'] ?? ''));
        if ($provinceSlug === '' && $districtSlug === '') return null;

        try {
            $province = $provinceSlug !== ''
                ? \App\Models\TrProvince::where('slug', $provinceSlug)->first()
                : null;
            $district = $districtSlug !== ''
                ? \App\Models\TrDistrict::where('slug', $districtSlug)->first()
                : null;

            $provinceName = $province->name ?? null;
            $districtName = $district->name ?? null;

            if ($districtName && $provinceName) return mb_strtoupper("{$districtName} / {$provinceName}");
            if ($provinceName) return mb_strtoupper($provinceName);
            if ($districtName) return mb_strtoupper($districtName);
        } catch (\Throwable $e) {
            // DB hatası → null döner, mapper meta fallback'e gider
        }
        return null;
    }

    /** application_type → VIDEX Zweck dropdown key */
    private function derivedZweckFromApplicationType(array $draft): ?string
    {
        $type = strtolower((string) ($draft['application_type'] ?? ''));
        return match ($type) {
            'bachelor', 'master'      => 'Studium',
            'language_course'         => 'Sprachkurs',
            'ausbildung'              => 'Erwerbstätigkeit',
            'residence'               => 'Familienzusammenführung',
            default                   => null,
        };
    }

    /** application_meta flat (Uni-Assist ile shared key'ler için) */
    private function flatMeta(GuestApplication $guest, string $key, ?string $default = null): ?string
    {
        $meta = is_array($guest->application_meta) ? $guest->application_meta : [];
        $v = $meta[$key] ?? null;
        if ($v === null || $v === '') return $default;
        return (string) $v;
    }

    private function formatDate(string $raw): string
    {
        try {
            return \Carbon\Carbon::parse($raw)->format('d.m.Y');
        } catch (\Throwable $e) {
            return $raw;
        }
    }

    /** TR telefon → "0090 XXX XXX XX XX" formatına benzeştir (giriş yine de free-text) */
    private function formatPhone(?string $raw): ?string
    {
        if ($raw === null) return null;
        $digits = preg_replace('/\D+/', '', $raw);
        if ($digits === null || $digits === '') return $raw;
        // 90 ile başlamıyorsa ekle
        if (! str_starts_with($digits, '90') && strlen($digits) <= 10) {
            $digits = '90' . ltrim($digits, '0');
        }
        return '00' . $digits;
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
