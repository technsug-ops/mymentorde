<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_registration_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('guest_application_id')->constrained('guest_applications')->cascadeOnDelete();
            $table->unsignedInteger('snapshot_version')->default(1);
            $table->string('submitted_by_email', 190)->nullable();
            $table->json('payload_json');
            $table->json('meta_json')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['guest_application_id', 'snapshot_version'], 'guest_reg_snap_unique');
            $table->index(['guest_application_id', 'submitted_at'], 'guest_reg_snap_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_registration_snapshots');
    }
};

