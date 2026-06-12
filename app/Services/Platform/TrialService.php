<?php

namespace App\Services\Platform;

use App\Models\Company;
use App\Models\TrialExtension;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Platform Owner — Trial yonetimi.
 *
 * Sorumluluklar:
 *   - getConversionStats()      : bu ay/yil trial -> paid conversion rate
 *   - getExpiringSoon($days)    : onumuzdeki N gun icinde trial'i bitecek company'ler
 *   - extendTrial(...)          : Platform Owner manuel uzatma (audit kaydi ile)
 *   - convertToPaid(...)        : trial -> basic/gold/premium
 *   - getNurtureCandidates()    : day 3/7/14/17 nurture mail aday'lari
 *   - sendNurtureMail(...)      : Mail::raw ile customer manager'a gonderir
 *
 * Tum trial sorgulari subscription_tier='trial' uzerinden — converted_to_paid_at
 * sadece historik konversiyon raporu icin kullanilir (tier yer degistirir).
 */
class TrialService
{
    public const NURTURE_STAGES = [
        'day3'  => ['days_after_start' => 3,  'subject' => 'MentorDE\'ye hoş geldin — ilk adımlar'],
        'day7'  => ['days_after_start' => 7,  'subject' => 'Trial yarıda — kullandığın özellikler + öneriler'],
        'day14' => ['days_after_start' => 14, 'subject' => 'Trial bitmek üzere — şimdi yükselt'],
        'day17' => ['days_after_start' => 17, 'subject' => 'Aynı tier\'a geri dön — sana özel'],
    ];

    // ────────────────────────────────────────────────────────────────────────
    // STATS
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Bu ay / bu yil conversion rate + son 12 ay aylik trend.
     *
     * Conversion rate = (donem icinde converted_to_paid_at set olanlar) /
     *                   (donem icinde trial_started_at set olanlar)
     *
     * Bolen 0 olursa NULL doner — view "veri yok" gosterir.
     */
    public function getConversionStats(): array
    {
        $now = CarbonImmutable::now();

        // ── Bu ay ─────────────────────────────────────────────────────────
        $monthStart = $now->startOfMonth();
        $monthEnd   = $now->endOfMonth();

        $monthTrials = (int) Company::query()
            ->whereBetween('trial_started_at', [$monthStart, $monthEnd])
            ->count();
        $monthConverted = (int) Company::query()
            ->whereBetween('converted_to_paid_at', [$monthStart, $monthEnd])
            ->count();

        // ── Bu yil ────────────────────────────────────────────────────────
        $yearStart = $now->startOfYear();
        $yearEnd   = $now->endOfYear();

        $yearTrials = (int) Company::query()
            ->whereBetween('trial_started_at', [$yearStart, $yearEnd])
            ->count();
        $yearConverted = (int) Company::query()
            ->whereBetween('converted_to_paid_at', [$yearStart, $yearEnd])
            ->count();

        // ── 12 ay trend (her ay icin trial_started + converted) ───────────
        $trend = [];
        for ($i = 11; $i >= 0; $i--) {
            $m         = $now->subMonths($i);
            $mStart    = $m->startOfMonth();
            $mEnd      = $m->endOfMonth();
            $tCount    = (int) Company::query()
                ->whereBetween('trial_started_at', [$mStart, $mEnd])->count();
            $cCount    = (int) Company::query()
                ->whereBetween('converted_to_paid_at', [$mStart, $mEnd])->count();
            $rate      = $tCount > 0 ? round(($cCount / $tCount) * 100, 1) : 0.0;

            $trend[] = [
                'label'     => $m->translatedFormat('M Y'),
                'trials'    => $tCount,
                'converted' => $cCount,
                'rate'      => $rate,
            ];
        }

        return [
            'month_trials'     => $monthTrials,
            'month_converted'  => $monthConverted,
            'month_rate'       => $monthTrials > 0 ? round(($monthConverted / $monthTrials) * 100, 1) : null,

            'year_trials'      => $yearTrials,
            'year_converted'   => $yearConverted,
            'year_rate'        => $yearTrials > 0 ? round(($yearConverted / $yearTrials) * 100, 1) : null,

            'trend'            => $trend,
        ];
    }

