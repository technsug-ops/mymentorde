<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('marketing_campaigns', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->json('channels')->nullable()->after('channel');
            $table->decimal('spent_amount', 12, 2)->default(0)->after('budget');
            $table->string('target_country')->nullable()->after('target_audience');
            $table->json('utm_params')->nullable()->after('status');
            $table->string('image_url')->nullable()->after('linked_cms_content_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketing_campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'channels',
                'spent_amount',
                'target_country',
                'utm_params',
                'image_url',
            ]);
        });
    }
};
