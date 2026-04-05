<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            if (!Schema::hasColumn('guest_applications', 'gender')) {
                $table->string('gender', 24)->default('not_specified')->after('phone');
            }
            if (!Schema::hasColumn('guest_applications', 'application_country')) {
                $table->string('application_country', 120)->nullable()->after('gender');
            }
            if (!Schema::hasColumn('guest_applications', 'communication_language')) {
                $table->string('communication_language', 8)->default('tr')->after('application_country');
            }
        });
    }

    public function down(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            if (Schema::hasColumn('guest_applications', 'communication_language')) {
                $table->dropColumn('communication_language');
            }
            if (Schema::hasColumn('guest_applications', 'application_country')) {
                $table->dropColumn('application_country');
            }
            if (Schema::hasColumn('guest_applications', 'gender')) {
                $table->dropColumn('gender');
            }
        });
    }
};

