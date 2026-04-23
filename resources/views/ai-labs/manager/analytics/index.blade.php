@extends('manager.layouts.app')
@section('title', ($aiLabsName ?? 'AI Labs') . ' — Analytics')
@section('page_title','📊 ' . ($aiLabsName ?? 'AI Labs') . ' — Analytics')

@section('content')
<style>
.ala-wrap { max-width:1300px; margin:20px auto; padding:0 16px; }
.ala-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:22px; margin-bottom:18px; }
.ala-card h2 { margin:0 0 6px; font-size:16px; color:#0f172a; display:flex; align-items:center; gap:8px; }
.ala-card p.hint { margin:0 0 14px; font-size:12px; color:#64748b; }

.ala-period { display:inline-block; background:#faf7ff; color:#5b2e91; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; margin-bottom:16px; }

/* KPI grid */
.ala-kpis { display:grid; grid-template-columns:repeat(4, 1fr); gap:14px; margin-bottom:18px; }
@media(max-width:900px){ .ala-kpis { grid-template-columns:repeat(2, 1fr); } }
.ala-kpi { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:18px; text-align:center; }
.ala-kpi-value { font-size:28px; font-weight:800; color:#5b2e91; line-height:1.1; }
.ala-kpi-label { font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:.04em; margin-top:6px; }
.ala-kpi-sub { font-size:10px; color:#94a3b8; margin-top:2px; }

/* Bar chart (CSS) */
.ala-bars { display:flex; flex-direction:column; gap:10px; }
.ala-bar-row { display:grid; grid-template-columns:120px 1fr 80px; gap:10px; align-items:center; font-size:12px; }
.ala-bar-row .label { color:#334155; font-weight:600; }
.ala-bar-track { background:#f1f5f9; border-radius:6px; height:22px; position:relative; overflow:hidden; }
.ala-bar-fill { height:100%; border-radius:6px; transition:width .4s ease; }
.ala-bar-fill.source { background:linear-gradient(90deg, #86efac, #22c55e); }
.ala-bar-fill.external { background:linear-gradient(90deg, #fcd34d, #f59e0b); }
.ala-bar-fill.refused { background:linear-gradient(90deg, #cbd5e1, #64748b); }
.ala-bar-fill.role { background:linear-gradient(90deg, #a78bfa, #5b2e91); }
.ala-bar-fill.topic { background:linear-gradient(90deg, #fbbf24, #e8b931); }
.ala-bar-value { text-align:right; font-weight:700; color:#5b2e91; font-size:12px; }

/* Alert */
.ala-alert { border-radius:10px; padding:12px 16px; margin-bottom:10px; display:flex; gap:10px; align-items:flex-start; font-size:13px; }
.ala-alert.warning { background:#fef3c7; border:1px solid #fcd34d; color:#92400e; }
.ala-alert.info    { background:#dbeafe; border:1px solid #93c5fd; color:#1e40af; }
.ala-alert.danger  { background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; }
.ala-alert-icon { font-size:18px; line-height:1; }
.ala-alert-body strong { display:block; font-size:13px; margin-bottom:2px; }

/* Two-column grid */
.ala-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
@media(max-width:900px){ .ala-grid-2 { grid-template-columns:1fr; } }

/* Sources table */
.ala-table { width:100%; border-collapse:collapse; font-size:12px; }
.ala-table th { text-align:left; padding:8px 10px; background:#f8fafc; color:#64748b; font-weight:600; font-size:10px; text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid #e2e8f0; }
.ala-table td { padding:8px 10px; border-bottom:1px solid #f1f5f9; }
.ala-table td.nowrap { white-space:nowrap; }
.ala-badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:10px; font-weight:700; }
.ala-badge.green { background:#dcfce7; color:#166534; }
.ala-badge.gray  { background:#f1f5f9; color:#64748b; }
.ala-badge.blue  { background:#dbeafe; color:#1e40af; }

/* Trend sparkline (son 30 gün) */
.ala-trend { display:flex; align-items:flex-end; gap:2px; height:60px; padding:0 2px; margin-top:10px; }
.ala-trend-bar { flex:1; background:linear-gradient(to top, #a78bfa, #5b2e91); border-radius:2px 2px 0 0; min-height:2px; }
.ala-trend-bar[data-count="0"] { background:#e2e8f0; }

.ala-empty { text-align:center; padding:30px 20px; color:#94a3b8; font-size:13px; }
</style>

<div class="ala-wrap">
    <div class="ala-period">📅 {{ $period_label }}</div>

    {{-- Alerts --}}
    @foreach ($alerts as $a)
        <div class="ala-alert {{ $a['level'] }}">
            <span class="ala-alert-icon">{{ $a['icon'] }}</span>
            <div class="ala-alert-body">
                <strong>{{ $a['title'] }}</strong>
                <span>{{ $a['message'] }}</span>
            </div>
        </div>
    @endforeach

    {{-- KPI'lar --}}
    <div class="ala-kpis">
        <div class="ala-kpi">
            <div class="ala-kpi-value">{{ number_format($conversations['total_count']) }}</div>
            <div class="ala-kpi-label">Toplam Soru</div>
            <div class="ala-kpi-sub">bu ay</div>
        </div>
        <div class="ala-kpi">
            <div class="ala-kpi-value">{{ number_format($conversations['total_tokens'] / 1000, 1) }}<span style="font-size:14px;">K</span></div>
            <div class="ala-kpi-label">Token Kullanımı</div>
            <div class="ala-kpi-sub">{{ number_format($conversations['tokens_in']/1000, 1) }}K girdi · {{ number_format($conversations['tokens_out']/1000, 1) }}K çıktı</div>
        </div>
        <div class="ala-kpi">
            <div class="ala-kpi-value">€{{ number_format($conversations['cost_eur'], 2) }}</div>
            <div class="ala-kpi-label">Tahmini Maliyet</div>
            <div class="ala-kpi-sub">Gemini 2.5 Flash</div>
        </div>
        <div class="ala-kpi">
            <div class="ala-kpi-value">{{ $content_drafts['total'] }}</div>
            <div class="ala-kpi-label">Üretilen İçerik</div>
            <div class="ala-kpi-sub">draft/published</div>
        </div>
    </div>

    {{-- Response mode dağılım --}}
    <div class="ala-card">
        <h2>🎯 Yanıt Modu Dağılımı</h2>
        <p class="hint">AI'ın ne kadarının kaynaklardan yanıtlandığını görür. 🟢 source oranı ideal hedef: %60+. {{ $response_modes['total'] }} soru analiz edildi.</p>

        @if ($response_modes['total'] === 0)
            <div class="ala-empty">Bu ay henüz soru yok.</div>
        @else
            <div class="ala-bars">
                @foreach (['source' => ['🟢 Kaynaktan', 'source'], 'external' => ['🟡 Genel Bilgi', 'external'], 'refused' => ['⚪ Kapsam Dışı', 'refused']] as $key => [$label, $cls])
                    <div class="ala-bar-row">
                        <div class="label">{{ $label }}</div>
                        <div class="ala-bar-track">
                            <div class="ala-bar-fill {{ $cls }}" style="width: {{ $response_modes['percent'][$key] ?? 0 }}%;"></div>
                        </div>
                        <div class="ala-bar-value">%{{ $response_modes['percent'][$key] ?? 0 }} · {{ $response_modes['counts'][$key] ?? 0 }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Role dağılımı + Top konular (2 kolon) --}}
    <div class="ala-grid-2">
        <div class="ala-card">
            <h2>👥 Rol Bazlı Kullanım</h2>
            @php
                $roleLabels = ['guest' => '🙋 Aday', 'student' => '🎓 Öğrenci', 'senior' => '👨‍🏫 Senior', 'manager' => '👔 Yönetici', 'admin_staff' => '🏢 Admin'];
                $maxRoleCount = max(array_column($conversations['by_role'], 'count')) ?: 1;
            @endphp
            <div class="ala-bars">
                @foreach ($roleLabels as $role => $label)
                    @php $c = $conversations['by_role'][$role]['count'] ?? 0; @endphp
                    <div class="ala-bar-row">
                        <div class="label">{{ $label }}</div>
                        <div class="ala-bar-track">
                            <div class="ala-bar-fill role" style="width: {{ $maxRoleCount > 0 ? round($c / $maxRoleCount * 100, 1) : 0 }}%;"></div>
                        </div>
                        <div class="ala-bar-value">{{ $c }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="ala-card">
            <h2>🔥 En Çok Konuşulan Konular</h2>
            <p class="hint">Sorulardan çıkartılan kelime frekansı. Top 10.</p>
            @php $maxTopicCount = !empty($top_topics) ? max(array_column($top_topics, 'count')) : 1; @endphp
            @if (empty($top_topics))
                <div class="ala-empty">Henüz yeterli veri yok.</div>
            @else
                <div class="ala-bars">
                    @foreach ($top_topics as $t)
                        <div class="ala-bar-row">
                            <div class="label">{{ $t['word'] }}</div>
                            <div class="ala-bar-track">
                                <div class="ala-bar-fill topic" style="width: {{ round($t['count'] / $maxTopicCount * 100, 1) }}%;"></div>
                            </div>
                            <div class="ala-bar-value">{{ $t['count'] }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Günlük trend --}}
    <div class="ala-card">
        <h2>📈 Son 30 Gün</h2>
        <p class="hint">Günlük soru sayısı. Kısa barlar = o gün az soru.</p>
        <div class="ala-trend" title="Son 30 gün">
            @php $maxDay = max(array_values($daily_trend)) ?: 1; @endphp
            @foreach ($daily_trend as $day => $cnt)
                @php $h = $maxDay > 0 ? round($cnt / $maxDay * 100, 1) : 0; @endphp
                <div class="ala-trend-bar"
                     data-count="{{ $cnt }}"
                     style="height:{{ max(2, $h) }}%;"
                     title="{{ $day }}: {{ $cnt }} soru"></div>
            @endforeach
        </div>
        <div style="display:flex; justify-content:space-between; font-size:10px; color:#94a3b8; margin-top:6px;">
            <span>{{ array_key_first($daily_trend) }}</span>
            <span>Bugün</span>
        </div>
    </div>

    {{-- 🔥 HOT LEADS — AI kullanan adaylar, öncelik sırasıyla --}}
    @if (!empty($hot_leads))
    <div class="ala-card" style="border-left:4px solid #f59e0b;">
        <h2>🔥 Hot Leads
            <span style="font-size:11px; font-weight:normal; color:#64748b; margin-left:8px;">
                AI kullanan adaylar — hotness skoruna göre sıralı (son 30 gün)
            </span>
        </h2>
        <p class="hint">
            Soru sayısı + lead skoru + son aktivite + tier kombinasyonu.
            <strong>Öncelik listesi:</strong> bu adaylara hemen dönün.
        </p>
        <table class="ala-table">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Aday</th>
                    <th style="width:60px;" title="Hotness skoru">🔥</th>
                    <th style="width:80px;">Lead Score</th>
                    <th style="width:100px;">Tier</th>
                    <th style="width:60px;" title="Soru sayısı">Soru</th>
                    <th>Konuştuğu Konular</th>
                    <th style="width:100px;">Son Soru</th>
                    <th style="width:60px;">Durum</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($hot_leads as $i => $lead)
                @php
                    $tierColor = match($lead['tier']) {
                        'champion'    => '#7c3aed',
                        'sales_ready' => '#dc2626',
                        'hot'         => '#f59e0b',
                        'warm'        => '#eab308',
                        default       => '#94a3b8',
                    };
                    $hotBg = $lead['hotness'] >= 50 ? '#fef3c7' : ($lead['hotness'] >= 30 ? '#fef9c3' : '#f8fafc');
                @endphp
                <tr style="background:{{ $hotBg }};" class="hot-lead-row" data-lead-href="{{ route('manager.ai-labs.analytics.lead', $lead['lead_id']) }}">
                    <td style="font-weight:700; color:#64748b;">{{ $i + 1 }}</td>
                    <td>
                        <a href="{{ route('manager.ai-labs.analytics.lead', $lead['lead_id']) }}" style="text-decoration:none;">
                            <div style="font-weight:600; color:#5b2e91;">{{ $lead['full_name'] }} →</div>
                            <div style="font-size:10px; color:#64748b;">{{ $lead['email'] }}</div>
                        </a>
                    </td>
                    <td>
                        <span style="font-size:12px; font-weight:700; color:#f59e0b;">{{ $lead['hotness'] }}</span>
                    </td>
                    <td>
                        <div style="background:#f1f5f9; border-radius:4px; height:14px; width:70px; position:relative; overflow:hidden;">
                            <div style="background:linear-gradient(90deg,#22c55e,#eab308,#dc2626); height:100%; width:{{ min(100, $lead['lead_score']) }}%;"></div>
                        </div>
                        <span style="font-size:10px; color:#64748b;">{{ $lead['lead_score'] }}</span>
                    </td>
                    <td>
                        <span class="ala-badge" style="background:{{ $tierColor }}22; color:{{ $tierColor }}; font-weight:700;">
                            {{ $lead['tier'] ?? 'cold' }}
                        </span>
                    </td>
                    <td><span class="ala-badge blue">{{ $lead['question_count'] }}</span></td>
                    <td style="font-size:10px;">
                        @foreach ($lead['top_topics'] as $cat => $n)
                            <span class="ala-badge gray" style="font-size:9px; margin-right:2px;">{{ $cat }} {{ $n }}</span>
                        @endforeach
                        @if (empty($lead['top_topics']))
                            <span style="color:#94a3b8; font-size:10px;">—</span>
                        @endif
                    </td>
                    <td class="nowrap" style="color:#64748b; font-size:10px;">
                        {{ $lead['last_question_at'] ? \Carbon\Carbon::parse($lead['last_question_at'])->diffForHumans() : '—' }}
                    </td>
                    <td>
                        @if ($lead['converted'])
                            <span class="ala-badge green" title="Müşteri oldu">✅</span>
                        @elseif ($lead['assigned_senior'])
                            <span class="ala-badge blue" title="Senior atandı">👥</span>
                        @else
                            <span class="ala-badge gray" title="Atanmamış">⚠️</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- 🏷️ KONU KATEGORİLERİ — kullanıcılar hangi konularda en çok soru soruyor? --}}
    @if (!empty($topic_categories))
    <div class="ala-card">
        <h2>🏷️ Konu Kategorileri
            <span style="font-size:11px; font-weight:normal; color:#64748b; margin-left:8px;">
                Keyword eşleme — bu ay AI'ya sorulan konular
            </span>
        </h2>
        <p class="hint">
            Kategorilere göre soru dağılımı. Yoğun olan konular → daha fazla kaynak veya FAQ gerekebilir.
        </p>
        @php $maxCat = max($topic_categories) ?: 1; @endphp
        <div class="ala-bars">
            @foreach ($topic_categories as $cat => $count)
                <div class="ala-bar-row">
                    <div class="label">{{ ucfirst($cat) }}</div>
                    <div class="ala-bar-track">
                        <div class="ala-bar-fill topic" style="width:{{ round($count / $maxCat * 100, 1) }}%;"></div>
                    </div>
                    <div class="ala-bar-value">{{ $count }}</div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- 📊 CONVERTED vs LOST — hangi konular müşteriye dönüştürüyor? --}}
    @if (!empty($conversion_intents['insight']) && ($conversion_intents['converted_count'] > 0 || $conversion_intents['not_converted_count'] > 0))
    <div class="ala-card">
        <h2>📊 Converted vs Not-Converted — Konu Analizi
            <span style="font-size:11px; font-weight:normal; color:#64748b; margin-left:8px;">
                {{ $conversion_intents['converted_count'] }} müşteri vs {{ $conversion_intents['not_converted_count'] }} adayın soruları (son 180 gün)
            </span>
        </h2>
        <p class="hint">
            <strong>Pozitif sinyal (yeşil):</strong> bu konuyu soranların müşteri olma oranı daha yüksek.
            <strong>Negatif (kırmızı):</strong> bu konudaki sorular conversion ile ters korelasyonlu —
            belki zor/kaçış/objection sinyali.
        </p>
        <table class="ala-table">
            <thead>
                <tr>
                    <th>Konu</th>
                    <th>Müşteri (%)</th>
                    <th>Aday (%)</th>
                    <th>Sinyal</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($conversion_intents['insight'] as $cat => $stats)
                @php
                    $signalColor = $stats['signal'] > 5 ? '#16a34a' : ($stats['signal'] < -5 ? '#dc2626' : '#64748b');
                    $signalBg = $stats['signal'] > 5 ? '#dcfce7' : ($stats['signal'] < -5 ? '#fee2e2' : '#f1f5f9');
                @endphp
                <tr>
                    <td style="font-weight:600;">{{ ucfirst($cat) }}</td>
                    <td><span class="ala-badge green">{{ $stats['converted_pct'] }}%</span></td>
                    <td><span class="ala-badge gray">{{ $stats['not_converted_pct'] }}%</span></td>
                    <td>
                        <span class="ala-badge" style="background:{{ $signalBg }}; color:{{ $signalColor }}; font-weight:700;">
                            {{ $stats['signal'] > 0 ? '+' : '' }}{{ $stats['signal'] }}
                        </span>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- FAQ Adayları — 2+ kez sorulmuş sorular, cümle-seviyesi gruplandırma --}}
    @if (!empty($faq_candidates))
    <div class="ala-card">
        <h2>💡 FAQ Adayları
            <span style="font-size:11px; font-weight:normal; color:#64748b; margin-left:8px;">
                ({{ count($faq_candidates) }} aday — son 60 günde 2+ kez sorulmuş)
            </span>
        </h2>
        <p class="hint">
            Benzer sorular — ilk 6 kelimelik normalize ile gruplandı.
            Yüksek frekanslı olanları <a href="{{ route('manager.ai-labs.sources') }}" style="color:#5b2e91;">kaynaklara ekle</a> ya da
            <a href="{{ route('manager.ai-labs.analytics.faq-csv') }}" style="color:#5b2e91;">CSV indir</a>.
        </p>
        <table class="ala-table">
            <thead>
                <tr>
                    <th style="width:40px;">Sayı</th>
                    <th>Örnek Soru</th>
                    <th style="width:140px;">Kim Sordu</th>
                    <th class="nowrap">Son</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($faq_candidates as $c)
                <tr>
                    <td><span class="ala-badge blue" style="font-size:11px;">× {{ $c['count'] }}</span></td>
                    <td style="font-size:12px; color:#1e293b;">
                        {{ \Illuminate\Support\Str::limit($c['sample_question'], 120) }}
                    </td>
                    <td style="font-size:10px; color:#64748b;">
                        @foreach ($c['roles'] as $role => $n)
                            <span class="ala-badge gray" style="font-size:9px; margin-right:3px;">
                                {{ $roleLabels[$role] ?? $role }} {{ $n }}
                            </span>
                        @endforeach
                    </td>
                    <td class="nowrap" style="color:#94a3b8; font-size:10px;">
                        {{ $c['last_asked'] ? \Carbon\Carbon::parse($c['last_asked'])->diffForHumans() : '—' }}
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Kaynaklar: en çok citation alan + kullanılmayan --}}
    <div class="ala-grid-2">
        <div class="ala-card">
            <h2>📚 En Çok Kullanılan Kaynaklar</h2>
            @if (empty($top_cited_sources))
                <div class="ala-empty">Henüz citation yok.</div>
            @else
                <table class="ala-table">
                    <thead>
                        <tr>
                            <th>Kaynak</th>
                            <th>Tip</th>
                            <th class="nowrap">Citation</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($top_cited_sources as $s)
                        <tr>
                            <td>{{ \Illuminate\Support\Str::limit($s['title'], 50) }}</td>
                            <td><span class="ala-badge gray">{{ $s['type'] }}</span></td>
                            <td class="nowrap"><span class="ala-badge green">⭐ {{ $s['citation_count'] }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="ala-card">
            <h2>📦 Kullanılmayan Kaynaklar</h2>
            <p class="hint">Son 30 gündür citation almamış aktif kaynaklar. Pasifleştirmeyi veya güncellemeyi düşünün.</p>
            @if (empty($unused_sources))
                <div class="ala-empty">✅ Tüm aktif kaynaklar kullanılıyor.</div>
            @else
                <table class="ala-table">
                    <thead>
                        <tr>
                            <th>Kaynak</th>
                            <th>Tip</th>
                            <th class="nowrap">Son Kullanım</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($unused_sources as $s)
                        <tr>
                            <td>{{ \Illuminate\Support\Str::limit($s['title'], 50) }}</td>
                            <td><span class="ala-badge gray">{{ $s['type'] }}</span></td>
                            <td class="nowrap" style="color:#94a3b8; font-size:11px;">
                                {{ $s['last_used_at'] ? \Carbon\Carbon::parse($s['last_used_at'])->diffForHumans() : 'hiç' }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- Feedback (satisfaction) + Problem cevaplar --}}
    @if (($feedback['total'] ?? 0) > 0 || !empty($problem_answers))
        <div class="ala-grid-2">
            <div class="ala-card">
                <h2>😊 Kullanıcı Memnuniyeti</h2>
                <p class="hint">AI yanıtlarına verilen 👍 / 👎 oranı.</p>
                <div class="ala-kpi" style="text-align:left; padding:14px;">
                    <div class="ala-kpi-value" style="color:{{ $feedback['satisfaction'] >= 80 ? '#16a34a' : ($feedback['satisfaction'] >= 60 ? '#f59e0b' : '#dc2626') }}">
                        %{{ $feedback['satisfaction'] }}
                    </div>
                    <div class="ala-kpi-label">memnuniyet</div>
                    <div class="ala-kpi-sub">👍 {{ $feedback['good'] }} &nbsp;·&nbsp; 👎 {{ $feedback['bad'] }} &nbsp;·&nbsp; toplam {{ $feedback['total'] }} oy</div>
                </div>
            </div>

            <div class="ala-card">
                <h2>👎 Problem Cevaplar (son {{ count($problem_answers) }})</h2>
                <p class="hint">Kullanıcıların "yanlış" işaretlediği cevaplar — kaynak eksikliği veya prompt iyileştirme fırsatı.</p>
                @if (empty($problem_answers))
                    <div class="ala-empty">✅ Problem bildirilmedi.</div>
                @else
                    <div style="max-height:400px; overflow-y:auto;">
                    @foreach ($problem_answers as $p)
                        <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:10px 12px; margin-bottom:8px; font-size:12px;">
                            <div style="color:#991b1b; font-weight:700; margin-bottom:4px;">❓ {{ $p['question'] }}</div>
                            <div style="color:#64748b; line-height:1.5;">🤖 {{ $p['answer'] }}</div>
                            <div style="color:#94a3b8; font-size:10px; margin-top:6px;">
                                [{{ $p['role'] ?? '—' }}] · {{ $p['created_at'] }}
                                @if (!empty($p['reason']))
                                    · 💬 "{{ $p['reason'] }}"
                                @endif
                            </div>
                        </div>
                    @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Content draft istatistikleri --}}
    @if (!empty($content_drafts['by_template']))
        <div class="ala-card">
            <h2>✨ İçerik Üretici Kullanımı</h2>
            <p class="hint">Bu ay hangi template ne kadar kullanıldı.</p>

            @php $maxDraft = !empty($content_drafts['by_template']) ? max(array_column($content_drafts['by_template'], 'count')) : 1; @endphp
            <div class="ala-bars">
                @foreach ($content_drafts['by_template'] as $code => $stats)
                    @php $tpl = $templates[$code] ?? ['icon' => '📄', 'name' => $code]; @endphp
                    <div class="ala-bar-row">
                        <div class="label">{{ $tpl['icon'] }} {{ $tpl['name'] }}</div>
                        <div class="ala-bar-track">
                            <div class="ala-bar-fill topic" style="width: {{ round($stats['count'] / $maxDraft * 100, 1) }}%;"></div>
                        </div>
                        <div class="ala-bar-value">{{ $stats['count'] }} · {{ number_format($stats['tokens']/1000, 1) }}K tok</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
