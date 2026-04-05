<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_material_reads')) {
            Schema::create('student_material_reads', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('company_id')->default(1)->index();
                $table->string('student_id', 64)->index();
                $table->unsignedBigInteger('knowledge_base_article_id')->index();
                $table->dateTime('read_at')->nullable();
                $table->timestamps();
                $table->unique(['student_id', 'knowledge_base_article_id'], 'student_material_reads_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_material_reads');
    }
};

