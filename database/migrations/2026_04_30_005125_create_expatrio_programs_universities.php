<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── 500+ Almanya üniversitesi ──
        Schema::create('expatrio_universities', function (Blueprint $table) {
            $table->string('id', 36)->primary()->comment('Expatrio UUID');
            $table->string('name', 255);
            $table->string('image_path', 500)->nullable();
            $table->unsignedInteger('program_count')->default(0)->comment('Bu üniversitenin program sayısı');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });

        // ── 13K+ program kataloğu ──
        Schema::create('expatrio_programs', function (Blueprint $table) {
            $table->string('id', 36)->primary()->comment('Expatrio UUID');
            $table->string('university_id', 36)->index();
            $table->string('university_name', 255)->index()->comment('Denormalize — search hızı için');
            $table->string('course_name', 255)->index();
            $table->string('degree_specification', 120)->nullable()->comment('Bachelor of Arts, Master of Science vb.');
            $table->string('location', 120)->nullable();
            $table->json('languages')->nullable()->comment('["English", "German"]');
            $table->json('study_fields')->nullable()->comment('Akademik alanlar listesi');
            $table->json('subjects')->nullable()->comment('Konu/spesifik alan listesi');
            $table->unsignedSmallInteger('semester_count')->nullable();
            $table->unsignedInteger('tuition_fees_per_semester')->nullable()->comment('EUR (tam değer)');
            $table->json('data')->nullable()->comment('Tam Expatrio JSON — gelecekteki alanlar için');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->foreign('university_id')->references('id')->on('expatrio_universities')->cascadeOnDelete();
            $table->index(['degree_specification', 'university_id'], 'expatrio_progs_degree_uni_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expatrio_programs');
        Schema::dropIfExists('expatrio_universities');
    }
};
