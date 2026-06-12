{{--
    Trial status banner — manager layout'larında @yield('content') öncesi include edilir.

    Davranış:
      - subscription_tier != trial         → hiçbir şey gösterilmez
      - kalan > 7 gün                       → sessiz (banner yok)
      - kalan 4-7 gün                       → info banner (mavi, dismissable session)
      - kalan 1-3 gün                       → warn banner (sarı, dismiss yok)
      - kalan 0 (bugün biter)              → critical banner (turuncu, dismiss yok)
      - expired (negatif)                  → bloklanmış zaten — middleware yönlendirir;
                                              yine de safety net olarak danger banner
--}}

@php
    $currentCompanyId = (int) (app('current_company_id') ?? 0);
    $tbCompany = null;
    if ($currentCompanyId > 0 && \Illuminate\Support\Facades\Schema::hasTable('companies')) {
        try {
            $tbCompany = \App\Models\Company::query()
                ->withoutGlobalScopes()
                ->find($currentCompanyId);
        } catch (\Throwable $e) { $tbCompany = null; }
    }

    $tbShow = false;
    $tbVariant = 'info';
    $tbDaysLeft = null;
    $tbHeadline = '';
    $tbBody = '';
    $tbDismissable = false;

    if ($tbCompany && $tbCompany->isTrial()) {
        $tbDaysLeft = $tbCompany->trialDaysRemaining();
        if ($tbDaysLeft !== null) {
            if ($tbDaysLeft < 0) {
                $tbShow = true;
                $tbVariant = 'danger';
                $tbHeadline = '🚨 Trial sürenin doldu';
                $tbBody = 'Trial süreniz bitti. Bir plan seçerek devam edebilirsin — tüm verilerin korunuyor.';
            } elseif ($tbDaysLeft === 0) {
                $tbShow = true;
                $tbVariant = 'critical';
                $tbHeadline = '⏰ Trial bugün bitiyor';
                $tbBody = 'Trial süren bugün 23:59 itibariyle sona eriyor. Hesabını açık tutmak için planını şimdi seç.';
            } elseif ($tbDaysLeft <= 3) {
                $tbShow = true;
                $tbVariant = 'warn';
                $tbHeadline = '⚠️ Trial süren ' . $tbDaysLeft . ' gün sonra dolacak';
                $tbBody = 'Verilerini kaybetmemek için bir plan seçmeyi unutma. Yükseltme anında yapılır.';
            } elseif ($tbDaysLeft <= 7) {
                $sessionKey = 'tb_dismissed_' . $tbCompany->id . '_' . $tbDaysLeft;
                if (!session($sessionKey)) {
                    $tbShow = true;
                    $tbVariant = 'info';
                    $tbHeadline = '🎁 Trial süreniz: ' . $tbDaysLeft . ' gün kaldı';
                    $tbBody = 'Tüm Gold özellikler hala açık — kalan günlerde test etmediğin modülleri dene.';
                    $tbDismissable = true;
                }
            }
        }
    }

    $tbStyles = [
        'info'     => ['bg' => 'linear-gradient(135deg,#dbeafe,#eff6ff)', 'border' => '#3b82f6', 'text' => '#1e3a8a', 'btn' => '#1e40af'],
        'warn'     => ['bg' => 'linear-gradient(135deg,#fef3c7,#fffbeb)', 'border' => '#f59e0b', 'text' => '#78350f', 'btn' => '#b45309'],
        'critical' => ['bg' => 'linear-gradient(135deg,#ffedd5,#fff7ed)', 'border' => '#ea580c', 'text' => '#7c2d12', 'btn' => '#c2410c'],
        'danger'   => ['bg' => 'linear-gradient(135deg,#fee2e2,#fef2f2)', 'border' => '#dc2626', 'text' => '#7f1d1d', 'btn' => '#b91c1c'],
    ];
    $s = $tbStyles[$tbVariant] ?? $tbStyles['info'];
@endphp

@if($tbShow)
<div style="margin:0 0 18px;padding:14px 22px;background:{{ $s['bg'] }};border:1px solid {{ $s['border'] }};border-left-width:5px;border-radius:12px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
    <div style="flex:1;min-width:240px;">
        <div style="font-size:14.5px;font-weight:800;color:{{ $s['text'] }};letter-spacing:-.2px;">{{ $tbHeadline }}</div>
        <div style="font-size:12.5px;color:{{ $s['text'] }};opacity:.88;margin-top:3px;line-height:1.5;">{{ $tbBody }}</div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="{{ route('manager.my-plan') }}" style="padding:8px 18px;background:{{ $s['btn'] }};color:#fff;border-radius:8px;font-size:12.5px;font-weight:700;text-decoration:none;white-space:nowrap;">
            Planları gör →
        </a>
        @if($tbDismissable)
            <form method="POST" action="{{ url('/trial-banner/dismiss') }}" style="display:inline;">
                @csrf
                <input type="hidden" name="days" value="{{ $tbDaysLeft }}">
                <button type="submit" style="padding:8px 14px;background:transparent;color:{{ $s['text'] }};border:1px solid {{ $s['border'] }};border-radius:8px;font-size:12.5px;font-weight:600;cursor:pointer;opacity:.7;">
                    Kapat ✕
                </button>
            </form>
        @endif
    </div>
</div>
@endif
