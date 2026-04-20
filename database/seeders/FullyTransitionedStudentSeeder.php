<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\DocumentCategory;
use App\Models\GuestApplication;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Demo icin tam gecisli (kontrat imzali, vize surecinde, universite basvurusu yapilmis)
 * test student hesabina zengin demo veri yukler.
 *
 * Hem local (student@mentorde.local) hem prod (student@my.mentorde.com) destekler.
 * Calistirma:  php artisan db:seed --class=FullyTransitionedStudentSeeder
 */
class FullyTransitionedStudentSeeder extends Seeder
{
    /** Test student account adaylari - hangisi varsa o zenginlestirilir */
    protected const CANDIDATE_EMAILS = [
        'student@mentorde.local',
        'student@my.mentorde.com',
    ];

    public function run(): void
    {
        $companyId = (int) (Company::query()->where('is_active', true)->orderBy('id')->value('id') ?? 1);

        $student = null;
        foreach (self::CANDIDATE_EMAILS as $email) {
            $student = User::withoutGlobalScopes()->where('email', $email)->first();
            if ($student) break;
        }

        if (! $student) {
            $this->command->warn('Test student bulunamadi (' . implode(', ', self::CANDIDATE_EMAILS) . '). Once DevUserSeeder calistir.');
            return;
        }

        $studentId = (string) ($student->student_id ?: 'BCS100001');
        if (empty($student->student_id)) {
            $student->forceFill(['student_id' => $studentId])->save();
        }

        $seniorEmail = (string) (User::where('role', 'senior')->orderBy('id')->value('email') ?? 'seniorww@mentorde.local');

        $this->seedGuestApplication($student, $studentId, $companyId, $seniorEmail);
        $this->seedStudentAssignment($studentId, $companyId, $seniorEmail);
        $this->seedDocuments($studentId, $companyId);
        $this->seedRegistrationSnapshots($studentId, $companyId);
        $this->seedTimelineMilestones($studentId);
        $this->seedUniversityApplications($studentId, $companyId, $student->id);
        $this->seedVisaApplication($studentId, $companyId, $student->id);
        $this->seedAccommodation($studentId, $companyId, $student->id);
        $this->seedChecklist($studentId, $companyId, $seniorEmail);
        $this->seedAppointments($studentId, $companyId, $seniorEmail, $student->email);
        $this->seedPayments($studentId, $companyId);
        $this->seedProcessOutcomes($studentId);
        $this->seedNotifications($studentId, $student->email, $student->id);
        $this->seedLanguageCourse($studentId, $companyId);

        $this->command->info("FullyTransitionedStudentSeeder tamamlandi: {$student->email} (id={$studentId}).");
    }

    private function seedGuestApplication(User $student, string $studentId, int $companyId, string $seniorEmail): void
    {
        $app = GuestApplication::withoutGlobalScopes()->where('email', $student->email)->first();

        $data = [
            'first_name'             => 'Ahmet',
            'last_name'              => 'Yilmaz',
            'phone'                  => '+49 170 1234567',
            'application_type'       => 'bachelor',
            'application_country'    => 'Türkiye',
            'target_city'            => 'Berlin',
            'target_term'            => 'WS2026/27',
            'language_level'         => 'B2',
            'lead_status'            => 'converted',
            'lead_source'            => 'organic',
            'priority'               => 'normal',
            'risk_level'             => 'normal',
            'kvkk_consent'           => true,
            'docs_ready'             => true,
            'converted_to_student'   => true,
            'converted_student_id'   => $studentId,
            'guest_user_id'          => $student->id,
            'company_id'             => $companyId,
            'assigned_senior_email'  => $seniorEmail,
        ];

        if (Schema::hasColumn('guest_applications', 'contract_status')) {
            $data['contract_status']       = 'signed';
            $data['contract_requested_at'] = now()->subDays(45);
            $data['contract_signed_at']    = now()->subDays(38);
            $data['contract_approved_at']  = now()->subDays(37);
        }

        if (Schema::hasColumn('guest_applications', 'selected_package_code')) {
            $data['selected_package_code']  = 'premium';
            $data['selected_package_price'] = 2800.00;
        }
        if (Schema::hasColumn('guest_applications', 'contract_amount_eur')) {
            $data['contract_amount_eur'] = 2800.00;
        }

        if ($app) {
            $app->forceFill($data)->save();
        } else {
            $data['email']          = $student->email;
            $data['tracking_token'] = Str::upper(Str::random(16));
            GuestApplication::create($data);
        }
    }

