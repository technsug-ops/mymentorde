<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_institution_documents', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('student_id', 64)->index();

            // Katalog
            $table->string('institution_category', 32);  // uni_assist, university, visa, ...
            $table->string('document_type_code', 32);    // UA-EINGANG, UNI-ZULAS, ...
            $table->string('document_type_label');       // Katalogdan çekilen etiket

            $table->string('institution_name')->nullable(); // "TU Dortmund", "Türkiye Konsolosluğu" vb.
            $table->date('received_date')->nullable();

            $table->enum('status', ['expected', 'received', 'action_required', 'completed', 'archived'])
                  ->default('received');

            $table->text('notes')->nullable();

            // İlişkili dosya (opsiyonel: belge kaydı dosyasız da oluşturulabilir)
            $table->unsignedBigInteger('file_id')->nullable();

            // Görünürlük
            $table->boolean('is_visible_to_student')->default(false);
            $table->boolean('is_visible_to_dealer')->default(false);
            $table->timestamp('made_visible_at')->nullable();
            $table->unsignedBigInteger('made_visible_by')->nullable();

            // Kim ekledi
            $table->unsignedBigInteger('added_by');

            $table->softDeletes();
            $table->timestamps();

            $table->foreign('file_id')
                  ->references('id')
                  ->on('documents')
                  ->nullOnDelete();

            $table->index(['student_id', 'institution_category'], 'sid_institution_category_idx');
            $table->index(['student_id', 'is_visible_to_student'], 'sid_visible_student_idx');
            $table->index(['student_id', 'is_visible_to_dealer'], 'sid_visible_dealer_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_institution_documents');
    }
};
