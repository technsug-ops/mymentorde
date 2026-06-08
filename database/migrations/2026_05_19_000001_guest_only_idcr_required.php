<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Guest aşamasında SADECE Kimlik Ön-Arka (DOC-IDCR) zorunlu olsun.
 * Diğer guest belgeleri (transkript, pasaport, YKS, üniversite kabul vb.)
 * is_required=false olur — yine yüklenebilirler ama zorunlu değil.
 *
 * Karar: 2026-05-19 manager talebi.
 * Geri alınabilir (down) — eski state'i snapshot olarak metaya kaydetmiyoruz,
 * dolayısıyla down sadece DOC-IDCR dışındakileri yeniden required'a almaz.
 */
return new class extends Migration {
    public function up(): void
    {
        DB::table('guest_required_documents')
            ->where('stage', 'guest')
            ->where('document_code', '!=', 'DOC-IDCR')
            ->where('is_required', true)
            ->update(['is_required' => false, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Bu migration veri değişikliği — down işlemi orijinal seed kayıtlarını
        // restore edemez. Tüm guest belgeleri tekrar required yapmak yanlış olur,
        // dolayısıyla down no-op (idempotent).
    }
};
