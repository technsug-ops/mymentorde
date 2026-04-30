<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('study_buddy_responses') && ! Schema::hasTable('uni_match_responses')) {
            Schema::rename('study_buddy_responses', 'uni_match_responses');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('uni_match_responses') && ! Schema::hasTable('study_buddy_responses')) {
            Schema::rename('uni_match_responses', 'study_buddy_responses');
        }
    }
};
