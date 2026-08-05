@extends('platform.layouts.app')

@section('title', 'Aday Hacmi — Platform')

@section('content')

<div class="plat-page-header">
    <div>
        <h1 class="plat-page-title">Aday Hacmi</h1>
        <p class="plat-page-sub">Şirket başına aday sayıları — kişisel veri gösterilmez</p>
    </div>
</div>

{{-- Bu ekranın neden sayı gösterdiği görünür olsun --}}
<div class="plat-card" style="margin-bottom:18px;border-left:3px solid var(--plat-accent-2);">
    <div style="font-size:12px;color:var(--plat-muted);line-height:1.7;">
        <strong style="color:#fff;">Neden isim yok:</strong>
        DGmarkt yazılım servisi sağlar; müşterilerinin öğrencileri için veri sorumlusu değildir.
        Ad, e-posta ve telefon bu konsolda <strong style="color:#fff;">bilerek gösterilmez</strong>.
        <br>Kişi düzeyindeki işler operasyonu yürüten şirkete aittir — onun personeli
        partner adaylarını kendi ekranlarında görür.
    </div>
</div>

{{-- ÜST ÖZET --}}
<div class="plat-grid plat-grid-4" style="margin-bottom:24px;">
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="users" size="12" /> Toplam Aday</div>
        <div class="plat-kpi-value">{{ number_format($grandTotal, 0, ',', '.') }}</div>
        <div class="plat-kpi-sub">dönüşmemiş kayıtlar</div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="trending-up" size="12" /> Son 30 Gün</div>
        <div class="plat-kpi-value">{{ number_format($recent30, 0, ',', '.') }}</div>
        <div class="plat-kpi-sub">yeni başvuru</div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="building-2" size="12" /> Aday Alan Şirket</div>
        <div class="plat-kpi-value">{{ $companies->where('leads', '>', 0)->count() }}</div>
        <div class="plat-kpi-sub">/ {{ $companies->count() }} toplam</div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="check" size="12" /> Nitelikli</div>
        <div class="plat-kpi-value">{{ number_format((int) ($statusTotals['qualified'] ?? 0), 0, ',', '.') }}</div>
        <div class="plat-kpi-sub">durum: nitelikli</div>
    </div>
</div>

{{-- ŞİRKET BAZLI --}}
<div class="plat-card" style="margin-bottom:18px;">
    <h3 class="plat-card-title"><x-icon name="building-2" size="16" /> Şirket Başına</h3>

    @if($companies->isEmpty())
        <p style="margin:0;color:var(--plat-muted);">Şirket yok.</p>
    @else
        <div style="overflow-x:auto;">
            <table class="plat-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>Şirket</th>
                        <th style="text-align:right;">Aday</th>
                        <th style="text-align:right;">Öğrenci</th>
                        <th>Durum</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($companies as $c)
                        <tr>
                            <td>
                                <span style="font-weight:600;">{{ $c['name'] }}</span>
                                <span style="display:block;font-size:11px;color:var(--plat-muted);">
                                    #{{ $c['id'] }} · {{ $c['code'] }}@if($c['parent']) · üst firma #{{ $c['parent'] }}@endif
                                </span>
                            </td>
                            <td style="text-align:right;font-variant-numeric:tabular-nums;font-weight:600;">
                                {{ number_format($c['leads'], 0, ',', '.') }}
                            </td>
                            <td style="text-align:right;font-variant-numeric:tabular-nums;">
                                {{ number_format($c['students'], 0, ',', '.') }}
                            </td>
                            <td>
                                @if($c['active'])
                                    <span class="plat-badge plat-badge-active">Aktif</span>
                                @else
                                    <span class="plat-badge plat-badge-inactive">Pasif</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- DURUM DAĞILIMI --}}
<div class="plat-card" style="margin-bottom:18px;">
    <h3 class="plat-card-title"><x-icon name="activity" size="16" /> Durum Dağılımı</h3>
    <div style="display:flex;flex-wrap:wrap;gap:10px;">
        @forelse($statusLabels as $key => $label)
            @php $count = (int) ($statusTotals[$key] ?? 0); @endphp
            <div style="flex:1 1 150px;padding:12px 16px;background:var(--plat-panel-2);border:1px solid var(--plat-border);border-radius:8px;">
                <div style="font-size:11px;color:var(--plat-muted);text-transform:uppercase;letter-spacing:.05em;">{{ $label }}</div>
                <div style="font-size:22px;font-weight:700;color:#fff;font-variant-numeric:tabular-nums;">{{ number_format($count, 0, ',', '.') }}</div>
            </div>
        @empty
            <p style="margin:0;color:var(--plat-muted);">Veri yok.</p>
        @endforelse
    </div>
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
