@extends('layouts.staff')

@section('title', 'Devam Takibi')
@section('page_title', 'Devam Takibi')
@section('page_subtitle', 'Günlük giriş/çıkış kaydınız')

@section('content')
<div style="display:grid;gap:14px;max-width:680px;">

    {{-- Büyük Saat --}}
    <div class="panel" style="text-align:center;padding:28px;">
        <div id="live-clock" style="font-size:48px;font-weight:800;color:var(--u-brand);letter-spacing:-.02em;line-height:1;">--:--:--</div>
        <div style="font-size:13px;color:var(--u-muted);margin-top:6px;" id="live-date">—</div>
    </div>

    {{-- Durum Kartı --}}
    <div class="panel">
        <div style="font-size:14px;font-weight:700;color:var(--u-text);margin-bottom:14px;">Bugünkü Kayıt</div>
        <div class="grid3" style="margin-bottom:0;">
            <div style="text-align:center;padding:12px;background:var(--u-bg);border-radius:10px;">
                <div style="font-size:11px;font-weight:600;color:var(--u-muted);margin-bottom:4px;">Giriş Saati</div>
                <div style="font-size:22px;font-weight:700;color:var(--u-ok);" id="checkin-display">
                    {{ $attendance->check_in_at ? $attendance->check_in_at->format('H:i') : '—' }}
                </div>
            </div>
            <div style="text-align:center;padding:12px;background:var(--u-bg);border-radius:10px;">
                <div style="font-size:11px;font-weight:600;color:var(--u-muted);margin-bottom:4px;">Çıkış Saati</div>
                <div style="font-size:22px;font-weight:700;color:var(--u-danger);" id="checkout-display">
                    {{ $attendance->check_out_at ? $attendance->check_out_at->format('H:i') : '—' }}
                </div>
            </div>
            <div style="text-align:center;padding:12px;background:var(--u-bg);border-radius:10px;">
                <div style="font-size:11px;font-weight:600;color:var(--u-muted);margin-bottom:4px;">Çalışma Süresi</div>
                <div style="font-size:22px;font-weight:700;color:var(--u-brand);" id="work-duration">
                    @if($attendance->work_minutes > 0)
                        {{ intdiv($attendance->work_minutes, 60) }}s {{ $attendance->work_minutes % 60 }}d
                    @else
                        —
                    @endif
                </div>
            </div>
        </div>

        @php
            $statusLabel = match($attendance->status) {
                'present'     => ['Zamanında', 'ok'],
                'late'        => ['Geç Geldi', 'warn'],
                'early_leave' => ['Erken Çıktı', 'warn'],
                'absent'      => ['Devamsız', 'danger'],
                'half_day'    => ['Yarım Gün', 'info'],
                default       => [$attendance->status, 'info'],
            };
        @endphp
        <div style="margin-top:12px;display:flex;align-items:center;gap:8px;">
            <span style="font-size:12px;color:var(--u-muted);">Durum:</span>
            <span class="badge {{ $statusLabel[1] }}" id="status-badge">{{ $statusLabel[0] }}</span>
        </div>
    </div>

    {{-- Butonlar --}}
    <div class="panel" style="display:flex;gap:12px;">
        <button id="btn-checkin" class="btn ok" style="flex:1;justify-content:center;padding:12px;"
            {{ $attendance->check_in_at ? 'disabled' : '' }}
            onclick="doCheckIn()">
            ✅ Giriş Yap
        </button>
        <button id="btn-checkout" class="btn warn" style="flex:1;justify-content:center;padding:12px;"
            {{ (!$attendance->check_in_at || $attendance->check_out_at) ? 'disabled' : '' }}
            onclick="doCheckOut()">
            🚪 Çıkış Yap
        </button>
    </div>

    {{-- Son 7 Gün --}}
    @if($recentDays->count() > 0)
    <div class="panel" style="padding:0;overflow:hidden;">
        <div style="padding:14px 16px 10px;font-size:14px;font-weight:700;border-bottom:1px solid var(--u-line);">Son 7 Gün</div>
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:color-mix(in srgb,var(--u-brand) 4%,var(--u-card));">
                    <th style="padding:8px 14px;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--u-muted);text-align:left;border-bottom:1px solid var(--u-line);">Tarih</th>
                    <th style="padding:8px 14px;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--u-muted);text-align:left;border-bottom:1px solid var(--u-line);">Giriş</th>
                    <th style="padding:8px 14px;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--u-muted);text-align:left;border-bottom:1px solid var(--u-line);">Çıkış</th>
                    <th style="padding:8px 14px;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--u-muted);text-align:left;border-bottom:1px solid var(--u-line);">Süre</th>
                    <th style="padding:8px 14px;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--u-muted);text-align:left;border-bottom:1px solid var(--u-line);">Durum</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentDays as $day)
                @php
                    $dLabel = match($day->status) {
                        'present'     => ['Zamanında', 'ok'],
                        'late'        => ['Geç', 'warn'],
                        'early_leave' => ['Erken Çıkış', 'warn'],
                        'absent'      => ['Devamsız', 'danger'],
                        'half_day'    => ['Yarım Gün', 'info'],
                        default       => [$day->status, 'info'],
                    };
                @endphp
                <tr style="border-bottom:1px solid var(--u-line);">
                    <td style="padding:8px 14px;font-size:13px;">{{ $day->work_date->format('d.m.Y') }}</td>
                    <td style="padding:8px 14px;font-size:13px;">{{ $day->check_in_at?->format('H:i') ?? '—' }}</td>
                    <td style="padding:8px 14px;font-size:13px;">{{ $day->check_out_at?->format('H:i') ?? '—' }}</td>
                    <td style="padding:8px 14px;font-size:13px;">
                        @if($day->work_minutes > 0)
                            {{ intdiv($day->work_minutes, 60) }}s {{ $day->work_minutes % 60 }}d
                        @else
                            —
                        @endif
                    </td>
                    <td style="padding:8px 14px;"><span class="badge {{ $dLabel[1] }}">{{ $dLabel[0] }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

</div>

<script>
// Canlı saat
(function(){
    function tick(){
        var now = new Date();
        var h = String(now.getHours()).padStart(2,'0');
        var m = String(now.getMinutes()).padStart(2,'0');
        var s = String(now.getSeconds()).padStart(2,'0');
        var el = document.getElementById('live-clock');
        if(el) el.textContent = h+':'+m+':'+s;

        var days = ['Pazar','Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi'];
        var months = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
        var dd = document.getElementById('live-date');
        if(dd) dd.textContent = days[now.getDay()] + ', ' + now.getDate() + ' ' + months[now.getMonth()] + ' ' + now.getFullYear();
    }
    tick();
    setInterval(tick, 1000);
})();

var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

function doCheckIn(){
    var btn = document.getElementById('btn-checkin');
    btn.disabled = true;
    btn.textContent = 'Kaydediliyor...';
    fetch('/hr/check-in', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json'},
        body: '{}'
    }).then(function(r){ return r.json(); }).then(function(d){
        if(d.error){ alert(d.error); btn.disabled=false; btn.textContent='✅ Giriş Yap'; return; }
        document.getElementById('checkin-display').textContent = d.check_in_at;
        document.getElementById('btn-checkout').disabled = false;
        var sb = document.getElementById('status-badge');
        if(sb){ sb.textContent = d.status === 'late' ? 'Geç Geldi' : 'Zamanında'; sb.className = 'badge ' + (d.status === 'late' ? 'warn' : 'ok'); }
        btn.textContent = '✅ Giriş Yapıldı';
    }).catch(function(){ btn.disabled=false; btn.textContent='✅ Giriş Yap'; alert('Bir hata oluştu.'); });
}

function doCheckOut(){
    var btn = document.getElementById('btn-checkout');
    btn.disabled = true;
    btn.textContent = 'Kaydediliyor...';
    fetch('/hr/check-out', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json'},
        body: '{}'
    }).then(function(r){ return r.json(); }).then(function(d){
        if(d.error){ alert(d.error); btn.disabled=false; btn.textContent='🚪 Çıkış Yap'; return; }
        document.getElementById('checkout-display').textContent = d.check_out_at;
        var mins = d.work_minutes;
        document.getElementById('work-duration').textContent = Math.floor(mins/60) + 's ' + (mins%60) + 'd';
        btn.textContent = '🚪 Çıkış Yapıldı';
    }).catch(function(){ btn.disabled=false; btn.textContent='🚪 Çıkış Yap'; alert('Bir hata oluştu.'); });
}
</script>
@endsection
