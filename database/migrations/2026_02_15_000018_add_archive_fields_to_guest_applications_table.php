<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            if (!Schema::hasColumn('guest_applications', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('converted_to_student');
                $table->timestamp('archived_at')->nullable()->after('is_archived');
                $table->string('archived_by')->nullable()->after('archived_at');
                $table->string('archive_reason', 120)->nullable()->after('archived_by');
                $table->index(['is_archived', 'created_at']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            if (Schema::hasColumn('guest_applications', 'is_archived')) {
                $table->dropIndex(['is_archived', 'created_at']);
                $table->dropColumn(['is_archived', 'archived_at', 'archived_by', 'archive_reason']);
            }
        });
    }
};

