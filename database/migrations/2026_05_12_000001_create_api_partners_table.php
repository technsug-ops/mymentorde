<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * api_partners — public REST API'ye erişen kardeş siteler / iş ortakları.
 *
 * Her partner unique bir API key alır (örn. mtde_live_xxxxxxxxx).
 * DB'de SADECE sha256 hash tutulur — plaintext key sadece bir kez gösterilir.
 * Her key bağımsız rate limit'e sahip (default: 1000 req/saat).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('api_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 80)->unique();
            $table->string('api_key_prefix', 24);             // mtde_live_abcdef12 — masked görünüm için
            $table->string('api_key_hash', 64)->unique();      // sha256 hex
            $table->string('contact_email', 160)->nullable();
            $table->string('website', 200)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('rate_limit_per_hour')->default(1000);
            $table->unsignedBigInteger('total_requests')->default(0);  // lifetime sayaç
            $table->timestamp('last_used_at')->nullable();
            $table->text('notes')->nullable();                 // manager için iç not
            $table->timestamps();

            $table->index('is_active');
            $table->index('last_used_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_partners');
    }
};
