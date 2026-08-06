<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Operasyon → Partner bilgi/belge talebi.
 *
 * Eksik belgeyi operasyon doğrudan öğrenciden istiyordu. Oysa öğrenciyi
 * partner getiriyor ve müşteri ilişkisi onda: eksiği partnerden istemek,
 * partnerin de kendi öğrencisinden istemesi gerekiyor.
 *
 *      Operasyon ──ister──▶ Partner ──ister──▶ Öğrenci
 *                    ◀──iletir──        ◀──yükler──
 *
 * Talep başlık (request), istenen her kalem satır (item). Kalem bazlı
 * olmasının sebebi: "hangisi geldi, hangisi gelmedi" ancak böyle görünür.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_info_requests', function (Blueprint $table) {
            $table->id();

            // Talebi AÇAN firma (operasyonu yürüten) ve talep edilen partner.
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('partner_company_id')->index();

            // Talep hangi kişi hakkında — aday ya da öğrenci.
            $table->string('subject_type', 20);          // guest | student
            $table->string('subject_id', 64);
            $table->string('subject_name', 180)->nullable();

            $table->text('note')->nullable();
            $table->timestamp('due_at')->nullable();

            // open → kalemlerden en az biri bekliyor
            // fulfilled → hepsi geldi
            $table->string('status', 20)->default('open')->index();

            $table->string('created_by', 180)->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamps();

            $table->index(['partner_company_id', 'status']);
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('partner_info_request_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_id')->index();

            // document → katalogdan bir belge · info → serbest bilgi sorusu
            $table->string('kind', 20)->default('document');
            $table->string('category_code', 64)->nullable();
            $table->string('label', 180);

            $table->string('status', 20)->default('pending')->index();

            // Belge geldiyse hangi kayıt; bilgi ise yazılan cevap.
            $table->unsignedBigInteger('document_id')->nullable();
            $table->text('response_text')->nullable();

            // Partner kalemi kendi öğrencisine ilettiyse üretilen yükleme
            // jetonu — zincirin son halkası buradan izleniyor.
            $table->unsignedBigInteger('forwarded_token_id')->nullable();
            $table->timestamp('forwarded_at')->nullable();

            $table->string('provided_by', 180)->nullable();
            $table->timestamp('provided_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_info_request_items');
        Schema::dropIfExists('partner_info_requests');
    }
};
