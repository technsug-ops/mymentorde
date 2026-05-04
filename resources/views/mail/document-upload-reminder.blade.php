<x-mail::message>
# {{ $stageLabel }} 📎

Merhaba,

@if($stage === 'final')
**{{ $categoryLabel }}** belgeni yüklemen için verilen sürenin dolmasına yalnızca **{{ $hoursLeft }}** kaldı.
@else
Sana **{{ $categoryLabel }}** belgesini yüklemen için bir bağlantı gönderilmişti, ancak henüz yüklenmemiş görünüyor.
@endif

@if($customMessage)
---

**Danışmanından not:**
> {{ $customMessage }}
@endif

---

<x-mail::button :url="$uploadUrl">
{{ $stage === 'final' ? 'Hemen Yükle' : 'Belgeyi Yükle' }}
</x-mail::button>

@if($stage === 'final')
**⚠ Süre dolduktan sonra** bu bağlantı çalışmaz; danışmanından yeni link talep etmen gerekir.
@else
Bağlantının geçerlilik süresi içinde herhangi bir cihazdan yükleyebilirsin.
@endif

İyi günler,<br>
**{{ config('brand.name', 'MentorDE') }}**
</x-mail::message>
