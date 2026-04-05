<?php

namespace Database\Seeders;

use App\Models\GuestApplication;
use App\Models\GuestTicket;
use App\Models\Hr\HrAttendance;
use App\Models\Hr\HrCertification;
use App\Models\Hr\HrLeaveRequest;
use App\Models\Hr\HrPersonProfile;
use App\Models\MarketingTask;
use App\Models\StaffKpiTarget;
use App\Models\TaskTimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class HrDemoSeeder extends Seeder
{
    private int $companyId = 1;

    public function run(): void
    {
        $this->command->info('HR Demo Seeder başlatılıyor...');

        // ── 1. Demo çalışanları oluştur ──────────────────────────────────────
        $employees = $this->createEmployees();

        // ── 2. HrPersonProfile ───────────────────────────────────────────────
        $this->createProfiles($employees);

        // ── 3. İzin Talepleri ────────────────────────────────────────────────
        $this->createLeaves($employees);

        // ── 4. Sertifikalar ──────────────────────────────────────────────────
        $this->createCertifications($employees);

        // ── 5. KPI Hedefleri ─────────────────────────────────────────────────
        $this->createKpiTargets($employees);

        // ── 6. KPI Gerçekleşenler (Tasks + Tickets + Hours) ──────────────────
        $this->createKpiActuals($employees);

        // ── 7. Devam Kayıtları (son 30 gün) ──────────────────────────────────
        $this->createAttendance($employees);

        $this->command->info('HR Demo Seeder tamamlandı! ' . count($employees) . ' çalışan işlendi.');
    }

    // ── Çalışan Tanımları ─────────────────────────────────────────────────────
    private function employeeDefs(): array
    {
        return [
            // Performans: Yıldız (score ~95)
            [
                'name' => 'Ayşe Kaya',        'email' => 'ayse.kaya@mentorde.demo',
                'role' => 'marketing_staff',   'position' => 'Dijital Pazarlama Uzmanı',
                'hire_months_ago' => 18,       'quota' => 20,
                'perf' => 'star',              'cert_profile' => 'full',
                'leave_profile' => 'healthy',
            ],
            // Performans: İyi (score ~78)
            [
                'name' => 'Mehmet Yılmaz',    'email' => 'mehmet.yilmaz@mentorde.demo',
                'role' => 'operations_staff',  'position' => 'Operasyon Koordinatörü',
                'hire_months_ago' => 24,       'quota' => 18,
                'perf' => 'good',              'cert_profile' => 'partial',
                'leave_profile' => 'moderate',
            ],
            // Performans: Orta (score ~58)
            [
                'name' => 'Fatma Çelik',      'email' => 'fatma.celik@mentorde.demo',
                'role' => 'finance_staff',     'position' => 'Finans Analisti',
                'hire_months_ago' => 10,       'quota' => 14,
                'perf' => 'medium',            'cert_profile' => 'expiring',
                'leave_profile' => 'high_usage',
            ],
            // Performans: Riskli (score ~38)
            [
                'name' => 'Emre Demir',        'email' => 'emre.demir@mentorde.demo',
                'role' => 'sales_staff',       'position' => 'Satış Temsilcisi',
                'hire_months_ago' => 6,        'quota' => 14,
                'perf' => 'risk',              'cert_profile' => 'expired',
                'leave_profile' => 'pending',
            ],
            // Performans: Düşük (score ~15)
            [
                'name' => 'Selin Arslan',      'email' => 'selin.arslan@mentorde.demo',
                'role' => 'system_staff',      'position' => 'Sistem Destek Uzmanı',
                'hire_months_ago' => 3,        'quota' => 14,
                'perf' => 'low',               'cert_profile' => 'none',
                'leave_profile' => 'rejected',
            ],
            // Performans: Hedef yok (actuals var ama target set edilmemiş)
            [
                'name' => 'Haluk Şahin',       'email' => 'haluk.sahin@mentorde.demo',
                'role' => 'operations_admin',  'position' => 'Operasyon Yöneticisi',
                'hire_months_ago' => 36,       'quota' => 20,
                'perf' => 'no_target',         'cert_profile' => 'full',
                'leave_profile' => 'healthy',
            ],
            // Admin roller (hedef + sertifika zengin profil)
            [
                'name' => 'Zeynep Öztürk',    'email' => 'zeynep.ozturk@mentorde.demo',
                'role' => 'finance_admin',     'position' => 'Finans Yöneticisi',
                'hire_months_ago' => 30,       'quota' => 20,
                'perf' => 'star',              'cert_profile' => 'full',
                'leave_profile' => 'moderate',
            ],
            [
                'name' => 'Can Koçak',         'email' => 'can.kocak@mentorde.demo',
                'role' => 'marketing_admin',   'position' => 'Pazarlama Müdürü',
                'hire_months_ago' => 20,       'quota' => 20,
                'perf' => 'good',              'cert_profile' => 'partial',
                'leave_profile' => 'healthy',
            ],
            [
                'name' => 'Nilüfer Aydın',    'email' => 'nilufer.aydin@mentorde.demo',
                'role' => 'sales_admin',       'position' => 'Satış Müdürü',
                'hire_months_ago' => 28,       'quota' => 20,
                'perf' => 'medium',            'cert_profile' => 'expiring',
                'leave_profile' => 'moderate',
            ],
            [
                'name' => 'Burak Erdoğan',    'email' => 'burak.erdogan@mentorde.demo',
                'role' => 'system_admin',      'position' => 'Sistem Yöneticisi',
                'hire_months_ago' => 15,       'quota' => 16,
                'perf' => 'good',              'cert_profile' => 'full',
                'leave_profile' => 'healthy',
            ],
            // 2. tier staff (farklı departmanlar)
            [
                'name' => 'Merve Doğan',       'email' => 'merve.dogan@mentorde.demo',
                'role' => 'marketing_staff',   'position' => 'İçerik Editörü',
                'hire_months_ago' => 8,        'quota' => 14,
                'perf' => 'good',              'cert_profile' => 'partial',
                'leave_profile' => 'moderate',
            ],
            [
                'name' => 'Tarık Yücel',       'email' => 'tarik.yucel@mentorde.demo',
                'role' => 'operations_staff',  'position' => 'Süreç Analisti',
                'hire_months_ago' => 12,       'quota' => 14,
                'perf' => 'risk',              'cert_profile' => 'expired',
                'leave_profile' => 'high_usage',
            ],
        ];
    }

    private function createEmployees(): array
    {
        $defs = $this->employeeDefs();
        $result = [];
        $password = Hash::make('Demo1234!');

        foreach ($defs as $def) {
            $user = User::updateOrCreate(
                ['email' => $def['email']],
                [
                    'name'       => $def['name'],
                    'role'       => $def['role'],
                    'password'   => $password,
                    'company_id' => $this->companyId,
                    'is_active'  => true,
                ]
            );
            $result[] = array_merge($def, ['user' => $user]);
        }

        $this->command->info(count($result) . ' demo çalışan oluşturuldu/güncellendi.');
        return $result;
    }

    // ── HR Profilleri ─────────────────────────────────────────────────────────
    private function createProfiles(array $employees): void
    {
        $emergencyNames = ['Ali Kaya', 'Fatma Yılmaz', 'Mehmet Çelik', 'Ayşe Demir', 'Hasan Arslan'];
        foreach ($employees as $i => $emp) {
            HrPersonProfile::updateOrCreate(
                ['user_id' => $emp['user']->id],
                [
                    'company_id'              => $this->companyId,
                    'hire_date'               => now()->subMonths($emp['hire_months_ago'])->startOfMonth(),
                    'position_title'          => $emp['position'],
                    'phone'                   => '+90 5' . rand(30, 59) . ' ' . rand(100, 999) . ' ' . rand(1000, 9999),
                    'emergency_contact_name'  => $emergencyNames[$i % count($emergencyNames)],
                    'emergency_contact_phone' => '+90 5' . rand(30, 59) . ' ' . rand(100, 999) . ' ' . rand(1000, 9999),
                    'annual_leave_quota'      => $emp['quota'],
                    'notes'                   => $this->profileNote($emp['perf']),
                ]
            );
        }
        $this->command->info('HR profilleri oluşturuldu.');
    }

    private function profileNote(string $perf): string
    {
        return match($perf) {
            'star'      => 'Ekibin en yüksek performanslı çalışanı. Mentoring programına dahil.',
            'good'      => 'Tutarlı performans. Hedeflerini genellikle karşılıyor.',
            'medium'    => 'Orta düzey performans. Gelişim planı hazırlandı.',
            'risk'      => 'Performans takip altında. Aylık 1-on-1 toplantı yapılıyor.',
            'low'       => 'Yeni başladı, adaptasyon süreci devam ediyor.',
            'no_target' => 'Yönetici pozisyonu. KPI hedefleri farklı kriterlerle değerlendiriliyor.',
            default     => '',
        };
    }

    // ── İzin Talepleri ────────────────────────────────────────────────────────
    private function createLeaves(array $employees): void
    {
        $managerId = User::where('email', 'manager@mentorde.local')->value('id') ?? 1;

        foreach ($employees as $emp) {
            $uid = $emp['user']->id;
            // Önce mevcut izinleri temizle (idempotent)
            HrLeaveRequest::where('user_id', $uid)->delete();

            match($emp['leave_profile']) {
                'healthy'    => $this->leavesHealthy($uid, $managerId),
                'moderate'   => $this->leavesModerate($uid, $managerId),
                'high_usage' => $this->leavesHighUsage($uid, $managerId),
                'pending'    => $this->leavesPending($uid),
                'rejected'   => $this->leavesRejected($uid, $managerId),
                default      => null,
            };
        }
        $this->command->info('İzin kayıtları oluşturuldu.');
    }

    private function leavesHealthy(int $uid, int $managerId): void
    {
        // 1 geçmiş onaylı yıllık izin, 1 yaklaşan onaylı izin
        HrLeaveRequest::create([
            'company_id'  => $this->companyId, 'user_id' => $uid,
            'leave_type'  => 'annual',
            'start_date'  => now()->subMonths(3)->startOfMonth()->addDays(14),
            'end_date'    => now()->subMonths(3)->startOfMonth()->addDays(18),
            'days_count'  => 5, 'status' => 'approved',
            'approved_by' => $managerId, 'approved_at' => now()->subMonths(3)->addDays(10),
        ]);
        HrLeaveRequest::create([
            'company_id'  => $this->companyId, 'user_id' => $uid,
            'leave_type'  => 'annual',
            'start_date'  => now()->addDays(15),
            'end_date'    => now()->addDays(19),
            'days_count'  => 5, 'status' => 'approved',
            'approved_by' => $managerId, 'approved_at' => now()->subDays(5),
        ]);
    }

    private function leavesModerate(int $uid, int $managerId): void
    {
        // Geçmiş hastalık + onaylı yıllık
        HrLeaveRequest::create([
            'company_id'  => $this->companyId, 'user_id' => $uid,
            'leave_type'  => 'sick',
            'start_date'  => now()->subMonths(2)->addDays(5),
            'end_date'    => now()->subMonths(2)->addDays(7),
            'days_count'  => 3, 'status' => 'approved',
            'approved_by' => $managerId, 'approved_at' => now()->subMonths(2)->addDays(4),
        ]);
        HrLeaveRequest::create([
            'company_id'  => $this->companyId, 'user_id' => $uid,
            'leave_type'  => 'annual',
            'start_date'  => now()->subMonths(1)->startOfMonth()->addDays(6),
            'end_date'    => now()->subMonths(1)->startOfMonth()->addDays(10),
            'days_count'  => 5, 'status' => 'approved',
            'approved_by' => $managerId, 'approved_at' => now()->subMonths(1)->subDays(3),
        ]);
        // Bekleyen mazeret
        HrLeaveRequest::create([
            'company_id' => $this->companyId, 'user_id' => $uid,
            'leave_type' => 'personal',
            'start_date' => now()->addDays(8),
            'end_date'   => now()->addDays(9),
            'days_count' => 2, 'status' => 'pending',
        ]);
    }

    private function leavesHighUsage(int $uid, int $managerId): void
    {
        // Çok fazla izin kullandı (kotasının büyük kısmı bitti)
        foreach ([5, 4, 3, 2] as $m) {
            HrLeaveRequest::create([
                'company_id'  => $this->companyId, 'user_id' => $uid,
                'leave_type'  => 'annual',
                'start_date'  => now()->subMonths($m)->startOfMonth()->addDays(10),
                'end_date'    => now()->subMonths($m)->startOfMonth()->addDays(12),
                'days_count'  => 3, 'status' => 'approved',
                'approved_by' => $managerId, 'approved_at' => now()->subMonths($m)->addDays(8),
            ]);
        }
        HrLeaveRequest::create([
            'company_id'  => $this->companyId, 'user_id' => $uid,
            'leave_type'  => 'sick',
            'start_date'  => now()->subMonths(1)->addDays(14),
            'end_date'    => now()->subMonths(1)->addDays(15),
            'days_count'  => 2, 'status' => 'approved',
            'approved_by' => $managerId, 'approved_at' => now()->subMonths(1)->addDays(13),
        ]);
    }

    private function leavesPending(int $uid): void
    {
        HrLeaveRequest::create([
            'company_id' => $this->companyId, 'user_id' => $uid,
            'leave_type' => 'annual',
            'start_date' => now()->addDays(5),
            'end_date'   => now()->addDays(9),
            'days_count' => 5, 'status' => 'pending',
            'reason'     => 'Aile ziyareti — önceden planlandı.',
        ]);
        HrLeaveRequest::create([
            'company_id' => $this->companyId, 'user_id' => $uid,
            'leave_type' => 'personal',
            'start_date' => now()->addDays(20),
            'end_date'   => now()->addDays(20),
            'days_count' => 1, 'status' => 'pending',
        ]);
    }

    private function leavesRejected(int $uid, int $managerId): void
    {
        HrLeaveRequest::create([
            'company_id'     => $this->companyId, 'user_id' => $uid,
            'leave_type'     => 'annual',
            'start_date'     => now()->subDays(20),
            'end_date'       => now()->subDays(16),
            'days_count'     => 5, 'status' => 'rejected',
            'rejection_note' => 'Yoğun dönem — tarih değiştirilmesi istendi.',
            'approved_by'    => $managerId, 'approved_at' => now()->subDays(22),
        ]);
        // Yeniden onaylı talep
        HrLeaveRequest::create([
            'company_id'  => $this->companyId, 'user_id' => $uid,
            'leave_type'  => 'annual',
            'start_date'  => now()->addDays(25),
            'end_date'    => now()->addDays(29),
            'days_count'  => 5, 'status' => 'pending',
            'reason'      => 'Yeniden planlandı.',
        ]);
    }

    // ── Sertifikalar ──────────────────────────────────────────────────────────
    private function createCertifications(array $employees): void
    {
        $certBank = [
            ['cert_name' => 'Google Ads Sertifikası',          'issuer' => 'Google'],
            ['cert_name' => 'HubSpot Inbound Marketing',       'issuer' => 'HubSpot'],
            ['cert_name' => 'Meta Blueprint',                  'issuer' => 'Meta'],
            ['cert_name' => 'Scrum Master (PSM I)',            'issuer' => 'Scrum.org'],
            ['cert_name' => 'AWS Cloud Practitioner',          'issuer' => 'Amazon Web Services'],
            ['cert_name' => 'Google Analytics 4',              'issuer' => 'Google'],
            ['cert_name' => 'IELTS — C1',                      'issuer' => 'British Council'],
            ['cert_name' => 'Türkçe İş Hukuku Sertifikası',   'issuer' => 'Bar Birliği'],
            ['cert_name' => 'Excel İleri Düzey',               'issuer' => 'Microsoft'],
            ['cert_name' => 'Proje Yönetimi (PMP)',            'issuer' => 'PMI'],
            ['cert_name' => 'ISO 27001 Lead Auditor',          'issuer' => 'BSI'],
            ['cert_name' => 'Veri Koruma (GDPR)',              'issuer' => 'CIPP/E'],
        ];

        foreach ($employees as $emp) {
            $uid = $emp['user']->id;
            HrCertification::where('user_id', $uid)->delete();

            match($emp['cert_profile']) {
                'full'     => $this->certsActive($uid, $certBank, 3),
                'partial'  => $this->certsActive($uid, $certBank, 1),
                'expiring' => $this->certsExpiringSoon($uid, $certBank),
                'expired'  => $this->certsExpired($uid, $certBank),
                'none'     => null,
                default    => null,
            };
        }
        $this->command->info('Sertifika kayıtları oluşturuldu.');
    }

    private function certsActive(int $uid, array $bank, int $count): void
    {
        shuffle($bank);
        foreach (array_slice($bank, 0, $count) as $c) {
            HrCertification::create([
                'company_id'  => $this->companyId, 'user_id' => $uid,
                'cert_name'   => $c['cert_name'], 'issuer' => $c['issuer'],
                'issue_date'  => now()->subMonths(rand(6, 18)),
                'expiry_date' => now()->addMonths(rand(8, 24)),
            ]);
        }
    }

    private function certsExpiringSoon(int $uid, array $bank): void
    {
        HrCertification::create([
            'company_id'  => $this->companyId, 'user_id' => $uid,
            'cert_name'   => 'Google Ads Sertifikası', 'issuer' => 'Google',
            'issue_date'  => now()->subMonths(11),
            'expiry_date' => now()->addDays(rand(8, 25)),  // yakında bitiyor
        ]);
        HrCertification::create([
            'company_id'  => $this->companyId, 'user_id' => $uid,
            'cert_name'   => 'Scrum Master (PSM I)', 'issuer' => 'Scrum.org',
            'issue_date'  => now()->subMonths(24),
            'expiry_date' => now()->addMonths(5),
        ]);
    }

    private function certsExpired(int $uid, array $bank): void
    {
        HrCertification::create([
            'company_id'  => $this->companyId, 'user_id' => $uid,
            'cert_name'   => 'HubSpot Inbound Marketing', 'issuer' => 'HubSpot',
            'issue_date'  => now()->subMonths(14),
            'expiry_date' => now()->subDays(rand(15, 60)),  // geçmiş
        ]);
        HrCertification::create([
            'company_id'  => $this->companyId, 'user_id' => $uid,
            'cert_name'   => 'Meta Blueprint', 'issuer' => 'Meta',
            'issue_date'  => now()->subMonths(18),
            'expiry_date' => now()->subMonths(2),
        ]);
    }

    // ── KPI Hedefleri ─────────────────────────────────────────────────────────
    private function kpiMap(): array
    {
        // perf_key => [target_tasks, target_tickets, target_hours, tasks_actual, tickets_actual, hours_actual]
        return [
            'star'      => [20, 10, 160,  20, 11, 172],
            'good'      => [20, 10, 160,  15,  8, 135],
            'medium'    => [20, 10, 160,  10,  5,  92],
            'risk'      => [15,  8, 120,   5,  2,  48],
            'low'       => [15,  8, 120,   2,  0,  18],
            'no_target' => [0,   0,   0,   9,  4,  78], // target yok
        ];
    }

    private function createKpiTargets(array $employees): void
    {
        $managerId = User::where('email', 'manager@mentorde.local')->value('id') ?? 1;
        $map = $this->kpiMap();
        $periods = [
            now()->format('Y-m'),
            now()->subMonth()->format('Y-m'),
            now()->subMonths(2)->format('Y-m'),
        ];

        foreach ($employees as $emp) {
            if ($emp['perf'] === 'no_target') continue;
            [$tTask, $tTicket, $tHours] = array_slice($map[$emp['perf']], 0, 3);

            foreach ($periods as $period) {
                StaffKpiTarget::updateOrCreate(
                    ['user_id' => $emp['user']->id, 'period' => $period],
                    [
                        'company_id'              => $this->companyId,
                        'target_tasks_done'       => $tTask,
                        'target_tickets_resolved' => $tTicket,
                        'target_hours_logged'     => $tHours,
                        'set_by_user_id'          => $managerId,
                    ]
                );
            }
        }
        $this->command->info('KPI hedefleri oluşturuldu.');
    }

    // ── KPI Gerçekleşenler ────────────────────────────────────────────────────
    private function createKpiActuals(array $employees): void
    {
        $map      = $this->kpiMap();
        $guestApp = GuestApplication::first();
        $periods  = [
            now()->format('Y-m'),
            now()->subMonth()->format('Y-m'),
            now()->subMonths(2)->format('Y-m'),
        ];

        foreach ($employees as $emp) {
            $uid  = $emp['user']->id;
            $perf = $emp['perf'];
            [, , , $aTasks, $aTickets, $aHours] = $map[$perf];

            // Önceki demo verilerini temizle
            MarketingTask::where('assigned_user_id', $uid)->where('title', 'like', '[DEMO]%')->delete();
            TaskTimeEntry::where('user_id', $uid)->where('note', 'DEMO')->delete();
            if ($guestApp) {
                GuestTicket::where('assigned_user_id', $uid)->where('subject', 'like', '[DEMO]%')->delete();
            }

            foreach ($periods as $pi => $period) {
                [$year, $month] = explode('-', $period);
                $baseDate = Carbon::create($year, $month, 1);

                // Önceki aylarda biraz daha düşük/yüksek varyasyon
                $variance = match($pi) { 1 => 0.85, 2 => 0.70, default => 1.0 };
                $t = max(0, (int) round($aTasks * $variance));
                $k = max(0, (int) round($aTickets * $variance));
                $h = max(0, round($aHours * $variance));

                // MarketingTask (done)
                for ($i = 0; $i < $t; $i++) {
                    $completedAt = $baseDate->copy()->addDays(rand(0, 27))->setTime(rand(9, 17), rand(0, 59));
                    $task = MarketingTask::create([
                        'company_id'          => $this->companyId,
                        'title'               => '[DEMO] Görev ' . ($i + 1) . ' — ' . $emp['name'],
                        'status'              => 'done',
                        'priority'            => ['low','medium','high'][rand(0,2)],
                        'department'          => $this->roleToDept($emp['role']),
                        'assigned_user_id'    => $uid,
                        'created_by_user_id'  => $uid,
                        'completed_at'        => $completedAt,
                    ]);

                    // Her görev için saat girişi — toplam saat hedefine ulaş
                    $minutesPerTask = $t > 0 ? (int) round($h * 60 / $t) : 0;
                    if ($minutesPerTask > 0) {
                        $startedAt = $completedAt->copy()->subMinutes($minutesPerTask);
                        TaskTimeEntry::create([
                            'task_id'          => $task->id,
                            'user_id'          => $uid,
                            'started_at'       => $startedAt,
                            'ended_at'         => $completedAt,
                            'duration_minutes' => $minutesPerTask,
                            'note'             => 'DEMO',
                        ]);
                    }
                }

                // GuestTicket (resolved)
                if ($guestApp && $k > 0) {
                    for ($i = 0; $i < $k; $i++) {
                        $closedAt = $baseDate->copy()->addDays(rand(0, 27))->setTime(rand(9, 17), rand(0, 59));
                        GuestTicket::create([
                            'company_id'          => $this->companyId,
                            'guest_application_id'=> $guestApp->id,
                            'subject'             => '[DEMO] Destek Talebi ' . ($i + 1),
                            'message'             => 'Demo amaçlı oluşturulmuş destek talebi.',
                            'status'              => 'closed',
                            'priority'            => 'medium',
                            'department'          => 'operations',
                            'assigned_user_id'    => $uid,
                            'created_by_email'    => 'demo@mentorde.demo',
                            'closed_at'           => $closedAt,
                        ]);
                    }
                }
            }
        }
        $this->command->info('KPI gerçekleşen verileri oluşturuldu.');
    }

    private function roleToDept(string $role): string
    {
        return match(true) {
            str_contains($role, 'marketing') => 'marketing',
            str_contains($role, 'finance')   => 'finance',
            str_contains($role, 'sales')     => 'sales',
            str_contains($role, 'system')    => 'system',
            default                          => 'operations',
        };
    }

    // ── Devam Kayıtları ───────────────────────────────────────────────────────
    private function createAttendance(array $employees): void
    {
        foreach ($employees as $emp) {
            $uid = $emp['user']->id;
            HrAttendance::where('user_id', $uid)->where('note', 'DEMO')->delete();

            // Farklı devam profillerine göre son 30 iş günü
            $absenceRate = match($emp['perf']) {
                'star'      => 0.02,
                'good'      => 0.05,
                'medium'    => 0.10,
                'risk'      => 0.20,
                'low'       => 0.30,
                'no_target' => 0.05,
                default     => 0.05,
            };

            for ($d = 30; $d >= 1; $d--) {
                $date = now()->subDays($d)->startOfDay();

                // Hafta sonlarını atla
                if ($date->isWeekend()) continue;

                $rand  = mt_rand(0, 99) / 100;
                $status = 'present';
                $checkIn  = null;
                $checkOut = null;
                $workMin  = null;

                if ($rand < $absenceRate) {
                    $status  = (mt_rand(0, 1) === 0) ? 'absent' : 'half_day';
                    $workMin = $status === 'half_day' ? 240 : 0;
                } elseif ($rand < $absenceRate + 0.08) {
                    // geç geldi
                    $status   = 'late';
                    $checkIn  = $date->copy()->setTime(9, rand(30, 59));
                    $checkOut = $date->copy()->setTime(18, rand(0, 30));
                    $workMin  = $checkIn->diffInMinutes($checkOut);
                } elseif ($rand < $absenceRate + 0.12) {
                    // erken çıkış
                    $status   = 'early_leave';
                    $checkIn  = $date->copy()->setTime(8, rand(45, 59));
                    $checkOut = $date->copy()->setTime(15, rand(0, 30));
                    $workMin  = $checkIn->diffInMinutes($checkOut);
                } else {
                    $checkIn  = $date->copy()->setTime(8, rand(45, 59));
                    $checkOut = $date->copy()->setTime(17, rand(30, 59));
                    $workMin  = $checkIn->diffInMinutes($checkOut);
                }

                HrAttendance::create([
                    'user_id'      => $uid,
                    'company_id'   => $this->companyId,
                    'work_date'    => $date->toDateString(),
                    'check_in_at'  => $checkIn,
                    'check_out_at' => $checkOut,
                    'work_minutes' => $workMin,
                    'status'       => $status,
                    'note'         => 'DEMO',
                ]);
            }
        }
        $this->command->info('Devam kayıtları oluşturuldu.');
    }
}