    /**
     * KPI: aktif trial sayisi (subscription_tier='trial' + is_active).
     */
    public function activeTrialCount(): int
    {
        return (int) Company::query()
            ->where('subscription_tier', Company::TIER_TRIAL)
            ->where('is_active', true)
            ->count();
    }

    /**
     * KPI: trial'i gecmis ama hala trial tier'da takili (overdue).
     */
    public function expiredTrialCount(): int
    {
        return (int) Company::query()
            ->where('subscription_tier', Company::TIER_TRIAL)
            ->whereNotNull('trial_ends_at')
            ->whereDate('trial_ends_at', '<', CarbonImmutable::now()->toDateString())
            ->count();
    }

    /**
     * Onumuzdeki N gun icinde trial'i bitecek company'ler (henuz bitmemis).
     */
    public function getExpiringSoon(int $days = 7): Collection
    {
        $today    = CarbonImmutable::now()->toDateString();
        $deadline = CarbonImmutable::now()->addDays($days)->toDateString();

        return Company::query()
            ->where('subscription_tier', Company::TIER_TRIAL)
            ->where('is_active', true)
            ->whereNotNull('trial_ends_at')
            ->whereDate('trial_ends_at', '>=', $today)
            ->whereDate('trial_ends_at', '<=', $deadline)
            ->orderBy('trial_ends_at')
            ->get();
    }

    /**
     * Tum aktif trial'lar (ana tablo icin) — expired olanlari da gosterir.
     */
    public function getActiveTrials(): Collection
    {
        return Company::query()
            ->where('subscription_tier', Company::TIER_TRIAL)
            ->orderBy('trial_ends_at')
            ->get();
    }

    // ────────────────────────────────────────────────────────────────────────
    // ACTIONS
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Platform Owner manuel trial uzatma.
     *
     * Mevcut trial_ends_at NULL ise bugunden N gun ileri set eder; doluysa
     * mevcut tarihe N gun ekler. Audit kaydi olarak trial_extensions tablosuna
     * satir ekler. Transaction'la sarmalanir — biri patlarsa ikisi de geri alinir.
     */
    public function extendTrial(Company $company, int $days, string $reason, int $byUserId): TrialExtension
    {
        $days = max(1, min($days, 365)); // 1..365 gun guvenlik tampi

        return DB::transaction(function () use ($company, $days, $reason, $byUserId): TrialExtension {
            $previous = $company->trial_ends_at
                ? Carbon::parse($company->trial_ends_at)
                : null;

            // Yeni tarih: mevcut ileride mi geride mi olduguna bakmaksizin ekle.
            // Geride kalmissa bugunden ekle (overdue trial'i normalize et).
            $base = $previous && $previous->gt(CarbonImmutable::now())
                ? $previous->copy()
                : Carbon::now();

            $newDate = $base->copy()->addDays($days)->startOfDay();

            $company->trial_ends_at = $newDate->toDateString();
            $company->save();

            return TrialExtension::create([
                'company_id'             => $company->id,
                'extension_days'         => $days,
                'reason'                 => trim($reason) !== '' ? $reason : null,
                'granted_by_user_id'     => $byUserId,
                'previous_trial_ends_at' => $previous?->toDateString(),
                'new_trial_ends_at'      => $newDate->toDateString(),
            ]);
        });
    }

    /**
     * Trial -> Paid donusumu. tier degistirir, converted_to_paid_at set eder,
     * mrr_eur'i config'ten yeniden alir. Idempotent: zaten paid ise tier'i
     * gunceller ama converted_to_paid_at'i ezmez (ilk donusum sabit kalir).
     */
    public function convertToPaid(Company $company, string $newTier): void
    {
        if (!in_array($newTier, [Company::TIER_BASIC, Company::TIER_GOLD, Company::TIER_PREMIUM], true)) {
            throw new \InvalidArgumentException("Gecersiz tier: {$newTier}");
        }

        $tierConfig = config("subscription_tiers.{$newTier}", []);
        $mrr        = (float) ($tierConfig['mrr_eur'] ?? 0);

        $company->subscription_tier = $newTier;
        $company->mrr_eur           = $mrr;

        // Ilk donusumde converted_to_paid_at set et; mevcut ise dokunma
        if (empty($company->converted_to_paid_at)) {
            $company->converted_to_paid_at = now();
        }

        // Trial bitis tarihini temizle — artik trial degil
        $company->trial_ends_at = null;

        $company->save();
    }

