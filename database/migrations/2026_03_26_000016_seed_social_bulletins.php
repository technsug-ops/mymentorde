<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $company  = DB::table('companies')->where('is_active', true)->orderBy('id')->first();
        $cid      = $company?->id ?? 1;
        $manager  = DB::table('users')->where('role', 'manager')->first();
        if (!$manager) {
            return; // Users not seeded yet — skip bulletin seed
        }
        $authorId = $manager->id;
        $now      = now();

        // ── Dev kullanıcılara doğum tarihi ata (test için bugün = 26 Mart) ──
        $birthDates = [
            ['email' => 'seniorww@mentorde.local',       'birth_date' => '1990-03-26'], // Bugün!
            ['email' => 'marketing.admin@mentorde.local', 'birth_date' => '1992-06-15'],
            ['email' => 'sales.admin@mentorde.local',     'birth_date' => '1988-11-22'],
            ['email' => 'senior2@mentorde.local',         'birth_date' => '1995-03-26'], // Bugün!
        ];
        foreach ($birthDates as $bd) {
            DB::table('users')->where('email', $bd['email'])->update(['birth_date' => $bd['birth_date']]);
        }

        // ── Örnek duyurular ──────────────────────────────────────────────────
        $bulletins = [

            // KUTLAMA — Manuel oluşturulmuş (örnek)
            [
                'company_id'   => $cid,
                'author_id'    => $authorId,
                'title'        => '🎉 Sena Yılmaz — Aramıza Katılışının 1. Yılı! 🥂',
                'body'         => "Sevgili Sena,\n\nBugün MentorDE ailesine katılışının tam 1 yılı! 🎊\n\nBir yıl önce ekibimize kattığın enerji, özveri ve başarı odaklı çalışma anlayışın için teşekkür ederiz. Ekibimizin vazgeçilmez bir parçası oldun.\n\nSana önümüzdeki yıllarda da daha büyük başarılar diliyoruz. 🌟\n\nMentorDE Ailesi 💙",
                'category'     => 'kutlama',
                'is_pinned'    => false,
                'published_at' => $now->copy()->subDays(1),
                'expires_at'   => $now->copy()->addDays(3),
                'created_at'   => $now,
                'updated_at'   => $now,
            ],

            // KUTLAMA — Proje tamamlama
            [
                'company_id'   => $cid,
                'author_id'    => $authorId,
                'title'        => '🏆 Tebrikler! Q1 Hedefini %118 ile Kapattık! 🎯',
                'body'         => "Değerli ekibimiz,\n\nBirinci çeyreği büyük bir başarıyla kapattık! 🎉\n\nHedefimiz 85 başarılı yerleştirmeydi. Gerçekleşen: 100 yerleştirme (%118). 🚀\n\nBu başarı hepinizin ortak emeğinin ürünü:\n\n✅ Senior Danışmanlar — öğrenci takibi ve ilgi\n✅ Sales Ekibi — lead kalitesi ve dönüşüm\n✅ Marketing Ekibi — marka bilinirliği ve içerik\n✅ Operations — süreç kolaylığı\n\nHerkese büyük teşekkür! Cuma öğle yemeği şirket tarafından ısmarlama! 🍽️\n\nYönetim",
                'category'     => 'kutlama',
                'is_pinned'    => true,
                'published_at' => $now->copy()->subDays(2),
                'expires_at'   => $now->copy()->addDays(5),
                'created_at'   => $now,
                'updated_at'   => $now,
            ],

            // MOTİVASYON — Haftalık
            [
                'company_id'   => $cid,
                'author_id'    => $authorId,
                'title'        => '✨ Haftanın Motivasyon Mesajı — "Küçük adımlar büyük yolculuklar yaratır."',
                'body'         => "Merhaba değerli ekip,\n\n\"Bir öğrencinin hayatını değiştirmeye başlarsın küçük bir konuşmayla.\nO konuşma üniversite kabulüne dönüşür.\nKabul, bir kariyere dönüşür.\nKariyer, bir aileye.\nBir aile, bir nesle.\"\n\nYaptığınız işin büyüklüğünü unutmayın. Her gün yaptığınız o 'küçük' şeyler, biri için dünyayı değiştiriyor. 🌍\n\nHarika bir hafta geçirin! 💪\n\n— Yönetim",
                'category'     => 'motivasyon',
                'is_pinned'    => false,
                'published_at' => $now->copy()->subDays(4),
                'expires_at'   => $now->copy()->addDays(3),
                'created_at'   => $now,
                'updated_at'   => $now,
            ],

            // MOTİVASYON — Öğrenci geri bildirimi
            [
                'company_id'   => $cid,
                'author_id'    => $authorId,
                'title'        => '💌 Öğrencimizden Teşekkür Mesajı — Bizi Güldürdü!',
                'body'         => "Ekibimize iletmek istediğimiz güzel bir mesaj var:\n\n---\n\"MentorDE olmadan bu yolculuk çok daha zorlu olurdu. Sadece üniversite bulmakla kalmadılar, bizi o ülkeye hazırladılar, elimizden tuttular. Almanya'da ilk günüm böyle güzel başladı çünkü arkamda böyle bir ekip vardı. Herkese teşekkürler 🙏\"\n— Mehmet A., TU Berlin Mühendislik, 2025\n---\n\nBu mesajları okumak için çalışıyoruz. Teşekkürler! ❤️",
                'category'     => 'motivasyon',
                'is_pinned'    => false,
                'published_at' => $now->copy()->subDays(6),
                'expires_at'   => null,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],

        ];

        DB::table('company_bulletins')->insert($bulletins);
    }

    public function down(): void
    {
        DB::table('company_bulletins')->whereIn('category', ['kutlama', 'motivasyon'])->delete();
        DB::table('users')->whereIn('email', [
            'seniorww@mentorde.local', 'marketing.admin@mentorde.local',
            'sales.admin@mentorde.local', 'senior2@mentorde.local',
        ])->update(['birth_date' => null]);
    }
};
