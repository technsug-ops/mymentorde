<?php

namespace App\Services\AiLabs;

/**
 * AI Labs 3-seviye system prompt üreticisi.
 *
 * Davranış:
 *   🟢 source-grounded  — kaynaklarda cevap var: citation + yeşil rozet
 *   🟡 external         — kaynaklarda yok ama yurt dışı eğitim ile ilgili: genel bilgi + uyarı
 *   ⚪ refused          — yurt dışı eğitimle alakasız: polite red
 *
 * JSON response bekliyoruz — Gemini 1.5 Flash structured output destekler ama
 * bu MVP için JSON anahtarlarını prompt içinde belirtiyoruz.
 */
class SystemPromptBuilder
{
    /** @var array<string, string> */
    private const ROLE_PERSONAS = [
        'guest'       => 'Aday öğrenci bir kişiyle konuşuyorsun. Henüz sisteme kayıt olmamış, yurt dışı eğitim süreci hakkında bilgi topluyor.',
        'student'     => 'Kayıtlı bir öğrenciyle konuşuyorsun. Başvuru süreci, vize, konaklama gibi aktif konularla ilgileniyor.',
        'senior'      => 'Bir eğitim danışmanıyla konuşuyorsun. Öğrencilere rehberlik ederken karşılaştığı süreç/prosedür sorularına yanıt üret. Operasyonel ve teknik detaylarda derinleş.',
        'manager'     => 'Bir yöneticiyle konuşuyorsun. Operasyon, iş süreçleri, ekip yönetimi soruları hakkında konuşuyorsun.',
        'admin_staff' => 'Bir admin personeliyle konuşuyorsun. Prosedürler, iç iş akışları, müşteri yönetimi konularında sorular alabilirsin.',
    ];

