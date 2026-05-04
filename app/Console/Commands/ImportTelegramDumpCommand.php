<?php

namespace App\Console\Commands;

use App\Models\TelegramMessage;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportTelegramDumpCommand extends Command
{
    protected $signature = 'telegram:import
                            {--file=analysis/telegram_dump.json : JSON dump path}
                            {--truncate : Önce tabloyu temizle}
                            {--batch=2000 : Insert batch size}';

    protected $description = 'Telegram JSON dump\'ı telegram_messages tablosuna yükler (idempotent değil — truncate öneriyoruz).';

    private const TOPICS = [
        'vize'         => '/\b(vize|visa|sperrkonto|sperrkont|bloke|elcilik|konsolosluk|videx)\b/i',
        'uni_assist'   => '/\b(uni[- ]?assist|uniassist|vpd|hzb)\b/i',
        'aps'          => '/\baps\b/i',
        'anmeldung'    => '/\b(anmeldung|anmelden|wohnsitz)\b/i',
        'dil'          => '/\b(testdaf|dsh|telc|goethe|c1|c2|b1|b2|almanca|deutsch|ielts|toefl)\b/i',
        'yurt'         => '/\b(yurt|wohnung|wg|wohnheim|kira|miete|kaution)\b/i',
        'sigorta'      => '/\b(sigorta|krankenkasse|krankenversicherung|tk |aok|barmer)\b/i',
        'burs'         => '/\b(burs|stipendium|daad|scholarship)\b/i',
        'para'         => '/\b(para|butce|euro|tl|maddi|harclik|gecim)\b/i',
        'is'           => '/\b(is|minijob|werkstudent|calis|part time)\b/i',
        'studienkolleg'=> '/\b(studienkolleg|studkol|hazirlik|feststellungspruefung)\b/i',
        'master'       => '/\b(master|yuksek lisans)\b/i',
        'ausbildung'   => '/\b(ausbildung|meslek egitim)\b/i',
        'denklik'      => '/\b(denklik|taninma|recognition|anerkennung|zeugnis)\b/i',
        'randevu'      => '/\b(randevu|appointment|termin|idata)\b/i',
        'sehir'        => '/\b(berlin|munih|munich|munchen|hamburg|frankfurt|koln|stuttgart|leipzig|dresden|heidelberg|freiburg)\b/i',
        'doktor_approbation'    => '/\b(approbation|approbat|appro\b|approbasyon)\b/i',
        'doktor_fsp'            => '/\b(fsp|fachsprach|fachsprache|telc med|telc-med)\b/i',
        'doktor_hospitation_be' => '/\b(hospitation|hospitasyon|hospitati|berufserlaubnis|be\b)\b/i',
        'doktor_kp_gutachten'   => '/\b(kenntnispr|gutachten|kp\b)\b/i',
        'doktor_fachgebiet'     => '/\b(asistan|fachgebiet|fachartz|cerrahi|psikiyatri|pediatri|kardio|nefro|onkoloji|jinekoloji|noroloji|patoloji|radyoloji|anestezi|dermatoloji|uroloji|ortopedi|aile hekimli|tukmos|stellenangebot|stellen)\b/i',
    ];

    public function handle(): int
    {
        $file = $this->option('file');
        $batchSize = (int) $this->option('batch');

        if (!file_exists($file)) {
            $this->error("Dosya bulunamadı: $file");
            return self::FAILURE;
        }

        if ($this->option('truncate')) {
            $this->info('Tablo temizleniyor...');
            DB::table('telegram_messages')->truncate();
        }

        $this->info("JSON yükleniyor: $file");
        $raw = file_get_contents($file);
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $this->error('JSON parse edilemedi');
            return self::FAILURE;
        }

        $total = count($data);
        $this->info("$total mesaj parse edildi, DB'ye yazılıyor (batch=$batchSize)...");

        $batchId = 'batch_' . date('YmdHis');
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $buffer = [];
        $inserted = 0;
        $skipped  = 0;

        foreach ($data as $m) {
            $text = (string) ($m['text'] ?? '');
            if ($text === '') {
                $skipped++;
                continue;
            }
            $dateStr = (string) ($m['date'] ?? '');
            $sentAt = $this->parseDate($dateStr);

            $topics = $this->detectTopics($text);

            $buffer[] = [
                'source'         => substr((string) ($m['source'] ?? ''), 0, 120),
                'sender_hash'    => substr((string) ($m['sender'] ?? 'anon'), 0, 32),
                'sent_at'        => $sentAt,
                'text'           => $text,
                'is_question'    => (bool) ($m['is_question'] ?? false),
                'is_short'       => (bool) ($m['is_short'] ?? false),
                'text_len'       => min(65535, mb_strlen($text)),
                'year'           => $sentAt?->year,
                'month'          => $sentAt ? $sentAt->format('Y-m') : null,
                'dow'            => $sentAt?->dayOfWeek,
                'hour'           => $sentAt?->hour,
                'topics'         => json_encode($topics, JSON_UNESCAPED_UNICODE),
                'imported_batch' => $batchId,
                'created_at'     => now(),
                'updated_at'     => now(),
            ];

            if (count($buffer) >= $batchSize) {
                DB::table('telegram_messages')->insert($buffer);
                $inserted += count($buffer);
                $buffer = [];
                $bar->advance($batchSize);
            }
        }

        if (!empty($buffer)) {
            DB::table('telegram_messages')->insert($buffer);
            $inserted += count($buffer);
            $bar->advance(count($buffer));
        }
        $bar->finish();
        $this->newLine();

        $this->info("✓ Insert: $inserted  |  Skip: $skipped  |  Batch: $batchId");
        return self::SUCCESS;
    }

    private function parseDate(string $s): ?Carbon
    {
        // Telegram format: "DD.MM.YYYY HH:MM:SS [TZ]"
        if (strlen($s) < 10) return null;
        try {
            return Carbon::createFromFormat('d.m.Y H:i:s', substr($s, 0, 19));
        } catch (\Throwable) {
            return null;
        }
    }

    private function detectTopics(string $text): array
    {
        $hits = [];
        foreach (self::TOPICS as $topic => $pattern) {
            if (preg_match($pattern, $text)) {
                $hits[] = $topic;
            }
        }
        return $hits;
    }
}
