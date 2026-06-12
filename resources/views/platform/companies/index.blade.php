@extends('platform.layouts.app')

@section('title', 'Şirketler — Platform')

@section('content')

<div class="plat-page-header">
    <div>
        <h1 class="plat-page-title">Şirketler</h1>
        <p class="plat-page-sub">Tüm SaaS müşterileri — tier yönetimi + modül kontrolü</p>
    </div>
    <a href="{{ route('platform.companies.create') }}" class="plat-btn plat-btn-primary">
        <x-icon name="plus" size="16" /> Yeni Şirket
    </a>
</div>

{{-- FILTER BAR --}}
<div class="plat-card" style="margin-bottom:18px;">
    <form method="GET" action="{{ route('platform.companies') }}" style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:12px;align-items:end;">
        <div>
            <label class="plat-form-label">Arama</label>
            <input type="text" name="q" value="{{ $filters['q'] }}" class="plat-input" placeholder="İsim, kod veya billing email...">
        </div>
        <div>
            <label class="plat-form-label">Tier</label>
            <select name="tier" class="plat-select">
                <option value="">Hepsi</option>
                @foreach($tierLabels as $tierKey => $label)
                    <option value="{{ $tierKey }}" {{ $filters['tier'] === $tierKey ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="plat-form-label">Durum</label>
            <select name="status" class="plat-select">
                <option value="">Hepsi</option>
                <option value="active"   {{ $filters['status'] === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="inactive" {{ $filters['status'] === 'inactive' ? 'selected' : '' }}>Pasif</option>
                <option value="trial"    {{ $filters['status'] === 'trial' ? 'selected' : '' }}>Trial</option>
                <option value="expired"  {{ $filters['status'] === 'expired' ? 'selected' : '' }}>Trial Süresi Geçmiş</option>
            </select>
        </div>
        <div>
            <button type="submit" class="plat-btn plat-btn-primary"><x-icon name="search" size="14" /> Filtrele</button>
        </div>
    </form>
</div>

{{-- TABLE --}}
<div class="plat-card" style="padding:0;overflow:hidden;">
    <table class="plat-table">
        <thead>
            <tr>
                <th>
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'dir' => $filters['sort'] === 'name' && $filters['dir'] === 'asc' ? 'desc' : 'asc']) }}" style="color:inherit;">Şirket</a>
                </th>
                <th>
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'subscription_tier', 'dir' => $filters['sort'] === 'subscription_tier' && $filters['dir'] === 'asc' ? 'desc' : 'asc']) }}" style="color:inherit;">Tier</a>
                </th>
                <th style="text-align:right;">Öğrenci</th>
                <th style="text-align:right;">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'mrr_eur', 'dir' => $filters['sort'] === 'mrr_eur' && $filters['dir'] === 'asc' ? 'desc' : 'asc']) }}" style="color:inherit;">MRR</a>
                </th>
                <th>Durum</th>
                <th>Trial</th>
                <th>
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'dir' => $filters['sort'] === 'created_at' && $filters['dir'] === 'asc' ? 'desc' : 'asc']) }}" style="color:inherit;">Eklendi</a>
                </th>
                <th style="text-align:right;">İşlem</th>
            </tr>
        </thead>
        <tbody>
            @forelse($companies as $c)
                <tr>
                    <td>
                        <a href="{{ route('platform.companies.show', $c->id) }}" style="color:#fff;font-weight:700;">{{ $c->name }}</a>
                        <div style="font-size:11px;color:var(--plat-muted);">#{{ $c->id }} · {{ $c->code }}</div>
                        @if($c->billing_email)
                            <div style="font-size:11px;color:var(--plat-muted);"><x-icon name="mail" size="10" /> {{ $c->billing_email }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="plat-badge plat-badge-{{ $c->subscription_tier ?? 'trial' }}">{{ $tierLabels[$c->subscription_tier] ?? $c->subscription_tier }}</span>
                    </td>
                    <td style="text-align:right;font-weight:700;color:#fff;">{{ number_format($studentCounts[$c->id] ?? 0) }}</td>
                    <td style="text-align:right;font-weight:700;color:#fff;">€{{ number_format((float) $c->mrr_eur, 0, ',', '.') }}</td>
                    <td>
                        @if($c->is_active)
                            <span class="plat-badge plat-badge-active"><x-icon name="check" size="10" /> Aktif</span>
                        @else
                            <span class="plat-badge plat-badge-inactive"><x-icon name="x" size="10" /> Pasif</span>
                        @endif
                    </td>
                    <td style="font-size:12px;">
                        @if($c->subscription_tier === 'trial' && $c->trial_ends_at)
                            @php $days = (int) now()->startOfDay()->diffInDays($c->trial_ends_at, false); @endphp
                            @if($days < 0)
                                <span style="color:var(--plat-danger);"><x-icon name="circle-alert" size="10" /> Süresi geçmiş</span>
                            @elseif($days <= 7)
                                <span style="color:var(--plat-warn);">{{ $days }} gün kaldı</span>
                            @else
                                <span style="color:var(--plat-muted);">{{ $days }} gün</span>
                            @endif
                        @else
                            <span style="color:var(--plat-muted);">—</span>
                        @endif
                    </td>
                    <td style="font-size:11px;color:var(--plat-muted);">{{ optional($c->created_at)->format('d.m.Y') }}</td>
                    <td style="text-align:right;white-space:nowrap;">
                        <a href="{{ route('platform.companies.show', $c->id) }}" class="plat-btn plat-btn-ghost plat-btn-sm">
                            <x-icon name="eye" size="12" /> Detay
                        </a>
                        <form method="POST" action="{{ route('platform.companies.impersonate', $c->id) }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="plat-btn plat-btn-ghost plat-btn-sm" title="Bu sirketin Manager paneline gecici eris"
                                    onclick="return confirm('Impersonate baslatilacak: {{ addslashes($c->name) }}\n\nManager paneli o sirketin context'inde acilacak. Tum islemler audit log\'a kaydedilir.');">
                                <x-icon name="user" size="12" /> Gir
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;color:var(--plat-muted);padding:40px;">
                    <x-icon name="building-2" size="24" /><br><br>
                    Filtre kriterlerine uyan şirket yok.
                </td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- PAGINATION --}}
<div style="margin-top:18px;">
    {{ $companies->links() }}
</div>

@endsection
