<?php

namespace App\Services\AiLabs;

/**
 * AI Labs içerik üretici — template tanımları.
 *
 * Her template:
 *   - name: kullanıcıya gösterilen etiket
 *   - icon: emoji
 *   - description: ne üretir
 *   - fields: form alanları — [key => [label, type, required, placeholder, help]]
 *   - prompt_builder: callable → variables'tan AI prompt üretir
 *   - output_format: 'markdown' | 'faq_json' | 'blog_html'
 *   - uses_knowledge_base: bool — prompt'a bilgi havuzu kaynakları eklensin mi
 */
class ContentTemplates
{
    public static function all(): array
    {
        return [
            'motivation_letter' => [
                'name'        => 'Motivation Letter',
                'icon'        => '🎓',
                'description' => 'Üniversite başvurusu için motivasyon mektubu. Öğrenci profiline + hedef programa göre kişiselleştirilir.',
                'fields'      => [
                    'student_name'      => ['label' => 'Öğrenci Adı', 'type' => 'text', 'required' => true, 'placeholder' => 'Ahmet Yılmaz'],
                    'target_uni'        => ['label' => 'Hedef Üniversite', 'type' => 'text', 'required' => true, 'placeholder' => 'TU Berlin'],
                    'target_program'    => ['label' => 'Hedef Bölüm/Program', 'type' => 'text', 'required' => true, 'placeholder' => 'M.Sc. Computer Engineering'],
                    'cv_summary'        => ['label' => 'CV Özeti', 'type' => 'textarea', 'required' => true, 'placeholder' => 'ODTÜ Elektrik-Elektronik Müh. 2024 mezunu, 3.2 GPA. 2 yıl yazılım stajı...', 'rows' => 5],
                    'language'          => ['label' => 'Yazım Dili', 'type' => 'select', 'options' => ['tr' => 'Türkçe', 'en' => 'İngilizce', 'de' => 'Almanca'], 'required' => true],
                    'tone'              => ['label' => 'Ton', 'type' => 'select', 'options' => ['formal' => 'Resmi', 'friendly' => 'Samimi-Profesyonel'], 'required' => true],
                ],
                'output_format'       => 'markdown',
                'uses_knowledge_base' => true,
                'max_tokens'          => 2500,
            ],

            'sperrkonto' => [
                'name'        => 'Sperrkonto Başvuru',
                'icon'        => '🏦',
                'description' => 'Bloke hesap (Sperrkonto) açılış için banka başvuru taslağı. Öğrenci bilgileri ile doldurulur.',
                'fields'      => [
                    'student_name'   => ['label' => 'Öğrenci Adı Soyadı', 'type' => 'text', 'required' => true],
                    'birth_date'     => ['label' => 'Doğum Tarihi', 'type' => 'date', 'required' => true],
                    'passport_no'    => ['label' => 'Pasaport No', 'type' => 'text', 'required' => true],
                    'target_uni'     => ['label' => 'Kabul Mektubu Aldığı Uni', 'type' => 'text', 'required' => true],
                    'bank_choice'    => ['label' => 'Tercih Edilen Banka', 'type' => 'select', 'options' => ['fintiba' => 'Fintiba', 'expatrio' => 'Expatrio', 'deutsche_bank' => 'Deutsche Bank', 'other' => 'Diğer'], 'required' => true],
                    'notes'          => ['label' => 'Ek Notlar', 'type' => 'textarea', 'required' => false, 'rows' => 3],
                ],
                'output_format'       => 'markdown',
                'uses_knowledge_base' => true,
                'max_tokens'          => 1500,
            ],

            'visa_call' => [
                'name'        => 'Vize Çağrı Mektubu',
                'icon'        => '📧',
                'description' => 'Velinin öğrenciye Almanya\'dan gönderdiği mali destek / çağrı mektubu taslağı.',
                'fields'      => [
                    'parent_name'    => ['label' => 'Veli Adı Soyadı', 'type' => 'text', 'required' => true],
                    'parent_address' => ['label' => 'Veli Adresi (TR)', 'type' => 'textarea', 'required' => true, 'rows' => 2],
                    'student_name'   => ['label' => 'Öğrenci Adı Soyadı', 'type' => 'text', 'required' => true],
                    'monthly_support'=> ['label' => 'Aylık Destek Miktarı (€)', 'type' => 'number', 'required' => true, 'placeholder' => '992'],
                    'duration'       => ['label' => 'Destek Süresi', 'type' => 'text', 'required' => true, 'placeholder' => '5 yıl (2026-2031)'],
                    'language'       => ['label' => 'Yazım Dili', 'type' => 'select', 'options' => ['tr' => 'Türkçe', 'de' => 'Almanca'], 'required' => true],
                ],
                'output_format'       => 'markdown',
                'uses_knowledge_base' => false,
                'max_tokens'          => 1200,
            ],

            'uni_recommendation' => [
                'name'        => 'Üniversite Önerisi Raporu',
                'icon'        => '🏫',
                'description' => 'Öğrenci profiline göre Almanya\'da 3-5 üniversite önerisi — güçlü/zayıf yönler, kabul kriterleri, maliyet karşılaştırma.',
                'fields'      => [
                    'student_name'   => ['label' => 'Öğrenci Adı', 'type' => 'text', 'required' => true],
                    'degree_level'   => ['label' => 'Derece', 'type' => 'select', 'options' => ['bachelor' => 'Lisans', 'master' => 'Yüksek Lisans', 'phd' => 'Doktora'], 'required' => true],
                    'field'          => ['label' => 'Alan', 'type' => 'text', 'required' => true, 'placeholder' => 'Bilgisayar Mühendisliği'],
                    'gpa'            => ['label' => 'GPA / Not Ortalaması', 'type' => 'text', 'required' => true, 'placeholder' => '3.2 / 4.0'],
                    'language_level' => ['label' => 'Almanca/İngilizce Seviyesi', 'type' => 'text', 'required' => true, 'placeholder' => 'B2 Almanca, C1 İngilizce'],
                    'budget_monthly' => ['label' => 'Aylık Bütçe (€)', 'type' => 'number', 'required' => false, 'placeholder' => '1000'],
                    'city_preference'=> ['label' => 'Şehir Tercihi', 'type' => 'text', 'required' => false, 'placeholder' => 'Berlin, Münih'],
                ],
                'output_format'       => 'markdown',
                'uses_knowledge_base' => true,
                'max_tokens'          => 3000,
            ],

            'blog_post' => [
                'name'        => 'Blog Yazısı (SEO)',
                'icon'        => '📰',
                'description' => 'SEO uyumlu blog yazısı — H1/H2 yapısı, meta description, anahtar kelime optimizasyonu. 800-1500 kelime.',
                'fields'      => [
                    'topic'          => ['label' => 'Konu / Başlık Fikri', 'type' => 'text', 'required' => true, 'placeholder' => 'Almanya\'da Sperrkonto Nasıl Açılır?'],
                    'keywords'       => ['label' => 'Anahtar Kelimeler (virgülle)', 'type' => 'text', 'required' => true, 'placeholder' => 'sperrkonto, fintiba, bloke hesap, öğrenci vizesi'],
                    'target_audience'=> ['label' => 'Hedef Kitle', 'type' => 'select', 'options' => ['prospective_students' => 'Aday Öğrenciler', 'parents' => 'Veliler', 'current_students' => 'Mevcut Öğrenciler', 'general' => 'Genel'], 'required' => true],
                    'word_count'     => ['label' => 'Hedef Kelime Sayısı', 'type' => 'select', 'options' => ['800' => '~800 kelime', '1200' => '~1200 kelime', '1500' => '~1500 kelime'], 'required' => true],
                    'tone'           => ['label' => 'Ton', 'type' => 'select', 'options' => ['friendly' => 'Samimi', 'informative' => 'Bilgilendirici', 'authoritative' => 'Otorite'], 'required' => true],
                ],
                'output_format'       => 'blog_html',
                'uses_knowledge_base' => true,
                'max_tokens'          => 4000,
            ],

            'faq' => [
                'name'        => 'FAQ Oluşturucu',
                'icon'        => '❓',
                'description' => 'Bilgi havuzundaki kaynaklardan 10-20 sık sorulan soru + cevap çıkartır. Landing page\'e eklenebilir JSON.',
                'fields'      => [
                    'topic_focus'    => ['label' => 'Konu Odağı', 'type' => 'text', 'required' => true, 'placeholder' => 'Almanya vize süreci'],
                    'question_count' => ['label' => 'Soru Sayısı', 'type' => 'select', 'options' => ['8' => '8 soru', '12' => '12 soru', '20' => '20 soru'], 'required' => true],
                    'target_audience'=> ['label' => 'Hedef Kitle', 'type' => 'select', 'options' => ['guest' => 'Aday Öğrenci', 'student' => 'Mevcut Öğrenci', 'parent' => 'Veli', 'general' => 'Genel'], 'required' => true],
                ],
                'output_format'       => 'faq_json',
                'uses_knowledge_base' => true,
                'max_tokens'          => 4000,
            ],

            'custom' => [
                'name'        => 'Özel Prompt',
                'icon'        => '✨',
                'description' => 'Serbest format. Ne istediğini aç seçik yaz, AI üretir.',
                'fields'      => [
                    'title'   => ['label' => 'Başlık', 'type' => 'text', 'required' => true],
                    'prompt'  => ['label' => 'AI\'a talimatın', 'type' => 'textarea', 'required' => true, 'rows' => 8, 'placeholder' => 'Örn: Almanca A1 seviye kursu için 5 günlük çalışma planı hazırla. Her gün 2 saat, hafta içi, kelime + dilbilgisi + dinleme karışık.'],
                    'language'=> ['label' => 'Yazım Dili', 'type' => 'select', 'options' => ['tr' => 'Türkçe', 'en' => 'İngilizce', 'de' => 'Almanca'], 'required' => true],
                ],
                'output_format'       => 'markdown',
                'uses_knowledge_base' => true,
                'max_tokens'          => 3000,
            ],
        ];
    }

    public static function find(string $code): ?array
    {
        return self::all()[$code] ?? null;
    }
}
