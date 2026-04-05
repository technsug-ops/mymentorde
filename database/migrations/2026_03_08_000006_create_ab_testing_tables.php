<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ab_tests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('name');
            $table->string('test_type', 64); // email_subject, email_content, landing_page, cms_title, workflow_split, package_display
            $table->enum('status', ['draft', 'pending_approval', 'running', 'paused', 'completed', 'winner_applied'])->default('draft');
            $table->json('traffic_split'); // { "A": 50, "B": 50 }
            $table->string('primary_metric', 64); // open_rate, click_rate, conversion_rate
            $table->unsignedInteger('min_sample_size')->default(100);
            $table->float('confidence_level')->default(0.95);
            $table->boolean('auto_winner')->default(false);
            $table->string('winner_variant', 8)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ab_test_variants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ab_test_id')->index();
            $table->string('variant_code', 8); // A, B, C
            $table->json('variant_config');
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('conversions')->default(0);
            $table->float('conversion_rate')->default(0);
            $table->timestamps();

            $table->foreign('ab_test_id')
                ->references('id')->on('ab_tests')
                ->cascadeOnDelete();
        });

        Schema::create('ab_test_assignments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ab_test_id')->index();
            $table->unsignedBigInteger('guest_application_id')->index();
            $table->string('variant_code', 8);
            $table->boolean('converted')->default(false);
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('converted_at')->nullable();

            $table->foreign('ab_test_id')
                ->references('id')->on('ab_tests')
                ->cascadeOnDelete();
            $table->foreign('guest_application_id')
                ->references('id')->on('guest_applications')
                ->cascadeOnDelete();
            $table->unique(['ab_test_id', 'guest_application_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ab_test_assignments');
        Schema::dropIfExists('ab_test_variants');
        Schema::dropIfExists('ab_tests');
    }
};
