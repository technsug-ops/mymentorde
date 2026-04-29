@php /** @var string $recipientName, string $contractTitle, ?string $contractNo, ?string $paymentAmountText, ?string $paymentDateText, ?string $portalUrl, ?string $advisorName */ @endphp
<x-mail::message>
# Ödemeniz Ulaştı ✅

Merhaba **{{ $recipientName }}**,

@if($contractNo)
**{{ $contractTitle }}** ({{ '#' . $contractNo }}) sözleşmen için yaptığın ödeme bize ulaştı.
@else
**{{ $contractTitle }}** sözleşmen için yaptığın ödeme bize ulaştı.
@endif

@if(!empty($paymentAmountText) || !empty($paymentDateText))
| | |
|---|---|
@if(!empty($paymentAmountText))
| **Tutar** | {{ $paymentAmountText }} |
@endif
@if(!empty($paymentDateText))
| **Teyit Tarihi** | {{ $paymentDateText }} |
@endif
@endif

---

## 🚀 Sürecine Başladık

Ekibimiz dosyanı hazırlamaya başladı. Bundan sonraki adımlar:

@if($advisorName)
- 📞 Danışmanın **{{ $advisorName }}** seninle iletişime geçecek ve süreç planınızı netleştirecek.
@else
- 📞 Atanan danışmanın seninle iletişime geçecek ve süreç planınızı netleştirecek.
@endif
- 📋 Eksik belgeleri panelinden tamamlamaya devam et — bu adım sürecini hızlandırır.
- 📅 Yaklaşan görüşme/teslim tarihlerini panelindeki ajandadan takip edebilirsin.

@if($portalUrl)
<x-mail::button :url="$portalUrl">
Panele Git
</x-mail::button>
@endif

---

Yolculuğunda yanındayız.

İyi çalışmalar dileriz,
**MentorDE Ekibi**
</x-mail::message>
