<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_retention_policies', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            // Hangi entity tipi: guest_application | user | document
            $table->string('entity_type', 64)->unique();
            // Arşivlendikten bu kadar gün sonra anonimleştir
            $table->unsignedInteger('anonymize_after_days')->default(1095); // 3 yıl
            // true → politika aktif
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Varsayılan politikalar
        $now = now();
        DB::table('data_retention_policies')->insert([
            [
                'entity_type'           => 'guest_application',
                'anonymize_after_days'  => 1095, // 3 yıl
                'is_active'             => true,
                'notes'                 => 'Arşivlenen guest başvuruları 3 yıl sonra anonimleştirilir.',
                'created_at'            => $now,
                'updated_at'            => $now,
            ],
            [
                'entity_type'           => 'user',
                'anonymize_after_days'  => 1825, // 5 yıl
                'is_active'             => true,
                'notes'                 => 'Silinme talebi olan kullanıcılar 5 yıl sonra tamamen anonimleştirilir.',
                'created_at'            => $now,
                'updated_at'            => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('data_retention_policies');
    }
};
