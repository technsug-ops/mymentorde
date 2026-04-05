<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\GuestApplication;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $company   = Company::first();
        $companyId = $company?->id ?? 1;
        $senior    = User::where('role', 'senior')->first();
        $seniorEmail = $senior?->email ?? 'seniorww@mentorde.local';

        // ── GUEST demo kişileri ───────────────────────────────────────────────
        $guestStages = [
            ['stage' => 'new',             'first' => 'Ayşe',    'last' => 'Kaya',       'country' => 'DE', 'type' => 'master',   'tier' => 'cold', 'score' => 15],
            ['stage' => 'contacted',        'first' => 'Mehmet',  'last' => 'Demir',      'country' => 'AT', 'type' => 'bachelor', 'tier' => 'warm', 'score' => 35],
            ['stage' => 'docs_pending',     'first' => 'Zeynep',  'last' => 'Arslan',     'country' => 'DE', 'type' => 'master',   'tier' => 'warm', 'score' => 42],
            ['stage' => 'in_progress',      'first' => 'Emre',    'last' => 'Çelik',      'country' => 'NL', 'type' => 'bachelor', 'tier' => 'hot',  'score' => 65],
            ['stage' => 'in_progress',      'first' => 'Fatma',   'last' => 'Yıldız',     'country' => 'DE', 'type' => 'language', 'tier' => 'hot',  'score' => 71],
            ['stage' => 'evaluating',       'first' => 'Burak',   'last' => 'Şahin',      'country' => 'DE', 'type' => 'master',   'tier' => 'hot',  'score' => 78],
            ['stage' => 'contract_signed',  'first' => 'Elif',    'last' => 'Öztürk',     'country' => 'AT', 'type' => 'bachelor', 'tier' => 'hot',  'score' => 88],
            ['stage' => 'new',             'first' => 'Kemal',   'last' => 'Aydın',      'country' => 'CH', 'type' => 'phd',      'tier' => 'cold', 'score' => 10],
            ['stage' => 'contacted',        'first' => 'Selin',   'last' => 'Koç',        'country' => 'DE', 'type' => 'master',   'tier' => 'warm', 'score' => 28],
            ['stage' => 'docs_pending',     'first' => 'Tarık',   'last' => 'Erdoğan',    'country' => 'DE', 'type' => 'bachelor', 'tier' => 'warm', 'score' => 45],
            ['stage' => 'in_progress',      'first' => 'Neslihan','last' => 'Polat',      'country' => 'NL', 'type' => 'prep',     'tier' => 'hot',  'score' => 60],
            ['stage' => 'evaluating',       'first' => 'Serkan',  'last' => 'Güneş',      'country' => 'DE', 'type' => 'master',   'tier' => 'hot',  'score' => 82],
        ];

        foreach ($guestStages as $g) {
            $email = strtolower($g['first']) . '.' . strtolower(Str::ascii($g['last'])) . '@demo.test';

            // Skip if already exists
            if (GuestApplication::where('email', $email)->exists()) {
                continue;
            }

            GuestApplication::create([
                'company_id'             => $companyId,
                'first_name'             => $g['first'],
                'last_name'              => $g['last'],
                'email'                  => $email,
                'phone'                  => '+90 555 ' . rand(100, 999) . ' ' . rand(1000, 9999),
                'application_country'    => $g['country'],
                'application_type'       => $g['type'],
                'communication_language' => 'tr',
                'lead_status'            => $g['stage'],
                'lead_score'             => $g['score'],
                'lead_score_tier'        => $g['tier'],
                'lead_source'            => collect(['organic','referral','social','campaign'])->random(),
                'assigned_senior_email'  => $seniorEmail,
                'kvkk_consent'           => true,
                'tracking_token'         => Str::uuid(),
                'branch'                 => 'istanbul',
            ]);
        }

        $this->command->info('✓ ' . count($guestStages) . ' demo guest oluşturuldu (mevcutlar atlandı)');

        // ── STUDENT demo kişileri ─────────────────────────────────────────────
        $studentStages = [
            ['name' => 'Ahmet Yılmaz',    'email' => 'ahmet.yilmaz@demo.test',    'stage' => 'application_prep'],
            ['name' => 'Ceren Doğan',     'email' => 'ceren.dogan@demo.test',     'stage' => 'uni_assist'],
            ['name' => 'Murat Kaplan',    'email' => 'murat.kaplan@demo.test',    'stage' => 'visa_application'],
            ['name' => 'İrem Şimşek',     'email' => 'irem.simsek@demo.test',     'stage' => 'language_course'],
            ['name' => 'Onur Çetin',      'email' => 'onur.cetin@demo.test',      'stage' => 'residence'],
            ['name' => 'Gizem Acar',      'email' => 'gizem.acar@demo.test',      'stage' => 'official_services'],
            ['name' => 'Berkay Tunç',     'email' => 'berkay.tunc@demo.test',     'stage' => 'completed'],
            ['name' => 'Merve Özbek',     'email' => 'merve.ozbek@demo.test',     'stage' => 'application_prep'],
            ['name' => 'Enes Kılıç',      'email' => 'enes.kilic@demo.test',      'stage' => 'uni_assist'],
            ['name' => 'Pınar Arslan',    'email' => 'pinar.arslan@demo.test',    'stage' => 'visa_application'],
        ];

        $createdStudents = 0;
        foreach ($studentStages as $s) {
            if (User::where('email', $s['email'])->exists()) {
                continue;
            }

            $user = User::create([
                'name'       => $s['name'],
                'email'      => $s['email'],
                'password'   => Hash::make('Demo1234!'),
                'role'       => 'student',
                'company_id' => $companyId,
            ]);

            // StudentAssignment kaydı
            DB::table('student_assignments')->insert([
                'student_id'   => $user->id,
                'company_id'   => $companyId,
                'senior_email' => $seniorEmail,
                'display_name' => $s['name'],
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            // ProcessOutcome — pipeline stage'i bu tablodan okunuyor
            DB::table('process_outcomes')->insert([
                'student_id'            => $user->id,
                'process_step'          => $s['stage'],
                'outcome_type'          => 'info',
                'details_tr'            => 'Demo kayıt — ' . $s['stage'] . ' aşaması',
                'is_visible_to_student' => 1,
                'pipeline_impact'       => 'neutral',
                'added_by'              => $seniorEmail,
                'created_at'            => now()->subDays(rand(1, 30)),
                'updated_at'            => now()->subDays(rand(1, 30)),
            ]);

            $createdStudents++;
        }

        $this->command->info("✓ {$createdStudents} demo student oluşturuldu (mevcutlar atlandı)");
    }
}
