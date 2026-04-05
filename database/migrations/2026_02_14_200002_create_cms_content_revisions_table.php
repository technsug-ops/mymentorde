<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_content_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cms_content_id')->constrained('cms_contents')->cascadeOnDelete();
            $table->unsignedInteger('revision_number');
            $table->foreignId('edited_by')->constrained('users')->cascadeOnDelete();
            $table->string('change_note')->nullable();
            $table->json('snapshot_data')->nullable();
            $table->timestamp('created_at');

            $table->index(['cms_content_id', 'revision_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_content_revisions');
    }
};
