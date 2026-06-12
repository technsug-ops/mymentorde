@php
/**
 * @var \App\Models\Company $company
 * @var \App\Models\User $manager
 * @var \Carbon\Carbon|null $trialEndsAt
 * @var string $loginUrl
 * @var string $dashboardUrl
 * @var string $planUrl
 * @var string $brandName
 * @var string $supportEmail
 */
@endphp
<x-mail::message>
# Hoş geldin {{ $manager->name }}! 🎉

**{{ $company->name }}** firması için {{ $brandName }} hesabın **başarıyla oluşturuldu**.

14 gün boyunca **tüm Gold özelliklerini ücretsiz** test edebilirsin — kredi kartı eklemen gerekmez.

@if($trialEndsAt)
| | |
|---|---|
| **Plan** | {{ ucfirst($company->subscription_tier) }} (Trial) |
| **Trial bitiş** | {{ \Carbon\Carbon::parse($trialEndsAt)->locale('tr')->isoFormat('D MMMM YYYY') }} |
| **Giriş e-posta** | {{ $manager->email }} |
@endif

<x-mail::button :url="$dashboardUrl">
🚀 Panele Git
</x-mail::button>

## İlk 24 saatte 4 adım

İlk birkaç saatte sistemi tanımak için:

1. **📚 İlk öğrencini ekle** — Manager Dashboard'dan "Yeni Aday" / "Yeni Öğrenci"
2. **👥 Danışmanlarını davet et** — Kullanıcı yönetimi → "+ Yeni Danışman/Mentor Ekle"
3. **🎨 Marka renklerini ayarla** — `Ayarlar → Tema` (logo + renk paleti)
4. **🔗 Public formlarını test et** — `/apply` (aday başvuru), `/randevu` (booking)

---

## Trial süresince neler açık?

Hesabında **14 gün boyunca** şu modüller aktif:

- ✅ Booking & Randevu sistemi
- ✅ Marka Kütüphanesi (DAM)
- ✅ İçerik Hub'ı
- ✅ Çoklu AI Provider (Gemini + Claude + GPT)
- ✅ AI Labs (asistan + doküman üretici)
- ✅ Belge Talep Linki (premium)
- ✅ Application Guides (APS, Uni-Assist, Vize rehberleri)
- ✅ Sınırsız danışman hesabı

Tüm özellikleri rahatça dene, sonra planını seç.

---

## Yardıma ihtiyacın olursa

- 📧 **E-posta**: [{{ $supportEmail }}](mailto:{{ $supportEmail }})
- 📖 **Plan & Modüller**: [{{ $planUrl }}]({{ $planUrl }})
- ⚙️ **Giriş**: [{{ $loginUrl }}]({{ $loginUrl }})

Genelde 1 saat içinde dönüyoruz. Sorularını çekinme.

Başarılar! 🎓

— {{ $brandName }} ekibi
</x-mail::message>