    public function build(string $role, string $mode, string $brandName, string $systemContext = '', array $availableSourceIds = [], string $adminInstructions = '', array $userContext = []): string
    {
        $persona = self::ROLE_PERSONAS[$role] ?? self::ROLE_PERSONAS['guest'];
        $modeClause = $this->modeClause($mode);

        $context = $systemContext !== ''
            ? "\n\n{$systemContext}\n\n---"
            : '';

        // Manager tarafından girilen persistent talimatlar — her cevapta dikkat edilmeli
        $adminBlock = '';
        $clean = trim($adminInstructions);
        if ($clean !== '') {
            $adminBlock = "\n\n**⚡ YÖNETİCİ TALİMATLARI — HER CEVAPTA UYULMASI ZORUNLU:**\n{$clean}\n\nYukarıdaki talimatlar kaynak kurallarından önce uygulanır, ihlal edilemez.";
        }

        // Kişiselleştirme — kullanıcı profili (varsa)
        $userBlock = $this->buildUserProfileBlock($userContext, $role);

        // Mevcut kaynak ID'lerini prompt'a yaz — AI bunlardan birini seçecek
        $sourceIdsHint = '';
        if (!empty($availableSourceIds)) {
            $idsCsv = implode(', ', array_map('intval', $availableSourceIds));
            $sourceIdsHint = "\n**MEVCUT KAYNAK ID'leri:** {$idsCsv}\n" .
                "Sadece bu listeden ID kullan. Uydurma ID yazma.";
        } else {
            $sourceIdsHint = "\n**UYARI:** Sana hiç kaynak verilmedi. Mode olarak 'external' veya 'refused' kullan, 'source' KULLANMA.";
        }

        return <<<PROMPT
Sen {$brandName}'in yurt dışı (özellikle Almanya) eğitim danışmanı AI asistanısın.

**Rol bağlamı:** {$persona}

**Uzmanlık alanın:** Almanya'da üniversite başvurusu, Uni-Assist, APS sertifikası, Sperrkonto, öğrenci vizesi, Anmeldung, sağlık sigortası, dil sınavları (TestDaF/DSH/Goethe), öğrenci konaklaması, Erasmus.

**KAYNAK SAHİPLİĞİ — KRİTİK KURAL:**
Sana verilen PDF dosyaları ve metin blokları {$brandName} şirketinin KENDİ bilgi havuzudur. Şirket yöneticisi (manager) tarafından önceden yüklenmiştir.
- "Kullanıcı bana X dokümanı yükledi" gibi ifadeler KULLANMA
- "Broşürünüzü aldım", "gönderdiğiniz dosya" gibi ifadeler KULLANMA
- "Mesaj öncesi bir dosya verdin" gibi ifadeler KULLANMA
- Kullanıcı sana hiçbir şey göndermedi; kaynaklar zaten havuzda.
- Sadece kaynak içeriğini kullan ve "**Kaynak #ID'ye göre**" veya "**{$brandName} bilgi havuzuna göre**" şeklinde atıf ver.
{$sourceIdsHint}

**Yanıt kuralları:**
1. Kaynaklar PDF dosyaları + metin blokları olarak verildi.
2. **Soru kaynaklarda cevaplanmışsa** → yanıtı TAMAMEN o kaynaklardan türet. Yanıt içinde "**Kaynak #ID**" şeklinde açıkça referans göster. Mode: `source`.
3. **Kaynaklarda yoksa ama yurt dışı eğitim ile ilgiliyse** → {$modeClause} Mode: `external`.
4. **Yurt dışı eğitimle tamamen alakasızsa** → kibarca reddet: "Uzmanlık alanım yurt dışı eğitim danışmanlığı. Bu konuda yardımcı olamam." Mode: `refused`.

**ZORUNLU META SATIRI — her yanıtın SON SATIRI olarak:**
```
[MODE: source] [SOURCES: 2,7]
```
Kurallar:
- Mode 'source' seçtiysen SOURCES mutlaka dolu olmalı. Boş bırakamazsın.
- Mode 'external' veya 'refused' seçtiysen SOURCES boş olmalı.
- Format tam bu şekilde, alıntı parantezsiz, ek metin ekleme.
- Bu satır yanıtın EN SONUNDA olmalı, üstünde 1 boş satır bırak.

**Geçerli örnekler:**
- `[MODE: source] [SOURCES: 3]`
- `[MODE: source] [SOURCES: 1,2,5]`
- `[MODE: external] [SOURCES: ]`
- `[MODE: refused] [SOURCES: ]`

**Dil:** Soru hangi dildeyse o dilde yanıtla. Türkçe öncelikli. Almanca resmi terimleri (Sperrkonto, Anmeldung, Visa Type D) orijinal formda bırak, parantez içinde Türkçe açıklama ver.

**Stil:** Kısa, net, madde madde. Gereksiz giriş cümleleri kullanma.{$userBlock}{$adminBlock}{$context}
PROMPT;
    }

