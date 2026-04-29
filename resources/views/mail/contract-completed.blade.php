@php /** @var string $recipientName, string $contractTitle, ?string $contractNo, array $annexNotes, ?string $portalUrl, ?string $paymentAmountText, ?string $paymentReference, array $bankInfo, int $paymentDueDays */ @endphp
<x-mail::message>
# Sözleşmeniz Tamamlandı ✅

Merhaba **{{ $recipientName }}**,

@if($contractNo)
**{{ $contractTitle }}** ({{ '#' . $contractNo }}) sözleşmeniz başarıyla onaylandı ve süreç tamamlandı.
@else
**{{ $contractTitle }}** sözleşmeniz başarıyla onaylandı ve süreç tamamlandı.
@endif

İmzalı sözleşmenizin kopyası bu e-postanın ekindedir. Lütfen kayıtlarınız için saklayınız.

@if(!empty($paymentAmountText) && !empty($bankInfo['iban'] ?? ''))
---

## 💳 Ödeme Bilgisi

Süreci başlatabilmek için sözleşmede belirlenen ücreti aşağıdaki hesaba **{{ $paymentDueDays }} iş günü** içinde havale etmen gerekiyor.

| | |
|---|---|
| **Tutar** | {{ $paymentAmountText }} |
| **Hesap Sahibi** | {{ $bankInfo['account_holder'] ?? '' }} |
| **Banka** | {{ $bankInfo['bank_name'] ?? '' }} |
| **IBAN** | `{{ $bankInfo['iban'] ?? '' }}` |
@if(!empty($bankInfo['bic'] ?? ''))
| **BIC/SWIFT** | {{ $bankInfo['bic'] }} |
@endif
| **Açıklama (mutlaka yaz)** | `{{ $paymentReference }}` |

> ⚠ **Açıklamayı tam olarak yukarıdaki şekilde yaz** — adın ve ID'n birlikte olmalı. Bu, ödemenin doğru kayda eşlenmesi için gerekli; eksik veya farklı açıklama eşleşme gecikmesine neden olur.

Ödemeni manuel olarak teyit ettikten sonra panelinde **"Ödeme alındı"** bildirimi görecek ve sürecin başlayacak.
@endif

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
