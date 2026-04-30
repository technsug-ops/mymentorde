<?php

namespace App\Services\StudyBuddy;

/**
 * Discovery Wizard adım tanımları (Faz 2).
 *
 * MVP — 5 adım (kullanıcı onayı sonrası 25'e genişletilir):
 *   1. target_degree       — Bachelor / Master / PhD / Studienkolleg
 *   2. target_field        — Computer Science, Engineering, Business, ...
 *   3. study_language      — Almanca / İngilizce / Esnek
 *   4. german_level        — A1...C2 (Almanca seçilirse)
 *   5. finance_method      — Sperrkonto / sponsor / scholarship / ...
 *
 * Her adım: key, type, title, subtitle, options (varsa), validation, icon
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
            // ── Adım 1: Hedef derece ──
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

            // ── Adım 2: Alan ──
            [
                'key'      => 'target_field',
                'type'     => 'cards',
                'title'    => 'Hangi alanda okumak istersin?',
                'subtitle' => 'Programları senin alanına göre filtreleriz.',
                'options'  => [
                    ['value' => 'Computer Science',          'label' => 'Bilgisayar / Yazılım',  'icon' => '💻'],
                    ['value' => 'Engineering',               'label' => 'Mühendislik',           'icon' => '⚙️'],
                    ['value' => 'Business',                  'label' => 'İşletme / Ekonomi',     'icon' => '📈'],
                    ['value' => 'Natural Sciences',          'label' => 'Doğa Bilimleri',        'icon' => '🧪'],
                    ['value' => 'Medicine',                  'label' => 'Tıp / Sağlık',          'icon' => '⚕️'],
                    ['value' => 'Social Sciences',           'label' => 'Sosyal Bilimler',       'icon' => '👥'],
                    ['value' => 'Arts',                      'label' => 'Sanat / Tasarım',       'icon' => '🎨'],
                    ['value' => 'Law',                       'label' => 'Hukuk',                 'icon' => '⚖️'],
                    ['value' => 'other',                     'label' => 'Diğer',                 'icon' => '✨'],
                ],
                'validation' => ['required', 'string', 'max:120'],
            ],

            // ── Adım 3: Eğitim dili ──
            [
                'key'      => 'study_language',
                'type'     => 'cards',
                'title'    => 'Hangi dilde okumak istersin?',
                'subtitle' => 'Almanca daha çok seçenek sunar; İngilizce ile de pek çok master programı bulabilirsin.',
                'options'  => [
                    ['value' => 'de',       'label' => 'Almanca',  'icon' => '🇩🇪', 'desc' => 'En geniş program seçeneği'],
                    ['value' => 'en',       'label' => 'İngilizce', 'icon' => '🇬🇧', 'desc' => 'Master programlarında yaygın'],
                    ['value' => 'flexible', 'label' => 'Esnek',     'icon' => '🌍', 'desc' => 'Her ikisi de olur'],
                ],
                'validation' => ['required', 'in:de,en,flexible'],
            ],

            // ── Adım 4: Almanca seviyesi ──
            [
                'key'      => 'german_level',
                'type'     => 'cards',
                'title'    => 'Almanca seviyen ne?',
                'subtitle' => 'B2 ve üstü çoğu Almanca programa giriş için gereklidir.',
                'options'  => [
                    ['value' => 'none', 'label' => 'Yok',           'icon' => '○',  'desc' => 'Hiç bilmiyorum'],
                    ['value' => 'a1',   'label' => 'A1',            'icon' => '🌱', 'desc' => 'Başlangıç'],
                    ['value' => 'a2',   'label' => 'A2',            'icon' => '🌿', 'desc' => 'Temel'],
                    ['value' => 'b1',   'label' => 'B1',            'icon' => '🌳', 'desc' => 'Orta'],
                    ['value' => 'b2',   'label' => 'B2',            'icon' => '⭐', 'desc' => 'Üst-orta (Master için yeterli)'],
                    ['value' => 'c1',   'label' => 'C1',            'icon' => '⚡', 'desc' => 'İleri (Lisans için yeterli)'],
                    ['value' => 'c2',   'label' => 'C2',            'icon' => '🔥', 'desc' => 'İleri-yüksek'],
                    ['value' => 'native','label' => 'Anadil',       'icon' => '🏆', 'desc' => 'Anadil seviyesinde'],
                ],
                'validation' => ['required', 'in:none,a1,a2,b1,b2,c1,c2,native'],
            ],

            // ── Adım 5: Finans yöntemi ──
            [
                'key'      => 'finance_method',
                'type'     => 'cards',
                'title'    => 'Almanya\'daki yaşam masraflarını nasıl karşılayacaksın?',
                'subtitle' => 'Bu, sana uygun ücret düzeyindeki üniversiteleri filtrelemek için.',
                'options'  => [
                    ['value' => 'blocked_account', 'label' => 'Sperrkonto', 'icon' => '🏦', 'desc' => 'Bloke hesap (~12,000 €/yıl)'],
                    ['value' => 'sponsor',         'label' => 'Garantör',   'icon' => '🤝', 'desc' => 'Aile/akraba mali sorumluluk'],
                    ['value' => 'self_funded',     'label' => 'Kendim',     'icon' => '💰', 'desc' => 'Kendi birikimimle'],
                    ['value' => 'scholarship',     'label' => 'Burs',       'icon' => '🎯', 'desc' => 'Burs alacağım/aldım'],
                    ['value' => 'undecided',       'label' => 'Henüz belli değil', 'icon' => '🤔', 'desc' => 'Karar vereceğim'],
                ],
                'validation' => ['required', 'in:blocked_account,sponsor,self_funded,scholarship,undecided'],
            ],
        ];
    }
}
