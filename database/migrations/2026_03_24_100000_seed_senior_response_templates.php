<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $templates = [
            // BELGE
            ['category' => 'document', 'title' => 'Belge Eksik — Pasaport', 'body' => "Merhaba {{student_name}},\n\nBaşvuru dosyanızı inceledim. Pasaportunuzun renkli fotokopisinin sisteme yüklenmesi gerekiyor. Lütfen en kısa sürede \"Belgelerim\" bölümünden yükleyiniz.\n\nHerhangi bir sorunuz olursa yazabilirsiniz."],
            ['category' => 'document', 'title' => 'Belge Eksik — Transkript', 'body' => "Merhaba {{student_name}},\n\nDosyanızda onaylı transkript belgesi eksik. Lütfen üniversitenizden resmi mühürlü transkriptinizi temin ederek sisteme yükleyiniz. Apostil ya da noter onayı gerekip gerekmediğini başvuracağınız üniversiteye göre birlikte değerlendireceğiz."],
            ['category' => 'document', 'title' => 'Belge Onaylandı', 'body' => "Merhaba {{student_name}},\n\nYüklediğiniz belgeler incelendi ve onaylandı. Başvuru süreciniz bir sonraki aşamaya geçmiştir. Sizi bilgilendirmeye devam edeceğim."],
            ['category' => 'document', 'title' => 'Belge Reddedildi — Netlik', 'body' => "Merhaba {{student_name}},\n\nYüklediğiniz belge maalesef kabul edilemedi. Belgenin okunaksız/bulanık göründüğünü fark ettik. Lütfen daha net, yüksek çözünürlüklü bir tarama yükleyin. JPEG veya PDF formatı tercih edilir, minimum 300 DPI önerilir."],

            // VİZE
            ['category' => 'visa', 'title' => 'Vize Randevusu Hatırlatma', 'body' => "Merhaba {{student_name}},\n\nVize randevunuz {{deadline}} tarihine yaklaşıyor. Randevu için gerekli belge listesini kontrol ettiniz mi? Eksik bir belge varsa lütfen en kısa sürede tamamlayın. Herhangi bir sorunuz olursa buradayım."],
            ['category' => 'visa', 'title' => 'Vize Başvurusu — Onaylandı', 'body' => "Merhaba {{student_name}},\n\nHarika haber! Almanya öğrenci vizeniz onaylandı. Pasaportunuzu Konsolosluktan teslim almayı unutmayın. Bundan sonraki adımlar için sizinle iletişime geçeceğim."],
            ['category' => 'visa', 'title' => 'Vize — Ek Belge Talebi', 'body' => "Merhaba {{student_name}},\n\nKonsolosluk vize dosyanız için ek belge talep etti. İstenen belgeler:\n- Güncel banka hesap dökümü (son 3 ay)\n- Konaklama teyit belgesi\n\nBelgeleri en geç 5 iş günü içinde teslim etmeniz gerekmektedir."],

            // DİL
            ['category' => 'language', 'title' => 'Dil Belgesi Gereksinimi', 'body' => "Merhaba {{student_name}},\n\n{{university}} için başvurunuzda geçerli bir Almanca dil belgesi (en az B2 düzeyinde) gerekmektedir. TestDaF, DSH veya Goethe sertifikası kabul edilmektedir. Sınav planlamanız için yardımcı olmamı ister misiniz?"],
            ['category' => 'language', 'title' => 'Dil Kursu Tavsiyesi', 'body' => "Merhaba {{student_name}},\n\nDil seviyenizi değerlendirdim ve B1 düzeyinde olduğunuzu gördüm. Almanya'ya gitmeden önce B2 seviyesine ulaşmanız için yoğun bir dil programına başlamanızı öneririm. Bu konuda size uygun seçenekleri paylaşabilirim."],

            // KONUT
            ['category' => 'housing', 'title' => 'Yurt Başvurusu Hatırlatma', 'body' => "Merhaba {{student_name}},\n\n{{university}} öğrenci yurdu için başvuru tarihi yaklaşıyor. Studierendenwerk üzerinden başvurunuzu yaptınız mı? Yurt kontenjanları hızlı dolmaktadır, en kısa sürede başvurmanızı öneririm. Yardıma ihtiyacınız olursa buradayım."],
            ['category' => 'housing', 'title' => 'Konut Alternatifi — WG', 'body' => "Merhaba {{student_name}},\n\nÖğrenci yurdu kontenjanı dolmuş durumda. Alternatif olarak WG (Wohngemeinschaft / paylaşımlı daire) seçeneğini değerlendirebilirsiniz. WG-Gesucht ve Studenten-WG platformlarını takip etmenizi öneririm. Arama stratejileri hakkında konuşmak için randevu oluşturabilirsiniz."],

            // ÖDEME
            ['category' => 'payment', 'title' => 'Sperrkonto Açılış Bilgisi', 'body' => "Merhaba {{student_name}},\n\nAlmanya öğrenci vizesi için Sperrkonto (bloke hesap) açmanız gerekmektedir. Güncel tutar yaklaşık 11.208 EUR (2024 yılı). Fintiba veya Deutsche Bank üzerinden açabilirsiniz. Hesap açma süreciyle ilgili detaylı bilgi için benimle iletişime geçebilirsiniz."],
            ['category' => 'payment', 'title' => 'Program Ücreti — Taksit Hatırlatması', 'body' => "Merhaba {{student_name}},\n\n{{deadline}} tarihinde program ücretinin bir sonraki taksiti tahakkuk edecektir. Ödeme planınızı takip etmenizi ve sorun yaşamanız halinde önceden bilgi vermenizi rica ederim."],

            // GENEL
            ['category' => 'general', 'title' => 'Hoş Geldiniz', 'body' => "Merhaba {{student_name}},\n\nMentorDE ailesine hoş geldiniz! Ben danışmanınız olacağım. Süreç boyunca her adımda yanınızda olacağım. Sorularınız, endişeleriniz veya paylaşmak istediğiniz her şey için bu platform üzerinden bana ulaşabilirsiniz.\n\nBaşarılı bir süreç dilerim!"],
            ['category' => 'general', 'title' => 'Randevu Onayı', 'body' => "Merhaba {{student_name}},\n\nRandevunuz onaylandı. {{appointment_date}} tarihinde görüşeceğiz. Görüşmeden önce gündem maddelerinizi not etmenizi öneririm. Görüşme linki veya adres bilgisini ayrıca ileteceğim."],
            ['category' => 'general', 'title' => 'Süreç Güncelleme', 'body' => "Merhaba {{student_name}},\n\nDosyanızı güncelledim. Şu an {{current_stage}} aşamasındasınız. Bir sonraki adım: {{next_step}}. Takipte kalın, herhangi bir gelişme olduğunda sizi bilgilendireceğim."],
            ['category' => 'general', 'title' => 'Bilgi Talebi Yanıtı', 'body' => "Merhaba {{student_name}},\n\nSorunuz için teşekkür ederim. Bu konuyla ilgili şunları paylaşabilirim:\n\n[Burayı bilgiyle doldurun]\n\nBaşka sorunuz olursa lütfen çekinmeden yazın."],
            ['category' => 'general', 'title' => 'Süreç Tamamlandı — Tebrik', 'body' => "Merhaba {{student_name}},\n\nTebrikler! Almanya eğitim başvuru sürecinizin tüm aşamalarını başarıyla tamamladınız. Bu yolculukta size eşlik etmek büyük bir zevkti. Almanya'da başarılı ve mutlu bir öğrencilik hayatı dilerim!\n\nHerhangi bir konuda destek gerekirse her zaman buradayım."],
        ];

        foreach ($templates as $tpl) {
            DB::table('senior_response_templates')->insert([
                'company_id'    => null,
                'owner_user_id' => null,
                'category'      => $tpl['category'],
                'title'         => $tpl['title'],
                'body'          => $tpl['body'],
                'usage_count'   => 0,
                'is_active'     => true,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('senior_response_templates')
            ->whereNull('company_id')
            ->whereNull('owner_user_id')
            ->delete();
    }
};
