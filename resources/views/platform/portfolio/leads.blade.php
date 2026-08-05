@extends('platform.layouts.app')

@section('title', 'Aday Kotası — Platform')

@section('content')

<div class="plat-page-header">
    <div>
        <h1 class="plat-page-title">Aday Kotası</h1>
        <p class="plat-page-sub">Hangi firma paket sınırına dayandı — üst paket sinyali</p>
    </div>
</div>

<div class="plat-card" style="margin-bottom:18px;border-left:3px solid var(--plat-accent-2);">
    <div style="font-size:12px;color:var(--plat-muted);line-height:1.7;">
        <strong style="color:#fff;">Bu ekranda ne yok:</strong>
        kişisel veri (ad, e-posta, telefon) ve satış hunisi (kim hangi aşamada).
        <br>Birincisi veri sorumluluğu bizde olmadığı için, ikincisi müşterinin kendi
        operasyonu olduğu için gösterilmez. Burada olan tek şey <strong style="color:#fff;">kapasite</strong>.
    </div>
</div>

<div class="plat-grid plat-grid-4" style="margin-bottom:24px;">
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="alert-triangle" size="12" /> Limit Doldu</div>
        <div class="plat-kpi-value" style="{{ $atLimit > 0 ? 'color:#dc2626;' : '' }}">{{ $atLimit }}</div>
        <div class="plat-kpi-sub">firma · üst pakete geçmeli</div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="trending-up" size="12" /> Sınıra Yakın</div>
        <div class="plat-kpi-value" style="{{ $nearLimit > 0 ? 'color:#f59e0b;' : '' }}">{{ $nearLimit }}</div>
        <div class="plat-kpi-sub">%{{ $threshold }} üzeri kullanım</div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="users" size="12" /> Toplam Aday</div>
        <div class="plat-kpi-value">{{ number_format($totalLeads, 0, ',', '.') }}</div>
        <div class="plat-kpi-sub">tüm firmalar</div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="building-2" size="12" /> Firma</div>
        <div class="plat-kpi-value">{{ $companies->count() }}</div>
        <div class="plat-kpi-sub">{{ $companies->where('active', true)->count() }} aktif</div>
    </div>
</div>

<div class="plat-card" style="margin-bottom:18px;">
    <h3 class="plat-card-title"><x-icon name="gauge" size="16" /> Kota Kullanımı</h3>
    @include('platform.portfolio._quota-table')
</div>

{{-- DEVİR — kişisel veri göstermeden, numara ile --}}
<div class="plat-card">
    <h3 class="plat-card-title"><x-icon name="arrow-right" size="16" /> Aday Devri</h3>
    <p class="plat-card-sub" style="margin-bottom:14px;">
        Firma başvuru linkini kullandıramadığında kayıt B2C havuzuna düşer.
        Operasyon ekibi adayı kendi ekranından bulur, <strong>numarasını</strong> buraya girer.
        Bağlı tüm kayıtlar birlikte taşınır.
    </p>

    <form method="POST" action="{{ route('platform.leads.transfer', ['application' => 0]) }}"
          data-transfer-form="1"
          style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
        @csrf

        <div>
            <label class="plat-form-label">Aday Numarası</label>
            <input type="number" name="application_id" class="plat-input" required min="1"
                   placeholder="örn. 1423" style="width:160px;">
        </div>

        <div>
            <label class="plat-form-label">Hedef Firma</label>
            <select name="company_id" class="plat-select" required style="min-width:220px;">
                <option value="">Firma seç…</option>
                @foreach($companies as $c)
                    @continue(!$c['active'])
                    <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="plat-btn plat-btn-primary">
            <x-icon name="check" size="14" /> Devret
        </button>
    </form>
</div>

{{-- Aday numarası URL'e yazılır; CSP nedeniyle inline handler yerine nonce'lu blok. --}}
<script nonce="{{ $cspNonce ?? '' }}">
(function () {
    var form = document.querySelector('[data-transfer-form]');
    if (!form) return;

    form.addEventListener('submit', function () {
        var id = encodeURIComponent(form.elements.application_id.value);
        form.action = form.action.replace(/\/\d+\/transfer$/, '/' + id + '/transfer');
    });
})();
</script>

@endsection
