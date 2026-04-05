<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_templates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('code', 80)->default('consultancy_v1');
            $table->string('name', 180);
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true)->index();
            $table->longText('body_text');
            $table->longText('annex_kvkk_text')->nullable();
            $table->longText('annex_commitment_text')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'code', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_templates');
    }
};

