@extends(\App\Support\PanelRouting::layout())

@section('title', 'Bayi Komisyon Kademeleri')
@section('page_title', 'Bayi Komisyon Kademeleri')
@section('page_subtitle', '/satis-ortagi sayfasındaki tier matrisini buradan düzenle — eşik, oran, avantajlar')

@push('head')
<style>
.dt-section { background:var(--u-card); border:1px solid var(--u-line); border-radius:12px; padding:18px 20px; margin-bottom:18px; }
.dt-section h3 { margin:0 0 12px; font-size:14px; color:var(--u-text); text-transform:uppercase; letter-spacing:.5px; }
.dt-table { width:100%; border-collapse:collapse; font-size:13px; }
.dt-table th { padding:10px 12px; text-align:left; font-size:11px; font-weight:700; color:var(--u-muted); text-transform:uppercase; letter-spacing:.4px; border-bottom:2px solid var(--u-line); background:var(--u-bg); }
.dt-table td { padding:11px 12px; border-bottom:1px solid var(--u-line); color:var(--u-text); }
.dt-table tr:hover td { background:var(--u-bg); }
.dt-emoji { font-size:18px; }
.dt-badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:700; }
.dt-active { background:rgba(22,163,74,.12); color:#15803d; }
.dt-inactive { background:rgba(100,116,139,.15); color:#475569; }
.dt-rate { font-weight:800; color:var(--u-text); }
.dt-actions { display:flex; gap:6px; }
.dt-btn { padding:5px 10px; font-size:11.5px; font-weight:600; border-radius:6px; border:1px solid var(--u-line); background:var(--u-bg); color:var(--u-text); cursor:pointer; text-decoration:none; }
.dt-btn:hover { border-color:var(--u-brand); }
.dt-btn.primary { background:var(--u-brand,#2563eb); color:white; border-color:var(--u-brand); }
</style>
@endpush

@section('content')
<div class="container-fluid">

    @if(session('success'))<div style="background:rgba(22,163,74,.08);color:#15803d;border:1px solid rgba(22,163,74,.3);padding:10px 14px;border-radius:10px;margin-bottom:14px;">✅ {{ session('success') }}</div>@endif

    <div style="display:flex; gap:8px; margin-bottom:14px;">
        <a class="dt-btn primary" href="{{ \App\Support\PanelRouting::url('dealer-tiers', 'create') }}">+ Yeni Tier</a>
        <a class="dt-btn" href="{{ url('/satis-ortagi') }}" target="_blank">👁 Public sayfayı gör</a>
    </div>

    @forelse($tiersByType as $typeCode => $tiers)
        <div class="dt-section">
            <h3>
                @if($typeCode === 'lead_generation') 💼 Lead Generation ({{ count($tiers) }} tier)
                @elseif($typeCode === 'freelance_danisman') 🎓 Freelance Danışman ({{ count($tiers) }} tier)
                @elseif($typeCode === 'b2b_partner') 🤝 B2B Partner ({{ count($tiers) }} tier)
                @else {{ $typeCode }} ({{ count($tiers) }} tier)
                @endif
            </h3>
            <table class="dt-table">
                <thead>
                    <tr>
                        <th>Sıra</th>
                        <th>Tier</th>
                        <th>Kayıt aralığı</th>
                        <th>Oran</th>
                        <th>Avantajlar</th>
                        <th>Durum</th>
                        <th>Aksiyon</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tiers as $t)
                        <tr>
                            <td>{{ $t->display_order }}</td>
                            <td><span class="dt-emoji">{{ $t->tier_emoji }}</span> <strong>{{ $t->tier_name }}</strong> <code style="font-size:10px;color:var(--u-muted);">{{ $t->tier_code }}</code></td>
                            <td>{{ $t->rangeLabel() }}</td>
                            <td class="dt-rate">{{ $t->rateLabel() }}/kayıt</td>
                            <td style="max-width:300px; font-size:12px; color:var(--u-muted);">{{ $t->advantages_text }}</td>
                            <td>
                                @if($t->is_active)<span class="dt-badge dt-active">Aktif</span>
                                @else<span class="dt-badge dt-inactive">Pasif</span>@endif
                            </td>
                            <td>
                                <div class="dt-actions">
                                    <a class="dt-btn" href="{{ \App\Support\PanelRouting::url('dealer-tiers', 'edit', $t) }}">Düzenle</a>
                                    <form method="POST" action="{{ \App\Support\PanelRouting::url('dealer-tiers', 'toggle', $t) }}" style="display:inline;">
                                        @csrf
                                        <button class="dt-btn" type="submit">{{ $t->is_active ? 'Pasif' : 'Aktif' }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <div style="padding:30px; text-align:center; color:var(--u-muted);">
            Henüz tier yok. Seederi çalıştır: <code>php artisan db:seed --class=DealerCommissionTierSeeder</code>
        </div>
    @endforelse
</div>
@endsection
