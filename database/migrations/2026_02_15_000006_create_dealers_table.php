<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dealers', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name', 255);
            $table->string('dealer_type_code', 64)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['dealer_type_code', 'is_active']);
        });

        // Backfill existing dealer codes from student assignments (if any).
        if (Schema::hasTable('student_assignments')) {
            $codes = DB::table('student_assignments')
                ->whereNotNull('dealer_id')
                ->where('dealer_id', '!=', '')
                ->distinct()
                ->pluck('dealer_id');

            foreach ($codes as $code) {
                $code = strtoupper(trim((string) $code));
                if ($code === '') {
                    continue;
                }
                DB::table('dealers')->insertOrIgnore([
                    'code' => $code,
                    'name' => $code,
                    'dealer_type_code' => null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dealers');
    }
};
