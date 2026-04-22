<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Manager'ın AI asistanı "eğitmek" için yazdığı kalıcı talimatlar.
     * Örnek: "Her öğrenciye Fintiba'yı öner", "TU Berlin başvurusunda sadece Master programları için GRE şart",
     * "Müşteriyle samimi ama profesyonel konuş"
     *
     * Bu metin her sorunun system prompt'una eklenir → AI bunları her cevapta gözetir.
     * admin_instructions değişince response cache tamamen boşaltılır.
     */
    public function up(): void
    {
        if (Schema::hasTable('ai_labs_settings') && !Schema::hasColumn('ai_labs_settings', 'admin_instructions')) {
            Schema::table('ai_labs_settings', function (Blueprint $t): void {
                $t->text('admin_instructions')->nullable()->after('content_generator_enabled');
                $t->timestamp('instructions_updated_at')->nullable()->after('admin_instructions');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ai_labs_settings', 'admin_instructions')) {
            Schema::table('ai_labs_settings', function (Blueprint $t): void {
                $t->dropColumn(['admin_instructions', 'instructions_updated_at']);
            });
        }
    }
};
