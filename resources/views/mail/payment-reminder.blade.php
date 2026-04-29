@php /** @var string $recipientName, string $contractTitle, ?string $contractNo, string $paymentAmountText, string $paymentReference, array $bankInfo, ?string $portalUrl, int $level, int $daysOverdue, int $finalGraceDays */ @endphp
<x-mail::message>
@switch($level)
@case(1)
# Ödeme Hatırlatması ⏰

Merhaba **{{ $recipientName }}**,

@if($contractNo)
**{{ $contractTitle }}** ({{ '#' . $contractNo }}) sözleşmen onaylandı, ancak henüz ödeme bilgini sistemimizde göremedik.
@else
**{{ $contractTitle }}** sözleşmen onaylandı, ancak henüz ödeme bilgini sistemimizde göremedik.
@endif

Yoğunluktan kayma olmuş olabilir — bu kısa bir hatırlatma. Sürecini başlatabilmemiz için sözleşmede belirlenen ücreti aşağıdaki hesaba havale etmen yeterli.
@break

@case(2)
# İkinci Hatırlatma 📨

Merhaba **{{ $recipientName }}**,

Geçtiğimiz günlerde **{{ $contractTitle }}** sözleşmen için ödeme bilgilerini göndermiştik, fakat ödemenin sistemimize ulaştığına dair bir kayıt henüz yok.

Süreciniz başlamayı bekliyor; ödeme tamamlandığında ekip danışmanlığa başlayabilecek. Lütfen aşağıdaki bilgilerle ödemeni gerçekleştir.
@break

@case(3)
# Önemli: Ödeme Bekleniyor 🔔

Merhaba **{{ $recipientName }}**,

Sözleşmen imzalanalı **{{ $daysOverdue }} gün** geçti ve süreç ödeme bekliyor. Bu, sürecindeki **üçüncü hatırlatmamız**.

Danışman ekibimiz seni planlamaya almak için bekliyor — ancak ödeme alınmadan resmi adımlar (üniversite başvuru hazırlığı, vize dosyalama, vb.) açılmıyor. Lütfen aşağıdaki bilgilerle ödemeni en kısa sürede tamamla.
@break

@case(4)
# Acil: Aksiyon Gerekli ⚠️

Merhaba **{{ $recipientName }}**,

Bu **dördüncü hatırlatma** ve ödeme hâlâ sistemimize ulaşmadı. **Bir sonraki mailimiz son bildirim** niteliğinde olacaktır.

Sözleşmenin geçerliliğini koruması için ödeme akışını bugün/yarın tamamlaman önemli. Eğer banka tarafında bir sorun yaşıyorsan veya farklı bir ödeme yöntemi gerekiyorsa, lütfen bize hemen yaz — birlikte bir çözüm bulalım.
@break

@case(5)
# Son Bildirim — Sözleşme İptal Uyarısı 🛑

Merhaba **{{ $recipientName }}**,

Bu **son hatırlatmamız**. Önceki bildirimlerimize rağmen ödeme bilgisi sistemimize ulaşmadı.

Bugün itibarıyla **{{ $finalGraceDays }} gün ek süre** tanıyoruz. Bu süre içinde ödeme yapılmaması durumunda:

- ❌ **Sözleşmen otomatik olarak iptal edilecektir.**
- 💼 Bu zamana kadar yapılmış olan **kısmi ödemeler, gerçekleştirilen danışmanlık ve servis hizmetleri karşılığı olarak şirket tarafından alıkonulacak** ve iade edilmeyecektir.
- 🔁 Sürecini sonradan tekrar başlatmak istersen yeni bir sözleşme imzalanması ve ücretin tam olarak yeniden ödenmesi gerekecektir.

Bu adımı atmak istemeyiz — sürecin başarıyla tamamlanmasını biz de istiyoruz. Lütfen aşağıdaki bilgilerle ödemeni en geç **{{ $finalGraceDays }} gün** içinde gerçekleştir.

Eğer ödeme tarafında bir engel varsa (banka, döviz transferi, mücbir sebep vb.) **derhal** bizimle iletişime geç — birlikte çözüm arayabiliriz.
@break
@endswitch

---

## 💳 Ödeme Bilgisi

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

> ⚠ **Açıklamayı tam olarak yukarıdaki şekilde yaz** — adın ve ID'n birlikte olmalı. Bu, ödemenin doğru kayda eşlenmesi için gerekli.

Ödemeni gerçekleştirdiysen bu mailden sonraki **1–2 iş günü içinde** panelinde "Ödeme alındı" bildirimi göreceksin. Bildirim gelmezse lütfen bize ulaş.

@if($portalUrl)
<x-mail::button :url="$portalUrl">
Panele Git
</x-mail::button>
@endif

---

İyi çalışmalar dileriz,
**MentorDE Ekibi**
</x-mail::message>
