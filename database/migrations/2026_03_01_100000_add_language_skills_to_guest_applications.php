<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_applications', function (Blueprint $table) {
            // JSON array: [{"lang":"de","level":"B2"},{"lang":"en","level":"C1","custom":""}]
            $table->json('language_skills')->nullable()->after('language_level');
        });
    }

    public function down(): void
    {
        Schema::table('guest_applications', function (Blueprint $table) {
            $table->dropColumn('language_skills');
        });
    }
};
