<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\KnowledgeSource;
use Illuminate\Database\Seeder;

/**
 * Topluluk FAQ Knowledge Base Seeder
 *
 * config/community_faq.php'den 15 konu × 25 soru = 374 anonim soruyu
 * AI Labs knowledge base'ine yükler. Her konu ayrı bir KnowledgeSource olur.
 *
 * Kullanım:
 *   AI asistan kullanıcıdan vize/dil/randevu vb. soru aldığında bu kaynaklara
 *   bakar. "Topluluk şunları sıkça soruyor" sinyaliyle yanıtını şekillendirir.
 *
 * Tüm sorular anonim — PII (isim, telefon, e-posta, mention) temizlenmiş.
 *
 * idempotent: aynı title + company + type kombinasyonu için updateOrCreate.
 */
class CommunityFaqKnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        $config = config('community_faq', ['topics' => []]);
        $topics = $config['topics'] ?? [];

        if (empty($topics)) {
            $this->command?->warn('CommunityFaqKnowledgeSeeder: config/community_faq.php boş, atlanıyor.');
            return;
        }

        $companies = Company::query()->select('id')->get();
        if ($companies->isEmpty()) {
            $this->command?->warn('CommunityFaqKnowledgeSeeder: aktif şirket yok, atlanıyor.');
            return;
        }

        $totalSeeded = 0;

        foreach ($companies as $company) {
            foreach ($topics as $topicKey => $topicData) {
                $title = $topicData['title'] ?? $topicKey;
                $description = $topicData['description'] ?? '';
                $questions = $topicData['questions'] ?? [];

                if (empty($questions)) continue;

                $markdown = $this->buildMarkdown($title, $description, $questions, count($questions));
                $sourceTitle = "Topluluk FAQ — {$title}";

                KnowledgeSource::query()
                    ->updateOrCreate(
                        [
                            'company_id' => $company->id,
                            'title' => $sourceTitle,
                        ],
                        [
                            'type' => 'text',
                            'category' => 'Topluluk FAQ',
                            'content_markdown' => $markdown,
                            'target_audience' => 'both',
                            'is_active' => true,
                            'visible_to_roles' => ['guest', 'student', 'senior', 'manager'],
                            'url' => null,
                            'file_path' => null,
                        ]
                    );

                $totalSeeded++;
            }
        }

        $this->command?->info("CommunityFaqKnowledgeSeeder: {$totalSeeded} kaynak seedlendi (" . $companies->count() . " şirket × " . count($topics) . " konu).");
    }

    /**
     * Konu için AI'a uygun markdown oluştur.
     * AI cevap üretirken "Topluluk şu soruları sıkça soruyor" sinyalini alır.
     */
    private function buildMarkdown(string $title, string $description, array $questions, int $count): string
    {
        $lines = [];
        $lines[] = "# Topluluk Sıkça Sorulan Sorular: {$title}";
        $lines[] = '';
        $lines[] = "**Konu açıklaması:** {$description}";
        $lines[] = '';
        $lines[] = "**Veri kaynağı:** Türkiye-Almanya eğitim, vize ve akademik kariyer topluluk forumlarında bu konu altında tekrarlanan {$count} anonim soru.";
        $lines[] = '';
        $lines[] = "**Anonimleştirme:** Tüm kişisel bilgiler (isim, telefon, e-posta, kullanıcı adı) temizlenmiştir.";
        $lines[] = '';
        $lines[] = "## AI Asistan İçin Önemli Notlar";
        $lines[] = '';
        $lines[] = "Bu kaynak yalnızca **soru havuzudur** — yanıt içermez. Bir kullanıcı bu konuda soru sorduğunda:";
        $lines[] = '';
        $lines[] = "1. Önce kendi bilgi tabanından (diğer KnowledgeSource'lar) yanıt üret";
        $lines[] = "2. Bu listeyi referans olarak kullan: \"Toplulukta bu konuda sıkça şunlar soruluyor...\"";
        $lines[] = "3. Yanıt verirken kullanıcının kendine özel durumunu sor, çünkü süreçler kişiseldir";
        $lines[] = "4. Spesifik tıbbi/hukuki durumlarda \"Ücretsiz danışmanlık randevu al\" yönlendirmesi yap";
        $lines[] = '';
        $lines[] = "## Sıkça Sorulan Sorular ({$count} adet)";
        $lines[] = '';

        $i = 1;
        foreach ($questions as $q) {
            $text = $q['text'] ?? '';
            $date = $q['date'] ?? '';
            if ($text === '') continue;
            $lines[] = "{$i}. ({$date}) {$text}";
            $i++;
        }

        $lines[] = '';
        $lines[] = "---";
        $lines[] = '';
        $lines[] = "**İlgili sayfa:** /sss/topluluk — public sayfa";

        return implode("\n", $lines);
    }
}
