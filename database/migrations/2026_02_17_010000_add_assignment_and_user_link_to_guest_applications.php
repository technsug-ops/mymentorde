<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            if (!Schema::hasColumn('guest_applications', 'guest_user_id')) {
                $table->unsignedBigInteger('guest_user_id')->nullable()->after('id')->index();
            }
            if (!Schema::hasColumn('guest_applications', 'assigned_senior_email')) {
                $table->string('assigned_senior_email', 190)->nullable()->after('application_type')->index();
            }
            if (!Schema::hasColumn('guest_applications', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable()->after('assigned_senior_email');
            }
            if (!Schema::hasColumn('guest_applications', 'assigned_by')) {
                $table->string('assigned_by', 120)->nullable()->after('assigned_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            foreach (['guest_user_id', 'assigned_senior_email', 'assigned_at', 'assigned_by'] as $col) {
                if (Schema::hasColumn('guest_applications', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

