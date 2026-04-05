<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CV ve Motivasyon Mektubu belgelerini guest_required_documents tablosundan kaldir.
     * Detayli belgeler student kabulu sonrasinda istenir.
     */
    public function up(): void
    {
        if (!Schema::hasTable('guest_required_documents')) {
            return;
        }

        DB::table('guest_required_documents')
            ->whereIn('document_code', ['DOC-CV__', 'DOC-MOTV'])
            ->delete();
    }

    public function down(): void
    {
        // Geri alma gerekirse seed migration'i tekrar calistirin.
    }
};
