<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * #18 — Not görünürlüğü: senior bir notu öğrenciye/adaya görünür yapabilsin.
 * Varsayılan false (gizli — eski davranış korunur). true ise öğrenci kendi
 * panelinde "Danışmanımdan Notlar" altında görür.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internal_notes', function (Blueprint $table): void {
            if (!Schema::hasColumn('internal_notes', 'is_visible_to_student')) {
                $table->boolean('is_visible_to_student')->default(false)->after('priority');
                $table->index(['student_id', 'is_visible_to_student']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('internal_notes', function (Blueprint $table): void {
            if (Schema::hasColumn('internal_notes', 'is_visible_to_student')) {
                $table->dropIndex(['student_id', 'is_visible_to_student']);
                $table->dropColumn('is_visible_to_student');
            }
        });
    }
};
