<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $company = DB::table('companies')->where('is_active', true)->orderBy('id')->first();
        $companyId = $company?->id ?? 1;

        $manager = DB::table('users')->where('role', 'manager')->first();
        if (!$manager) {
            return; // Users not seeded yet — skip bulletin seed
        }
        $authorId = $manager->id;

        $now = now();

        $bulletins = [
            // ─── ACİL ───────────────────────────────────────────────────────────
            [
                'company_id'   => $companyId,
                'author_id'    => $authorId,
                'title'        => '🚨 Sunucu Bakımı — 26 Mart 23:00–01:00 arası sistem kapalı',
                'body'         => "Sevgili Ekip,\n\n26 Mart Salı gecesi saat 23:00 ile 01:00 arasında altyapı bakımı yapılacaktır. Bu süre zarfında tüm portallara erişim geçici olarak kesintiye uğrayacaktır.\n\nÖnemli: Gece vardiyasında çalışan arkadaşlar lütfen önceden gerekli dokümanlarını indirip yerel ortama alın.\n\nBakım tamamlanır tamamlanmaz sistem otomatik olarak ayağa kalkacaktır. Sorunuz olursa teknik ekibe yazabilirsiniz.",
                'category'     => 'acil',
                'is_pinned'    => true,
                'published_at' => $now->copy()->subHour(),
                'expires_at'   => $now->copy()->addDays(1),
                'created_at'   => $now,
                'updated_at'   => $now,
            ],

            // ─── DUYURU (pinned) ─────────────────────────────────────────────
            [
                'company_id'   => $companyId,
                'author_id'    => $authorId,
                'title'        => '📋 Nisan Ayı Hedefleri & OKR Değerlendirme Takvimi',
                'body'         => "Ekibimize duyurulur,\n\nNisan ayı OKR hedefleri yönetim tarafından sisteme girilmiştir. Her departman aşağıdaki takvime göre bireysel değerlendirme toplantısına hazırlanmalıdır:\n\n• Marketing & Sales: 2 Nisan Çarşamba 10:00\n• Senior Danışmanlar: 3 Nisan Perşembe 14:00\n• Operations: 4 Nisan Cuma 11:00\n\nDeğerlendirme formlarını profil sayfanızdaki dökümanlar bölümünden indirebilirsiniz. Doldurup toplantıya gelmeniz beklenmektedir.\n\nİyi çalışmalar,\nYönetim",
                'category'     => 'duyuru',
                'is_pinned'    => true,
                'published_at' => $now->copy()->subDays(2),
                'expires_at'   => $now->copy()->addDays(7),
                'created_at'   => $now,
                'updated_at'   => $now,
            ],

            // ─── İK ──────────────────────────────────────────────────────────
            [
                'company_id'   => $companyId,
                'author_id'    => $authorId,
                'title'        => '🌴 2026 Yıllık İzin Hakkı Güncellendi',
                'body'         => "Tüm çalışanlara bilgi verilmesi gereken bir İK güncellemesi:\n\nÇalışma yasasında yapılan düzenleme kapsamında, kıdemi 5 yılı aşan personelin yıllık izin hakkı 14 günden 20 güne yükseltilmiştir. Bu değişiklik 1 Nisan 2026 itibarıyla geçerlidir.\n\nİzin bakiyeniz profil sayfanızda güncel şekilde yansıtılmaktadır. Herhangi bir tutarsızlık görürseniz İK birimiyle iletişime geçiniz.\n\nSaygılarımızla,\nİnsan Kaynakları",
                'category'     => 'ik',
                'is_pinned'    => false,
                'published_at' => $now->copy()->subDays(3),
                'expires_at'   => $now->copy()->addDays(30),
                'created_at'   => $now,
                'updated_at'   => $now,
            ],

            // ─── GENEL ───────────────────────────────────────────────────────
            [
                'company_id'   => $companyId,
                'author_id'    => $authorId,
                'title'        => '☕ Yeni Mutfak Etiketi — Ortak Alanları Birlikte Temiz Tutalım',
                'body'         => "Ofisimizi paylaşan herkese hatırlatma:\n\nMutfak ve ortak alan kurallarını güncelledik. Özellikle dikkat edilmesi gerekenler:\n\n• Bulaşıklarınızı kullandıktan sonra yıkayın veya bulaşık makinesine koyun\n• Buzdolabındaki yiyeceklerinizi Cuma günü etiketle işaretleyin; etiketsize Cuma akşamı imha edilir\n• Kahve makinesinin su haznesi boşalınca doldurun\n\nKüçük bir özen büyük bir fark yaratır. Teşekkürler!",
                'category'     => 'genel',
                'is_pinned'    => false,
                'published_at' => $now->copy()->subDays(5),
                'expires_at'   => null,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],

            // ─── DUYURU ──────────────────────────────────────────────────────
            [
                'company_id'   => $companyId,
                'author_id'    => $authorId,
                'title'        => '🏆 Şubat Ayı En Başarılı Danışman: Sena Yılmaz',
                'body'         => "Ekibimize duyurulur,\n\nŞubat 2026 döneminde en yüksek öğrenci yerleştirme oranını yakalayan Senior Danışmanımız Sena Yılmaz'ı tebrik ederiz! 🎉\n\nSena, bu ay toplamda 12 başarılı yerleştirme gerçekleştirerek ekip rekorunu kırmıştır.\n\nBu başarı tüm ekibimize ilham kaynağı olmaktadır. Sena'ya başarılarının devamını dileriz.\n\nYönetim adına",
                'category'     => 'duyuru',
                'is_pinned'    => false,
                'published_at' => $now->copy()->subDays(10),
                'expires_at'   => null,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],

            // ─── GENEL (süresi dolmuş — arşiv testi) ────────────────────────
            [
                'company_id'   => $companyId,
                'author_id'    => $authorId,
                'title'        => '📦 Eski Yazıcı Değiştirildi — Test Baskısı Alınız',
                'body'         => "3. kattaki lazer yazıcı yenisiyle değiştirilmiştir. Sürücü kurulumu IT tarafından yapılacaktır, ancak test çıktısı alarak çalıştığını doğrulamanız beklenmektedir. Sorun yaşarsanız iç ticket sistemi üzerinden IT ekibine bildiriniz.",
                'category'     => 'genel',
                'is_pinned'    => false,
                'published_at' => $now->copy()->subDays(20),
                'expires_at'   => $now->copy()->subDays(5), // süresi geçmiş → arşivde
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
        ];

        DB::table('company_bulletins')->insert($bulletins);
    }

    public function down(): void
    {
        DB::table('company_bulletins')->whereIn('title', [
            '🚨 Sunucu Bakımı — 26 Mart 23:00–01:00 arası sistem kapalı',
            '📋 Nisan Ayı Hedefleri & OKR Değerlendirme Takvimi',
            '🌴 2026 Yıllık İzin Hakkı Güncellendi',
            '☕ Yeni Mutfak Etiketi — Ortak Alanları Birlikte Temiz Tutalım',
            '🏆 Şubat Ayı En Başarılı Danışman: Sena Yılmaz',
            '📦 Eski Yazıcı Değiştirildi — Test Baskısı Alınız',
        ])->delete();
    }
};
