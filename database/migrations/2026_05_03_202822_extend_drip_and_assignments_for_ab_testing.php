<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * UniMatch drip step'leri A/B testing framework'üyle entegre etmek için:
     * - email_drip_steps.ab_test_id (nullable FK) — step bir A/B test ile bağlanır
     * - ab_test_assignments.uni_match_response_id (nullable FK) — UniMatch lead'leri
     *   guest_application_id zorunlu olduğu için yan kolon
     */
    public function up(): void
    {
        Schema::table('email_drip_steps', function (Blueprint $table) {
            if (! Schema::hasColumn('email_drip_steps', 'ab_test_id')) {
                $table->unsignedBigInteger('ab_test_id')->nullable()->after('view_path');
                $table->index('ab_test_id', 'eds_ab_test_idx');
            }
        });

        // ab_test_assignments.guest_application_id nullable yap + uni_match_response_id ekle
        // change() kullaniliyor: ham "MODIFY" SQL'i MySQL'e ozgu, SQLite'ta patliyordu.
        Schema::table('ab_test_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('guest_application_id')->nullable()->change();
        });

        Schema::table('ab_test_assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('ab_test_assignments', 'uni_match_response_id')) {
                $table->unsignedBigInteger('uni_match_response_id')->nullable()->after('guest_application_id');
                $table->index('uni_match_response_id', 'aba_unimatch_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('email_drip_steps', function (Blueprint $table) {
            if (Schema::hasColumn('email_drip_steps', 'ab_test_id')) {
                $table->dropIndex('eds_ab_test_idx');
                $table->dropColumn('ab_test_id');
            }
        });

        Schema::table('ab_test_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('ab_test_assignments', 'uni_match_response_id')) {
                $table->dropIndex('aba_unimatch_idx');
                $table->dropColumn('uni_match_response_id');
            }
        });
    }
};
