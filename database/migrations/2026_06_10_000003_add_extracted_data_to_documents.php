<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OCR / AI extraction sütunları — public belge yüklemelerinde
 * Gemini Vision ile structured field çıkarımı için.
 *
 * extracted_data: structured field/value pairs (passport: full_name, document_number...)
 * extraction_status: pending → processing → completed | failed
 * extraction_confidence: model'in self-reported güveni (0.00 - 1.00)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->json('extracted_data')->nullable()->after('review_note');
            $table->timestamp('extracted_at')->nullable()->after('extracted_data');
            $table->decimal('extraction_confidence', 3, 2)->nullable()->after('extracted_at');
            $table->enum('extraction_status', ['pending', 'processing', 'completed', 'failed'])
                ->nullable()->after('extraction_confidence');
            $table->text('extraction_error')->nullable()->after('extraction_status');

            $table->index('extraction_status');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropIndex(['extraction_status']);
            $table->dropColumn([
                'extracted_data',
                'extracted_at',
                'extraction_confidence',
                'extraction_status',
                'extraction_error',
            ]);
        });
    }
};
