<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('guest_required_documents')) {
            return;
        }
        Schema::table('guest_required_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('guest_required_documents', 'name_de')) {
                $table->string('name_de', 190)->nullable()->after('name');
            }
            if (!Schema::hasColumn('guest_required_documents', 'uni_assist_category')) {
                $table->string('uni_assist_category', 80)->nullable()->after('name_de');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('guest_required_documents')) {
            return;
        }
        Schema::table('guest_required_documents', function (Blueprint $table) {
            if (Schema::hasColumn('guest_required_documents', 'uni_assist_category')) {
                $table->dropColumn('uni_assist_category');
            }
            if (Schema::hasColumn('guest_required_documents', 'name_de')) {
                $table->dropColumn('name_de');
            }
        });
    }
};
