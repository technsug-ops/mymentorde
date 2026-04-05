<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_templates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('name', 190);
            $table->text('description')->nullable();
            $table->string('department', 64);
            $table->string('category', 64)->default('general'); // onboarding|contract|process|general
            $table->boolean('is_chain')->default(false);
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['company_id', 'department', 'is_active']);
        });

        Schema::create('task_template_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('template_id');
            $table->string('title', 190);
            $table->text('description')->nullable();
            $table->string('priority', 16)->default('normal'); // low|normal|high|urgent
            $table->unsignedSmallInteger('due_offset_days')->default(0);
            $table->string('assign_to_role', 64)->nullable();
            $table->string('assign_to_source', 64)->nullable(); // senior_of_student|creator|specific_role
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedSmallInteger('depends_on_order')->nullable();
            $table->json('checklist_items')->nullable(); // ["Adım 1","Adım 2"]
            $table->decimal('estimated_hours', 5, 2)->nullable();
            $table->timestamps();

            $table->foreign('template_id')->references('id')->on('task_templates')->cascadeOnDelete();
            $table->index(['template_id', 'sort_order']);
        });

        // marketing_tasks'a template_id ekle
        Schema::table('marketing_tasks', function (Blueprint $table): void {
            $table->unsignedBigInteger('template_id')->nullable()->after('checklist_done');
            $table->decimal('estimated_hours', 5, 2)->nullable()->after('template_id');
            $table->foreign('template_id')->references('id')->on('task_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('marketing_tasks', function (Blueprint $table): void {
            $table->dropForeign(['template_id']);
            $table->dropColumn(['template_id', 'estimated_hours']);
        });
        Schema::dropIfExists('task_template_items');
        Schema::dropIfExists('task_templates');
    }
};
