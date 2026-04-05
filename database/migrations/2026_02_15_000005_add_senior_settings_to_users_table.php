<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('senior_type', 100)->nullable()->after('role');
            $table->unsignedInteger('max_capacity')->nullable()->after('senior_type');
            $table->boolean('auto_assign_enabled')->default(true)->after('max_capacity');
            $table->boolean('can_view_guest_pool')->default(false)->after('auto_assign_enabled');
            $table->boolean('is_active')->default(true)->after('can_view_guest_pool');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'senior_type',
                'max_capacity',
                'auto_assign_enabled',
                'can_view_guest_pool',
                'is_active',
            ]);
        });
    }
};
