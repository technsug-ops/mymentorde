<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_dispatches', function (Blueprint $table): void {
            // Guest bildirimleri için
            $table->string('guest_id', 64)->nullable()->after('student_id');

            // Okunma takibi
            $table->boolean('is_read')->default(false)->after('status');
            $table->timestamp('read_at')->nullable()->after('is_read');

            // Opt-out / deduplicate kaydı
            $table->string('skip_reason', 64)->nullable()->after('fail_reason');

            // İndeksler
            $table->index(['guest_id', 'category']);
            $table->index(['created_at']);
            $table->index(['category', 'source_type', 'source_id', 'status'], 'nd_cat_src_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('notification_dispatches', function (Blueprint $table): void {
            $table->dropIndex(['guest_id', 'category']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['category', 'source_type', 'source_id', 'status']);
            $table->dropColumn(['guest_id', 'is_read', 'read_at', 'skip_reason']);
        });
    }
};
