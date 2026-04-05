<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            if (!Schema::hasColumn('guest_applications', 'utm_source')) {
                $table->string('utm_source', 120)->nullable()->after('campaign_code');
            }
            if (!Schema::hasColumn('guest_applications', 'utm_medium')) {
                $table->string('utm_medium', 120)->nullable()->after('utm_source');
            }
            if (!Schema::hasColumn('guest_applications', 'utm_campaign')) {
                $table->string('utm_campaign', 191)->nullable()->after('utm_medium');
            }
            if (!Schema::hasColumn('guest_applications', 'utm_term')) {
                $table->string('utm_term', 191)->nullable()->after('utm_campaign');
            }
            if (!Schema::hasColumn('guest_applications', 'utm_content')) {
                $table->string('utm_content', 191)->nullable()->after('utm_term');
            }
            if (!Schema::hasColumn('guest_applications', 'click_id')) {
                $table->string('click_id', 191)->nullable()->after('utm_content');
            }
            if (!Schema::hasColumn('guest_applications', 'landing_url')) {
                $table->text('landing_url')->nullable()->after('click_id');
            }
            if (!Schema::hasColumn('guest_applications', 'referrer_url')) {
                $table->text('referrer_url')->nullable()->after('landing_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            $drop = [];
            foreach ([
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'utm_term',
                'utm_content',
                'click_id',
                'landing_url',
                'referrer_url',
            ] as $col) {
                if (Schema::hasColumn('guest_applications', $col)) {
                    $drop[] = $col;
                }
            }
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};

