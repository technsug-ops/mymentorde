<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Şirket yetki TAVANI — üst firmanın alt firmaya koyduğu kısıt.
 *
 * İş modeli: partner firmalar öğrenciyi bize devreder, operasyonu biz
 * yürütürüz. Ama her firma aynı değil — biri sadece izlesin, biri belge de
 * yükleyebilsin, biri kendi operasyonunu yürütsün. Bunu SABİT bir rolle
 * çözmek yanlış olurdu; ağacın üstündeki firma kısıtı kendisi ayarlamalı.
 *
 * MODEL: varsayılan TAM yetki, kısıt EKLENEREK daraltılır (deny list).
 * Boş liste = hiçbir kısıt yok = rolün verdiği her şey geçerli.
 *
 * Kısıtlar AĞAÇTAN AŞAĞI birikir: bir firmaya konan kısıt, onun altındaki
 * firmaları da bağlar. MentorDE YourGermanUni'ye "dönüştürme yapamaz" derse,
 * Aythink ve Novavia da yapamaz.
 *
 * Platform sahibi bu tavandan MUAF — aksi halde kendi platformunu kilitleyebilirdi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('companies', 'denied_permission_codes')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table): void {
            $table->json('denied_permission_codes')->nullable()->after('enabled_modules');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('companies', 'denied_permission_codes')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn('denied_permission_codes');
        });
    }
};
