<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            if (!Schema::hasColumn('guest_applications', 'registration_form_draft')) {
                $table->json('registration_form_draft')->nullable()->after('status_message');
            }
            if (!Schema::hasColumn('guest_applications', 'registration_form_draft_saved_at')) {
                $table->timestamp('registration_form_draft_saved_at')->nullable()->after('registration_form_draft');
            }
            if (!Schema::hasColumn('guest_applications', 'registration_form_submitted_at')) {
                $table->timestamp('registration_form_submitted_at')->nullable()->after('registration_form_draft_saved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            if (Schema::hasColumn('guest_applications', 'registration_form_submitted_at')) {
                $table->dropColumn('registration_form_submitted_at');
            }
            if (Schema::hasColumn('guest_applications', 'registration_form_draft_saved_at')) {
                $table->dropColumn('registration_form_draft_saved_at');
            }
            if (Schema::hasColumn('guest_applications', 'registration_form_draft')) {
                $table->dropColumn('registration_form_draft');
            }
        });
    }
};

