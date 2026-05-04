<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * universities tablosuna video_url ve video_caption ekler.
 * UniMatch program detay sayfasinda + sonuc kartlarinda universite tanitim
 * videosu (YouTube/Vimeo embed) gosterilir; bos kalirsa mevcut image_path
 * fallback'i devam eder. video_caption opsiyonel — kisa altyazi (orn:
 * "Berlin kampusu turu" / "Yeni ogrenci hosgeldin").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('universities', function (Blueprint $table): void {
            if (! Schema::hasColumn('universities', 'video_url')) {
                $table->string('video_url', 500)->nullable()->after('image_path');
            }
            if (! Schema::hasColumn('universities', 'video_caption')) {
                $table->string('video_caption', 200)->nullable()->after('video_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('universities', function (Blueprint $table): void {
            if (Schema::hasColumn('universities', 'video_caption')) {
                $table->dropColumn('video_caption');
            }
            if (Schema::hasColumn('universities', 'video_url')) {
                $table->dropColumn('video_url');
            }
        });
    }
};
