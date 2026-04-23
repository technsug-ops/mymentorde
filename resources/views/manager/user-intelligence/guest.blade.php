@extends('manager.layouts.app')
@section('title', 'Aday Timeline — ' . trim(($guest->first_name ?? '') . ' ' . ($guest->last_name ?? '')))
@section('page_title', '🙋 Aday Aktivite Timeline — ' . trim(($guest->first_name ?? '') . ' ' . ($guest->last_name ?? '')))

@section('content')
<style>
.uig-wrap { max-width:1000px; margin:20px auto; padding:0 16px; }
.uig-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:22px; margin-bottom:18px; }
.uig-card h2 { margin:0 0 6px; font-size:16px; color:#0f172a; display:flex; align-items:center; gap:8px; }

.uig-header { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; }
@media(max-width:900px){ .uig-header { grid-template-columns:1fr; } }
.uig-meta-row { display:flex; gap:8px; font-size:12px; margin:6px 0; }
.uig-meta-row .key { color:#64748b; min-width:110px; }
.uig-meta-row .val { color:#1e293b; font-weight:600; }

.uig-timeline { position:relative; padding-left:30px; }
.uig-timeline::before { content:''; position:absolute; left:10px; top:10px; bottom:10px; width:2px; background:#e2e8f0; }
.uig-event { position:relative; padding:10px 14px; margin-bottom:10px; background:#f8fafc; border-radius:8px; border:1px solid #e2e8f0; }
.uig-event::before { content:''; position:absolute; left:-25px; top:16px; width:12px; height:12px; border-radius:50%; background:#5b2e91; border:2px solid #fff; box-shadow:0 0 0 2px #e2e8f0; }
.uig-event.ai::before       { background:#06b6d4; }
.uig-event.funnel::before   { background:#16a34a; }
.uig-event.senior::before   { background:#f59e0b; }
.uig-event.audit::before    { background:#64748b; }
.uig-event.created::before  { background:#dc2626; }
.uig-ev-title { font-weight:600; color:#1e293b; font-size:13px; }
.uig-ev-meta  { color:#64748b; font-size:11px; margin-top:3px; }
.uig-ev-date  { color:#94a3b8; font-size:10px; }

.uig-back { display:inline-flex; align-items:center; gap:6px; color:#5b2e91; font-size:13px; margin-bottom:16px; text-decoration:none; }
.uig-back:hover { text-decoration:underline; }

.uig-stats { display:grid; grid-template-columns:repeat(4, 1fr); gap:12px; margin-bottom:16px; }
@media(max-width:700px) { .uig-stats { grid-template-columns:repeat(2, 1fr); } }
.uig-stat { background:#f8fafc; border-radius:8px; padding:12px; text-align:center; }
.uig-stat-value { font-size:24px; font-weight:800; color:#5b2e91; }
.uig-stat-label { font-size:10px; color:#64748b; text-transform:uppercase; }
</style>

<div class="uig-wrap">
    <a href="{{ route('manager.user-intelligence') }}" class="uig-back">← User Intelligence'a dön</a>

    {{-- Lead başlık --}}
    <div class="uig-card">
        <h2>
            👤 {{ trim(($guest->first_name ?? '') . ' ' . ($guest->last_name ?? '')) ?: 'İsimsiz aday' }}
            @if ($guest->converted_to_student)
                <span class="uix-badge" style="background:#dcfce7; color:#166534; margin-left:8px;">✅ Müşteri oldu</span>
            @endif
        </h2>
        <div class="uig-header">
            <div>
                <div class="uig-meta-row"><span class="key">Email</span><span class="val">{{ $guest->email }}</span></div>
                <div class="uig-meta-row"><span class="key">Telefon</span><span class="val">{{ $guest->phone ?: '—' }}</span></div>
                <div class="uig-meta-row"><span class="key">Kayıt</span><span class="val">{{ $guest->created_at->format('d.m.Y H:i') }}</span></div>
            </div>
            <div>
                <div class="uig-meta-row">
                    <span class="key">Lead Skoru</span>
                    <span class="val" style="color:{{ ($guest->lead_score ?? 0) >= 75 ? '#dc2626' : (($guest->lead_score ?? 0) >= 50 ? '#f59e0b' : '#64748b') }}">
                        {{ $guest->lead_score ?? 0 }} / 100
                    </span>
                </div>
                <div class="uig-meta-row"><span class="key">Tier</span><span class="val">{{ $guest->lead_score_tier ?? '—' }}</span></div>
                <div class="uig-meta-row">
                    <span class="key">Senior</span>
                    <span class="val">{{ $guest->assigned_senior_email ?: '⚠️ Atanmamış' }}</span>
                </div>
            </div>
            <div>
                <div class="uig-meta-row"><span class="key">Source</span><span class="val">{{ $guest->source ?: '—' }}</span></div>
                <div class="uig-meta-row"><span class="key">UTM Source</span><span class="val">{{ $guest->utm_source ?: '—' }}</span></div>
                <div class="uig-meta-row"><span class="key">Son Aksiyon</span><span class="val">{{ $guest->last_senior_action_at ? \Carbon\Carbon::parse($guest->last_senior_action_at)->diffForHumans() : '—' }}</span></div>
            </div>
        </div>

        {{-- Özet stat'ler --}}
        @php
            $aiCount = collect($timeline)->where('type', 'ai_question')->count();
            $funnelCount = collect($timeline)->filter(fn($e) => str_starts_with($e['type'], 'funnel_'))->count();
            $seniorCount = collect($timeline)->where('type', 'senior_action')->count();
            $totalEvents = count($timeline);
        @endphp
        <div class="uig-stats">
            <div class="uig-stat">
                <div class="uig-stat-value">{{ $totalEvents }}</div>
                <div class="uig-stat-label">Toplam olay</div>
            </div>
            <div class="uig-stat">
                <div class="uig-stat-value">{{ $aiCount }}</div>
                <div class="uig-stat-label">AI sorusu</div>
            </div>
            <div class="uig-stat">
                <div class="uig-stat-value">{{ $funnelCount }}</div>
                <div class="uig-stat-label">Funnel adımı</div>
            </div>
            <div class="uig-stat">
                <div class="uig-stat-value">{{ $seniorCount }}</div>
                <div class="uig-stat-label">Senior aksiyonu</div>
            </div>
        </div>
    </div>

    {{-- Timeline --}}
    <div class="uig-card">
        <h2>📅 Aktivite Timeline</h2>
        <p style="font-size:12px; color:#64748b; margin:0 0 18px;">Kronolojik sırayla tüm olaylar (en yeni üstte).</p>

        @if (empty($timeline))
            <div style="text-align:center; color:#94a3b8; padding:40px;">Bu aday için aktivite kaydı yok.</div>
        @else
            <div class="uig-timeline">
                @foreach ($timeline as $event)
                    @php
                        $cls = match(true) {
                            str_starts_with($event['type'], 'ai_')          => 'ai',
                            str_starts_with($event['type'], 'funnel_')      => 'funnel',
                            str_starts_with($event['type'], 'senior_')      => 'senior',
                            str_starts_with($event['type'], 'audit_')       => 'audit',
                            $event['type'] === 'lead_created'               => 'created',
                            default                                         => '',
                        };
                    @endphp
                    <div class="uig-event {{ $cls }}">
                        <div class="uig-ev-title">{{ $event['icon'] }} {{ $event['title'] }}</div>
                        @if (!empty($event['meta']))
                            <div class="uig-ev-meta">{{ $event['meta'] }}</div>
                        @endif
                        <div class="uig-ev-date">
                            {{ \Carbon\Carbon::parse($event['at'])->format('d.m.Y H:i') }}
                            <span style="color:#cbd5e1;">·</span>
                            {{ \Carbon\Carbon::parse($event['at'])->diffForHumans() }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
