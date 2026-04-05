<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Dealer;
use App\Models\DealerType;
use App\Models\GuestApplication;
use App\Models\MarketingTeam;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * E2E test ortamı için tüm test kullanıcılarını seed eder.
 * php artisan migrate:fresh --seed --seeder=E2EUserSeeder --env=testing
 */
class E2EUserSeeder extends Seeder
{
    public function run(): void
    {
        $pass = 'ChangeMe123!';

        // 1. Migration tarafından oluşturulan varsayılan şirketi kullan (code='mentorde')
        // Bu sayede seeded dokümanlar (company_id=1) ve kullanıcılar aynı şirkette kalır
        $company = Company::firstOrCreate(
            ['code' => 'mentorde'],
            ['name' => 'MentorDE', 'is_active' => true]
        );

        // Company context'i app container'a bağla (User::booted bunu kullanır)
        app()->instance('current_company_id', $company->id);

        // 2. Manager
        User::updateOrCreate(
            ['email' => 'manager@mentorde.local'],
            [
                'name'       => 'Test Manager',
                'role'       => 'manager',
                'password'   => $pass,
                'company_id' => $company->id,
            ]
        );

        // 3. Marketing Admin
        $mktgAdmin = User::updateOrCreate(
            ['email' => 'omer@mentorde.local'],
            [
                'name'       => 'Omer',
                'role'       => 'marketing_admin',
                'password'   => $pass,
                'company_id' => $company->id,
            ]
        );
        MarketingTeam::updateOrCreate(
            ['user_id' => $mktgAdmin->id],
            [
                'role'        => 'marketing_admin',
                'permissions' => [
                    'canCreateCampaign'       => true,
                    'canEditCMS'              => true,
                    'canViewAnalytics'        => true,
                    'canManageTeam'           => true,
                    'canAccessEmailMarketing' => true,
                ],
            ]
        );

        // 4. Marketing Staff
        $mktgStaff = User::updateOrCreate(
            ['email' => 'sule@mentorde.local'],
            [
                'name'       => 'Sule',
                'role'       => 'marketing_staff',
                'password'   => $pass,
                'company_id' => $company->id,
            ]
        );
        MarketingTeam::updateOrCreate(
            ['user_id' => $mktgStaff->id],
            [
                'role'        => 'marketing_staff',
                'permissions' => [
                    'canCreateCampaign'       => true,
                    'canEditCMS'              => true,
                    'canViewAnalytics'        => true,
                    'canManageTeam'           => false,
                    'canAccessEmailMarketing' => true,
                ],
            ]
        );

        // 5. Senior
        User::updateOrCreate(
            ['email' => 'seniorww@mentorde.local'],
            [
                'name'       => 'Senior Test',
                'role'       => 'senior',
                'password'   => $pass,
                'company_id' => $company->id,
            ]
        );

        // 6. Student: kullanici + GuestApplication (EnsureStudentRole / StudentGuestResolver icin)
        $studentUser = User::updateOrCreate(
            ['email' => 'student@mentorde.local'],
            [
                'name'       => 'Student Test',
                'role'       => 'student',
                'password'   => $pass,
                'student_id' => 'BCS100001',
                'company_id' => $company->id,
            ]
        );
        GuestApplication::updateOrCreate(
            ['email' => 'student@mentorde.local'],
            [
                'first_name'           => 'Ahmet',
                'last_name'            => 'Yilmaz',
                'phone'                => '+49 170 1234567',
                'application_type'     => 'bachelor',
                'lead_status'          => 'converted',
                'tracking_token'       => Str::random(32),
                'company_id'           => $company->id,
                'guest_user_id'        => $studentUser->id,
                'converted_to_student' => true,
                'converted_student_id' => 'BCS100001',
                'kvkk_consent'         => true,
            ]
        );

        // 7. Dealer: önce DealerType, sonra Dealer kaydı, sonra kullanıcı
        // 'operational' tipi canViewStudentDetails=true içeriyor (CheckDealerTypePermission middleware için gerekli)
        DealerType::updateOrCreate(
            ['code' => 'operational'],
            [
                'name_tr'    => 'Operasyonel Bayi',
                'name_de'    => 'Operativer Partner',
                'name_en'    => 'Operational Dealer',
                'permissions' => [
                    'canViewStudentDetails' => true,
                    'canViewDocuments'      => true,
                    'canUploadDocuments'    => true,
                    'canMessageStudent'     => true,
                    'canViewProcessDetails' => true,
                    'canViewFinancials'     => false,
                    'customPermissions'     => [],
                ],
                'default_commission_config' => [
                    'type'        => 'percentage',
                    'percentage'  => 20,
                    'fixedAmount' => null,
                    'currency'    => null,
                ],
                'is_active'  => true,
                'sort_order' => 1,
                'created_by' => 'e2e-seeder',
            ]
        );

        Dealer::updateOrCreate(
            ['code' => 'OPE-000001'],
            [
                'name'             => 'Test Dealer',
                'dealer_type_code' => 'operational',
                'is_active'        => true,
            ]
        );
        User::updateOrCreate(
            ['email' => 'dealer@mentorde.local'],
            [
                'name'        => 'Dealer Test',
                'role'        => 'dealer',
                'password'    => $pass,
                'dealer_code' => 'OPE-000001',
                'company_id'  => $company->id,
            ]
        );

        // 8. Guest: kullanıcı + GuestApplication (guest portal resolveGuest() için)
        $guestUser = User::updateOrCreate(
            ['email' => 'guest@mentorde.local'],
            [
                'name'       => 'Guest Test',
                'role'       => 'guest',
                'password'   => $pass,
                'company_id' => $company->id,
            ]
        );
        GuestApplication::updateOrCreate(
            ['email' => 'guest@mentorde.local'],
            [
                'first_name'       => 'Guest',
                'last_name'        => 'Test',
                'phone'            => '+49 123 4567890',
                'application_type' => 'bachelor',
                'lead_status'      => 'new',
                'tracking_token'   => Str::random(32),
                'company_id'       => $company->id,
                'guest_user_id'    => $guestUser->id,
            ]
        );
    }
}
