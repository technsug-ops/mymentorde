@php
    $role = auth()->user()?->role;
    $availLayout = match(true) {
        in_array($role, ['senior','mentor']) => 'senior.layouts.app',
        in_array($role, ['marketing_admin','marketing_staff','sales_admin','sales_staff']) => 'marketing-admin.layouts.app',
        default => 'manager.layouts.app',
    };
@endphp
@extends($availLayout)

@section('title', 'Müsaitlik & Çalışma Saatleri')
@section('page_title', 'Müsaitlik Ayarları')

@push('head')
<style>
.av-section { background:var(--u-card);border:1px solid var(--u-line);border-radius:14px;padding:20px 24px;margin-bottom:16px; }
.av-section h2 { font-size:15px;font-weight:700;margin:0 0 16px;display:flex;align-items:center;gap:8px; }
.av-day-row { display:grid;grid-template-columns:120px auto 1fr 1fr 80px;gap:10px;align-items:center;padding:8px 0;border-bottom:1px solid var(--u-line); }
.av-day-row:last-child { border-bottom:none; }
.av-day-label { font-weight:600;font-size:13px; }
.av-toggle { position:relative;display:inline-block;width:40px;height:22px; }
.av-toggle input { opacity:0;width:0;height:0; }
.av-slider { position:absolute;inset:0;background:#cbd5e1;border-radius:11px;cursor:pointer;transition:.2s; }
.av-slider:before { content:'';position:absolute;height:16px;width:16px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.2s; }
.av-toggle input:checked + .av-slider { background:#16a34a; }
.av-toggle input:checked + .av-slider:before { transform:translateX(18px); }
.av-time-input { height:34px;border:1px solid var(--u-line);border-radius:7px;padding:0 8px;font-size:13px;width:100%; }
.av-time-input:focus { outline:none;border-color:#2563eb; }

.pres-badge { display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:999px;font-size:12px;font-weight:600; }
.pres-dot { width:8px;height:8px;border-radius:50%;flex-shrink:0; }

.away-card { border:1.5px solid var(--u-line);border-radius:10px;padding:14px 16px;margin-bottom:10px;display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap; }
.away-card.active { border-color:#d97706;background:#fffbeb; }
.away-dates { font-weight:600;font-size:13px; }
.away-msg { font-size:12px;color:var(--u-muted);margin-top:3px; }
</style>
@endpush

@section('content')
@if(session('status'))
<div style="background:#dcfce7;border:1px solid #86efac;padding:10px 16px;border-radius:8px;font-size:13px;color:#15803d;margin-bottom:12px;">
    ✅ {{ session('status') }}
</div>
@endif

{{-- Mevcut Durum --}}
<div class="av-section">
    <h2>📡 Şu Anki Durum</h2>
    @php
        $statusColors = ['online'=>'#16a34a','away'=>'#d97706','busy'=>'#dc2626','offline'=>'#9ca3af'];
        $sc = $statusColors[$presence['status']] ?? '#9ca3af';
    @endphp
    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
        <div class="pres-badge" style="background:{{ $sc }}18;border:1.5px solid {{ $sc }}40;">
            <span class="pres-dot" style="background:{{ $sc }};{{ $presence['status']==='online' ? 'animation:pulse 1.5s infinite;' : '' }}"></span>
            <span style="color:{{ $sc }};">{{ $presence['label'] }}</span>
        </div>
        @if(!empty($presence['away_until_fmt']))
            <span style="font-size:12px;color:var(--u-muted);">Dönüş: {{ $presence['away_until_fmt'] }}</span>
        @endif
    </div>
</div>

{{-- Çalışma Saatleri --}}
<div class="av-section">
    <h2>🗓 Çalışma Saatleri</h2>
    <p style="font-size:13px;color:var(--u-muted);margin:-8px 0 16px;">Tanımlı saatler dışında otomatik olarak "Çevrimdışı" görünürsünüz. Hiç tanımlanmamışsa her zaman müsait sayılırsınız.</p>

    <form method="POST" action="{{ route('availability.schedule.save') }}">
        @csrf
        <input type="hidden" name="timezone" value="{{ auth()->user()->availabilitySchedules()->first()?->timezone ?? 'Europe/Berlin' }}">

        <div style="display:grid;grid-template-columns:120px 50px 1fr 1fr 80px;gap:10px;padding:6px 0;margin-bottom:6px;">
            <span style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--u-muted);">Gün</span>
            <span style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--u-muted);">Aktif</span>
            <span style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--u-muted);">Başlangıç</span>
            <span style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--u-muted);">Bitiş</span>
            <span></span>
        </div>

        @foreach(\App\Models\UserAvailabilitySchedule::$DAY_LABELS as $dayNum => $dayLabel)
            @php $s = $schedules[$dayNum] ?? null; @endphp
            <div class="av-day-row">
                <span class="av-day-label">{{ $dayLabel }}</span>
                <label class="av-toggle">
                    <input type="checkbox" name="schedules[{{ $dayNum }}][active]" value="1"
                           {{ $s?->is_active ? 'checked' : '' }}>
                    <span class="av-slider"></span>
                </label>
                <input type="hidden" name="schedules[{{ $dayNum }}][day]" value="{{ $dayNum }}">
                <input type="time" name="schedules[{{ $dayNum }}][start]" class="av-time-input"
                       value="{{ $s ? substr($s->start_time,0,5) : '09:00' }}">
                <input type="time" name="schedules[{{ $dayNum }}][end]" class="av-time-input"
                       value="{{ $s ? substr($s->end_time,0,5) : '18:00' }}">
            </div>
        @endforeach

        <div style="margin-top:16px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <button type="submit" class="btn ok">Saatleri Kaydet</button>
            <div>
                <label style="font-size:12px;color:var(--u-muted);margin-right:6px;">Saat dilimi:</label>
                <select name="timezone" style="height:34px;border-radius:7px;border:1px solid var(--u-line);font-size:13px;padding:0 8px;">
                    @foreach(['Europe/Berlin','Europe/Istanbul','UTC','America/New_York','Asia/Dubai'] as $tz)
                        <option value="{{ $tz }}" @selected(($schedules->first()?->timezone ?? 'Europe/Berlin')===$tz)>{{ $tz }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>
</div>

{{-- Away Periods --}}
<div class="av-section">
    <h2>✈️ Dışarıda Olma Dönemleri</h2>
    <p style="font-size:13px;color:var(--u-muted);margin:-8px 0 16px;">Bu süre zarfında "Dışarıda" görünürsünüz ve otomatik yanıt gönderilebilir.</p>

    {{-- Mevcut dönemler --}}
    @php
        $user       = auth()->user();
        $awayPeriods = \App\Models\UserAwayPeriod::where('user_id', $user->id)->orderBy('away_from')->get();
    @endphp

    @forelse($awayPeriods as $ap)
        @php $isActive = $ap->isActive(); @endphp
        <div class="away-card {{ $isActive ? 'active' : '' }}">
            <div style="flex:1;">
                <div class="away-dates">
                    {{ $ap->away_from->format('d.m.Y H:i') }} → {{ $ap->away_until->format('d.m.Y H:i') }}
                    @if($isActive)<span class="badge warn" style="margin-left:6px;font-size:10px;">Aktif</span>@endif
                </div>
                @if($ap->away_message)
                    <div class="away-msg">💬 {{ $ap->away_message }}</div>
                @endif
                @if($ap->auto_reply_enabled && $ap->auto_reply_message)
                    <div class="away-msg">🤖 Auto-reply: {{ \Illuminate\Support\Str::limit($ap->auto_reply_message, 80) }}</div>
                @endif
            </div>
            <form method="POST" action="{{ route('availability.away.delete', $ap->id) }}" class="av-del-form">
                @csrf @method('DELETE')
                <button type="submit" class="btn warn" style="font-size:12px;padding:5px 12px;">Sil</button>
            </form>
        </div>
    @empty
        <div class="muted" style="margin-bottom:16px;">Tanımlı dönem yok.</div>
    @endforelse

    {{-- Yeni dönem ekle --}}
    <details style="margin-top:8px;">
        <summary style="cursor:pointer;font-weight:600;font-size:13px;color:#2563eb;margin-bottom:12px;">+ Yeni Dönem Ekle</summary>
        <form method="POST" action="{{ route('availability.away.store') }}" style="margin-top:14px;">
            @csrf
            <div class="grid2" style="margin-bottom:12px;">
                <div>
                    <label style="font-size:12px;font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Başlangıç *</label>
                    <input type="datetime-local" name="away_from" required
                           style="width:100%;height:34px;border:1px solid var(--u-line);border-radius:7px;font-size:13px;padding:0 8px;">
                    @error('away_from')<div style="color:#dc2626;font-size:11px;margin-top:2px;">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Bitiş *</label>
                    <input type="datetime-local" name="away_until" required
                           style="width:100%;height:34px;border:1px solid var(--u-line);border-radius:7px;font-size:13px;padding:0 8px;">
                    @error('away_until')<div style="color:#dc2626;font-size:11px;margin-top:2px;">{{ $message }}</div>@enderror
                </div>
            </div>
            <div style="margin-bottom:12px;">
                <label style="font-size:12px;font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Durum Mesajı</label>
                <input type="text" name="away_message" placeholder="Tatildeyim, Pazartesi dönerim…" maxlength="300"
                       style="width:100%;height:34px;border:1px solid var(--u-line);border-radius:7px;font-size:13px;padding:0 8px;">
            </div>
            <div style="margin-bottom:12px;display:flex;align-items:center;gap:10px;">
                <label class="av-toggle">
                    <input type="checkbox" name="auto_reply_enabled" value="1" checked id="auto-reply-toggle">
                    <span class="av-slider"></span>
                </label>
                <label for="auto-reply-toggle" style="font-size:13px;font-weight:600;cursor:pointer;">Otomatik Yanıt Gönder</label>
            </div>
            <div id="auto-reply-field" style="margin-bottom:12px;">
                <label style="font-size:12px;font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Otomatik Yanıt Metni</label>
                <textarea name="auto_reply_message" rows="3" maxlength="500"
                          placeholder="Merhaba, şu an dışarıdayım. En kısa sürede dönüş yapacağım."
                          style="width:100%;border:1px solid var(--u-line);border-radius:7px;font-size:13px;padding:8px;resize:vertical;"></textarea>
            </div>
            <button type="submit" class="btn ok">Dönem Ekle</button>
        </form>
    </details>
</div>

@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function () {
    // Otomatik yanıt toggle
    const toggle = document.getElementById('auto-reply-toggle');
    const field  = document.getElementById('auto-reply-field');
    if (toggle && field) {
        field.style.display = toggle.checked ? '' : 'none';
        toggle.addEventListener('change', function () {
            field.style.display = this.checked ? '' : 'none';
        });
    }

    // Away dönem silme confirm
    document.querySelectorAll('.av-del-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm('Bu dönemi silmek istiyor musun?')) {
                e.preventDefault();
            }
        });
    });
})();
</script>
<style>
@keyframes pulse {
    0%,100% { opacity:1; }
    50%      { opacity:.4; }
}
</style>
@endpush
