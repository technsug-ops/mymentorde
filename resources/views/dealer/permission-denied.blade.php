@extends('dealer.layouts.app')
@section('page_title', 'Erişim Kısıtlı')

@section('content')
<div style="display:flex;align-items:center;justify-content:center;min-height:60vh;padding:24px;">
    <div style="max-width:480px;text-align:center;">

        {{-- Icon --}}
        <div style="font-size:64px;margin-bottom:16px;opacity:.8;">🔒</div>

        {{-- Title --}}
        <h2 style="font-size:22px;font-weight:700;color:var(--text,#111);margin-bottom:8px;">
            Bu Alan Mevcut Paketinizde Kullanılamaz
        </h2>

        {{-- Subtitle --}}
        <p style="font-size:14px;color:var(--muted,#64748b);margin-bottom:24px;line-height:1.6;">
            @php
                $tierLabel = $dealerType->name_tr ?? $dealerType->code ?? '-';
                $featureLabels = [
                    'canAccessSupport'       => 'Danışman Desteği',
                    'canViewStudentDetails'  => 'Öğrenci Detayları',
                    'canViewDocuments'       => 'Belge Görüntüleme',
                    'canViewProcessDetails'  => 'Süreç Takibi',
                    'canViewTerritoryStats'  => 'Bölge İstatistikleri',
                    'canAccessTraining'      => 'Eğitim Merkezi',
                    'canAccessCalculator'    => 'Komisyon Hesaplama',
                ];
                $featureName = $featureLabels[$permissionKey] ?? $permissionKey;
            @endphp
            <strong>{{ $featureName }}</strong> özelliği, <strong>{{ $tierLabel }}</strong> paketinde aktif değil.
            Üst kademe pakete geçmek için yöneticinize başvurun.
        </p>

        {{-- Tier upgrade card — dinamik, DB'den --}}
        @php
            $allTypes = \App\Models\DealerType::where('is_active', true)->orderBy('id')->get();
            $tierColors = ['#0891b2', '#7c3aed', '#1e40af', '#dc2626', '#16a34a'];
            $currentCode = $dealerType->code ?? '';
        @endphp
        <div style="background:var(--surface,#f9fafb);border:1px solid var(--border,#e2e8f0);border-radius:12px;padding:20px;margin-bottom:24px;text-align:left;">
            <div style="font-size:13px;font-weight:600;color:var(--text,#111);margin-bottom:10px;">Bayi Paket Seviyeleri</div>
            <div style="display:grid;gap:8px;font-size:13px;">
                @foreach($allTypes as $i => $t)
                    <div style="display:flex;align-items:center;gap:8px;{{ $t->code === $currentCode ? 'font-weight:700;' : '' }}">
                        <span style="width:10px;height:10px;border-radius:50%;background:{{ $tierColors[$i] ?? '#6b7280' }};flex-shrink:0;"></span>
                        <span>
                            <strong>{{ $t->name_tr ?? $t->code }}</strong>
                            @if($t->code === $currentCode)
                                <span style="color:#dc2626;font-size:11px;"> (mevcut paketiniz)</span>
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Actions --}}
        <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
            <a href="/dealer/dashboard" class="btn" style="background:var(--accent,#16a34a);color:#fff;padding:10px 24px;border-radius:8px;text-decoration:none;font-size:14px;font-weight:600;">Dashboard'a Dön</a>
            <a href="/dealer/advisor" class="btn" style="background:var(--surface,#f1f5f9);color:var(--text,#111);padding:10px 24px;border-radius:8px;text-decoration:none;font-size:14px;border:1px solid var(--border,#e2e8f0);">Destek Talebi Oluştur</a>
        </div>
    </div>
</div>
@endsection
