<x-mail::message>
# Uni-Assist Başvurun İçin Bilgi Lazım 📝

Merhaba **{{ $recipientName }}**,

Almanya üniversite başvurun için Uni-Assist sistemine eksiksiz başlayabilmemiz adına aşağıdaki bilgilere ihtiyacımız var:

@foreach($missingLabels as $label)
- {{ $label }}
@endforeach

@if($managerNote)
---

**Danışmanından not:**
> {{ $managerNote }}
@endif

---

Lütfen panele girip ilgili alanları tamamla — bu bilgiler olmadan başvurunu açamıyoruz.

@if($portalUrl)
<x-mail::button :url="$portalUrl">
Panele Git ve Bilgileri Tamamla
</x-mail::button>
@endif

Soru olursa danışmanına yaz, hemen yardımcı olur.

İyi çalışmalar,
**MentorDE Ekibi**
</x-mail::message>
