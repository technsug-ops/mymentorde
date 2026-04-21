<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('senior_availability_patterns', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('company_id')->nullable()->index();
            $t->unsignedBigInteger('senior_user_id')->index();

            // Haftalık tekrar eden pattern — 0=Pzt, 1=Sal, ..., 6=Paz (ISO 8601)
            $t->unsignedTinyInteger('weekday');
            $t->time('start_time');     // Europe/Berlin gibi senior'ın timezone'ında
            $t->time('end_time');

            $t->boolean('is_active')->default(true);
            $t->timestamps();

            $t->index(['senior_user_id', 'weekday', 'is_active'], 'sap_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('senior_availability_patterns');
    }
};
