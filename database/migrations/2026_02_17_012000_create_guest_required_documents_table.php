<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_required_documents', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('application_type', 64)->index();
            $table->string('document_code', 64);
            $table->string('category_code', 64)->index();
            $table->string('name', 190);
            $table->string('description', 500)->nullable();
            $table->boolean('is_required')->default(true)->index();
            $table->string('accepted', 120)->default('pdf,jpg,png');
            $table->unsignedInteger('max_mb')->default(10);
            $table->unsignedInteger('sort_order')->default(100);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['company_id', 'application_type', 'document_code'], 'grd_company_type_doc_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_required_documents');
    }
};

