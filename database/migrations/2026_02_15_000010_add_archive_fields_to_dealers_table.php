<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dealers', function (Blueprint $table): void {
            if (!Schema::hasColumn('dealers', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('is_active');
                $table->string('archived_by')->nullable()->after('is_archived');
                $table->timestamp('archived_at')->nullable()->after('archived_by');
                $table->index(['is_archived', 'is_active']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('dealers', function (Blueprint $table): void {
            if (Schema::hasColumn('dealers', 'is_archived')) {
                $table->dropIndex(['is_archived', 'is_active']);
                $table->dropColumn(['is_archived', 'archived_by', 'archived_at']);
            }
        });
    }
};

