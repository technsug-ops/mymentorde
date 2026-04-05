<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            if (!Schema::hasColumn('guest_applications', 'selected_package_code')) {
                $table->string('selected_package_code', 64)->nullable()->after('registration_form_submitted_at');
            }
            if (!Schema::hasColumn('guest_applications', 'selected_package_title')) {
                $table->string('selected_package_title', 180)->nullable()->after('selected_package_code');
            }
            if (!Schema::hasColumn('guest_applications', 'selected_package_price')) {
                $table->string('selected_package_price', 64)->nullable()->after('selected_package_title');
            }
            if (!Schema::hasColumn('guest_applications', 'package_selected_at')) {
                $table->timestamp('package_selected_at')->nullable()->after('selected_package_price');
            }
            if (!Schema::hasColumn('guest_applications', 'preferred_locale')) {
                $table->string('preferred_locale', 8)->default('tr')->after('package_selected_at');
            }
            if (!Schema::hasColumn('guest_applications', 'notifications_enabled')) {
                $table->boolean('notifications_enabled')->default(true)->after('preferred_locale');
            }
        });
    }

    public function down(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            foreach ([
                'notifications_enabled',
                'preferred_locale',
                'package_selected_at',
                'selected_package_price',
                'selected_package_title',
                'selected_package_code',
            ] as $column) {
                if (Schema::hasColumn('guest_applications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

