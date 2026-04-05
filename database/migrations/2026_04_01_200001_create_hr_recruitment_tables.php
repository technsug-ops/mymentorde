<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // İş ilanları
        Schema::create('hr_job_postings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('title');
            $table->string('department')->nullable();
            $table->string('role_type')->nullable();           // backend_dev, ops_coordinator, vb.
            $table->string('employment_type')->default('full_time'); // full_time, part_time, internship, freelance
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->boolean('is_remote')->default(false);
            $table->string('location')->nullable();
            $table->decimal('salary_min', 8, 2)->nullable();
            $table->decimal('salary_max', 8, 2)->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->string('status')->default('draft');        // draft, active, paused, closed
            $table->date('deadline_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // Adaylar
        Schema::create('hr_candidates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('job_posting_id')->nullable()->index();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('cv_path')->nullable();
            $table->string('cover_letter_path')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('source')->default('direct');       // linkedin, referral, website, agency, direct
            $table->string('status')->default('applied');      // applied, screening, interview, offer, hired, rejected
            $table->unsignedTinyInteger('rating')->nullable(); // 1-5
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable(); // recruiter user_id
            $table->string('rejection_reason')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // Mülakat kayıtları
        Schema::create('hr_interviews', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('candidate_id')->index();
            $table->unsignedBigInteger('interviewer_user_id')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->string('type')->default('video');          // phone, video, onsite, technical
            $table->string('status')->default('scheduled');    // scheduled, completed, cancelled, no_show
            $table->unsignedTinyInteger('score')->nullable();  // 1-10
            $table->text('feedback')->nullable();
            $table->string('recommendation')->nullable();      // hire, reject, maybe
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_interviews');
        Schema::dropIfExists('hr_candidates');
        Schema::dropIfExists('hr_job_postings');
    }
};