    private function seedStudentAssignment(string $studentId, int $companyId, string $seniorEmail): void
    {
        if (! Schema::hasTable('student_assignments')) return;

        $payload = [
            'senior_email'   => $seniorEmail,
            'branch'         => 'berlin',
            'risk_level'     => 'normal',
            'payment_status' => 'ok',
            'student_type'   => 'bachelor',
            'is_archived'    => false,
            'updated_at'     => now(),
        ];
        if (Schema::hasColumn('student_assignments', 'company_id')) {
            $payload['company_id'] = $companyId;
        }
        if (Schema::hasColumn('student_assignments', 'display_name')) {
            $payload['display_name'] = 'Ahmet Yilmaz';
        }

        $exists = DB::table('student_assignments')->where('student_id', $studentId)->first();
        if ($exists) {
            DB::table('student_assignments')->where('student_id', $studentId)->update($payload);
        } else {
            $payload['student_id'] = $studentId;
            $payload['created_at'] = now()->subDays(38);
            DB::table('student_assignments')->insert($payload);
        }
    }

    private function seedDocuments(string $studentId, int $companyId): void
    {
        if (! Schema::hasTable('documents') || ! Schema::hasTable('document_categories')) return;

        $categories = [
            'passport'       => ['tr' => 'Pasaport',               'order' => 10],
            'diploma'        => ['tr' => 'Diploma',                'order' => 20],
            'transcript'     => ['tr' => 'Transkript',             'order' => 30],
            'language_cert'  => ['tr' => 'Dil Sertifikasi',        'order' => 40],
            'motivation'     => ['tr' => 'Motivasyon Mektubu',     'order' => 50],
            'insurance'      => ['tr' => 'Saglik Sigortasi',       'order' => 60],
        ];

        $catIds = [];
        foreach ($categories as $code => $meta) {
            $cat = DocumentCategory::firstOrCreate(
                ['code' => $code],
                ['name_tr' => $meta['tr'], 'is_active' => true, 'sort_order' => $meta['order']]
            );
            $catIds[$code] = $cat->id;
        }

        $docs = [
            ['code' => 'passport',      'status' => 'approved', 'file' => 'passport.pdf',        'days' => 42],
            ['code' => 'diploma',       'status' => 'approved', 'file' => 'diploma.pdf',         'days' => 40],
            ['code' => 'transcript',    'status' => 'approved', 'file' => 'transkript.pdf',      'days' => 40],
            ['code' => 'language_cert', 'status' => 'approved', 'file' => 'telc_b2.pdf',         'days' => 35],
            ['code' => 'motivation',    'status' => 'uploaded', 'file' => 'motivasyon.pdf',      'days' => 5],
            ['code' => 'insurance',     'status' => 'rejected', 'file' => 'sigorta_poliçesi.pdf','days' => 3],
        ];

        foreach ($docs as $d) {
            $payload = [
                'student_id'         => $studentId,
                'category_id'        => $catIds[$d['code']],
                'original_file_name' => $d['file'],
                'standard_file_name' => "{$studentId}_{$d['code']}.pdf",
                'storage_path'       => "demo/{$studentId}/{$d['code']}.pdf",
                'mime_type'          => 'application/pdf',
                'status'             => $d['status'],
                'uploaded_by'        => 'student@mentorde.local',
                'created_at'         => now()->subDays($d['days']),
                'updated_at'         => now()->subDays(max($d['days'] - 1, 0)),
            ];
            if ($d['status'] === 'approved') {
                $payload['approved_by'] = 'seniorww@mentorde.local';
                $payload['approved_at'] = now()->subDays(max($d['days'] - 2, 0));
            }
            if ($d['status'] === 'rejected' && Schema::hasColumn('documents', 'review_note')) {
                $payload['review_note'] = 'Poliçe tarihi üniversite başlangıcından önce bitmiş. Lütfen geçerli bir poliçe yükleyin.';
            }

            $exists = DB::table('documents')
                ->where('student_id', $studentId)
                ->where('category_id', $catIds[$d['code']])
                ->exists();
            if (! $exists) {
                DB::table('documents')->insert($payload);
            }
        }
    }

