<?php

namespace Database\Seeders;

use App\Models\CompanyFinanceEntry;
use App\Models\GuestApplication;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * FinanceDemoSeeder — 12 aylık gerçekçi gelir/gider verisi
 *
 * Giderler: Maaş, kira, yazılım, pazarlama, vergi, seyahat, banka, diğer
 * Gelirler: Öğrenci sözleşmeleri (signed/approved/requested/cancelled)
 *           + manuel gelirler (danışmanlık, komisyon vb.)
 *
 * İdempotent: DEMO tag'li kayıtları ve demo sözleşmeleri siler, yeniden oluşturur.
 */
class FinanceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $manager = User::where('role', 'manager')->first();
        if (!$manager) {
            $this->command->warn('Manager kullanıcısı bulunamadı, seeder atlandı.');
            return;
        }

        $cid       = (int) ($manager->company_id ?? 1);
        $createdBy = $manager->id;

        // ── Temizlik ──────────────────────────────────────────────────────────
        CompanyFinanceEntry::where('notes', 'like', '%[DEMO]%')->delete();
        GuestApplication::where('notes', 'like', '%[DEMO_FINANCE]%')->forceDelete();

        $this->command->info('Demo finans verisi oluşturuluyor...');

        // ── 1. Aylık Giderler (son 12 ay) ─────────────────────────────────────
        $expenseTemplates = [
            // [kategori, başlık, min, max, gün, her_ay_mı]
            ['salary',       'Personel Maaşları (5 çalışan)',      14_000, 16_500, 28, true],
            ['salary',       'SGK & Sosyal Giderler',               2_800,  3_200, 28, true],
            ['rent',         'Ofis Kirası — Koşuyolu, İstanbul',    1_800,  1_800,  1, true],
            ['rent',         'Depo & Arşiv Kirası',                   350,    350,  1, true],
            ['software',     'CRM & ERP Lisansları (SaaS)',           480,    520,  5, true],
            ['software',     'Google Workspace + Slack + Notion',     120,    140,  5, true],
            ['software',     'Hosting & Domain Yenileme',             180,    250, 10, false], // her ay değil
            ['marketing',    'Google & Meta Reklam Bütçesi',          800,  2_200, 15, true],
            ['marketing',    'İçerik & Sosyal Medya Ajansı',          600,    900, 10, true],
            ['marketing',    'Fuar & Etkinlik Katılımı',              500,  1_500, 20, false],
            ['travel',       'Uçak & Konaklama (iş gezileri)',        400,  1_200, 18, false],
            ['travel',       'Yurt İçi Ulaşım & Taksi',               80,    200, 25, true],
            ['tax',          'KDV Ödemesi',                         1_200,  2_800,  3, true],
            ['tax',          'Stopaj Vergisi',                         400,    800,  3, true],
            ['bank_fee',     'Banka Komisyon & Transfer Ücretleri',    40,    120, 20, true],
            ['consulting',   'Mali Müşavirlik Ücreti',                 600,    600, 15, true],
            ['other_expense','Ofis Malzemeleri & Sarf',                80,    180, 12, true],
            ['other_expense','Eğitim & Kurs Giderleri',               200,    500, 22, false],
        ];

        $now     = now();
        $entries = [];

        for ($i = 11; $i >= 0; $i--) {
            $monthDate = $now->copy()->subMonths($i);
            $yearNum   = (int) $monthDate->format('Y');
            $monthNum  = (int) $monthDate->format('m');
            $daysInMonth = $monthDate->daysInMonth;

            foreach ($expenseTemplates as [$cat, $title, $min, $max, $day, $everyMonth]) {
                // %70 ihtimalle atla eğer her_ay_mı = false
                if (!$everyMonth && mt_rand(0, 9) < 3) continue;

                $amount = mt_rand((int)($min * 100), (int)($max * 100)) / 100;
                $safeDay = min($day, $daysInMonth);
                $date = Carbon::create($yearNum, $monthNum, $safeDay);

                // Gelecek tarih oluşturma
                if ($date->isFuture()) continue;

                $entries[] = [
                    'company_id'  => $cid,
                    'entry_date'  => $date->format('Y-m-d'),
                    'type'        => 'expense',
                    'category'    => $cat,
                    'title'       => $title,
                    'amount'      => round($amount, 2),
                    'currency'    => 'EUR',
                    'source'      => 'manual',
                    'created_by'  => $createdBy,
                    'notes'       => '[DEMO] Otomatik oluşturuldu',
                    'created_at'  => $date,
                    'updated_at'  => $date,
                ];
            }

            // Manuel gelirler (danışmanlık, komisyon - bazı aylarda)
            if (mt_rand(0, 2) === 0) {
                $date = Carbon::create($yearNum, $monthNum, min(20, $daysInMonth));
                if (!$date->isFuture()) {
                    $entries[] = [
                        'company_id' => $cid,
                        'entry_date' => $date->format('Y-m-d'),
                        'type'       => 'income',
                        'category'   => 'consulting',
                        'title'      => 'Danışmanlık Geliri — Kurumsal',
                        'amount'     => round(mt_rand(1_500_00, 4_000_00) / 100, 2),
                        'currency'   => 'EUR',
                        'source'     => 'manual',
                        'created_by' => $createdBy,
                        'notes'      => '[DEMO] Otomatik oluşturuldu',
                        'created_at' => $date,
                        'updated_at' => $date,
                    ];
                }
            }
        }

        // Toplu insert
        foreach (array_chunk($entries, 100) as $chunk) {
            DB::table('company_finance_entries')->insert($chunk);
        }
        $this->command->info(count($entries) . ' gider/manuel gelir kaydı oluşturuldu.');

        // ── 2. Öğrenci Sözleşmeleri (guest_applications) ─────────────────────
        $packages = [
            ['code' => 'pkg_starter', 'title' => 'Başlangıç Paket', 'price' => 1_490.00],
            ['code' => 'pkg_plus',    'title' => 'Plus Paket',       'price' => 2_490.00],
            ['code' => 'pkg_pro',     'title' => 'Pro Paket',        'price' => 3_490.00],
            ['code' => 'pkg_premium', 'title' => 'Premium Paket',    'price' => 4_990.00],
        ];

        $studentNames = [
            ['Ahmet', 'Yılmaz'],   ['Fatma', 'Kaya'],    ['Mehmet', 'Demir'],
            ['Ayşe', 'Şahin'],     ['Mustafa', 'Arslan'], ['Zeynep', 'Çelik'],
            ['Hüseyin', 'Aydın'],  ['Elif', 'Yıldız'],   ['İbrahim', 'Koç'],
            ['Hülya', 'Öztürk'],   ['Oğuz', 'Çetin'],    ['Merve', 'Doğan'],
            ['Sercan', 'Kılıç'],   ['Büşra', 'Aslan'],   ['Enes', 'Kurt'],
            ['Seda', 'Özdemir'],   ['Berk', 'Erdoğan'],  ['Cansu', 'Acar'],
            ['Tolga', 'Polat'],    ['Deniz', 'Güneş'],   ['Pınar', 'Taş'],
            ['Emre', 'Çelik'],     ['Seçil', 'Bozkurt'], ['Utku', 'Korkmaz'],
        ];

        // Durum dağılımı: 16 imzalı, 4 bekleyen, 2 iptal, 2 talep edildi
        $statusPool = array_merge(
            array_fill(0, 10, 'signed'),
            array_fill(0, 6,  'approved'),
            array_fill(0, 4,  'requested'),
            array_fill(0, 2,  'cancelled'),
            array_fill(0, 2,  'not_requested')
        );
        shuffle($statusPool);

        // Mevcut en düşük guest_user_id için bir placeholder user
        $guestUser = User::where('role', 'like', '%guest%')
            ->orWhere('role', 'student')
            ->first();
        $guestUserId = $guestUser?->id ?? $createdBy;

        foreach ($studentNames as $idx => [$first, $last]) {
            $pkg    = $packages[$idx % count($packages)];
            $status = $statusPool[$idx] ?? 'signed';

            // İmza tarihi: son 12 ay içinde dağıtılmış
            $monthsAgo = mt_rand(0, 11);
            $signedAt  = now()->subMonths($monthsAgo)->subDays(mt_rand(0, 25));
            if ($signedAt->isFuture()) $signedAt = now()->subDays(3);

            $isSignedStatus = in_array($status, ['signed', 'approved']);

            GuestApplication::create([
                'guest_user_id'          => $guestUserId,
                'tracking_token'         => \Illuminate\Support\Str::uuid(),
                'application_type'       => 'standard',
                'first_name'             => $first,
                'last_name'              => $last,
                'email'                  => strtolower($first) . '.' . strtolower($last) . '.demo' . $idx . '@example.com',
                'phone'                  => '+90 5' . mt_rand(00, 99) . ' ' . mt_rand(100, 999) . ' ' . mt_rand(10, 99) . ' ' . mt_rand(10, 99),
                'company_id'             => $cid,
                'selected_package_code'  => $pkg['code'],
                'selected_package_title' => $pkg['title'],
                'selected_package_price' => number_format($pkg['price'], 2, ',', '.') . ' EUR',
                'contract_amount_eur'    => $pkg['price'],
                'contract_status'        => $status,
                'contract_signed_at'     => $isSignedStatus ? $signedAt : null,
                'contract_approved_at'   => $status === 'approved' ? $signedAt->copy()->addDays(2) : null,
                'contract_requested_at'  => in_array($status, ['signed','approved','requested','cancelled'])
                    ? $signedAt->copy()->subDays(3)
                    : null,
                'contract_cancelled_at'  => $status === 'cancelled' ? $signedAt->copy()->addDays(5) : null,
                'lead_status'            => 'converted',
                'converted_to_student'   => false,
                'notes'                  => '[DEMO_FINANCE] Demo sözleşme kaydı',
                'kvkk_consent'           => true,
            ]);
        }

        $total = GuestApplication::where('notes', 'like', '%[DEMO_FINANCE]%')->count();
        $this->command->info($total . ' demo sözleşme kaydı oluşturuldu.');

        // Özet
        $signed   = GuestApplication::whereIn('contract_status',['signed','approved'])->whereNotNull('contract_amount_eur')->sum('contract_amount_eur');
        $pending  = GuestApplication::where('contract_status','requested')->whereNotNull('contract_amount_eur')->sum('contract_amount_eur');
        $this->command->info(sprintf(
            'Tahsilat: %.0f EUR onaylı, %.0f EUR bekleyen', $signed, $pending
        ));
    }
}
