<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_external_metrics', function (Blueprint $table): void {
            $table->id();
            $table->string('row_hash', 64)->unique();
            $table->string('provider', 40);
            $table->string('account_ref', 120)->nullable();
            $table->date('metric_date');
            $table->string('campaign_key', 191)->nullable();
            $table->string('campaign_name', 255)->nullable();
            $table->string('source', 80)->nullable();
            $table->string('medium', 80)->nullable();
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->decimal('spend', 14, 2)->default(0);
            $table->unsignedInteger('leads')->default(0);
            $table->unsignedInteger('conversions')->default(0);
            $table->json('raw_payload')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['provider', 'metric_date']);
            $table->index(['campaign_key', 'metric_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_external_metrics');
    }
};