    private function seedRegistrationSnapshots(string $studentId, int $companyId): void
    {
        if (! Schema::hasTable('guest_registration_snapshots')) return;

        $app = DB::table('guest_applications')->where('converted_student_id', $studentId)->first();
        if (! $app) return;

        DB::table('guest_registration_snapshots')->where('guest_application_id', $app->id)->delete();

        $baseline = [
            'first_name'      => 'Ahmet',
            'last_name'       => 'Yilmaz',
            'birth_date'      => '2002-03-14',
            'city'            => 'Ankara',
            'target_city'     => 'Berlin',
            'target_term'     => 'WS2026/27',
            'language_level'  => 'B2',
            'university_pref' => ['TU Berlin', 'HU Berlin'],
        ];

        $snaps = [
            ['version' => 1, 'days' => 60, 'payload' => array_merge($baseline, ['language_level' => 'B1'])],
            ['version' => 2, 'days' => 30, 'payload' => array_merge($baseline, ['language_level' => 'B2'])],
            ['version' => 3, 'days' => 7,  'payload' => array_merge($baseline, ['language_level' => 'B2', 'university_pref' => ['TU Berlin', 'HU Berlin', 'FU Berlin']])],
        ];

        foreach ($snaps as $s) {
            DB::table('guest_registration_snapshots')->insert([
                'guest_application_id' => $app->id,
                'snapshot_version'     => $s['version'],
                'submitted_by_email'   => 'student@mentorde.local',
                'payload_json'         => json_encode($s['payload']),
                'meta_json'            => json_encode(['source' => 'demo_seeder']),
                'submitted_at'         => now()->subDays($s['days']),
                'created_at'           => now()->subDays($s['days']),
                'updated_at'           => now()->subDays($s['days']),
            ]);
        }
    }

    private function seedTimelineMilestones(string $studentId): void
    {
        if (! Schema::hasTable('guest_timeline_milestones')) return;
        $app = DB::table('guest_applications')->where('converted_student_id', $studentId)->first();
        if (! $app) return;

        $milestones = [
            ['code' => 'consultation',     'label' => 'İlk Danışmanlık',           'cat' => 'onboarding', 'target' => now()->subDays(50), 'done' => now()->subDays(50)],
            ['code' => 'contract_signed',  'label' => 'Sözleşme İmzalandı',        'cat' => 'contract',   'target' => now()->subDays(38), 'done' => now()->subDays(38)],
            ['code' => 'docs_collected',   'label' => 'Belgeler Toplandı',          'cat' => 'documents',  'target' => now()->subDays(20), 'done' => now()->subDays(22)],
            ['code' => 'uni_application',  'label' => 'Üniversite Başvurusu',       'cat' => 'application','target' => now()->subDays(10), 'done' => now()->subDays(8)],
            ['code' => 'visa_appointment', 'label' => 'Vize Randevusu',             'cat' => 'visa',       'target' => now()->addDays(14), 'done' => null],
            ['code' => 'arrival',          'label' => 'Almanya\'ya Varış',          'cat' => 'arrival',    'target' => now()->addDays(90), 'done' => null],
        ];

        foreach ($milestones as $i => $m) {
            DB::table('guest_timeline_milestones')->updateOrInsert(
                ['guest_application_id' => $app->id, 'milestone_code' => $m['code']],
                [
                    'label'        => $m['label'],
                    'category'     => $m['cat'],
                    'target_date'  => $m['target']->format('Y-m-d'),
                    'completed_at' => $m['done'],
                    'sort_order'   => $i + 1,
                ]
            );
        }
    }

