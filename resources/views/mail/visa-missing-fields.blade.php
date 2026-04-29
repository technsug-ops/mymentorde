<x-mail::message>
# Vize Başvurun İçin Bilgi Lazım 🛂

Merhaba **{{ $recipientName }}**,

Almanya vize başvurun (VIDEX formu) için aşağıdaki bilgilere ihtiyacımız var. Bu bilgiler olmadan konsolosluk başvurunu eksiksiz hazırlayamıyoruz:

@foreach($missingLabels as $label)
- {{ $label }}
@endforeach

@if($managerNote)
---

**Danışmanından not:**
> {{ $managerNote }}
@endif

---

Lütfen panele girip ilgili alanları tamamla. Eksik bir alan vize randevunda problem yaratabilir.

@if($portalUrl)
<x-mail::button :url="$portalUrl">
Panele Git ve Bilgileri Tamamla
</x-mail::button>
@endif

Soru olursa danışmanına yaz, hemen yardımcı olur.

İyi çalışmalar,
**MentorDE Ekibi**
</x-mail::message>
