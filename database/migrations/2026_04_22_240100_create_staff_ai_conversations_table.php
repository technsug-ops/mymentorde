<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Manager + admin_staff + diğer iç roller için AI Labs sohbet kayıt tablosu.
     * guest_ai_conversations (guest+student) ve senior_ai_conversations (senior)
     * mevcut — bu tablo kalan iç roller için.
     */
    public function up(): void
    {
        Schema::create('staff_ai_conversations', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('company_id')->nullable()->index();
            $t->unsignedBigInteger('user_id')->index();
            $t->string('role', 32); // manager|admin_staff|marketing_admin|sales_admin|...
            $t->text('question');
            $t->text('answer');
            $t->json('context')->nullable();

            $t->string('response_mode', 20)->nullable(); // source|external|refused
            $t->json('cited_sources')->nullable();
            $t->integer('tokens_input')->default(0);
            $t->integer('tokens_output')->default(0);
            $t->integer('tokens_used')->default(0); // compat
            $t->string('provider', 32)->nullable();
            $t->string('model', 64)->nullable();

            $t->timestamp('created_at')->useCurrent();

            $t->index(['user_id', 'created_at'], 'idx_sac_user_date');
            $t->index(['company_id', 'role'], 'idx_sac_cid_role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_ai_conversations');
    }
};