    private function seedUniversityApplications(string $studentId, int $companyId, int $userId): void
    {
        if (! Schema::hasTable('student_university_applications')) return;

        DB::table('student_university_applications')->where('student_id', $studentId)->delete();

        $apps = [
            [
                'university_code'    => 'tu-berlin',
                'university_name'    => 'Technische Universität Berlin',
                'city'               => 'Berlin',
                'state'              => 'Berlin',
                'department_code'    => 'informatik',
                'department_name'    => 'Informatik (B.Sc.)',
                'degree_type'        => 'bachelor',
                'semester'           => 'WS2026/27',
                'application_portal' => 'uni_assist',
                'application_number' => 'UAB-2026-447821',
                'status'             => 'accepted',
                'priority'           => 1,
                'deadline'           => now()->addDays(40)->format('Y-m-d'),
                'submitted_at'       => now()->subDays(12)->format('Y-m-d'),
                'result_at'          => now()->subDays(3)->format('Y-m-d'),
                'notes'              => 'Koşulsuz kabul — WS2026/27 başlangıç.',
            ],
            [
                'university_code'    => 'hu-berlin',
                'university_name'    => 'Humboldt-Universität zu Berlin',
                'city'               => 'Berlin',
                'state'              => 'Berlin',
                'department_code'    => 'informatik',
                'department_name'    => 'Informatik (B.Sc.)',
                'degree_type'        => 'bachelor',
                'semester'           => 'WS2026/27',
                'application_portal' => 'uni_assist',
                'application_number' => 'UAB-2026-447822',
                'status'             => 'under_review',
                'priority'           => 2,
                'deadline'           => now()->addDays(40)->format('Y-m-d'),
                'submitted_at'       => now()->subDays(10)->format('Y-m-d'),
                'result_at'          => null,
                'notes'              => 'Hâlâ inceleme aşamasında.',
            ],
            [
                'university_code'    => 'fu-berlin',
                'university_name'    => 'Freie Universität Berlin',
                'city'               => 'Berlin',
                'state'              => 'Berlin',
                'department_code'    => 'wi-info',
                'department_name'    => 'Wirtschaftsinformatik (B.Sc.)',
                'degree_type'        => 'bachelor',
                'semester'           => 'WS2026/27',
                'application_portal' => 'uni_assist',
                'application_number' => 'UAB-2026-447823',
                'status'             => 'submitted',
                'priority'           => 3,
                'deadline'           => now()->addDays(40)->format('Y-m-d'),
                'submitted_at'       => now()->subDays(8)->format('Y-m-d'),
                'result_at'          => null,
                'notes'              => 'Yedek tercih.',
            ],
        ];

        foreach ($apps as $a) {
            DB::table('student_university_applications')->insert(array_merge($a, [
                'company_id'             => $companyId,
                'student_id'             => $studentId,
                'is_visible_to_student'  => true,
                'is_visible_to_dealer'   => false,
                'added_by'               => $userId,
                'created_at'             => now()->subDays(15),
                'updated_at'             => now(),
            ]));
        }
    }

    private function seedVisaApplication(string $studentId, int $companyId, int $userId): void
    {
        if (! Schema::hasTable('student_visa_applications')) return;

        DB::table('student_visa_applications')->where('student_id', $studentId)->delete();
        DB::table('student_visa_applications')->insert([
            'company_id'              => (string) $companyId,
            'student_id'              => $studentId,
            'visa_type'               => 'national_d',
            'status'                  => 'preparing',
            'application_date'        => now()->subDays(5)->format('Y-m-d'),
            'appointment_date'        => now()->addDays(14)->format('Y-m-d'),
            'consulate_city'          => 'Ankara',
            'submitted_documents'     => json_encode(['passport', 'acceptance_letter', 'insurance', 'finance_proof']),
            'notes'                   => 'Randevu onaylandı. Belge klasörü hazırlanıyor.',
            'is_visible_to_student'   => true,
            'added_by'                => $userId,
            'created_at'              => now()->subDays(5),
            'updated_at'              => now(),
        ]);
    }

