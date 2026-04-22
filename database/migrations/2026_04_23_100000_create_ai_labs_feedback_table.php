<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * AI Labs kullanıcı feedback — AI'ın verdiği cevaba 👍 / 👎 işareti.
     *
     * Polymorphic:
     *   conversation_type: 'guest' | 'senior' | 'staff'
     *   conversation_id:   ilgili conversation tablosundaki satır id'si
     *
     * Analytics'te "Problem cevaplar" listesi için sorgulanır.
     */
    public function up(): void
    {
        Schema::create('ai_labs_feedback', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->string('conversation_type', 20);  // guest|senior|staff
            $t->unsignedBigInteger('conversation_id');
            $t->unsignedBigInteger('user_id')->nullable()->index(); // feedback veren (null = guest)
            $t->unsignedBigInteger('guest_application_id')->nullable()->index(); // guest ise
            $t->enum('rating', ['good', 'bad']);
            $t->text('reason')->nullable();       // opsiyonel — kullanıcı neden yanlış dediği

            $t->string('role', 32)->nullable();   // guest|student|senior|manager|admin_staff
            $t->timestamps();

            // Unique: aynı konuşmaya aynı kullanıcı bir kez oy verir
            $t->unique(['conversation_type', 'conversation_id', 'user_id', 'guest_application_id'], 'ux_alf_one_vote');
            $t->index(['company_id', 'rating', 'created_at'], 'idx_alf_analytics');
            $t->index(['conversation_type', 'conversation_id'], 'idx_alf_conv');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_labs_feedback');
    }
};
