<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manager_reports', function (Blueprint $table): void {
            $table->json('sent_to')->nullable()->after('senior_email');
        });
    }

    public function down(): void
    {
        Schema::table('manager_reports', function (Blueprint $table): void {
            $table->dropColumn('sent_to');
        });
    }
};