    private function seedAccommodation(string $studentId, int $companyId, int $userId): void
    {
        if (! Schema::hasTable('student_accommodations')) return;

        DB::table('student_accommodations')->where('student_id', $studentId)->delete();
        DB::table('student_accommodations')->insert([
            'company_id'         => (string) $companyId,
            'student_id'         => $studentId,
            'type'               => 'off_campus',
            'booking_status'     => 'applied',
            'address'            => 'Friedrichshain, Warschauer Str. 45',
            'city'               => 'Berlin',
            'postal_code'        => '10243',
            'monthly_cost_eur'   => 620.00,
            'utilities_included' => true,
            'move_in_date'       => now()->addDays(85)->format('Y-m-d'),
            'contract_end_date'  => now()->addMonths(18)->format('Y-m-d'),
            'landlord_name'      => 'WG Berlin Studentenheim',
            'landlord_email'     => 'kontakt@wg-berlin-demo.de',
            'notes'              => 'Studentenwerk başvurusu gönderildi. Bekleme listesi sırası: 12.',
            'is_visible_to_student' => true,
            'added_by'           => $userId,
            'created_at'         => now()->subDays(20),
            'updated_at'         => now(),
        ]);
    }

    private function seedChecklist(string $studentId, int $companyId, string $seniorEmail): void
    {
        if (! Schema::hasTable('student_checklists')) return;

        $items = [
            ['label' => 'Pasaportu ofise teslim et',            'cat' => 'document',     'done' => true,  'days' => 42],
            ['label' => 'Noter onaylı diploma çevirisi',         'cat' => 'document',     'done' => true,  'days' => 40],
            ['label' => 'TELC B2 sertifikası',                   'cat' => 'language',     'done' => true,  'days' => 35],
            ['label' => 'APS sertifikası başvurusu',             'cat' => 'document',     'done' => true,  'days' => 30],
            ['label' => 'Uni-Assist başvurusu',                  'cat' => 'registration', 'done' => true,  'days' => 12],
            ['label' => 'Sağlık sigortası poliçesi (geçerli)',   'cat' => 'document',     'done' => false, 'days' => 0],
            ['label' => 'Finanzierungsnachweis (Sperrkonto)',    'cat' => 'visa',         'done' => false, 'days' => 0],
            ['label' => 'Vize randevusu için dosya hazırla',     'cat' => 'visa',         'done' => false, 'days' => 0],
            ['label' => 'Konut kontratı imzala',                 'cat' => 'housing',      'done' => false, 'days' => 0],
        ];

        foreach ($items as $i => $it) {
            DB::table('student_checklists')->updateOrInsert(
                ['student_id' => $studentId, 'label' => $it['label']],
                [
                    'company_id'       => $companyId,
                    'category'         => $it['cat'],
                    'is_done'          => $it['done'],
                    'done_at'          => $it['done'] ? now()->subDays($it['days']) : null,
                    'due_date'         => $it['done'] ? null : now()->addDays(10 + $i)->format('Y-m-d'),
                    'sort_order'       => $i + 1,
                    'created_by_email' => $seniorEmail,
                    'updated_at'       => now(),
                    'created_at'       => now()->subDays(50),
                ]
            );
        }
    }

    private function seedAppointments(string $studentId, int $companyId, string $seniorEmail, string $studentEmail): void
    {
        if (! Schema::hasTable('student_appointments')) return;

        DB::table('student_appointments')->where('student_id', $studentId)->delete();

        $items = [
            ['title' => 'İlk Danışmanlık',          'status' => 'done',      'scheduled' => now()->subDays(50), 'channel' => 'online',    'dur' => 45, 'note' => 'Süreç tanıtımı, belge listesi paylaşıldı.'],
            ['title' => 'Sözleşme Görüşmesi',       'status' => 'done',      'scheduled' => now()->subDays(38), 'channel' => 'in_person', 'dur' => 60, 'note' => 'Sözleşme okundu, imzalandı.'],
            ['title' => 'Üniversite Tercih Paneli', 'status' => 'done',      'scheduled' => now()->subDays(15), 'channel' => 'online',    'dur' => 45, 'note' => 'TU/HU/FU Berlin tercihleri netleşti.'],
            ['title' => 'Vize Dosyası İncelemesi',  'status' => 'scheduled', 'scheduled' => now()->addDays(3),  'channel' => 'online',    'dur' => 45, 'note' => 'Sperrkonto ve sigorta poliçesi kontrol.'],
            ['title' => 'Almanya Gelişi Brifingi',  'status' => 'requested', 'scheduled' => now()->addDays(80), 'channel' => 'online',    'dur' => 60, 'note' => 'Anmeldung ve ilk 2 hafta planı.'],
        ];

        foreach ($items as $it) {
            DB::table('student_appointments')->insert([
                'company_id'     => $companyId,
                'student_id'     => $studentId,
                'student_email'  => $studentEmail,
                'senior_email'   => $seniorEmail,
                'title'          => $it['title'],
                'note'           => $it['note'],
                'requested_at'   => $it['scheduled']->copy()->subDays(2),
                'scheduled_at'   => $it['scheduled'],
                'duration_minutes' => $it['dur'],
                'channel'        => $it['channel'],
                'status'         => $it['status'],
                'created_at'     => $it['scheduled']->copy()->subDays(3),
                'updated_at'     => now(),
            ]);
        }
    }

