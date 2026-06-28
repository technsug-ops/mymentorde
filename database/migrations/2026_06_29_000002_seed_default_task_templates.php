<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Varsayılan görev şablonları (eğitim danışmanlığı süreçleri). Şablon seçilince
 * göreve hazır checklist (adım listesi) oluşur. Idempotent: company_id=1 için
 * zaten şablon varsa atlar. Başka şirketler kendi şablonlarını oluşturabilir.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('task_templates')) {
            return;
        }
        $cid = 1;
        if (DB::table('task_templates')->where('company_id', $cid)->exists()) {
            return; // zaten şablon var → dokunma
        }

        $now = now();
        $templates = [
            ['name' => 'Yeni Öğrenci Onboarding', 'category' => 'onboarding', 'desc' => 'Yeni öğrenci kaydı sonrası karşılama ve başlangıç adımları.',
             'items' => ['Karşılama e-postası gönder', 'Kayıt formunu kontrol et', 'Eksik belge listesini paylaş', 'Danışman ata', 'İlk görüşmeyi planla']],
            ['name' => 'Belge Toplama & Onay', 'category' => 'process', 'desc' => 'Öğrenci belgelerinin toplanması ve onaylanması süreci.',
             'items' => ['Zorunlu belge listesini çıkar', 'Öğrenciye eksikleri bildir', 'Yüklenen belgeleri incele', 'Onayla veya reddet', 'Eksikse hatırlatma gönder']],
            ['name' => 'Vize Başvuru Süreci', 'category' => 'process', 'desc' => 'Almanya vize başvurusu için adım adım takip.',
             'items' => ['Konsolosluk randevusu al', 'Başvuru dosyasını hazırla', 'Niyet mektubunu yaz', 'Sperrkonto (bloke hesap) aç', 'Sağlık sigortası ayarla', 'Başvuruyu son kez kontrol et']],
            ['name' => 'Sözleşme Süreci', 'category' => 'contract', 'desc' => 'Paket seçiminden imzalı sözleşmeye kadar olan akış.',
             'items' => ['Paket ve hizmetleri belirle', 'Sözleşmeyi oluştur', 'Öğrenciye gönder', 'İmzayı al', 'Onayla ve arşivle']],
        ];

        foreach ($templates as $tpl) {
            $tplId = DB::table('task_templates')->insertGetId([
                'company_id'  => $cid,
                'name'        => $tpl['name'],
                'description' => $tpl['desc'],
                'department'  => 'operations',
                'category'    => $tpl['category'],
                'is_chain'    => false,
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
            $rows = [];
            foreach ($tpl['items'] as $i => $label) {
                $rows[] = [
                    'template_id' => $tplId,
                    'title'       => $label,
                    'priority'    => 'normal',
                    'sort_order'  => ($i + 1) * 10,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }
            DB::table('task_template_items')->insert($rows);
        }
    }

    public function down(): void
    {
        // Seed verisi — geri alımda dokunma (kullanıcı düzenlemiş olabilir).
    }
};
