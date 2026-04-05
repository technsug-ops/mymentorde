<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performans indeksleri — QW-5
 * Eksik olan 4 kompozit/tek sütun indeks eklenir.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Yardımcı: mevcut indeks isimlerini döner
        $hasIndex = fn(string $table, string $name): bool =>
            collect(Schema::getIndexes($table))->contains('name', $name);

        // 1. cms_contents — discover sayfalarında sık filtrelenen sütunlar
        Schema::table('cms_contents', function (Blueprint $table) use ($hasIndex) {
            if (!$hasIndex('cms_contents', 'cms_contents_status_category_featured_index')) {
                $table->index(['status', 'category', 'is_featured'], 'cms_contents_status_category_featured_index');
            }
            if (!$hasIndex('cms_contents', 'cms_contents_target_audience_index')) {
                $table->index('target_audience', 'cms_contents_target_audience_index');
            }
        });

        // 2. student_payments — due_date üzerinde sıralama yapılıyor
        Schema::table('student_payments', function (Blueprint $table) use ($hasIndex) {
            if (!$hasIndex('student_payments', 'student_payments_due_date_index')) {
                $table->index('due_date', 'student_payments_due_date_index');
            }
        });

        // 3. student_appointments — senior email + tarih bazlı filtreleme
        Schema::table('student_appointments', function (Blueprint $table) use ($hasIndex) {
            if (!$hasIndex('student_appointments', 'student_appointments_senior_email_scheduled_at_index')) {
                $table->index(['senior_email', 'scheduled_at'], 'student_appointments_senior_email_scheduled_at_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cms_contents', function (Blueprint $table) {
            $table->dropIndexIfExists('cms_contents_status_category_featured_index');
            $table->dropIndexIfExists('cms_contents_target_audience_index');
        });

        Schema::table('student_payments', function (Blueprint $table) {
            $table->dropIndexIfExists('student_payments_due_date_index');
        });

        Schema::table('student_appointments', function (Blueprint $table) {
            $table->dropIndexIfExists('student_appointments_senior_email_scheduled_at_index');
        });
    }
};
