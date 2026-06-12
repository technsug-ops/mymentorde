<?php

namespace App\Console\Commands;

use App\Services\Platform\TrialService;
use Illuminate\Console\Command;

/**
 * Trial nurture mail dispatcher.
 *
 * Her gun 09:00'da calisir (routes/console.php).
 * TrialService::getNurtureCandidates() ile bekleyen aday'lari bulur,
 * sendNurtureMail() ile gonderir. Aday yoksa sessiz cikar.
 *
 * --dry-run: gonderim yapmadan ne yapacagini gosterir.
 * --limit=N: tek calismada en fazla N mail (default 200).
 */
class SendTrialNurtureCommand extends Command
{
    protected $signature   = 'trial:send-nurture {--dry-run : Mail gondermez, sadece aday listeler} {--limit=200 : Max gonderim sayisi}';
    protected $description = 'Trial company\'lere day3/7/14/17 nurture mail kampanyasini tetikler';

    public function handle(TrialService $trial): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit  = (int) $this->option('limit');

        $candidates = $trial->getNurtureCandidates();

        if (count($candidates) === 0) {
            $this->info('Nurture mail aday yok — atlanan tur.');
            return self::SUCCESS;
        }

        $this->info('Bekleyen nurture mail aday sayisi: ' . count($candidates));

        if ($dryRun) {
            foreach (array_slice($candidates, 0, $limit) as $row) {
                $this->line(sprintf(
                    '  [DRY] %s (id=%d) → stage=%s',
                    $row['company']->name,
                    $row['company']->id,
                    $row['stage']
                ));
            }
            return self::SUCCESS;
        }

        $sent   = 0;
        $failed = 0;
        $count  = 0;

        foreach ($candidates as $row) {
            if ($count >= $limit) {
                break;
            }
            $count++;

            if ($trial->sendNurtureMail($row['company'], $row['stage'])) {
                $sent++;
                $this->line(sprintf(
                    '  ✓ %s → %s',
                    $row['company']->name,
                    $row['stage']
                ));
            } else {
                $failed++;
                $this->warn(sprintf(
                    '  ✗ %s → %s (basarisiz)',
                    $row['company']->name,
                    $row['stage']
                ));
            }
        }

        $this->info("Tamamlandi: {$sent} gonderildi, {$failed} basarisiz.");
        return self::SUCCESS;
    }
}
