<?php

namespace App\Services\StudyBuddy;

/**
 * Discovery Wizard adım tanımları (Faz 2).
 *
 * 20 adım — Expatrio kopyası DEĞİL, TR-spesifik öne çıkarılmış:
 *  - Türk lise türü (8 detay)
 *  - APS sertifikası (Türk lisans öğrenci zorunlu)
 *  - Vize hazırlık aşaması
 *  - MentorDE help_areas (hangi servislerde yardım?)
 *
 * Her adım: key, type, title, subtitle, options, validation
 * Çoklu-seçim: type='checkbox_group' + max param
 */
class WizardSchema
{
    public function totalSteps(): int
    {
        return count($this->steps());
    }

    public function stepAt(int $n): ?array
    {
        $steps = $this->steps();
        return $steps[$n - 1] ?? null;
    }

    /** @return array<int, array<string, mixed>> */
    public function steps(): array
    {
        return [
            // ── BÖLÜM 1: HEDEF ─────────────────────────────────────
            [
                'key'      => 'target_degree',
                'type'     => 'cards',
                'title'    => 'Almanya\'da hangi dereceyi hedefliyorsun?',
                'subtitle' => 'Sana uygun program tipini bulalım.',
                'options'  => [
                    ['value' => 'bachelor',      'label' => 'Bachelor (Lisans)',      'icon' => '🎓', 'desc' => '3-4 yıllık lisans programı'],
                    ['value' => 'master',        'label' => 'Master (Yüksek Lisans)', 'icon' => '📚', 'desc' => '1-2 yıllık yüksek lisans'],
                    ['value' => 'phd',           'label' => 'Doktora',                'icon' => '🔬', 'desc' => 'Akademik araştırma'],
                    ['value' => 'studienkolleg', 'label' => 'Studienkolleg',          'icon' => '📖', 'desc' => 'Lisans hazırlık (1 yıl)'],
                ],
                'validation' => ['required', 'in:bachelor,master,phd,studienkolleg'],
            ],

            [
                'key'      => 'target_field',
                'type'     => 'cards',
                'title'    => 'Hangi alanda okumak istersin?',
                'subtitle' => 'Programları senin alanına göre filtreleriz.',
                'options'  => [
                    ['value' => 'Computer Science',  'label' => 'Bilgisayar / Yazılım',  'icon' => '💻'],
                    ['value' => 'Engineering',       'label' => 'Mühendislik',           'icon' => '⚙️'],
                    ['value' => 'Business',          'label' => 'İşletme / Ekonomi',     'icon' => '📈'],
                    ['value' => 'Natural Sciences',  'label' => 'Doğa Bilimleri',        'icon' => '🧪'],
                    ['value' => 'Medicine',          'label' => 'Tıp / Sağlık',          'icon' => '⚕️'],
                    ['value' => 'Social Sciences',   'label' => 'Sosyal Bilimler',       'icon' => '👥'],
                    ['value' => 'Arts',              'label' => 'Sanat / Tasarım',       'icon' => '🎨'],
                    ['value' => 'Law',               'label' => 'Hukuk',                 'icon' => '⚖️'],
                    ['value' => 'other',             'label' => 'Diğer',                 'icon' => '✨'],
                ],
                'validation' => ['required', 'string', 'max:120'],
            ],

            [
                'key'      => 'study_language',
                'type'     => 'cards',
                'title'    => 'Hangi dilde okumak istersin?',
                'subtitle' => 'Almanca daha çok seçenek sunar; İngilizce ile master programları yaygın.',
                'options'  => [
                    ['value' => 'de',       'label' => 'Almanca',  'icon' => '🇩🇪', 'desc' => 'En geniş program seçeneği'],
                    ['value' => 'en',       'label' => 'İngilizce', 'icon' => '🇬🇧', 'desc' => 'Master programlarında yaygın'],
                    ['value' => 'flexible', 'label' => 'Esnek',     'icon' => '🌍', 'desc' => 'Her ikisi de olur'],
                ],
                'validation' => ['required', 'in:de,en,flexible'],
            ],

            // ── BÖLÜM 2: MOTİVASYON & ZAMAN ────────────────────────
            [
                'key'      => 'germany_motivation',
                'type'     => 'cards',
                'title'    => 'Almanya\'yı seçme nedenin?',
                'subtitle' => 'Önemli motivasyonun program önerilerini şekillendirir.',
                'options'  => [
                    ['value' => 'free_tuition',     'label' => 'Ücretsiz devlet üniversiteleri',  'icon' => '💸'],
                    ['value' => 'quality',          'label' => 'Yüksek akademik kalite',           'icon' => '🏆'],
                    ['value' => 'career',           'label' => 'Kariyer / iş fırsatları',          'icon' => '💼'],
                    ['value' => 'language_culture', 'label' => 'Almanca + Avrupa kültürü',         'icon' => '🌍'],
                    ['value' => 'family',           'label' => 'Almanya\'da ailem/akrabam var',   'icon' => '👨‍👩‍👧'],
                    ['value' => 'research',         'label' => 'Araştırma + bilimsel ortam',       'icon' => '🔬'],
                ],
                'validation' => ['required', 'in:free_tuition,quality,career,language_culture,family,research'],
            ],

            [
                'key'      => 'start_term',
                'type'     => 'cards',
                'title'    => 'Ne zaman başlamak istersin?',
                'subtitle' => 'Başvuru deadline\'larıyla eşleştirelim.',
                'options'  => [
                    ['value' => 'winter_2026', 'label' => 'Kış 2026 (Ekim)',  'icon' => '❄️', 'desc' => 'Son başvuru: 15 Tem 2026'],
                    ['value' => 'summer_2027', 'label' => 'Yaz 2027 (Nisan)', 'icon' => '☀️', 'desc' => 'Son başvuru: 15 Oca 2027'],
                    ['value' => 'winter_2027', 'label' => 'Kış 2027 (Ekim)',  'icon' => '❄️', 'desc' => 'Son başvuru: 15 Tem 2027'],
                    ['value' => 'summer_2028', 'label' => 'Yaz 2028 (Nisan)', 'icon' => '☀️', 'desc' => 'Son başvuru: 15 Oca 2028'],
                    ['value' => 'flexible',    'label' => 'Henüz belli değil', 'icon' => '🤔', 'desc' => 'Karar vereceğim'],
                ],
                'validation' => ['required', 'in:winter_2026,summer_2027,winter_2027,summer_2028,flexible'],
            ],

            // ── BÖLÜM 3: AKADEMİK PROFİL ───────────────────────────
            [
                'key'      => 'current_education_level',
                'type'     => 'cards',
                'title'    => 'Şu an hangi seviyedesin?',
                'subtitle' => 'Mevcut akademik durumun.',
                'options'  => [
                    ['value' => 'high_school_student',  'label' => 'Lise öğrencisiyim',          'icon' => '📚'],
                    ['value' => 'high_school_graduate', 'label' => 'Lise mezunuyum',             'icon' => '🎓'],
                    ['value' => 'bachelor_student',     'label' => 'Üniversitede okuyorum',      'icon' => '📖'],
                    ['value' => 'bachelor_graduate',    'label' => 'Lisans mezunuyum',           'icon' => '🎓'],
                    ['value' => 'master_student',       'label' => 'Yüksek lisans öğrencisiyim', 'icon' => '🔬'],
                    ['value' => 'master_graduate',      'label' => 'Master mezunuyum',           'icon' => '🏆'],
                ],
                'validation' => ['required', 'in:high_school_student,high_school_graduate,bachelor_student,bachelor_graduate,master_student,master_graduate'],
            ],

            [
                'key'      => 'high_school_type',
                'type'     => 'cards',
                'title'    => 'Hangi tip liseden mezun oldun (veya mezun olacaksın)?',
                'subtitle' => 'Almanya\'daki denklik durumun bu tipe göre belirlenir.',
                'options'  => [
                    ['value' => 'lise_12',         'label' => '12 yıllık genel lise (1997 sonrası)', 'icon' => '🎓', 'desc' => 'En yaygın'],
                    ['value' => 'lise_11',         'label' => '11 yıllık lise (1997 öncesi)',        'icon' => '📜'],
                    ['value' => 'anadolu_fen',     'label' => 'Anadolu / Fen / Sosyal Bilimler',     'icon' => '🔬'],
                    ['value' => 'imam_hatip',      'label' => 'Anadolu İmam Hatip Lisesi',           'icon' => '🕌'],
                    ['value' => 'meslek_teknik',   'label' => 'Anadolu Mesleki ve Teknik (MTAL)',    'icon' => '⚙️'],
                    ['value' => 'meslek',          'label' => 'Meslek Lisesi',                       'icon' => '🛠'],
                    ['value' => 'acik_lise',       'label' => 'Açık Öğretim Lisesi',                 'icon' => '📡', 'desc' => 'Studienkolleg gerekebilir'],
                    ['value' => 'lise_onlisans',   'label' => 'Lise + Ön Lisans',                    'icon' => '🎓'],
                ],
                'validation' => ['required', 'in:lise_12,lise_11,anadolu_fen,imam_hatip,meslek_teknik,meslek,acik_lise,lise_onlisans'],
            ],

            [
                'key'      => 'gpa_range',
                'type'     => 'cards',
                'title'    => 'Lise / lisans diploma notun?',
                'subtitle' => '100 üzerinden — burs ve kabul şansı için kritik.',
                'options'  => [
                    ['value' => 'excellent', 'label' => '90+',     'icon' => '🌟', 'desc' => 'Mükemmel — burs olasılığı yüksek'],
                    ['value' => 'very_good', 'label' => '80-90',   'icon' => '⭐', 'desc' => 'Çok iyi — geniş seçenek'],
                    ['value' => 'good',      'label' => '70-80',   'icon' => '✅', 'desc' => 'İyi'],
                    ['value' => 'medium',    'label' => '60-70',   'icon' => '👌', 'desc' => 'Orta'],
                    ['value' => 'low',       'label' => '50-60',   'icon' => '⚠️', 'desc' => 'Sınırlı seçenek'],
                    ['value' => 'unsure',    'label' => 'Henüz bilmiyorum', 'icon' => '🤔'],
                ],
                'validation' => ['required', 'in:excellent,very_good,good,medium,low,unsure'],
            ],

            // ── BÖLÜM 4: DİL ──────────────────────────────────────
            [
                'key'      => 'german_level',
                'type'     => 'cards',
                'title'    => 'Almanca seviyen ne?',
                'subtitle' => 'B2 ve üstü çoğu Almanca programa giriş için gereklidir.',
                'options'  => [
                    ['value' => 'none',  'label' => 'Yok',     'icon' => '○',  'desc' => 'Hiç bilmiyorum'],
                    ['value' => 'a1',    'label' => 'A1',      'icon' => '🌱', 'desc' => 'Başlangıç'],
                    ['value' => 'a2',    'label' => 'A2',      'icon' => '🌿', 'desc' => 'Temel'],
                    ['value' => 'b1',    'label' => 'B1',      'icon' => '🌳', 'desc' => 'Orta'],
                    ['value' => 'b2',    'label' => 'B2',      'icon' => '⭐', 'desc' => 'Üst-orta'],
                    ['value' => 'c1',    'label' => 'C1',      'icon' => '⚡', 'desc' => 'İleri (Lisans için yeterli)'],
                    ['value' => 'c2',    'label' => 'C2',      'icon' => '🔥', 'desc' => 'İleri-yüksek'],
                    ['value' => 'native','label' => 'Anadil',  'icon' => '🏆'],
                ],
                'validation' => ['required', 'in:none,a1,a2,b1,b2,c1,c2,native'],
            ],

            [
                'key'      => 'english_level',
                'type'     => 'cards',
                'title'    => 'İngilizce seviyen ne?',
                'subtitle' => 'İngilizce programlar için B2/C1 genelde yeterlidir.',
                'options'  => [
                    ['value' => 'none',  'label' => 'Yok',     'icon' => '○'],
                    ['value' => 'a1',    'label' => 'A1',      'icon' => '🌱'],
                    ['value' => 'a2',    'label' => 'A2',      'icon' => '🌿'],
                    ['value' => 'b1',    'label' => 'B1',      'icon' => '🌳'],
                    ['value' => 'b2',    'label' => 'B2',      'icon' => '⭐'],
                    ['value' => 'c1',    'label' => 'C1',      'icon' => '⚡'],
                    ['value' => 'c2',    'label' => 'C2',      'icon' => '🔥'],
                    ['value' => 'native','label' => 'Anadil',  'icon' => '🏆'],
                ],
                'validation' => ['required', 'in:none,a1,a2,b1,b2,c1,c2,native'],
            ],

            [
                'key'      => 'language_certificate',
                'type'     => 'cards',
                'title'    => 'Resmi dil sertifikan var mı?',
                'subtitle' => 'TestDaF / DSH / IELTS / TOEFL — çoğu üni başvurusunda zorunlu.',
                'options'  => [
                    ['value' => 'have_de_high', 'label' => 'TestDaF / DSH var (B2+)',   'icon' => '🇩🇪', 'desc' => 'Almanca için yeterli'],
                    ['value' => 'have_en_high', 'label' => 'IELTS / TOEFL var (B2+)',   'icon' => '🇬🇧', 'desc' => 'İngilizce için yeterli'],
                    ['value' => 'have_both',    'label' => 'Hem Almanca hem İngilizce', 'icon' => '🌍', 'desc' => 'Çift sertifika'],
                    ['value' => 'preparing',    'label' => 'Hazırlanıyorum',            'icon' => '📚', 'desc' => 'Henüz almadım'],
                    ['value' => 'no',           'label' => 'Yok / başlamadım',          'icon' => '○'],
                ],
                'validation' => ['required', 'in:have_de_high,have_en_high,have_both,preparing,no'],
            ],

            // ── BÖLÜM 5: MALİ ─────────────────────────────────────
            [
                'key'      => 'finance_method',
                'type'     => 'cards',
                'title'    => 'Yaşam masraflarını nasıl karşılayacaksın?',
                'subtitle' => 'Bu, ücret düzeyi ve burs filtreleri için kritik.',
                'options'  => [
                    ['value' => 'blocked_account', 'label' => 'Sperrkonto', 'icon' => '🏦', 'desc' => 'Bloke hesap (~12,000 €/yıl)'],
                    ['value' => 'sponsor',         'label' => 'Garantör (aile/akraba)', 'icon' => '🤝'],
                    ['value' => 'self_funded',     'label' => 'Kendim',     'icon' => '💰', 'desc' => 'Kendi birikimimle'],
                    ['value' => 'scholarship',     'label' => 'Burs',       'icon' => '🎯', 'desc' => 'Burs alacağım/aldım'],
                    ['value' => 'undecided',       'label' => 'Henüz belli değil', 'icon' => '🤔'],
                ],
                'validation' => ['required', 'in:blocked_account,sponsor,self_funded,scholarship,undecided'],
            ],

            [
                'key'      => 'monthly_budget',
                'type'     => 'cards',
                'title'    => 'Aylık bütçe planın?',
                'subtitle' => 'Almanya\'da ortalama yaşam gideri ~992 € (Bafög seviyesi). Sperrkonto bu rakamı temel alır.',
                'options'  => [
                    ['value' => 'tight',     'label' => '800 € altı',    'icon' => '⚠️', 'desc' => 'Sınırlı — küçük şehir tercih'],
                    ['value' => 'standard',  'label' => '800-1100 €',    'icon' => '✅', 'desc' => 'Bafög standart'],
                    ['value' => 'comfort',   'label' => '1100-1500 €',   'icon' => '⭐', 'desc' => 'Konforlu — büyük şehir mümkün'],
                    ['value' => 'plus',      'label' => '1500+ €',       'icon' => '🌟', 'desc' => 'Geniş bütçe'],
                    ['value' => 'unsure',    'label' => 'Henüz hesaplamadım', 'icon' => '🤔'],
                ],
                'validation' => ['required', 'in:tight,standard,comfort,plus,unsure'],
            ],

            [
                'key'      => 'tuition_tolerance',
                'type'     => 'cards',
                'title'    => 'Üniversite ücreti konusunda nasıl bakıyorsun?',
                'subtitle' => 'Devlet üniversiteleri çoğu programda ücretsizdir (sadece sömestr katkı payı ~300 €).',
                'options'  => [
                    ['value' => 'public_only', 'label' => 'Sadece ücretsiz devlet üni',  'icon' => '🏛', 'desc' => 'Ücretsiz programlara odakla'],
                    ['value' => 'both',        'label' => 'Özel üni de olabilir',        'icon' => '🎓', 'desc' => 'Daha geniş seçenek'],
                    ['value' => 'private_ok',  'label' => 'Özel ünide olur — kalite öncelikli', 'icon' => '⭐', 'desc' => 'Bütçem var'],
                ],
                'validation' => ['required', 'in:public_only,both,private_ok'],
            ],

            // ── BÖLÜM 6: ŞEHİR & YAŞAM ────────────────────────────
            [
                'key'      => 'preferred_cities',
                'type'     => 'checkbox_group',
                'title'    => 'Hangi şehirlerde okumak istersin?',
                'subtitle' => 'En fazla 3 şehir seçebilirsin (boş bırakırsan tümü görüntülenir).',
                'max'      => 3,
                'options'  => [
                    ['value' => 'Berlin',     'label' => 'Berlin',     'icon' => '🏙'],
                    ['value' => 'Munich',     'label' => 'Münih',      'icon' => '🍻'],
                    ['value' => 'Hamburg',    'label' => 'Hamburg',    'icon' => '⚓'],
                    ['value' => 'Cologne',    'label' => 'Köln',       'icon' => '⛪'],
                    ['value' => 'Frankfurt',  'label' => 'Frankfurt',  'icon' => '🏦'],
                    ['value' => 'Stuttgart',  'label' => 'Stuttgart',  'icon' => '🚗'],
                    ['value' => 'Düsseldorf', 'label' => 'Düsseldorf', 'icon' => '🌆'],
                    ['value' => 'Hannover',   'label' => 'Hannover',   'icon' => '🏘'],
                    ['value' => 'Bremen',     'label' => 'Bremen',     'icon' => '🐦'],
                    ['value' => 'Heidelberg', 'label' => 'Heidelberg', 'icon' => '🏰'],
                    ['value' => 'Leipzig',    'label' => 'Leipzig',    'icon' => '🎼'],
                    ['value' => 'Dresden',    'label' => 'Dresden',    'icon' => '🎨'],
                ],
                'validation' => ['nullable', 'array', 'max:3'],
            ],

            [
                'key'      => 'living_priority',
                'type'     => 'cards',
                'title'    => 'Yaşam tarzın için ideal şehir?',
                'subtitle' => 'Şehir karakteri program önerilerini ince ayarlayalım.',
                'options'  => [
                    ['value' => 'big_city',  'label' => 'Büyük şehir',          'icon' => '🌆', 'desc' => 'Berlin, Münih, Hamburg'],
                    ['value' => 'uni_town',  'label' => 'Üniversite kasabası',  'icon' => '🎓', 'desc' => 'Heidelberg, Tübingen'],
                    ['value' => 'mid_city',  'label' => 'Orta ölçekli şehir',   'icon' => '🏘', 'desc' => 'Bremen, Hannover'],
                    ['value' => 'flexible',  'label' => 'Esnek',                'icon' => '🌍', 'desc' => 'Önemsiz'],
                ],
                'validation' => ['required', 'in:big_city,uni_town,mid_city,flexible'],
            ],

            // ── BÖLÜM 7: TR-SPESİFİK + MENTORDE ───────────────────
            [
                'key'      => 'has_aps',
                'type'     => 'cards',
                'title'    => 'APS sertifikan var mı?',
                'subtitle' => 'Türkiye\'den Almanya\'ya lisans başvurusu için Akademik Tetkik Merkezi sertifikası zorunludur.',
                'options'  => [
                    ['value' => 'have',       'label' => 'Var',                       'icon' => '✅', 'desc' => 'APS belgesi tamam'],
                    ['value' => 'in_process', 'label' => 'Süreçte',                   'icon' => '⏳', 'desc' => 'Başvurdum, bekliyorum'],
                    ['value' => 'not_yet',    'label' => 'Henüz başlamadım',          'icon' => '○', 'desc' => 'Bilgi almak istiyorum'],
                    ['value' => 'not_needed', 'label' => 'Master için — gerek yok',   'icon' => '➖', 'desc' => 'Sadece lisans için zorunlu'],
                ],
                'validation' => ['required', 'in:have,in_process,not_yet,not_needed'],
            ],

            [
                'key'      => 'visa_readiness',
                'type'     => 'cards',
                'title'    => 'Vize hazırlığında neredeysin?',
                'subtitle' => 'Sperrkonto, sigorta, belge hazırlığı durumun.',
                'options'  => [
                    ['value' => 'not_started',     'label' => 'Henüz başlamadım',           'icon' => '○',  'desc' => 'İlk adımları öğrenmek istiyorum'],
                    ['value' => 'researching',     'label' => 'Araştırıyorum',              'icon' => '🔍', 'desc' => 'Bilgi topluyorum'],
                    ['value' => 'gathering_docs',  'label' => 'Belge topluyorum',           'icon' => '📋', 'desc' => 'Pasaport, transkript vb.'],
                    ['value' => 'sperrkonto_open', 'label' => 'Sperrkonto açtım',           'icon' => '🏦', 'desc' => 'Bloke hesap hazır'],
                    ['value' => 'submitted',       'label' => 'Vize başvurusunu yaptım',    'icon' => '📨', 'desc' => 'Sonuç bekliyorum'],
                    ['value' => 'visa_received',   'label' => 'Vizem hazır',                'icon' => '🎉', 'desc' => 'Yola çıkmaya hazırım'],
                ],
                'validation' => ['required', 'in:not_started,researching,gathering_docs,sperrkonto_open,submitted,visa_received'],
            ],

            [
                'key'      => 'mentorde_help_areas',
                'type'     => 'checkbox_group',
                'title'    => 'MentorDE\'den hangi konularda destek istersin?',
                'subtitle' => 'Birden fazla seçim yapabilirsin — danışmanın bu önceliklere göre yol haritası hazırlar.',
                'max'      => 6,
                'options'  => [
                    ['value' => 'program_selection', 'label' => 'Program seçimi',                'icon' => '🎓', 'desc' => 'Hangi üni, hangi bölüm?'],
                    ['value' => 'application',       'label' => 'Üniversite başvurusu',          'icon' => '📨', 'desc' => 'Uni-Assist + üni portalları'],
                    ['value' => 'aps_support',       'label' => 'APS sertifikası süreci',        'icon' => '📜'],
                    ['value' => 'language_prep',     'label' => 'Dil sertifikası hazırlık',      'icon' => '🗣'],
                    ['value' => 'visa_process',      'label' => 'Vize süreci (VIDEX)',           'icon' => '🛂'],
                    ['value' => 'sperrkonto',        'label' => 'Sperrkonto açılışı',            'icon' => '🏦'],
                    ['value' => 'health_insurance',  'label' => 'Sağlık sigortası',              'icon' => '🏥'],
                    ['value' => 'accommodation',     'label' => 'Konaklama / yurt',              'icon' => '🏠'],
                    ['value' => 'apostille_translation', 'label' => 'Apostil + tercüme',         'icon' => '📋'],
                ],
                'validation' => ['nullable', 'array', 'max:6'],
            ],
        ];
    }
}
