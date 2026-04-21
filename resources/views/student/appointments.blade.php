@extends('student.layouts.app')

@section('title', 'Randevularım')
@section('page_title', 'Randevularım')

@push('head')
<style>
/* ══════ Hero (Option B) ══════ */
.apt-hero { color:#fff; border-radius:14px; margin-bottom:16px; overflow:hidden; box-shadow:0 6px 24px rgba(0,0,0,.1); position:relative;
    background:#0c4a6e url('https://images.unsplash.com/photo-1551836022-deb4988cc6c0?w=1400&q=80') center/cover; }
.apt-hero::before { content:''; position:absolute; inset:0; background:linear-gradient(135deg, rgba(12,74,110,.93) 0%, rgba(14,165,233,.82) 100%); }
.apt-hero-body { position:relative; display:flex; align-items:center; gap:20px; padding:22px 26px; }
.apt-hero-main { flex:1; min-width:0; display:flex; flex-direction:column; gap:7px; }
.apt-hero-label { display:inline-flex; align-items:center; gap:7px; font-size:11px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; opacity:.85; }
.apt-hero-marker { display:inline-block; width:5px; height:14px; background:rgba(255,255,255,.75); border-radius:3px; }
.apt-hero-title { font-size:24px; font-weight:800; line-height:1.1; margin:0; letter-spacing:-.3px; }
.apt-hero-sub { font-size:12.5px; opacity:.88; line-height:1.5; max-width:560px; }
.apt-hero-stats { display:flex; gap:7px; flex-wrap:wrap; margin-top:8px; padding-top:12px; border-top:1px solid rgba(255,255,255,.2); }
.apt-hero-stat { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:18px; background:rgba(255,255,255,.18); font-size:11.5px; font-weight:600; line-height:1; border:1px solid rgba(255,255,255,.12); }
.apt-hero-icon { font-size:50px; line-height:1; flex-shrink:0; opacity:.88; filter:drop-shadow(0 4px 12px rgba(0,0,0,.25)); }
@media (max-width:640px){ .apt-hero-body { gap:14px; padding:18px; align-items:flex-start; } .apt-hero-title { font-size:20px; } .apt-hero-sub { font-size:12px; } .apt-hero-icon { font-size:36px; } }

/* ── apt-* Appointments scoped ── */
.apt-stats {
    display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; margin-bottom: 16px;
}
@media(max-width:800px){ .apt-stats { grid-template-columns: 1fr 1fr; } }
.apt-stat {
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 14px; padding: 14px 18px;
    border-left: 4px solid var(--u-line);
}
.apt-stat.s-pending  { border-left-color: #d97706; }
.apt-stat.s-ok       { border-left-color: #16a34a; }
.apt-stat.s-done     { border-left-color: #0891b2; }
.apt-stat.s-cancel   { border-left-color: #dc2626; }
.apt-stat-lbl { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: var(--u-muted); margin-bottom: 6px; }
.apt-stat-val { font-size: 26px; font-weight: 800; color: var(--u-text); line-height: 1; }

/* Layout */
.apt-layout { display: grid; grid-template-columns: 380px 1fr; gap: 16px; align-items: start; }
@media(max-width:900px){ .apt-layout { grid-template-columns: 1fr; } }

/* Form card */
.apt-form-card {
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 14px; overflow: hidden; position: sticky; top: 8px;
}
.apt-form-head {
    padding: 14px 18px;
    background: linear-gradient(to right, var(--u-brand-2), var(--u-brand));
}
.apt-form-head-title { font-size: 15px; font-weight: 700; color: #fff; }
.apt-form-head-sub   { font-size: 11px; color: rgba(255,255,255,.75); margin-top: 2px; }
.apt-form-body { padding: 16px 18px; }

.apt-field { display: flex; flex-direction: column; gap: 5px; margin-bottom: 12px; }
.apt-field label { font-size: 12px; font-weight: 700; color: var(--u-text); }
.apt-field input,
.apt-field select,
.apt-field textarea {
    width: 100%; box-sizing: border-box;
    padding: 9px 12px; border: 1.5px solid var(--u-line); border-radius: 8px;
    background: var(--u-bg); color: var(--u-text); font-size: 13px; font-family: inherit;
    transition: border-color .15s, box-shadow .15s;
}
.apt-field input:focus, .apt-field select:focus, .apt-field textarea:focus {
    outline: none; border-color: var(--u-brand);
    box-shadow: 0 0 0 3px rgba(124,58,237,.1);
}
.apt-field textarea { min-height: 72px; resize: vertical; }
.apt-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

.apt-submit {
    width: 100%; padding: 10px; background: var(--u-brand); color: #fff;
    border: none; border-radius: 8px; font-size: 13px; font-weight: 700;
    cursor: pointer; transition: opacity .15s;
}
.apt-submit:hover { opacity: .88; }

/* Appointment list */
.apt-list-card {
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 14px; overflow: hidden;
}
.apt-list-head {
    padding: 12px 18px; border-bottom: 1px solid var(--u-line);
    display: flex; justify-content: space-between; align-items: center;
}
.apt-list-title { font-size: 14px; font-weight: 700; color: var(--u-text); }

/* Appointment item */
.apt-item {
    display: flex; gap: 14px; align-items: flex-start;
    padding: 14px 18px; border-bottom: 1px solid var(--u-line);
    transition: background .12s;
}
.apt-item:last-child { border-bottom: none; }
.apt-item:hover { background: var(--u-bg); }

.apt-date-box {
    flex-shrink: 0; width: 48px; text-align: center;
    background: var(--u-bg); border: 1px solid var(--u-line);
    border-radius: 10px; padding: 6px 4px;
}
.apt-date-day   { font-size: 20px; font-weight: 800; color: var(--u-brand); line-height: 1; }
.apt-date-month { font-size: 10px; font-weight: 600; color: var(--u-muted); text-transform: uppercase; margin-top: 2px; }
.apt-date-time  { font-size: 10px; color: var(--u-muted); margin-top: 3px; }

.apt-item-body { flex: 1; min-width: 0; }
.apt-item-title { font-size: 13px; font-weight: 700; color: var(--u-text); margin-bottom: 5px; }
.apt-item-meta  { display: flex; gap: 5px; flex-wrap: wrap; align-items: center; }

.apt-cancel-form { display: flex; gap: 6px; align-items: center; flex-shrink: 0; margin-top: 2px; }
.apt-cancel-form input {
    border: 1px solid var(--u-line); border-radius: 6px;
    padding: 4px 8px; font-size: 11px; width: 120px;
    background: var(--u-bg); color: var(--u-text);
}
.apt-cancel-btn {
    padding: 4px 10px; border: 1px solid var(--u-line);
    border-radius: 6px; background: var(--u-card); color: var(--u-muted);
    font-size: 11px; cursor: pointer; white-space: nowrap;
}
.apt-cancel-btn:hover { border-color: #dc2626; color: #dc2626; }

.apt-meet-link {
    display: inline-flex; align-items: center; gap: 4px;
    margin-top: 5px; font-size: 12px; font-weight: 600;
    color: var(--u-brand); text-decoration: none;
}
.apt-meet-link:hover { text-decoration: underline; }

.apt-empty {
    padding: 32px 18px; text-align: center;
    color: var(--u-muted); font-size: 13px;
}
</style>
@endpush

@section('content')
@php
    $appts     = collect($appointments ?? []);
    $pending   = $appts->where('status', 'pending')->count();
    $scheduled = $appts->where('status', 'scheduled')->count();
    $done      = $appts->where('status', 'done')->count();
    $cancelled = $appts->where('status', 'cancelled')->count();

    $statusLabel = ['pending' => 'Bekliyor', 'scheduled' => 'Planlandı', 'done' => 'Tamamlandı', 'cancelled' => 'İptal'];
    $channelLabel = ['online' => '🖥 Online', 'phone' => '📞 Telefon', 'office' => '🏢 Ofis'];
@endphp

{{-- ══════ Hero ══════ --}}
<div class="apt-hero">
    <div class="apt-hero-body">
        <div class="apt-hero-main">
            <div class="apt-hero-label"><span class="apt-hero-marker"></span>Randevu Planlama</div>
            <h1 class="apt-hero-title">Randevularım</h1>
            <div class="apt-hero-sub">Danışmanınla birebir görüşmelerini buradan ayarla. Online, telefon veya ofiste — sana uygun olanı seç.</div>
            <div class="apt-hero-stats">
                <span class="apt-hero-stat">⏳ {{ $pending }} bekleyen</span>
                <span class="apt-hero-stat">🟢 {{ $scheduled }} planlı</span>
                <span class="apt-hero-stat">✅ {{ $done }} tamamlandı</span>
                @if($cancelled > 0)<span class="apt-hero-stat">❌ {{ $cancelled }} iptal</span>@endif
            </div>
        </div>
        <div class="apt-hero-icon">📆</div>
    </div>
</div>

{{-- Stats --}}
<div class="apt-stats">
    <div class="apt-stat s-pending">
        <div class="apt-stat-lbl">Bekleyen</div>
        <div class="apt-stat-val">{{ $pending }}</div>
    </div>
    <div class="apt-stat s-ok">
        <div class="apt-stat-lbl">Planlandı</div>
        <div class="apt-stat-val">{{ $scheduled }}</div>
    </div>
    <div class="apt-stat s-done">
        <div class="apt-stat-lbl">Tamamlandı</div>
        <div class="apt-stat-val">{{ $done }}</div>
    </div>
    <div class="apt-stat s-cancel">
        <div class="apt-stat-lbl">İptal</div>
        <div class="apt-stat-val">{{ $cancelled }}</div>
    </div>
</div>

<div class="apt-layout">

    {{-- Form --}}
    <div class="apt-form-card">
        <div class="apt-form-head">
            <div class="apt-form-head-title">📅 Yeni Randevu Talebi</div>
            <div class="apt-form-head-sub">Eğitim Danışmanı onayladıktan sonra toplantı linki eklenir.</div>
        </div>
        <div class="apt-form-body">
            <form method="post" action="{{ route('student.appointments.store') }}">
                @csrf
                <div class="apt-field">
                    <label>Konu <span style="color:var(--u-danger);">*</span></label>
                    <input name="title" placeholder="örn: Vize dosya kontrolü, ön görüşme" required>
                </div>
                <div class="apt-grid2">
                    <div class="apt-field">
                        <label>Tarih & Saat <span style="color:var(--u-danger);">*</span></label>
                        <input type="datetime-local" name="scheduled_at" required>
                    </div>
                    <div class="apt-field">
                        <label>Süre (dk)</label>
                        <input type="number" name="duration_minutes" min="15" max="180" value="30">
                    </div>
                </div>
                <div class="apt-field">
                    <label>Görüşme Kanalı</label>
                    <select name="channel">
                        <option value="online">🖥 Online (Zoom / Meet)</option>
                        <option value="phone">📞 Telefon</option>
                        <option value="office">🏢 Ofis</option>
                    </select>
                </div>
                <div class="apt-field">
                    <label>Not <span class="muted" style="font-weight:400;">(opsiyonel)</span></label>
                    <textarea name="note" placeholder="Görüşmek istediğiniz konuları özetleyin..."></textarea>
                </div>
                <button class="apt-submit" type="submit">Talep Gönder →</button>
            </form>
        </div>
    </div>

    {{-- List --}}
    <div class="apt-list-card">
        <div class="apt-list-head">
            <div class="apt-list-title">Randevularım</div>
            <span class="badge">{{ $appts->count() }} toplam</span>
        </div>

        @forelse($appts->sortByDesc('scheduled_at') as $a)
        @php
            $st    = strtolower((string) $a->status);
            $stCls = match($st) { 'pending' => 'warn', 'scheduled' => 'ok', 'done' => 'info', 'cancelled' => 'danger', default => '' };
            $dt    = $a->scheduled_at ? \Carbon\Carbon::parse($a->scheduled_at) : null;
        @endphp
        <div class="apt-item">
            {{-- Date box --}}
            <div class="apt-date-box">
                @if($dt)
                    <div class="apt-date-day">{{ $dt->format('d') }}</div>
                    <div class="apt-date-month">{{ $dt->locale('tr')->isoFormat('MMM') }}</div>
                    <div class="apt-date-time">{{ $dt->format('H:i') }}</div>
                @else
                    <div class="apt-date-day">—</div>
                @endif
            </div>

            {{-- Body --}}
            <div class="apt-item-body">
                <div class="apt-item-title">{{ $a->title }}</div>
                <div class="apt-item-meta">
                    <span class="badge {{ $stCls }}">{{ $statusLabel[$st] ?? $st }}</span>
                    <span class="badge">{{ $channelLabel[$a->channel] ?? $a->channel }}</span>
                    @if($a->duration_minutes)
                        <span class="badge">⏱ {{ $a->duration_minutes }} dk</span>
                    @endif
                </div>
                @if(!empty($a->meeting_url))
                    <a class="apt-meet-link" href="{{ $a->meeting_url }}" target="_blank">🔗 Toplantıya Katıl</a>
                @endif
                @if($st === 'cancelled' && $a->cancel_reason)
                    <div class="muted" style="font-size:var(--tx-xs);margin-top:4px;">↩ {{ $a->cancel_reason }}</div>
                @endif
            </div>

            {{-- Cancel --}}
            @if($st !== 'cancelled' && $st !== 'done')
            <form method="post" action="{{ route('student.appointments.cancel', $a->id) }}" class="apt-cancel-form">
                @csrf
                <input name="reason" placeholder="İptal nedeni...">
                <button class="apt-cancel-btn" type="submit">İptal</button>
            </form>
            @endif
        </div>
        @empty
        <div class="apt-empty">
            📅 Henüz randevu talebiniz yok.<br>
            <span style="font-size:var(--tx-xs);">Sol taraftan yeni bir randevu talebi oluşturabilirsiniz.</span>
        </div>
        @endforelse
    </div>

</div>
@endsection
