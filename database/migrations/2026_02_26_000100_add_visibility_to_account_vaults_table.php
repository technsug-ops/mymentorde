<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_vaults', function (Blueprint $table): void {
            $table->boolean('is_visible_to_student')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('account_vaults', function (Blueprint $table): void {
            $table->dropColumn('is_visible_to_student');
        });
    }
};
