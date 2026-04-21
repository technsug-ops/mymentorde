<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_rules', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->string('rule_name', 120);

            // Match filtreleri — null = wildcard
            $t->string('applies_to_tier', 32)->nullable();            // junior/mid/senior/expert
            $t->string('applies_to_service_type', 32)->nullable();    // consultation/doc_review/...

            $t->decimal('commission_pct', 5, 2);                      // platform payı %
            $t->unsignedSmallInteger('priority')->default(10);
            $t->boolean('is_active')->default(true);

            $t->timestamps();

            $t->index(['company_id', 'is_active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_rules');
    }
};
