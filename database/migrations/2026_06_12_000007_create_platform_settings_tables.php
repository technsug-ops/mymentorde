<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Platform Owner — Global platform settings (key-value, JSON value, category bucketed).
 *
 * Bu tablo Platform Owner Console'unun "Platform Ayarları" + "Güvenlik" sayfaları
 * tarafından kullanılır. Cross-tenant — company_id YOK; SaaS sahibinin global config'idir.
 *
 * Kategoriler: billing, security, system, notifications, email
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('platform_settings')) {
            Schema::create('platform_settings', function (Blueprint $table): void {
                $table->id();
                $table->string('key', 100)->unique();
                $table->json('value')->nullable();
                $table->string('category', 50)->default('system');
                $table->boolean('is_secret')->default(false);
                $table->unsignedBigInteger('updated_by_user_id')->nullable();
                $table->timestamps();

                $table->index('category', 'ps_category_idx');
            });
        } else {
            Schema::table('platform_settings', function (Blueprint $table): void {
                if (!Schema::hasColumn('platform_settings', 'key')) {
                    $table->string('key', 100)->unique();
                }
                if (!Schema::hasColumn('platform_settings', 'value')) {
                    $table->json('value')->nullable();
                }
                if (!Schema::hasColumn('platform_settings', 'category')) {
                    $table->string('category', 50)->default('system');
                }
                if (!Schema::hasColumn('platform_settings', 'is_secret')) {
                    $table->boolean('is_secret')->default(false);
                }
                if (!Schema::hasColumn('platform_settings', 'updated_by_user_id')) {
                    $table->unsignedBigInteger('updated_by_user_id')->nullable();
                }
            });
        }

        // Initial seed — idempotent (insertOrIgnore)
        $now = now();
        $seed = [
            // System / Genel
            ['key' => 'platform.support_email',     'category' => 'system',        'is_secret' => false, 'value' => json_encode('support@mentorde.com')],
            ['key' => 'platform.brand_name',        'category' => 'system',        'is_secret' => false, 'value' => json_encode('MentorDE')],
            ['key' => 'platform.default_locale',    'category' => 'system',        'is_secret' => false, 'value' => json_encode('tr')],
            ['key' => 'platform.default_timezone',  'category' => 'system',        'is_secret' => false, 'value' => json_encode('Europe/Berlin')],

            // Billing / Faturalama
            ['key' => 'platform.billing_company',   'category' => 'billing',       'is_secret' => false, 'value' => json_encode('MentorDE GmbH')],
            ['key' => 'platform.billing_iban',      'category' => 'billing',       'is_secret' => false, 'value' => json_encode('DE89 3704 0044 0532 0130 00')],
            ['key' => 'platform.billing_vat',       'category' => 'billing',       'is_secret' => false, 'value' => json_encode('DE000000000')],
            ['key' => 'platform.billing_email',     'category' => 'billing',       'is_secret' => false, 'value' => json_encode('billing@mentorde.com')],

            // KVKK
            ['key' => 'platform.kvkk_dpo_email',    'category' => 'system',        'is_secret' => false, 'value' => json_encode('dpo@mentorde.com')],

            // Email / SMTP
            ['key' => 'platform.smtp_host',         'category' => 'email',         'is_secret' => false, 'value' => json_encode('smtp.resend.com')],
            ['key' => 'platform.smtp_port',         'category' => 'email',         'is_secret' => false, 'value' => json_encode(587)],
            ['key' => 'platform.smtp_user',         'category' => 'email',         'is_secret' => false, 'value' => json_encode('resend')],
            ['key' => 'platform.smtp_password',     'category' => 'email',         'is_secret' => true,  'value' => json_encode('')],

            // Notifications
            ['key' => 'platform.notif_in_app',                  'category' => 'notifications', 'is_secret' => false, 'value' => json_encode(true)],
            ['key' => 'platform.daily_report_recipients',       'category' => 'notifications', 'is_secret' => false, 'value' => json_encode([])],

            // Security
            ['key' => 'security.session_timeout_minutes',       'category' => 'security', 'is_secret' => false, 'value' => json_encode(60)],
            ['key' => 'security.password_min_length',           'category' => 'security', 'is_secret' => false, 'value' => json_encode(12)],
            ['key' => 'security.require_2fa_for_platform_owner','category' => 'security', 'is_secret' => false, 'value' => json_encode(true)],
            ['key' => 'security.max_login_attempts',            'category' => 'security', 'is_secret' => false, 'value' => json_encode(5)],
        ];

        foreach ($seed as $row) {
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
            DB::table('platform_settings')->insertOrIgnore($row);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
