<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_tracking_links', function (Blueprint $table): void {
            if (!Schema::hasColumn('marketing_tracking_links', 'category_code')) {
                $table->string('category_code', 2)->nullable()->after('code');
            }
            if (!Schema::hasColumn('marketing_tracking_links', 'platform_code')) {
                $table->string('platform_code', 2)->nullable()->after('category_code');
            }
            if (!Schema::hasColumn('marketing_tracking_links', 'placement_code')) {
                $table->string('placement_code', 1)->nullable()->after('platform_code');
            }
            if (!Schema::hasColumn('marketing_tracking_links', 'variation_no')) {
                $table->unsignedTinyInteger('variation_no')->nullable()->after('placement_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('marketing_tracking_links', function (Blueprint $table): void {
            foreach (['variation_no', 'placement_code', 'platform_code', 'category_code'] as $col) {
                if (Schema::hasColumn('marketing_tracking_links', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