    /**
     * Kullanıcı profilinden prompt bloğu üretir.
     * AI'ın kişiselleştirilmiş yanıt verebilmesi için.
     */
    private function buildUserProfileBlock(array $ctx, string $role): string
    {
        $lines = [];

        $firstName = trim((string) ($ctx['first_name'] ?? ''));
        $fullName  = trim((string) ($ctx['full_name'] ?? ''));
        $displayName = $firstName ?: (explode(' ', $fullName)[0] ?? '');

        if ($displayName !== '') {
            $lines[] = "• Ad (hitap için): {$displayName}";
        }
        if ($fullName !== '' && $fullName !== $displayName) {
            $lines[] = "• Tam ad: {$fullName}";
        }
        if (!empty($ctx['email'])) {
            $lines[] = "• E-posta: {$ctx['email']}";
        }
        if (!empty($ctx['application_type'])) {
            $lines[] = "• Başvuru tipi: {$ctx['application_type']}";
        }
        if (!empty($ctx['target_city'])) {
            $lines[] = "• Hedef şehir: {$ctx['target_city']}";
        }
        if (!empty($ctx['target_program'])) {
            $lines[] = "• Hedef program/bölüm: {$ctx['target_program']}";
        }
        if (!empty($ctx['package_code']) || !empty($ctx['package_title'])) {
            $pkg = (string) ($ctx['package_title'] ?? $ctx['package_code']);
            $lines[] = "• Paket: {$pkg}";
        }
        if (!empty($ctx['student_id'])) {
            $lines[] = "• Öğrenci ID: {$ctx['student_id']}";
        }
        if (isset($ctx['docs_uploaded']) && isset($ctx['docs_required'])) {
            $lines[] = "• Belge durumu: {$ctx['docs_uploaded']}/{$ctx['docs_required']} yüklü";
        }
        if (isset($ctx['progress_percent'])) {
            $lines[] = "• Profil tamamlanma: %{$ctx['progress_percent']}";
        }
        if (!empty($ctx['contract_status'])) {
            $lines[] = "• Sözleşme durumu: {$ctx['contract_status']}";
        }
        if (!empty($ctx['language_level'])) {
            $lines[] = "• Dil seviyesi: {$ctx['language_level']}";
        }

        if (empty($lines)) {
            return '';
        }

        $roleLabel = match ($role) {
            'guest'       => 'Aday Öğrenci',
            'student'     => 'Kayıtlı Öğrenci',
            'senior'      => 'Eğitim Danışmanı',
            'manager'     => 'Yönetici',
            'admin_staff' => 'Admin Personel',
            default       => ucfirst($role),
        };

        $profile = implode("\n", $lines);

        return <<<BLOCK


**👤 KULLANICI PROFİLİ — Bu kişiyle konuşuyorsun ({$roleLabel}):**
{$profile}

**KİŞİSELLEŞTİRME KURALLARI:**
- İlk cevabında veya uygun bağlamda adıyla hitap et ("{$displayName}" gibi)
- Kullanıcının hedef şehrini/programını/paketini biliyorsan yanıtını ona göre özelleştir
- Belge durumu varsa eksik belgelere vurgu yap
- Konu ilgili değilse profili tekrarlama, sadece akılda tut
BLOCK;
    }

    private function modeClause(string $mode): string
    {
        return $mode === 'strict'
            ? '"🟡 Bu konuda kaynak havuzumuzda bilgi yok. Danışmanınla görüşmeni öneririm." şeklinde yanıtla. Genel bilgi verme.'
            : '"🟡 external" modunda yanıtla: genel bilgi ver ama yanıtın başında "Bu konuda kaynak havuzumuzda özel bilgi yok, genel bilgimden aktarıyorum:" uyarısı ekle. Yanlış bilgi verme riskine karşı kesin olmayan şeyler için "danışmana teyit ettir" notu düş.';
    }

    /**
     * AI yanıtındaki [MODE: ...] [SOURCES: ...] satırlarını ayrıştırır.
     *
     * @return array{mode:string, source_ids:array<int,int>, content:string}
     */
    public function parseResponseMeta(string $content): array
    {
        $mode = 'external'; // güvenli default
        $sourceIds = [];

        if (preg_match('/\[MODE:\s*(source|external|refused)\]/i', $content, $m)) {
            $mode = strtolower($m[1]);
        }
        if (preg_match('/\[SOURCES:\s*([\d,\s]*)\]/i', $content, $m)) {
            $raw = trim($m[1]);
            if ($raw !== '') {
                $sourceIds = array_values(array_filter(
                    array_map('intval', explode(',', $raw)),
                    fn ($n) => $n > 0
                ));
            }
        }

        // Meta satırını kullanıcı yanıtından temizle
        $clean = preg_replace('/\s*\[MODE:[^\]]*\]\s*\[SOURCES:[^\]]*\]\s*$/s', '', $content);

        return [
            'mode'       => $mode,
            'source_ids' => $sourceIds,
            'content'    => trim((string) $clean),
        ];
    }
}
