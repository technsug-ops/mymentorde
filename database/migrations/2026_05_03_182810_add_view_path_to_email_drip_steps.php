<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drip step'lerin Blade view path'i (örn 'emails.unimatch.drip_1').
     * Set ise ProcessEmailDripCommand bu view'i render eder, NotificationDispatch.body'a
     * HTML olarak yazar. Set değilse template_id üzerinden EmailTemplate.body_tr fallback.
     */
    public function up(): void
    {
        Schema::table('email_drip_steps', function (Blueprint $table) {
            if (! Schema::hasColumn('email_drip_steps', 'view_path')) {
                $table->string('view_path', 200)->nullable()->after('template_id');
            }
        });

        // template_id nullable — view_path kullanıldığında zorunlu değil
        \Illuminate\Support\Facades\DB::statement(
            'ALTER TABLE email_drip_steps MODIFY template_id BIGINT UNSIGNED NULL'
        );
    }

    public function down(): void
    {
        Schema::table('email_drip_steps', function (Blueprint $table) {
            if (Schema::hasColumn('email_drip_steps', 'view_path')) {
                $table->dropColumn('view_path');
            }
        });
    }
};
