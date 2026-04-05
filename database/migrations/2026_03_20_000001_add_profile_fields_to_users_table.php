<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'bio')) {
                $table->string('bio', 500)->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'expertise_tags')) {
                $table->string('expertise_tags', 500)->nullable()->after('bio');
            }
            if (!Schema::hasColumn('users', 'photo_url')) {
                $table->string('photo_url')->nullable()->after('expertise_tags');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(array_filter(['bio', 'expertise_tags', 'photo_url'], fn ($c) => Schema::hasColumn('users', $c)));
        });
    }
};