    private function seedPayments(string $studentId, int $companyId): void
    {
        if (! Schema::hasTable('student_payments')) return;

        $payments = [
            ['inv' => 'INV-DEMO-0001', 'desc' => 'Sözleşme peşinatı (%30)',       'amt' => 840.00,  'due' => now()->subDays(37), 'paid' => now()->subDays(37), 'status' => 'paid'],
            ['inv' => 'INV-DEMO-0002', 'desc' => 'Üniversite başvuru ücreti',     'amt' => 180.00,  'due' => now()->subDays(14), 'paid' => now()->subDays(14), 'status' => 'paid'],
            ['inv' => 'INV-DEMO-0003', 'desc' => 'Ara ödeme (%40)',               'amt' => 1120.00, 'due' => now()->addDays(10), 'paid' => null,              'status' => 'pending'],
            ['inv' => 'INV-DEMO-0004', 'desc' => 'Kalan ödeme (%30)',             'amt' => 840.00,  'due' => now()->addDays(60), 'paid' => null,              'status' => 'pending'],
        ];

        foreach ($payments as $p) {
            DB::table('student_payments')->updateOrInsert(
                ['invoice_number' => $p['inv']],
                [
                    'company_id'     => $companyId,
                    'student_id'     => $studentId,
                    'description'    => $p['desc'],
                    'amount_eur'     => $p['amt'],
                    'currency'       => 'EUR',
                    'due_date'       => $p['due']->format('Y-m-d'),
                    'paid_at'        => $p['paid'],
                    'payment_method' => $p['paid'] ? 'bank_transfer' : null,
                    'status'         => $p['status'],
                    'created_at'     => now()->subDays(45),
                    'updated_at'     => now(),
                ]
            );
        }
    }

    private function seedProcessOutcomes(string $studentId): void
    {
        if (! Schema::hasTable('process_outcomes')) return;

        DB::table('process_outcomes')->where('student_id', $studentId)->delete();

        $items = [
            [
                'process_step' => 'contract',
                'outcome_type' => 'success',
                'details_tr'   => 'Sözleşme imzalandı ve arşive eklendi.',
                'days'         => 38,
            ],
            [
                'process_step' => 'documents',
                'outcome_type' => 'success',
                'details_tr'   => 'Akademik belgeler (diploma, transkript) onaylandı.',
                'days'         => 25,
            ],
            [
                'process_step' => 'language',
                'outcome_type' => 'success',
                'details_tr'   => 'TELC B2 sertifikası doğrulandı.',
                'days'         => 20,
            ],
            [
                'process_step' => 'university_application',
                'outcome_type' => 'success',
                'university'   => 'Technische Universität Berlin',
                'program'      => 'Informatik (B.Sc.)',
                'details_tr'   => 'TU Berlin\'den WS2026/27 için koşulsuz kabul alındı.',
                'days'         => 3,
            ],
            [
                'process_step' => 'visa',
                'outcome_type' => 'in_progress',
                'details_tr'   => 'Ankara konsolosluk randevusu alındı. Evraklar hazırlanıyor.',
                'deadline'     => now()->addDays(14),
                'days'         => 5,
            ],
        ];

        foreach ($items as $i) {
            DB::table('process_outcomes')->insert([
                'student_id'            => $studentId,
                'process_step'          => $i['process_step'],
                'outcome_type'          => $i['outcome_type'],
                'university'            => $i['university'] ?? null,
                'program'               => $i['program'] ?? null,
                'details_tr'            => $i['details_tr'],
                'deadline'              => $i['deadline'] ?? null,
                'is_visible_to_student' => true,
                'made_visible_at'       => now()->subDays($i['days']),
                'made_visible_by'       => 'seniorww@mentorde.local',
                'student_notified'      => true,
                'notified_at'           => now()->subDays($i['days']),
                'added_by'              => 'seniorww@mentorde.local',
                'created_at'            => now()->subDays($i['days']),
                'updated_at'            => now()->subDays($i['days']),
            ]);
        }
    }

