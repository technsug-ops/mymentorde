<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mesaj şablonları — WhatsApp / Email / Çağrı scripti için hızlı kullanım.
        // Variables: {{first_name}}, {{senior_name}}, {{company_name}}, vb.
        Schema::create('action_templates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('name', 120);
            $table->enum('channel', ['whatsapp', 'email', 'call_script', 'note']);
            $table->enum('target_type', ['guest', 'student', 'both'])->default('both');
            $table->string('subject', 255)->nullable();
            $table->text('body');
            $table->string('variables', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['channel', 'target_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_templates');
    }
};
