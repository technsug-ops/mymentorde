<?php

namespace App\Console\Commands;

use App\Services\AiLabs\ResponseRouter;
use Illuminate\Console\Command;

/**
 * AI Labs CLI test — RAG'ın gerçekten çalışıp çalışmadığını terminal'den doğrula.
 *
 * Örnek:
 *   php artisan ai-labs:ask "Uni-Assist için hangi belgeler lazım?"
 *   php artisan ai-labs:ask --role=senior "Sperrkonto için müşteriye hangi bankayı önereyim?"
 *   php artisan ai-labs:ask --role=student --company=1 "Vize başvurusunda mali kanıt için ne yapmalıyım?"
 */
class AiLabsAsk extends Command
{
    protected $signature = 'ai-labs:ask
                            {question : Soru metni (tırnak içinde)}
                            {--role=student : Rol (guest, student, senior, manager, admin_staff)}
                            {--company=1 : Company ID}';

    protected $description = 'AI Labs RAG test — bilgi havuzuna soru sor, cevabı terminal\'de gör';

    public function handle(ResponseRouter $router): int
    {
        $question = (string) $this->argument('question');
        $role = (string) $this->option('role');
        $cid = (int) $this->option('company');

        if (!in_array($role, ['guest', 'student', 'senior', 'manager', 'admin_staff'], true)) {
            $this->error('Geçersiz rol. İzin verilenler: guest, student, senior, manager, admin_staff');
            return self::FAILURE;
        }

        $this->line("<fg=cyan>❓ Soru ({$role}):</> {$question}");
        $this->line('<fg=gray>⏳ Gemini\'ye soruluyor...</>');
        $this->newLine();

        $start = microtime(true);
        $result = $router->ask($cid, $role, $question);
        $elapsed = round((microtime(true) - $start) * 1000);

        if (!($result['ok'] ?? false)) {
            $this->error('Hata: ' . ($result['error'] ?? 'unknown'));
            return self::FAILURE;
        }

        // Mode badge
        $mode = $result['mode'] ?? 'external';
        $badge = match ($mode) {
            'source'   => '<fg=green>🟢 SOURCE-GROUNDED</>',
            'external' => '<fg=yellow>🟡 EXTERNAL</>',
            'refused'  => '<fg=white>⚪ REFUSED</>',
            default    => "<fg=gray>{$mode}</>",
        };
        $this->line("Mode: {$badge}");

        // Sources
        if (!empty($result['sources_meta'])) {
            $this->line('<fg=cyan>📚 Kullanılan kaynaklar:</>');
            foreach ($result['sources_meta'] as $s) {
                $this->line("  • #{$s['id']} [{$s['type']}] {$s['title']}");
            }
        }

        $this->newLine();
        $this->line('<fg=yellow>─── YANIT ────────────────────</>');
        $this->line($result['content']);
        $this->line('<fg=yellow>──────────────────────────────</>');
        $this->newLine();

        $this->line(sprintf(
            "<fg=gray>⏱ %d ms  •  📥 %d input tok  •  📤 %d output tok  •  🤖 %s</>",
            $elapsed,
            $result['tokens_input'] ?? 0,
            $result['tokens_output'] ?? 0,
            $result['model'] ?? 'n/a'
        ));

        return self::SUCCESS;
    }
}
