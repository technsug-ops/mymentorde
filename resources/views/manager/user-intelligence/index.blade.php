@extends('manager.layouts.app')
@section('title', 'Kullanıcı Aktivite İstihbaratı')
@section('page_title', '👥 Kullanıcı Aktivite İstihbaratı')

@section('content')
<style>
.uix-wrap { max-width:1300px; margin:20px auto; padding:0 16px; }
.uix-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:22px; margin-bottom:18px; }
.uix-card h2 { margin:0 0 6px; font-size:16px; color:#0f172a; display:flex; align-items:center; gap:8px; }
.uix-card p.hint { margin:0 0 14px; font-size:12px; color:#64748b; }

.uix-kpis { display:grid; grid-template-columns:repeat(4, 1fr); gap:14px; margin-bottom:18px; }
@media(max-width:900px){ .uix-kpis { grid-template-columns:repeat(2, 1fr); } }
.uix-kpi { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:18px; }
.uix-kpi-title { font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:.04em; margin-bottom:10px; }
.uix-kpi-big { font-size:28px; font-weight:800; color:#1e293b; line-height:1.1; }
.uix-kpi-sub { font-size:11px; color:#64748b; margin-top:4px; }

.uix-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
@media(max-width:900px){ .uix-grid-2 { grid-template-columns:1fr; } }

.uix-tiers { display:flex; gap:8px; margin-top:10px; }
.uix-tier { flex:1; background:#f8fafc; border-radius:8px; padding:10px; text-align:center; }
.uix-tier.active  { background:#dcfce7; color:#166534; }
.uix-tier.at-risk { background:#fef3c7; color:#92400e; }
.uix-tier.dormant { background:#fee2e2; color:#991b1b; }
.uix-tier-value { font-size:20px; font-weight:800; }
.uix-tier-label { font-size:10px; text-transform:uppercase; }

.uix-table { width:100%; border-collapse:collapse; font-size:12px; }
.uix-table th { text-align:left; padding:8px 10px; background:#f8fafc; color:#64748b; font-weight:600; font-size:10px; text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid #e2e8f0; }
.uix-table td { padding:8px 10px; border-bottom:1px solid #f1f5f9; }
.uix-table a { color:#5b2e91; text-decoration:none; font-weight:600; }
.uix-table a:hover { text-decoration:underline; }
.uix-badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:10px; font-weight:700; }
.uix-badge.green  { background:#dcfce7; color:#166534; }
.uix-badge.yellow { background:#fef3c7; color:#92400e; }
.uix-badge.red    { background:#fee2e2; color:#991b1b; }
.uix-badge.blue   { background:#dbeafe; color:#1e40af; }
.uix-badge.purple { background:#e9d5ff; color:#6b21a8; }
.uix-badge.gray   { background:#f1f5f9; color:#64748b; }

.uix-trend { display:flex; align-items:flex-end; gap:2px; height:60px; padding:0 2px; margin-top:10px; }
.uix-trend-bar { flex:1; background:linear-gradient(to top, #60a5fa, #2563eb); border-radius:2px 2px 0 0; min-height:2px; }
.uix-trend-bar[data-count="0"] { background:#e2e8f0; }

.uix-empty { text-align:center; color:#94a3b8; padding:40px 20px; font-size:13px; }
</style>

<div class="uix-wrap">

    {{-- Top KPI kartları --}}
    <div class="uix-kpis">
        <div class="uix-kpi">
            <div class="uix-kpi-title">🎓 Toplam Öğrenci</div>
            <div class="uix-kpi-big">{{ $overview['students']['total'] }}</div>
            <div class="uix-kpi-sub">{{ $overview['students']['active_7'] }} aktif (7 gün) — %{{ $overview['students']['active_pct'] }}</div>
        </div>
        <div class="uix-kpi">
            <div class="uix-kpi-title">🙋 Toplam Aday</div>
            <div class="uix-kpi-big">{{ $overview['guests']['total'] }}</div>
            <div class="uix-kpi-sub">{{ $overview['guests']['active_7'] }} aktif (7 gün) — %{{ $overview['guests']['active_pct'] }}</div>
        </div>
        <div class="uix-kpi">
            <div class="uix-kpi-title">⚠️ Dormant Öğrenci</div>
            <div class="uix-kpi-big" style="color:#dc2626;">{{ $overview['students']['dormant'] }}</div>
            <div class="uix-kpi-sub">30+ gündür aktivite yok</div>
        </div>
        <div class="uix-kpi">
            <div class="uix-kpi-title">⚠️ Dormant Aday</div>
            <div class="uix-kpi-big" style="color:#dc2626;">{{ $overview['guests']['dormant'] }}</div>
            <div class="uix-kpi-sub">30+ gündür aktivite yok</div>
        </div>
    </div>

    {{-- Engagement tiers --}}
    <div class="uix-grid-2">
        <div class="uix-card">
            <h2>🎓 Öğrenci Engagement</h2>
            <p class="hint">Son aktivite zamanına göre segmentasyon.</p>
            <div class="uix-tiers">
                <div class="uix-tier active">
                    <div class="uix-tier-value">{{ $tiers['students']['active'] }}</div>
                    <div class="uix-tier-label">aktif (&lt;7 gün)</div>
                </div>
                <div class="uix-tier at-risk">
                    <div class="uix-tier-value">{{ $tiers['students']['at_risk'] }}</div>
                    <div class="uix-tier-label">risk altında (7-30 gün)</div>
                </div>
                <div class="uix-tier dormant">
                    <div class="uix-tier-value">{{ $tiers['students']['dormant'] }}</div>
                    <div class="uix-tier-label">dormant (30+ gün)</div>
                </div>
            </div>
        </div>
        <div class="uix-card">
            <h2>🙋 Aday Engagement</h2>
            <p class="hint">AI soru + senior aksiyon + form aktivitelerine göre.</p>
            <div class="uix-tiers">
                <div class="uix-tier active">
                    <div class="uix-tier-value">{{ $tiers['guests']['active'] }}</div>
                    <div class="uix-tier-label">aktif</div>
                </div>
                <div class="uix-tier at-risk">
                    <div class="uix-tier-value">{{ $tiers['guests']['at_risk'] }}</div>
                    <div class="uix-tier-label">risk altında</div>
                </div>
                <div class="uix-tier dormant">
                    <div class="uix-tier-value">{{ $tiers['guests']['dormant'] }}</div>
                    <div class="uix-tier-label">dormant</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Günlük aktivite trendi --}}
    <div class="uix-card">
        <h2>📈 Son 30 Gün — Aktif Kullanıcı Trendi</h2>
        <p class="hint">Her gün kaç farklı öğrenci/aday aktiviteye katıldı.</p>
        @php $maxDay = max(array_column($daily_trend, 'total')) ?: 1; @endphp
        <div class="uix-trend">
            @foreach ($daily_trend as $day)
                @php $h = $maxDay > 0 ? round($day['total'] / $maxDay * 100, 1) : 0; @endphp
                <div class="uix-trend-bar"
                     data-count="{{ $day['total'] }}"
                     style="height:{{ max(2, $h) }}%;"
                     title="{{ $day['date'] }}: {{ $day['total'] }} kullanıcı ({{ $day['student'] }} öğrenci, {{ $day['guest'] }} aday)"></div>
            @endforeach
        </div>
        <div style="display:flex; justify-content:space-between; font-size:10px; color:#94a3b8; margin-top:6px;">
            <span>{{ $daily_trend[0]['date'] ?? '' }}</span>
            <span>Bugün</span>
        </div>
    </div>

    {{-- Dormant Alarm --}}
    <div class="uix-card" style="border-left:4px solid #dc2626;">
        <h2>🚨 Yüksek Skor + Dormant Alarmı
            <span style="font-size:11px; font-weight:normal; color:#64748b; margin-left:8px;">
                Lead score 40+ ama 14+ gündür aksiyon yok
            </span>
        </h2>
        <p class="hint">
            Bu adaylara senior <strong>hemen</strong> dönmeli — yüksek potansiyel kaybediyorsun.
        </p>
        @if (empty($dormant_alerts))
            <div class="uix-empty">✅ Tüm yüksek-skor adaylarla aktif iletişim var.</div>
        @else
            <table class="uix-table">
                <thead>
                    <tr>
                        <th>Aday</th>
                        <th style="width:90px;">Lead Score</th>
                        <th style="width:90px;">Tier</th>
                        <th>Senior</th>
                        <th style="width:120px;">Son Aksiyon</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($dormant_alerts as $a)
                    <tr>
                        <td>
                            <a href="{{ route('manager.user-intelligence.guest', $a['id']) }}">{{ $a['name'] }} →</a>
                            <div style="font-size:10px; color:#64748b;">{{ $a['email'] }}</div>
                        </td>
                        <td>
                            <span class="uix-badge" style="background:#fef3c7; color:#92400e;">{{ $a['lead_score'] }}</span>
                        </td>
                        <td><span class="uix-badge yellow">{{ $a['tier'] ?? '—' }}</span></td>
                        <td style="font-size:11px; color:#64748b;">{{ $a['assigned_senior'] ?: '⚠️ Atanmamış' }}</td>
                        <td style="font-size:10px; color:#dc2626; font-weight:600;">
                            {{ $a['days_since_action'] !== null ? $a['days_since_action'] . ' gün önce' : 'hiç' }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Top aktif kullanıcılar --}}
    <div class="uix-card">
        <h2>🔥 En Aktif Kullanıcılar (son 30 gün)</h2>
        <p class="hint">
            Platform'da en çok etkileşimde bulunan öğrenci ve adaylar — karma sıralı.
            Tıklayarak detaylı timeline'ı gör.
        </p>
        @if (empty($top_users))
            <div class="uix-empty">Aktif kullanıcı yok.</div>
        @else
            <table class="uix-table">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th style="width:80px;">Tip</th>
                        <th>Ad</th>
                        <th style="width:80px;">Durum</th>
                        <th style="width:80px;" title="Aktivite skoru">Skor</th>
                        <th style="width:130px;">Son Aktivite</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($top_users as $i => $u)
                    @php
                        $route = $u['type'] === 'student'
                            ? route('manager.user-intelligence.student', $u['id'])
                            : route('manager.user-intelligence.guest', $u['id']);
                    @endphp
                    <tr>
                        <td style="font-weight:700; color:#64748b;">{{ $i + 1 }}</td>
                        <td>
                            @if ($u['type'] === 'student')
                                <span class="uix-badge purple">🎓 Öğrenci</span>
                            @else
                                <span class="uix-badge blue">🙋 Aday</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ $route }}">{{ $u['name'] }} →</a>
                            <div style="font-size:10px; color:#64748b;">{{ $u['email'] }}</div>
                        </td>
                        <td>
                            @if ($u['type'] === 'student')
                                @php
                                    $presCls = match($u['presence'] ?? null) {
                                        'online' => 'green',
                                        'away'   => 'yellow',
                                        'busy'   => 'red',
                                        default  => 'gray',
                                    };
                                @endphp
                                <span class="uix-badge {{ $presCls }}">{{ $u['presence'] ?? 'offline' }}</span>
                            @else
                                <span class="uix-badge gray">{{ $u['tier'] ?? 'cold' }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="uix-badge blue">{{ round($u['activity_score']) }}</span>
                        </td>
                        <td style="font-size:11px; color:#64748b;">
                            {{ $u['last_activity_at'] ? \Carbon\Carbon::parse($u['last_activity_at'])->diffForHumans() : '—' }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
