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

/* Period selector pills */
.uix-periods { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:18px; align-items:center; }
.uix-periods .lbl { font-size:12px; color:#64748b; margin-right:8px; }
.uix-period { padding:6px 14px; border:1px solid #e2e8f0; border-radius:20px; background:#fff; color:#475569; font-size:12px; font-weight:600; text-decoration:none; cursor:pointer; }
.uix-period:hover { background:#f8fafc; }
.uix-period.active { background:#5b2e91; color:#fff; border-color:#5b2e91; }
.uix-custom-range { display:inline-flex; gap:4px; align-items:center; font-size:11px; }
.uix-custom-range input { padding:5px 8px; border:1px solid #e2e8f0; border-radius:6px; font-size:11px; }
.uix-custom-range button { padding:5px 12px; background:#5b2e91; color:#fff; border:none; border-radius:6px; cursor:pointer; font-size:11px; }

/* Campaign impact */
.uix-campaign-form { display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap; background:#f8fafc; padding:14px; border-radius:8px; margin-bottom:14px; }
.uix-campaign-form label { display:flex; flex-direction:column; gap:4px; font-size:11px; color:#64748b; }
.uix-campaign-form input, .uix-campaign-form select { padding:6px 10px; border:1px solid #e2e8f0; border-radius:6px; font-size:12px; }
.uix-campaign-form button { padding:8px 16px; background:#5b2e91; color:#fff; border:none; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600; }
.uix-campaign-metrics { display:grid; grid-template-columns:repeat(4, 1fr); gap:10px; }
@media(max-width:700px) { .uix-campaign-metrics { grid-template-columns:repeat(2, 1fr); } }
.uix-campaign-metric { background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:12px; text-align:center; }
.uix-campaign-metric .lbl { font-size:10px; color:#64748b; text-transform:uppercase; }
.uix-campaign-metric .vals { display:flex; justify-content:center; gap:8px; align-items:baseline; margin:6px 0; font-size:14px; }
.uix-campaign-metric .vals .b { color:#64748b; }
.uix-campaign-metric .vals .arrow { color:#94a3b8; }
.uix-campaign-metric .vals .a { color:#1e293b; font-weight:700; font-size:18px; }
.uix-campaign-metric .delta { font-size:12px; font-weight:700; }
.uix-campaign-metric .delta.up   { color:#16a34a; }
.uix-campaign-metric .delta.down { color:#dc2626; }
.uix-campaign-metric .delta.flat { color:#64748b; }
</style>

<div class="uix-wrap">

    {{-- PERIOD SELECTOR --}}
    <div class="uix-periods">
        <span class="lbl">📅 Aralık:</span>
        @foreach ([7 => '1 Hafta', 15 => '15 Gün', 30 => '1 Ay', 90 => '3 Ay', 180 => '6 Ay', 365 => '1 Yıl'] as $d => $label)
            <a href="{{ route('manager.user-intelligence', array_filter(['days' => $d, 'event_date' => $campaign_event_date ?? null, 'window' => $campaign_event_date ? $campaign_window : null])) }}"
               class="uix-period {{ ($selected_days == $d && !$custom_range) ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
        <form method="GET" action="{{ route('manager.user-intelligence') }}" class="uix-custom-range" data-track-skip style="margin-left:12px;">
            <input type="date" name="from" value="{{ $custom_range['from'] ?? '' }}" aria-label="Başlangıç">
            <span>→</span>
            <input type="date" name="to" value="{{ $custom_range['to'] ?? '' }}" aria-label="Bitiş">
            @if ($campaign_event_date)
                <input type="hidden" name="event_date" value="{{ $campaign_event_date }}">
                <input type="hidden" name="window" value="{{ $campaign_window }}">
            @endif
            <button type="submit">Uygula</button>
        </form>
        @if ($custom_range)
            <span style="font-size:11px; color:#5b2e91; margin-left:8px;">
                🗓️ {{ $custom_range['from'] }} → {{ $custom_range['to'] }} ({{ $selected_days }} gün)
            </span>
        @endif
    </div>

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
        <h2>📈 Son {{ $selected_days }} Gün — Aktif Kullanıcı Trendi</h2>
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
                        <th style="width:100px;">⚡ Aksiyon</th>
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
                        <td style="font-size:11px;">
                            @php $phClean = $a['phone'] ? preg_replace('/[^0-9+]/', '', $a['phone']) : ''; @endphp
                            @if ($phClean)
                                <a href="tel:{{ $phClean }}" title="Ara" style="text-decoration:none; margin-right:4px;">📞</a>
                                <a href="https://wa.me/{{ ltrim($phClean, '+') }}" target="_blank" rel="noopener" title="WhatsApp" style="text-decoration:none; margin-right:4px;">💬</a>
                            @endif
                            @if ($a['email'])
                                <a href="mailto:{{ $a['email'] }}" title="Email" style="text-decoration:none;">📧</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Student Dormant Alarm --}}
    <div class="uix-card" style="border-left:4px solid #f59e0b;">
        <h2>⚠️ Öğrenci Risk Alarmı
            <span style="font-size:11px; font-weight:normal; color:#64748b; margin-left:8px;">
                14+ gün inaktif + (bekleyen ödeme VEYA yaklaşan randevu yok)
            </span>
        </h2>
        <p class="hint">
            Kaybetme riski yüksek öğrenciler — iletişime geç, destek sağla.
        </p>
        @if (empty($student_dormant))
            <div class="uix-empty">✅ Tüm öğrenciler aktif, risk yok.</div>
        @else
            <table class="uix-table">
                <thead>
                    <tr>
                        <th>Öğrenci</th>
                        <th style="width:80px;">Risk</th>
                        <th style="width:90px;">Durum</th>
                        <th style="width:100px;" title="Bekleyen ödeme sayısı">💰 Ödeme</th>
                        <th style="width:100px;" title="Yaklaşan randevu">📅 Randevu</th>
                        <th style="width:130px;">Son Aktivite</th>
                        <th style="width:100px;">⚡ Aksiyon</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($student_dormant as $s)
                    <tr>
                        <td>
                            <a href="{{ route('manager.user-intelligence.student', $s['id']) }}">{{ $s['name'] }} →</a>
                            <div style="font-size:10px; color:#64748b;">{{ $s['email'] }}</div>
                        </td>
                        <td>
                            @php
                                $riskCls = $s['risk_score'] >= 80 ? 'red' : ($s['risk_score'] >= 60 ? 'yellow' : 'gray');
                            @endphp
                            <span class="uix-badge {{ $riskCls }}">{{ $s['risk_score'] }}</span>
                        </td>
                        <td>
                            @php
                                $presCls2 = match($s['presence'] ?? null) {
                                    'online' => 'green', 'away' => 'yellow', 'busy' => 'red', default => 'gray',
                                };
                            @endphp
                            <span class="uix-badge {{ $presCls2 }}">{{ $s['presence'] ?? 'offline' }}</span>
                        </td>
                        <td>
                            @if ($s['overdue_payments'] > 0)
                                <span class="uix-badge red" title="Bekleyen ödeme">{{ $s['overdue_payments'] }} adet</span>
                            @else
                                <span class="uix-badge gray">yok</span>
                            @endif
                        </td>
                        <td>
                            @if ($s['has_upcoming_appt'])
                                <span class="uix-badge green">var</span>
                            @else
                                <span class="uix-badge red">yok</span>
                            @endif
                        </td>
                        <td style="font-size:10px; color:#dc2626; font-weight:600;">
                            {{ $s['days_since'] !== null ? $s['days_since'] . ' gün önce' : 'hiç' }}
                        </td>
                        <td style="font-size:13px;">
                            @if ($s['email'])
                                <a href="mailto:{{ $s['email'] }}" title="Email" style="text-decoration:none; margin-right:4px;">📧</a>
                            @endif
                            <a href="{{ route('manager.user-intelligence.student', $s['id']) }}" title="Detay + tüm aksiyonlar" style="text-decoration:none;">⚡</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Kampanya Etki Ölçümü --}}
    <div class="uix-card" style="border-left:4px solid #06b6d4;">
        <h2>📣 Kampanya / İçerik Etki Ölçümü
            <span style="font-size:11px; font-weight:normal; color:#64748b; margin-left:8px;">
                Belirli bir tarih öncesi vs sonrası aktivite delta'sı
            </span>
        </h2>
        <p class="hint">
            Yeni bir blog, duyuru, kampanya, reklam çıktı mı? Tarihini seç — öncesi/sonrası karşılaştırmasını gör.
        </p>
        <form method="GET" action="{{ route('manager.user-intelligence') }}" class="uix-campaign-form" data-track-skip>
            <label>
                <span>Yayın/Etkinlik Tarihi</span>
                <input type="date" name="event_date" value="{{ $campaign_event_date ?? '' }}" required>
            </label>
            <label>
                <span>Karşılaştırma Penceresi</span>
                <select name="window">
                    @foreach ([3 => '± 3 gün', 7 => '± 1 hafta', 14 => '± 2 hafta', 30 => '± 1 ay'] as $w => $wl)
                        <option value="{{ $w }}" {{ $campaign_window == $w ? 'selected' : '' }}>{{ $wl }}</option>
                    @endforeach
                </select>
            </label>
            <input type="hidden" name="days" value="{{ $selected_days }}">
            <button type="submit">Analiz Et</button>
        </form>

        @if ($campaign && empty($campaign['error']))
            <div style="font-size:12px; color:#64748b; margin:10px 0;">
                🗓️ Öncesi: <strong>{{ $campaign['period_before'] }}</strong>
                &nbsp;·&nbsp; Sonrası: <strong>{{ $campaign['period_after'] }}</strong>
            </div>
            <div class="uix-campaign-metrics">
                @foreach ([
                    'new_leads'      => ['🙋 Yeni Aday', 'yeni kayıt'],
                    'ai_queries'     => ['🤖 AI Soruları', 'sorulan'],
                    'bookings'       => ['📅 Booking', 'randevu'],
                    'student_active' => ['🎓 Aktif Öğrenci', 'etkileşim'],
                ] as $key => [$icon, $sub])
                    @php
                        $m = $campaign['metrics'][$key];
                        $delta = $m['delta_pct'];
                        $deltaCls = $delta > 5 ? 'up' : ($delta < -5 ? 'down' : 'flat');
                        $deltaArrow = $delta > 0 ? '↑' : ($delta < 0 ? '↓' : '→');
                    @endphp
                    <div class="uix-campaign-metric">
                        <div class="lbl">{{ $icon }}</div>
                        <div class="vals">
                            <span class="b">{{ $m['before'] }}</span>
                            <span class="arrow">→</span>
                            <span class="a">{{ $m['after'] }}</span>
                        </div>
                        <div class="delta {{ $deltaCls }}">
                            {{ $deltaArrow }} %{{ abs($delta) }}
                            <span style="font-size:10px; color:#94a3b8; font-weight:normal;">{{ $sub }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif ($campaign && !empty($campaign['error']))
            <div class="uix-empty" style="color:#dc2626;">⚠️ {{ $campaign['error'] }}</div>
        @endif
    </div>

    {{-- Top aktif kullanıcılar --}}
    <div class="uix-card">
        <h2>🔥 En Aktif Kullanıcılar (son {{ $selected_days }} gün)</h2>
        <p class="hint">
            Platform'da etkileşimde bulunan öğrenci ve adaylar — karma sıralı.
            Tıklayarak detaylı timeline'ı gör.
        </p>
        <div class="uix-periods" style="margin-top:6px;">
            <span class="lbl">🔽 Sırala:</span>
            @foreach ([
                'activity'      => '🔥 Aktivite Skoru',
                'lead_score'    => '⭐ Lead Skoru',
                'last_activity' => '⏱️ Son Aktivite',
                'questions'     => '❓ Soru Sayısı',
                'name'          => '🔤 İsim',
            ] as $sortKey => $sortLbl)
                <a href="{{ route('manager.user-intelligence', array_filter([
                    'days' => $selected_days,
                    'sort' => $sortKey,
                    'event_date' => $campaign_event_date ?? null,
                    'window' => $campaign_event_date ? $campaign_window : null,
                ])) }}#top-users"
                   class="uix-period {{ ($selected_sort ?? 'activity') === $sortKey ? 'active' : '' }}">{{ $sortLbl }}</a>
            @endforeach
        </div>
        <div id="top-users"></div>
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
                        <th style="width:70px;" title="Lead skoru">⭐ Lead</th>
                        <th style="width:60px;" title="Son {{ $selected_days }} günde sorulan AI soruları">❓ Soru</th>
                        <th style="width:70px;" title="Aktivite skoru">🔥 Akt.</th>
                        <th style="width:130px;">Son Aktivite</th>
                        <th style="width:90px;">⚡ Aksiyon</th>
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
                            @if ($u['lead_score'] !== null)
                                <span class="uix-badge" style="background:#fef3c7; color:#92400e;">{{ $u['lead_score'] }}</span>
                            @else
                                <span style="color:#cbd5e1;">—</span>
                            @endif
                        </td>
                        <td>
                            @if ($u['questions'] > 0)
                                <span class="uix-badge blue">{{ $u['questions'] }}</span>
                            @else
                                <span style="color:#cbd5e1;">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="uix-badge blue">{{ round($u['activity_score']) }}</span>
                        </td>
                        <td style="font-size:11px; color:#64748b;">
                            {{ $u['last_activity_at'] ? \Carbon\Carbon::parse($u['last_activity_at'])->diffForHumans() : '—' }}
                        </td>
                        <td style="font-size:13px;">
                            @if ($u['email'])
                                <a href="mailto:{{ $u['email'] }}" title="Email" style="text-decoration:none; margin-right:4px;">📧</a>
                            @endif
                            <a href="{{ $route }}" title="Detay + aksiyonlar" style="text-decoration:none;">⚡</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
