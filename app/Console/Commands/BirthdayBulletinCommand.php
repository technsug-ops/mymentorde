<?php

namespace App\Console\Commands;

use App\Models\CompanyBulletin;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BirthdayBulletinCommand extends Command
{
    protected $signature   = 'bulletin:send-birthday-wishes';
    protected $description = 'Bugün doğum günü olan çalışanlar için otomatik kutlama duyurusu oluştur';

    // Doğum günü mesajı şablonları — her gün rastgele biri seçilir
    private const TEMPLATES = [
        [
            'title' => '🎂 Bugün {name} doğum günü! Tüm ekibimiz adına kutlarız 🎉',
            'body'  => "Sevgili {name},\n\nBugün özel günün! 🎂🎉\n\nMentorDE ailesi olarak doğum günününü en içten dileklerimizle kutluyoruz. Sağlık, mutluluk ve başarı dolu bir yıl geçirmeni diliyoruz.\n\nMutlu yıllar! 🥳🎈",
        ],
        [
            'title' => '🎈 {name} — Doğum günün kutlu olsun! 🥳',
            'body'  => "Merhaba {name}! 🎉\n\nBugün senin günün! Ekibimizin her bir üyesi adına en içten doğum günü dileklerimizi iletiyoruz.\n\n✨ Sağlık dolu bir yıl\n💪 Güçlü ve başarılı adımlar\n❤️ Mutluluk ve neşe\n\nGeçen yılda bize kattıkların için teşekkürler, önümüzdeki yılda daha güzel şeyler seni bekliyor! 🎂",
        ],
        [
            'title' => '🎊 Doğum Günü Kutlaması: {name}! 🎂',
            'body'  => "Bugün aramızdaki değerli ekip üyemiz {name}'in doğum günü! 🎊\n\nYaptığın katkılar, getirdiğin enerji ve ekibimize olan bağlılığın için teşekkür ederiz.\n\nSana bu özel günde:\n🎂 Lezzetli bir pasta\n🎈 Bolca neşe\n🌟 Güzel sürprizler\n\n...dileyebiliriz! Mutlu yıllar {name}! 🥳",
        ],
        [
            'title' => '🌟 Büyük Gün: {name} — Happy Birthday! 🎉',
            'body'  => "Hey {name}! 🎈\n\nBugün dünyaya adım attığın o muhteşem günün yıl dönümü! 🌍✨\n\nTüm ekibimiz seninle kutlamak istiyor. Bugün bir şeyler yiyelim, güzelce kutlayalım!\n\nSevgi ve saygıyla,\nMentorDE Ailesi 💙\n\nMutlu yıllar! 🎂🥳🎊",
        ],
    ];

    public function handle(): int
    {
        $today = now()->format('m-d'); // Ay-Gün
        $year  = now()->year;

        // Doğum günü bugün olan tüm çalışanlar (student, guest, dealer değil)
        $staffRoles = ['manager', 'system_admin', 'system_staff', 'operations_admin', 'operations_staff',
                       'finance_admin', 'finance_staff', 'marketing_admin', 'marketing_staff',
                       'sales_admin', 'sales_staff', 'senior', 'mentor'];

        $users = User::whereNotNull('birth_date')
            ->whereIn('role', $staffRoles)
            ->get()
            ->filter(fn($u) => \Carbon\Carbon::parse($u->birth_date)->format('m-d') === $today);

        if ($users->isEmpty()) {
            $this->info('Bugün doğum günü olan çalışan yok.');
            return self::SUCCESS;
        }

        foreach ($users as $user) {
            $companyId = $user->company_id;

            // Aynı kişi için bugün zaten duyuru oluşturduk mu?
            $exists = CompanyBulletin::where('company_id', $companyId)
                ->where('category', 'kutlama')
                ->where('author_id', null)
                ->whereDate('published_at', now()->toDateString())
                ->where('title', 'LIKE', '%' . $user->name . '%')
                ->exists();

            if ($exists) {
                $this->line("  ↳ {$user->name} için duyuru zaten var, atlanıyor.");
                continue;
            }

            // Şablon seç (gün bazlı sabit → tutarlı)
            $tpl  = self::TEMPLATES[crc32($user->name . $year) % count(self::TEMPLATES)];
            $name = $user->name;

            // Sistemi temsil eden yazar: şirketteki ilk manager veya null
            $authorId = User::where('company_id', $companyId)
                ->where('role', 'manager')
                ->value('id');

            CompanyBulletin::create([
                'company_id'   => $companyId,
                'author_id'    => $authorId,
                'title'        => str_replace('{name}', $name, $tpl['title']),
                'body'         => str_replace('{name}', $name, $tpl['body']),
                'category'     => 'kutlama',
                'is_pinned'    => true,
                'published_at' => now(),
                'expires_at'   => now()->endOfDay(), // Gün sonunda arşive düşer
            ]);

            // Cache temizle — tüm kullanıcılar yeni duyuruyu görsün
            Cache::forget("urgent_bulletins_{$companyId}");

            $this->info("✓ {$name} için doğum günü duyurusu oluşturuldu.");
        }

        return self::SUCCESS;
    }
}
