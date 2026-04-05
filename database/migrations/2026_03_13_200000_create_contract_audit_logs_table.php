<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('guest_application_id');
            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30);
            $table->string('changed_by', 191)->nullable();
            $table->text('note')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('guest_application_id', 'idx_contract_audit_guest');

            $table->foreign('guest_application_id')
                ->references('id')->on('guest_applications')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_audit_logs');
    }
};
