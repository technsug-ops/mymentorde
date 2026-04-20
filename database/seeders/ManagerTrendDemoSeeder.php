<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Demo veri: Manager Dashboard'daki "Gelir & Approval Trendi" chart'ını doldurur.
 *
 * 12 ay geriye dönük, student_revenues + field_rule_approvals tablolarına
 * realistic-ish veri üretir. Grafik bar (gelir EUR) + line (approval sayısı).
 *
 * Idempotent: daha önce eklenen demo kayıtlar (student_id LIKE 'STU-DEMO-TREND-%')
 * önce temizlenir, sonra tekrar eklenir. Gerçek kayıtlara dokunmaz.
 *
 * Çalıştır: php artisan db:seed --class=ManagerTrendDemoSeeder
 */
class ManagerTrendDemoSeeder extends Seeder
{
    public function run(): void
    {
        $prefix = 'STU-DEMO-TREND-';

        // Önce önceki demo kayıtları temizle (tekrar çalıştırılabilsin).
        DB::table('student_revenues')->where('student_id', 'like', $prefix . '%')->delete();
        DB::table('field_rule_approvals')->where('student_id', 'like', $prefix . '%')->delete();

        $now   = Carbon::now()->startOfMonth();
        $start = $now->copy()->subMonths(11); // son 12 ay (current dahil)

        // Realistic büyüme trendi: 1500 → 9500 EUR arası, pending %10-25 civarı.
        $revenueCurve = [1500, 2100, 2800, 3400, 3900, 4800, 5600, 6300, 7200, 7900, 8600, 9500];
        $approvalCurve = [3, 5, 6, 8, 7, 10, 12, 11, 14, 13, 16, 18];

        $revenueRows = [];
        $approvalRows = [];

        $month = $start->copy();
        for ($i = 0; $i < 12; $i++) {
            $monthMid = $month->copy()->addDays(14)->setTime(12, 0); // ayın ortası
            $studentId = $prefix . $month->format('Ym');

            $earned    = (float) ($revenueCurve[$i] ?? 5000);
            $pending   = round($earned * 0.18, 2);
            $totalPkg  = round($earned + $pending, 2);

            $revenueRows[] = [
                'student_id'          => $studentId,
                'package_id'          => null,
                'package_total_price' => $totalPkg,
                'package_currency'    => 'EUR',
                'milestone_progress'  => json_encode(['m1' => 'done', 'm2' => 'in_progress']),
                'total_earned'        => $earned,
                'total_pending'       => $pending,
                'total_remaining'     => 0,
                'created_at'          => $monthMid,
                'updated_at'          => $monthMid,
            ];

            // Approval'lar — o ay içinde dağıtılmış
            $approvalCount = (int) ($approvalCurve[$i] ?? 5);
            for ($j = 0; $j < $approvalCount; $j++) {
                $dayOffset = (int) floor(($j / max(1, $approvalCount)) * 27);
                $apAt = $month->copy()->addDays($dayOffset)->setTime(rand(9, 17), rand(0, 59));
                $approvalRows[] = [
                    'rule_id'          => 1, // FK bypass edilecek
                    'student_id'       => $studentId,
                    'guest_id'         => null,
                    'triggered_field'  => 'demo_field',
                    'triggered_value'  => json_encode(['demo' => true]),
                    'severity'         => 'info',
                    'status'           => 'approved',
                    'approved_by'      => 'demo@seeder',
                    'approved_at'      => $apAt,
                    'rejection_reason' => null,
                    'created_at'       => $apAt,
                    'updated_at'       => $apAt,
                ];
            }

            $month->addMonth();
        }

        // Insert (MySQL FK checks off for approval inserts — rule_id=1 gerçek bir rule olmayabilir)
        DB::table('student_revenues')->insert($revenueRows);

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('field_rule_approvals')->insert($approvalRows);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->command?->info('Manager Trend demo verisi yüklendi: ' . count($revenueRows) . ' revenue, ' . count($approvalRows) . ' approval.');

        // Trend cache invalidate — hemen görünsün
        try {
            \Illuminate\Support\Facades\Cache::flush();
            $this->command?->info('Cache flush: OK');
        } catch (\Throwable $e) {
            $this->command?->warn('Cache flush başarısız: ' . $e->getMessage());
        }
    }
}
