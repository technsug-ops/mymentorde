<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * guest_required_documents tablosuna stage kolonu ekle.
     * Guest-stage belgeler (sort_order <= 60) → 'guest'  (varsayılan)
     * Student-stage belgeler (sort_order >= 100) → 'student'
     */
    public function up(): void
    {
        if (! Schema::hasTable('guest_required_documents')) {
            return;
        }

        if (! Schema::hasColumn('guest_required_documents', 'stage')) {
            Schema::table('guest_required_documents', function (Blueprint $table): void {
                $table->string('stage', 20)->default('guest')->after('is_active');
            });
        }

        // Student-aşama belgeleri: sort_order >= 100 olanlar
        DB::table('guest_required_documents')
            ->where('sort_order', '>=', 100)
            ->update(['stage' => 'student']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('guest_required_documents', 'stage')) {
            Schema::table('guest_required_documents', function (Blueprint $table): void {
                $table->dropColumn('stage');
            });
        }
    }
};
