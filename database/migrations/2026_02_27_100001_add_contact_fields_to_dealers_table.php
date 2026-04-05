<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dealers', function (Blueprint $table): void {
            if (! Schema::hasColumn('dealers', 'email')) {
                $table->string('email', 255)->nullable()->after('name');
            }
            if (! Schema::hasColumn('dealers', 'phone')) {
                $table->string('phone', 50)->nullable()->after('email');
            }
            if (! Schema::hasColumn('dealers', 'whatsapp')) {
                $table->string('whatsapp', 50)->nullable()->after('phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dealers', function (Blueprint $table): void {
            $table->dropColumn(array_filter(['email', 'phone', 'whatsapp'], fn ($col) => Schema::hasColumn('dealers', $col)));
        });
    }
};
