<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ManagerUserSeeder::class,
            MarketingTeamUserSeeder::class,
            ProcessDefinitionSeeder::class,
            StudentTypeSeeder::class,
            DealerTypeSeeder::class,
            RevenueMilestoneSeeder::class,
            DealerRevenueMilestoneSeeder::class,
            LeadSourceDataSeeder::class,
            UniversityRequirementMapSeeder::class,
            CmsContentSeeder::class,
            ContentHubSeeder::class,
            ContentHubExtraSeeder::class,
            DocumentCategoryFromConfigSeeder::class,
            GuestRegistrationSpouseFieldsSeeder::class,
        ]);
    }
}