    // ────────────────────────────────────────────────────────────────────────
    // NURTURE
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Bu turu icin gonderilecek nurture mail adaylarini bulur.
     *
     * Donus: [
     *   ['company' => Company, 'stage' => 'day7'],
     *   ...
     * ]
     *
     * Stage'ler NURTURE_STAGES ile tanimli. Bir company ayni stage icin daha
     * once mail aldiysa (nurture_emails_sent JSON'ina yazilmissa) atlanir.
     */
    public function getNurtureCandidates(): array
    {
        $today      = CarbonImmutable::now()->startOfDay();
        $candidates = [];

        $trials = Company::query()
            ->where('subscription_tier', Company::TIER_TRIAL)
            ->where('is_active', true)
            ->whereNotNull('trial_started_at')
            ->get();

        foreach ($trials as $company) {
            $start = $company->trial_started_at ? Carbon::parse($company->trial_started_at) : null;
            if (!$start) {
                continue;
            }

            $daysSinceStart = (int) $today->diffInDays($start, false);
            // diffInDays(false) signed — bugun > start ise pozitif
            $daysSinceStart = abs($daysSinceStart);

            $sent = $this->getSentNurtureStages($company);

            foreach (self::NURTURE_STAGES as $stage => $cfg) {
                if (in_array($stage, $sent, true)) {
                    continue;
                }
                // Stage'in min gun esiginden buyukse aday
                if ($daysSinceStart >= $cfg['days_after_start']) {
                    $candidates[] = [
                        'company' => $company,
                        'stage'   => $stage,
                    ];
                    break; // Bir company icin tek stage bu turda
                }
            }
        }

        return $candidates;
    }

    /**
     * Bir company'e belirli stage icin nurture mail gonderir + JSON'a kaydeder.
     *
     * Mail::raw ile Customer Manager'in e-postasina gider (manager rolundeki
     * ilk kullanici). Hata olursa Log::warning, false doner.
     */
    public function sendNurtureMail(Company $company, string $stage): bool
    {
        if (!isset(self::NURTURE_STAGES[$stage])) {
            Log::warning('platform.trial.nurture: unknown stage', [
                'company_id' => $company->id,
                'stage'      => $stage,
            ]);
            return false;
        }

        // Customer Manager email — companies icindeki ilk manager rolunden kullanici
        $email = $this->resolveManagerEmail($company);
        if ($email === null) {
            Log::warning('platform.trial.nurture: manager email not found', [
                'company_id' => $company->id,
                'stage'      => $stage,
            ]);
            return false;
        }

        $stageConfig = self::NURTURE_STAGES[$stage];
        $subject     = $stageConfig['subject'];
        $body        = $this->buildNurtureBody($company, $stage);

        try {
            Mail::raw($body, function ($message) use ($email, $subject): void {
                $message->to($email)->subject($subject);
            });
        } catch (Throwable $e) {
            Log::warning('platform.trial.nurture: mail send failed', [
                'company_id' => $company->id,
                'stage'      => $stage,
                'error'      => $e->getMessage(),
            ]);
            return false;
        }

        // JSON'a kaydet
        $sent = $company->nurture_emails_sent ?? [];
        if (!is_array($sent)) {
            $sent = [];
        }
        $sent[] = [
            'stage'   => $stage,
            'sent_at' => CarbonImmutable::now()->toIso8601String(),
        ];
        $company->nurture_emails_sent = $sent;
        $company->save();

        return true;
    }

    // ────────────────────────────────────────────────────────────────────────
    // INTERNAL
    // ────────────────────────────────────────────────────────────────────────

    /** Bu company icin bugune kadar gonderilmis nurture stage anahtarlari. */
    private function getSentNurtureStages(Company $company): array
    {
        $raw = $company->nurture_emails_sent ?? [];
        if (!is_array($raw)) {
            return [];
        }
        return array_values(array_filter(array_map(
            fn ($r) => is_array($r) ? ($r['stage'] ?? null) : null,
            $raw
        )));
    }

    /**
     * Bir company icin manager rolundeki ilk kullanicinin e-postasini bulur.
     * Yoksa billing_email'e geri duser.
     */
    private function resolveManagerEmail(Company $company): ?string
    {
        // users.role = 'manager' varsayimi — projede manager rolu
        $manager = DB::table('users')
            ->where('company_id', $company->id)
            ->where('role', 'manager')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->first();

        if ($manager && !empty($manager->email)) {
            return $manager->email;
        }

        $billing = trim((string) ($company->billing_email ?? ''));
        return $billing !== '' ? $billing : null;
    }

    /**
     * Stage'e gore nurture mail govdesi. Plain text (Mail::raw) — sade tutuyoruz,
     * design'a bagimli degil. {company} ve URL placeholder'lari coker.
     */
    private function buildNurtureBody(Company $company, string $stage): string
    {
        $appName = config('app.name', 'MentorDE');
        $url     = rtrim((string) config('app.url', 'https://panel.mentorde.com'), '/');
        $name    = $company->name ?: 'Sayin yetkili';

        return match ($stage) {
            'day3' => <<<TXT
Merhaba {$name},

{$appName}'ye hos geldin! Trial donemin basladi ve seninle bazi ilk-adim
ipuclarini paylasmak istiyoruz:

1) Manager panelinden ilk Aday Ogrenci'ni ekle ve karsilama mailini kontrol et.
2) Sablonlu sozlesme & belge taleplerini dene — bunlar size en cok zamani
   kazandiracak iki ozellik.
3) /sss sayfasi ile Trial sirasinda en cok sorulanlara cevap bul.

Trial paketinde Core + Application Guides modulleri acik. Sorularin icin
yanitla — birebir destek veriyoruz.

Sevgiler,
{$appName} Ekibi
{$url}
TXT,

