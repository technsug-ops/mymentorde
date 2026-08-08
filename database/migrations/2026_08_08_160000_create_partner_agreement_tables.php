<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Portal ↔ partner firma anlaşmaları.
 *
 * ── HANGİ SÖZLEŞME ──────────────────────────────────────────────────────
 * Sistemde şimdiye kadar iki taraflı sözleşme vardı: bayi ve personel
 * (`business_contracts.contract_type`). Partner firmayla yapılan anlaşmanın
 * yeri yoktu.
 *
 * ⚠ Bu, öğrenciyle yapılan sözleşme DEĞİL. Partner firmanın öğrencisinden
 * ne aldığı ve sözleşmesinin metni bu sistemin konusu değil — öğrenciyi
 * portal (YourGermanUni) white-label takip ediyor, operasyonu MentorDE
 * yürütüyor. Partner isterse öğrenci sözleşmesini kaydeder, zorunlu değil.
 *
 * Burada tutulan, portal ile partner arasındaki iki katman:
 *
 *   1. GENEL ANLAŞMA (`partner_agreements`) — çerçeve sözleşme. İki taraf
 *      imzalar. Öğrenci başına standart bedeli de taşır; öğrenci bazlı
 *      anlaşmanın varsayılanı buradan gelir.
 *
 *   2. ÖĞRENCİ BAZLI ANLAŞMA (`partner_student_agreements`) — o öğrenci
 *      için partnerin portala ödeyeceği tutar. Finansın saydığı rakam bu.
 *
 * ── İKİ SAHİPLİ KAYIT ───────────────────────────────────────────────────
 * Her iki tabloda da `company_id` (portalı işleten firma) ve
 * `partner_company_id` (partner) birlikte duruyor. Modeller bu yüzden
 * `BelongsToCompany` DEĞİL `SharedBetweenTwoCompanies` kullanıyor: tek
 * sütuna bakan global kapsam taraflardan birini her zaman kör ederdi —
 * partner kendisine gelen anlaşmayı göremezdi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_agreements', function (Blueprint $table): void {
            $table->id();

            // Portalı işleten firma (anlaşmayı açan taraf)
            $table->unsignedBigInteger('company_id')->index();
            // Partner firma (karşı taraf)
            $table->unsignedBigInteger('partner_company_id')->index();

            $table->string('title', 200);
            $table->longText('body_text')->nullable();

            // Öğrenci başına standart bedel — öğrenci bazlı anlaşmanın
            // varsayılanı. Çerçevede peşinen anlaşıldığı için partner o
            // tutarda tek adımda kapatabiliyor; farklı tutar operasyonun
            // teklifini gerektirir.
            $table->decimal('standard_student_fee_eur', 10, 2)->nullable();
            $table->string('currency', 3)->default('EUR');

            // draft → sent → signed  (terminated her aşamadan)
            $table->string('status', 20)->default('draft')->index();

            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('signed_by_email', 190)->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->string('termination_reason', 500)->nullable();

            // Dışarıda imzalanmışsa kanıt belgesi (local disk yolu)
            $table->string('signed_file_path', 500)->nullable();

            $table->string('created_by', 190)->nullable();
            $table->timestamps();

            $table->index(['partner_company_id', 'status']);
        });

        Schema::create('partner_student_agreements', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('partner_company_id')->index();

            // Hangi çerçeveye dayanıyor (silinirse kayıt kalsın diye nullable)
            $table->unsignedBigInteger('agreement_id')->nullable()->index();

            // Konu: aday ya da (dönüşüm sonrası) öğrenci. İkisi de tutuluyor —
            // dönüşümden sonra aday kaydı üzerinden aramak zorunda kalmayalım.
            $table->unsignedBigInteger('guest_application_id')->nullable()->index();
            $table->string('student_id', 64)->nullable()->index();
            $table->string('subject_name', 200)->nullable();

            // Partnerin portala ödeyeceği tutar. Partnerin öğrenciden ne
            // aldığı burada YOK — bilerek.
            $table->decimal('fee_eur', 10, 2);
            $table->string('currency', 3)->default('EUR');

            // proposed → accepted  (rejected / cancelled uçları)
            $table->string('status', 20)->default('proposed')->index();

            $table->timestamp('proposed_at')->nullable();
            $table->string('proposed_by', 190)->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->string('accepted_by', 190)->nullable();
            $table->string('note', 500)->nullable();

            $table->timestamps();

            // "Bu adayın anlaşması var mı?" en sık sorulan soru.
            $table->index(['guest_application_id', 'status']);
            $table->index(['partner_company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_student_agreements');
        Schema::dropIfExists('partner_agreements');
    }
};
