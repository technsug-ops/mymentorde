<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * api_partner_requests — partner API request audit log.
 *
 * Her başarılı veya başarısız (401/403/429) request kaydedilir.
 * Manager dashboard'da per-partner usage stats + anomali tespiti için.
 *
 * Retention: 90 gün (cron ile eski kayıt silinir, ileride scheduler eklenir).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('api_partner_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_partner_id')->nullable()    // auth fail → null kalır
                  ->constrained('api_partners')->nullOnDelete();
            $table->string('endpoint', 200);                    // /api/v1/programs
            $table->string('method', 8)->default('GET');
            $table->string('ip', 45)->nullable();               // IPv6 max 45 char
            $table->json('query_params')->nullable();           // filter snapshot
            $table->unsignedSmallInteger('response_code');      // 200, 401, 429 vs
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->unsignedInteger('result_count')->nullable(); // sonuç sayısı (varsa)
            $table->string('user_agent', 200)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['api_partner_id', 'created_at']);
            $table->index('created_at');                        // retention purge için
            $table->index('response_code');                     // hata oranı sorgusu
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_partner_requests');
    }
};
