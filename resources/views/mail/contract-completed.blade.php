@php /** @var string $recipientName, string $contractTitle, ?string $contractNo, array $annexNotes, ?string $portalUrl */ @endphp
<x-mail::message>
# Sözleşmeniz Tamamlandı ✅

Merhaba **{{ $recipientName }}**,

@if($contractNo)
**{{ $contractTitle }}** ({{ '#' . $contractNo }}) sözleşmeniz başarıyla onaylandı ve süreç tamamlandı.
@else
**{{ $contractTitle }}** sözleşmeniz başarıyla onaylandı ve süreç tamamlandı.
@endif

İmzalı sözleşmenizin kopyası bu e-postanın ekindedir. Lütfen kayıtlarınız için saklayınız.

@if(!empty($annexNotes))
---

## 📎 Ek Maddeler

@foreach($annexNotes as $note)
- {{ $note }}
@endforeach
@endif

@if($portalUrl)
---

<x-mail::button :url="$portalUrl">
Sözleşme Detaylarını Görüntüle
</x-mail::button>
@endif

---

İyi çalışmalar dileriz,
**MentorDE Ekibi**
</x-mail::message>
