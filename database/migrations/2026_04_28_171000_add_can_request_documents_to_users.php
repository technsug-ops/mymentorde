<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Senior'lara per-user "Belge Talep Et" yetkisi.
 * Manager bu flag'i senior detayında toggle eder. Açıkken senior
 * 'doc_request.use' permission'ına sahip olur (template fallback'a ek).
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('users') || Schema::hasColumn('users', 'can_request_documents')) return;
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_request_documents')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'can_request_documents')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('can_request_documents');
            });
        }
    }
};
