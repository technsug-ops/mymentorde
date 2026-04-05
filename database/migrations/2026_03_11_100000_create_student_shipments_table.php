<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_shipments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('student_id', 64)->index();
            $table->enum('direction', ['outgoing', 'incoming'])->default('outgoing');
            $table->string('carrier', 80)->default('PTT'); // PTT, DHL, UPS, Yurtiçi, diğer
            $table->string('tracking_number', 120)->nullable();
            $table->string('content_description', 400);
            $table->date('sent_at')->nullable();
            $table->date('estimated_delivery')->nullable();
            $table->date('delivered_at')->nullable();
            $table->enum('status', ['preparing', 'shipped', 'in_transit', 'delivered', 'returned', 'lost'])
                  ->default('preparing');
            $table->text('notes')->nullable();
            $table->boolean('is_visible_to_student')->default(false);
            $table->unsignedBigInteger('added_by');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['student_id', 'is_visible_to_student']);
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_shipments');
    }
};
