<?php

namespace Database\Seeders;

use App\Models\Dealer;
use App\Models\GuestApplication;
use App\Models\User;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DevUserSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->where('is_active', true)->orderBy('id')->first();
        $companyId = $company?->id ?? 1;

        $password = Hash::make('ChangeMe123!');

        $users = [
            ['name' => 'Manager',            'email' => 'manager@mentorde.local',          'role' => 'manager'],
            ['name' => 'Sys Admin',          'email' => 'sysadmin@mentorde.local',          'role' => 'system_admin'],
            ['name' => 'Sys Staff',          'email' => 'sysstaff@mentorde.local',          'role' => 'system_staff'],
            ['name' => 'Ops Admin',          'email' => 'operations.admin@mentorde.local',  'role' => 'operations_admin'],
            ['name' => 'Ops Staff',          'email' => 'operations.staff@mentorde.local',  'role' => 'operations_staff'],
            ['name' => 'Finance Admin',      'email' => 'finance.admin@mentorde.local',     'role' => 'finance_admin'],
            ['name' => 'Finance Staff',      'email' => 'finance.staff@mentorde.local',     'role' => 'finance_staff'],
            ['name' => 'Marketing Admin',    'email' => 'marketing.admin@mentorde.local',   'role' => 'marketing_admin'],
            ['name' => 'Marketing Staff 1',  'email' => 'marketing.staff1@mentorde.local',  'role' => 'marketing_staff'],
            ['name' => 'Sales Admin',        'email' => 'sales.admin@mentorde.local',       'role' => 'sales_admin'],
            ['name' => 'Sales Staff',        'email' => 'sales.staff@mentorde.local',       'role' => 'sales_staff'],
            ['name' => 'Senior Advisor WW',  'email' => 'seniorww@mentorde.local',          'role' => 'senior',  'senior_code' => 'SR-WW001'],
            ['name' => 'Senior Advisor 2',   'email' => 'senior2@mentorde.local',           'role' => 'senior',  'senior_code' => 'SR-WW002'],
            ['name' => 'Mentor User',        'email' => 'mentor@mentorde.local',            'role' => 'mentor'],
            ['name' => 'Student Test',       'email' => 'student@mentorde.local',           'role' => 'student', 'student_id' => 'BCS100001'],
            ['name' => 'Guest Test',         'email' => 'guest@mentorde.local',             'role' => 'guest'],
            ['name' => 'Dealer Test',        'email' => 'dealer@mentorde.local',            'role' => 'dealer',  'dealer_code' => 'OPE-000001'],
        ];

        foreach ($users as $data) {
            $data['password']   = $password;
            $data['company_id'] = $companyId;
            $data['is_active']  = true;

            User::query()->updateOrCreate(
                ['email' => $data['email']],
                $data
            );
        }

        // Dev guest kullanıcısı için GuestApplication kaydı oluştur
        $guestUser = User::query()->where('email', 'guest@mentorde.local')->first();
        if ($guestUser) {
            $guestApp = GuestApplication::query()->updateOrCreate(
                ['email' => 'guest@mentorde.local', 'company_id' => $companyId],
                [
                    'guest_user_id'          => $guestUser->id,
                    'first_name'             => 'Guest',
                    'last_name'              => 'Test',
                    'phone'                  => '+49 1234567890',
                    'gender'                 => 'other',
                    'application_country'    => 'DE',
                    'communication_language' => 'tr',
                    'application_type'       => 'bachelor',
                    'lead_status'            => 'new',
                    'kvkk_consent'           => true,
                    'tracking_token'         => Str::uuid()->toString(),
                    'assigned_senior_email'  => 'seniorww@mentorde.local',
                ]
            );
            $this->command->info("Dev GuestApplication created/updated (id={$guestApp->id}).");
        }

        // Dev dealer kullanıcısı için Dealer kaydı oluştur
        Dealer::query()->updateOrCreate(
            ['code' => 'OPE-000001'],
            [
                'name'             => 'Dealer Test',
                'email'            => 'dealer@mentorde.local',
                'dealer_type_code' => 'operational',
                'is_active'        => true,
                'is_archived'      => false,
            ]
        );
        $this->command->info('Dev Dealer record created/updated (code=OPE-000001).');

        $this->command->info('Dev users created/updated. Password: ChangeMe123!');
    }
}
