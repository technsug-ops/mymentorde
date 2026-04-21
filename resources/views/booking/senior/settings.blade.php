@extends('senior.layouts.app')
@section('title','Randevu Ayarları')
@section('page_title','📅 Randevu Ayarları')

@section('content')
<style>
.bk-wrap { max-width: 960px; margin: 20px auto; padding: 0 16px; }
.bk-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:20px; margin-bottom:16px; }
.bk-card h2 { margin:0 0 12px; font-size:16px; color:#0f172a; }
.bk-card p.hint { margin:0 0 14px; font-size:12px; color:#64748b; line-height:1.6; }
.bk-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px; }
.bk-field label { display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:4px; }
.bk-field input, .bk-field select, .bk-field textarea {
    width:100%; padding:8px 10px; border:1px solid #cbd5e1; border-radius:8px;
    font-size:13px; background:#fff; box-sizing:border-box;
}
.bk-btn { padding:10px 20px; border:none; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; }
.bk-btn-primary { background:#0f172a; color:#fff; }
.bk-btn-danger { background:#dc2626; color:#fff; }
.bk-btn-ghost { background:#f1f5f9; color:#0f172a; border:1px solid #e2e8f0; }
.bk-row { display:flex; gap:10px; align-items:center; padding:10px 12px; border-bottom:1px solid #f1f5f9; font-size:13px; }
.bk-row:last-child { border-bottom:0; }
.bk-row .day { font-weight:700; color:#0f172a; min-width:100px; }
.bk-row .time { color:#64748b; font-family:monospace; }
.bk-badge { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:700; }
.bk-badge.green { background:#dcfce7; color:#166534; }
.bk-badge.red { background:#fee2e2; color:#991b1b; }
.bk-public-url { background:#eef2ff; border:1px solid #c7d2fe; padding:10px 14px; border-radius:8px; font-family:monospace; font-size:12px; color:#3730a3; display:flex; justify-content:space-between; align-items:center; gap:10px; word-break:break-all; }
.bk-status-msg { background:#dcfce7; border:1px solid #86efac; color:#166534; padding:10px 14px; border-radius:8px; font-size:13px; margin-bottom:12px; }
.bk-form-inline { display:flex; gap:8px; align-items:flex-end; flex-wrap:wrap; }
.bk-form-inline > * { flex-shrink:0; }
</style>

<div class="bk-wrap">

    @if (session('status'))
        <div class="bk-status-msg">✅ {{ session('status') }}</div>
    @endif

    {{-- ═══════ 1. GENEL AYARLAR ═══════ --}}
    <div class="bk-card">
        <h2>⚙️ Genel Ayarlar</h2>
        <p class="hint">Slot süresi, buffer, minimum bildirim ve public erişim ayarları.</p>

        @if ($publicUrl)
            <div class="bk-public-url">
                <span>🔗 {{ $publicUrl }}</span>
                <button type="button" class="bk-btn bk-btn-ghost" data-copy-url="{{ $publicUrl }}" style="padding:5px 10px;font-size:11px;">📋 Kopyala</button>
            </div>
        @endif

        <form method="POST" action="{{ route('senior.booking-settings.update') }}" style="margin-top:14px;">
            @csrf
            <div class="bk-grid">
                <div class="bk-field">
                    <label>Slot süresi (dk)</label>
                    <select name="slot_duration">
                        @foreach ([15, 20, 30, 45, 60, 90, 120] as $m)
                            <option value="{{ $m }}" @selected((int)$settings->slot_duration === $m)>{{ $m }} dakika</option>
                        @endforeach
                    </select>
                </div>
                <div class="bk-field">
                    <label>Buffer (randevular arası)</label>
                    <select name="buffer_minutes">
                        @foreach ([0, 5, 10, 15, 30] as $m)
                            <option value="{{ $m }}" @selected((int)$settings->buffer_minutes === $m)>{{ $m }} dakika</option>
                        @endforeach
                    </select>
                </div>
                <div class="bk-field">
                    <label>En az ne kadar önceden</label>
                    <select name="min_notice_hours">
                        @foreach ([0, 2, 4, 6, 12, 24, 48, 72] as $h)
                            <option value="{{ $h }}" @selected((int)$settings->min_notice_hours === $h)>{{ $h }} saat</option>
                        @endforeach
                    </select>
                </div>
                <div class="bk-field">
                    <label>Max. ne kadar ileri</label>
                    <select name="max_future_days">
                        @foreach ([14, 30, 60, 90, 180, 365] as $d)
                            <option value="{{ $d }}" @selected((int)$settings->max_future_days === $d)>{{ $d }} gün</option>
                        @endforeach
                    </select>
                </div>
                <div class="bk-field">
                    <label>Zaman dilimi</label>
                    <select name="timezone">
                        @foreach ($supportedTimezones as $tz)
                            <option value="{{ $tz }}" @selected($settings->timezone === $tz)>{{ $tz }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="bk-field">
                    <label>Görünen ad (başlık)</label>
                    <input type="text" name="display_name" value="{{ old('display_name', $settings->display_name) }}" placeholder="Örn: Danışmanlık Görüşmesi" maxlength="120">
                </div>
            </div>

            <div class="bk-field" style="margin-top:12px;">
                <label>Karşılama mesajı (public sayfa üstü)</label>
                <textarea name="welcome_message" rows="2" maxlength="2000" placeholder="Opsiyonel. Örn: 'Danışmanlık için aşağıdan size uygun saati seçiniz.'">{{ old('welcome_message', $settings->welcome_message) }}</textarea>
            </div>

            <div style="display:flex;gap:16px;margin-top:14px;flex-wrap:wrap;">
                <label style="display:flex;align-items:center;gap:6px;font-size:13px;">
                    <input type="checkbox" name="is_active" value="1" @checked($settings->is_active)>
                    <span>Randevu sistemim <strong>aktif</strong></span>
                </label>
                <label style="display:flex;align-items:center;gap:6px;font-size:13px;">
                    <input type="checkbox" name="is_public" value="1" @checked($settings->is_public)>
                    <span>🌐 <strong>Public link açık</strong> (herkes booking yapabilir)</span>
                </label>
            </div>

            <div style="margin-top:14px;">
                <button type="submit" class="bk-btn bk-btn-primary">💾 Ayarları Kaydet</button>
            </div>
        </form>
    </div>

    {{-- ═══════ 2. HAFTALIK MÜSAİTLİK PATTERN ═══════ --}}
    <div class="bk-card">
        <h2>🗓️ Haftalık Müsaitlik</h2>
        <p class="hint">Haftanın hangi günleri ve saatleri müsaitsin? Birden fazla dilim ekleyebilirsin (örn. Salı 09-12 + Salı 14-17).</p>

        <form method="POST" action="{{ route('senior.booking-settings.patterns.store') }}" class="bk-form-inline">
            @csrf
            <div class="bk-field" style="flex:1;min-width:140px;">
                <label>Gün</label>
                <select name="weekday">
                    @foreach ($weekdayLabels as $idx => $lbl)
                        <option value="{{ $idx }}">{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="bk-field" style="min-width:110px;">
                <label>Başlangıç</label>
                <input type="time" name="start_time" required value="09:00">
            </div>
            <div class="bk-field" style="min-width:110px;">
                <label>Bitiş</label>
                <input type="time" name="end_time" required value="17:00">
            </div>
            <button type="submit" class="bk-btn bk-btn-primary">➕ Ekle</button>
        </form>

        <div style="margin-top:14px;">
            @forelse ($patterns as $p)
                <div class="bk-row">
                    <span class="day">{{ $weekdayLabels[$p->weekday] ?? ('Gün ' . $p->weekday) }}</span>
                    <span class="time">{{ \Carbon\Carbon::parse($p->start_time)->format('H:i') }} – {{ \Carbon\Carbon::parse($p->end_time)->format('H:i') }}</span>
                    <span style="margin-left:auto;">
                        <form method="POST" action="{{ route('senior.booking-settings.patterns.destroy', $p) }}" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bk-btn bk-btn-danger" style="padding:5px 10px;font-size:11px;">Sil</button>
                        </form>
                    </span>
                </div>
            @empty
                <div style="padding:20px;text-align:center;color:#94a3b8;font-size:13px;">Henüz müsaitlik tanımı yok. Yukarıdan ekle.</div>
            @endforelse
        </div>
    </div>

    {{-- ═══════ 3. İSTİSNALAR (İZİN / ÖZEL GÜN) ═══════ --}}
    <div class="bk-card">
        <h2>🚫 İstisnalar / İzin Günleri</h2>
        <p class="hint">Belirli bir günü tamamen kapatabilir veya özel saat aralığı tanımlayabilirsin (tatil, izin, ek slot vb.).</p>

        <form method="POST" action="{{ route('senior.booking-settings.exceptions.store') }}" class="bk-form-inline">
            @csrf
            <div class="bk-field" style="min-width:140px;">
                <label>Tarih</label>
                <input type="date" name="date" required min="{{ now()->toDateString() }}">
            </div>
            <div class="bk-field" style="min-width:140px;">
                <label>Tür</label>
                <select name="is_blocked" id="bk-exc-type">
                    <option value="1">Tamamen kapalı (tatil/izin)</option>
                    <option value="0">Özel saat</option>
                </select>
            </div>
            <div class="bk-field bk-exc-times" style="min-width:100px;display:none;">
                <label>Başlangıç</label>
                <input type="time" name="override_start_time">
            </div>
            <div class="bk-field bk-exc-times" style="min-width:100px;display:none;">
                <label>Bitiş</label>
                <input type="time" name="override_end_time">
            </div>
            <div class="bk-field" style="min-width:180px;flex:1;">
                <label>Açıklama (opsiyonel)</label>
                <input type="text" name="reason" maxlength="255" placeholder="Örn: Resmi tatil">
            </div>
            <button type="submit" class="bk-btn bk-btn-primary">➕ Ekle</button>
        </form>

        <div style="margin-top:14px;">
            @forelse ($exceptions as $ex)
                <div class="bk-row">
                    <span class="day">{{ $ex->date->format('d.m.Y') }}</span>
                    @if ($ex->is_blocked)
                        <span class="bk-badge red">Kapalı</span>
                    @else
                        <span class="bk-badge green">Özel saat</span>
                        <span class="time">{{ \Carbon\Carbon::parse($ex->override_start_time)->format('H:i') }} – {{ \Carbon\Carbon::parse($ex->override_end_time)->format('H:i') }}</span>
                    @endif
                    @if ($ex->reason)
                        <span style="color:#64748b;font-size:12px;">— {{ $ex->reason }}</span>
                    @endif
                    <span style="margin-left:auto;">
                        <form method="POST" action="{{ route('senior.booking-settings.exceptions.destroy', $ex) }}" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bk-btn bk-btn-danger" style="padding:5px 10px;font-size:11px;">Sil</button>
                        </form>
                    </span>
                </div>
            @empty
                <div style="padding:20px;text-align:center;color:#94a3b8;font-size:13px;">İstisna tanımı yok.</div>
            @endforelse
        </div>
    </div>

</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    // İstisna tipi değişince özel saat input'larını göster/gizle
    var sel = document.getElementById('bk-exc-type');
    var times = document.querySelectorAll('.bk-exc-times');
    if (sel) {
        var toggle = function() {
            times.forEach(function(t){ t.style.display = sel.value === '0' ? '' : 'none'; });
        };
        sel.addEventListener('change', toggle);
        toggle();
    }

    // Copy public URL
    document.querySelectorAll('[data-copy-url]').forEach(function(btn){
        btn.addEventListener('click', function(){
            var url = btn.getAttribute('data-copy-url');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function(){
                    var prev = btn.textContent;
                    btn.textContent = '✅ Kopyalandı';
                    setTimeout(function(){ btn.textContent = prev; }, 1500);
                });
            }
        });
    });
})();
</script>
@endsection
