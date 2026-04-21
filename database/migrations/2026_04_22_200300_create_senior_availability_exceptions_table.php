<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('senior_availability_exceptions', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('company_id')->nullable()->index();
            $t->unsignedBigInteger('senior_user_id')->index();

            $t->date('date');                           // istisna günü
            $t->boolean('is_blocked')->default(true);   // true=tatil/izin, false=o gün ÖZEL saat
            $t->time('override_start_time')->nullable();
            $t->time('override_end_time')->nullable();
            $t->string('reason', 255)->nullable();

            $t->timestamps();

            $t->unique(['senior_user_id', 'date'], 'sae_unique_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('senior_availability_exceptions');
    }
};