    private function seedNotifications(string $studentId, string $studentEmail, int $userId): void
    {
        if (! Schema::hasTable('notification_dispatches')) return;

        $items = [
            [
                'subject' => 'TU Berlin\'den kabul mektubu geldi',
                'body'    => 'Tebrikler! Technische Universität Berlin WS2026/27 dönemi için koşulsuz kabul gönderdi. Vize sürecine geçebiliriz.',
                'days'    => 3,
                'cat'     => 'academic',
            ],
            [
                'subject' => 'Sigorta poliçesi belgesi reddedildi',
                'body'    => 'Yüklediğiniz sağlık sigortası poliçesi reddedildi. Lütfen geçerli bir poliçe yükleyin.',
                'days'    => 2,
                'cat'     => 'document_review',
            ],
            [
                'subject' => 'Vize randevunuz yaklaşıyor',
                'body'    => '14 gün sonra Ankara konsoloslugunda vize randevunuz var. Belgelerinizi danışmanınızla kontrol edin.',
                'days'    => 1,
                'cat'     => 'reminder',
            ],
        ];

        foreach ($items as $it) {
            DB::table('notification_dispatches')->insert([
                'channel'         => 'inapp',
                'category'        => $it['cat'],
                'student_id'      => $studentId,
                'recipient_email' => $studentEmail,
                'recipient_name'  => 'Ahmet Yilmaz',
                'subject'         => $it['subject'],
                'body'            => $it['body'],
                'status'          => 'sent',
                'queued_at'       => now()->subDays($it['days']),
                'sent_at'         => now()->subDays($it['days']),
                'user_id'         => Schema::hasColumn('notification_dispatches', 'user_id') ? $userId : null,
                'created_at'      => now()->subDays($it['days']),
                'updated_at'      => now()->subDays($it['days']),
            ]);
        }
    }

    private function seedLanguageCourse(string $studentId, int $companyId): void
    {
        if (! Schema::hasTable('student_language_courses')) return;

        DB::table('student_language_courses')->where('student_id', $studentId)->delete();

        $cols = Schema::getColumnListing('student_language_courses');
        $row = [
            'student_id' => $studentId,
            'created_at' => now()->subDays(30),
            'updated_at' => now(),
        ];
        if (in_array('company_id', $cols, true))          $row['company_id'] = $companyId;
        if (in_array('school_name', $cols, true))         $row['school_name'] = 'Goethe-Institut Ankara';
        if (in_array('course_name', $cols, true))         $row['course_name'] = 'B2 Intensiv';
        if (in_array('level', $cols, true))               $row['level'] = 'B2';
        if (in_array('target_level', $cols, true))        $row['target_level'] = 'C1';
        if (in_array('status', $cols, true))              $row['status'] = 'completed';
        if (in_array('start_date', $cols, true))          $row['start_date'] = now()->subMonths(4)->format('Y-m-d');
        if (in_array('end_date', $cols, true))            $row['end_date']   = now()->subDays(35)->format('Y-m-d');
        if (in_array('certificate_date', $cols, true))    $row['certificate_date'] = now()->subDays(35)->format('Y-m-d');
        if (in_array('is_visible_to_student', $cols, true)) $row['is_visible_to_student'] = true;

        DB::table('student_language_courses')->insert($row);
    }
}