            'day7' => <<<TXT
Merhaba {$name},

Trial doneminin yarisindasin. Su ana kadar bircok manager'in en cok kullandigi
ozellikler:

- Belge talep linki (token bazli upload)
- Aday Ogrenci -> Student donusum akisi
- Sozlesme sablonu ile e-imza akisi

Sana ekstra ipucu: Booking ve Content Hub gibi Gold seviyesindeki
moduller hangi durumlar icin ise yariyor? /platform-tour adresinde 5 dk'lik
video tur var.

Trial bitiminden once tier yukseltmeyi degerlendirebilirsin — ihtiyacina
gore birlikte bakalim.

Sevgiler,
{$appName} Ekibi
{$url}
TXT,

            'day14' => <<<TXT
Merhaba {$name},

Trial'inin bitmesine cok az kaldi. Eger {$appName} ekibimize fayda
sagliyorsa, simdi yukseltmek en mantiklisi:

- Basic (49€/ay): Core + Belge talep modulu, kucuk ofisler icin
- Gold (149€/ay): Booking, Content Hub, AI Labs, Dealer agi
- Premium (299€/ay): Tum moduller + sinirsiz kullanim

Yukseltmek icin manager panelinden faturalama bilgilerini kontrol et veya
bu maile yanit ver — sana ozel bir gecis plani hazirlayabiliriz.

Sevgiler,
{$appName} Ekibi
{$url}
TXT,

            'day17' => <<<TXT
Merhaba {$name},

Trial donemin bitti ama biz hala buradayiz. Eski kullaniminiza geri donmek
mumkun — ayni tier'a abone olarak verilerinize ve ayarlarinize kaldigin
yerden devam edebilirsin.

Sana ozel: yeniden basla butonuna basinca ilk fatura uzerinden %15 indirim
kodu (geri_don15) hazir. Detay icin yanitla.

Sevgiler,
{$appName} Ekibi
{$url}
TXT,

            default => "Merhaba {$name},\n\n{$appName} ile yolculugun nasil gidiyor?\n\n{$url}",
        };
    }
}
