<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currency_rates', function (Blueprint $table): void {
            $table->id();
            $table->char('base_currency', 3)->default('EUR');
            $table->char('target_currency', 3);
            $table->decimal('rate', 12, 6);
            $table->date('fetched_at');
            $table->string('source', 100)->default('open.er-api.com');
            $table->timestamps();

            $table->unique(['base_currency', 'target_currency', 'fetched_at']);
            $table->index(['base_currency', 'target_currency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_rates');
    }
};
