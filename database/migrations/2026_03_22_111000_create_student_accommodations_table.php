<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_accommodations')) {
            return;
        }
        Schema::create('student_accommodations', function (Blueprint $table) {
            $table->id();
            $table->string('company_id', 32)->index();
            $table->string('student_id', 64)->index();

            // Konut türü ve durum
            $table->enum('type', ['on_campus', 'off_campus', 'host_family', 'other'])->default('off_campus');
            $table->enum('booking_status', ['searching', 'applied', 'booked', 'confirmed', 'cancelled'])->default('searching');

            // Adres bilgileri
            $table->string('address', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('postal_code', 20)->nullable();

            // Maliyet
            $table->decimal('monthly_cost_eur', 8, 2)->nullable();
            $table->boolean('utilities_included')->default(false);

            // Tarihler
            $table->date('move_in_date')->nullable();
            $table->date('contract_end_date')->nullable();

            // İletişim
            $table->string('landlord_name', 150)->nullable();
            $table->string('landlord_phone', 30)->nullable();
            $table->string('landlord_email', 150)->nullable();

            // Notlar
            $table->text('notes')->nullable();
            $table->boolean('is_visible_to_student')->default(true);
            $table->unsignedBigInteger('added_by')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_accommodations');
    }
};
