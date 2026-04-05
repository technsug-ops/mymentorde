<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_trails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 50);           // create, update, delete, read, login, export
            $table->string('entity_type', 50);
            $table->string('entity_id', 50)->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('request_url', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['company_id', 'entity_type', 'entity_id'], 'at_company_entity');
            $table->index(['user_id', 'created_at'], 'at_user_date');
            $table->index('action', 'at_action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_trails');
    }
};
