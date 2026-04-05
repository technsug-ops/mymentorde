<?php

namespace Database\Seeders;

use App\Models\MarketingTeam;
use App\Models\User;
use Illuminate\Database\Seeder;
class MarketingTeamUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Omer',
                'email' => env('MARKETING_ADMIN_EMAIL', 'omer@mentorde.local'),
                'password' => env('MARKETING_ADMIN_PASSWORD', 'ChangeMe123!'),
                'role' => 'marketing_admin',
                'team_permissions' => [
                    'canCreateCampaign' => true,
                    'canEditCMS' => true,
                    'canViewAnalytics' => true,
                    'canManageTeam' => true,
                    'canAccessEmailMarketing' => true,
                ],
            ],
            [
                'name' => 'Sule',
                'email' => env('MARKETING_STAFF1_EMAIL', 'sule@mentorde.local'),
                'password' => env('MARKETING_STAFF1_PASSWORD', 'ChangeMe123!'),
                'role' => 'marketing_staff',
                'team_permissions' => [
                    'canCreateCampaign' => true,
                    'canEditCMS' => true,
                    'canViewAnalytics' => true,
                    'canManageTeam' => false,
                    'canAccessEmailMarketing' => true,
                ],
            ],
            [
                'name' => 'Merve',
                'email' => env('MARKETING_STAFF2_EMAIL', 'merve@mentorde.local'),
                'password' => env('MARKETING_STAFF2_PASSWORD', 'ChangeMe123!'),
                'role' => 'marketing_staff',
                'team_permissions' => [
                    'canCreateCampaign' => true,
                    'canEditCMS' => true,
                    'canViewAnalytics' => true,
                    'canManageTeam' => false,
                    'canAccessEmailMarketing' => false,
                ],
            ],
        ];

        foreach ($users as $item) {
            $user = User::updateOrCreate(
                ['email' => $item['email']],
                [
                    'name' => $item['name'],
                    'password' => $item['password'],
                    'role' => $item['role'],
                ]
            );

            MarketingTeam::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'role' => $item['role'],
                    'permissions' => $item['team_permissions'],
                ]
            );
        }
    }
}
