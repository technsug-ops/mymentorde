<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            if (!Schema::hasColumn('documents', 'review_note')) {
                $table->string('review_note', 500)->nullable()->after('approved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            if (Schema::hasColumn('documents', 'review_note')) {
                $table->dropColumn('review_note');
            }
        });
    }
};

