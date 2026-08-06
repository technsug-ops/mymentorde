<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Panel modu — firma tam yönetim panelini mi, sade takip penceresini mi görür?
 *
 * SORUN: partner firmalar `manager` rolü aldığı için MentorDE'nin tam panelini
 * görüyordu — İnsan Kaynakları, Finans, VIP Oversight, Sistem Yönetimi, AI
 * Labs, UniMatch katalog yönetimi... 60'tan fazla sayfa.
 *
 * Ama partnerlere SaaS satmıyoruz. Onlar öğrencilerini bize devredip süreci
 * izliyorlar. İhtiyaçları olan şey bir takip penceresi.
 *
 * `partner` modunda menü sadeleşir VE adresler kapanır (RestrictPartnerPanel).
 * Yalnızca menüyü gizlemek göstermelik olurdu — adresi bilen yine girerdi.
 *
 * Varsayılan `full`: mevcut hiçbir şirketin davranışı değişmez.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('companies', 'panel_mode')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table): void {
            $table->string('panel_mode', 20)->default('full')->after('enabled_modules');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('companies', 'panel_mode')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn('panel_mode');
        });
    }
};
