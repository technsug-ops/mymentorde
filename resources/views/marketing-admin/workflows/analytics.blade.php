@extends('marketing-admin.layouts.app')

@section('title', 'Workflow Analytics')
@section('page_subtitle', '{{ $workflow->name }} — enrollment istatistikleri ve node akışı')

@section('topbar-actions')
<a href="/mktg-admin/workflows" class="btn alt" style="font-size:var(--tx-xs);padding:6px 12px;">← Listele</a>
<a href="/mktg-admin/workflows/{{ $workflow->id }}/builder" class="btn alt" style="font-size:var(--tx-xs);padding:6px 12px;">Builder</a>
<a href="/mktg-admin/workflows/{{ $workflow->id }}/enrollments" class="btn alt" style="font-size:var(--tx-xs);padding:6px 12px;">Enrollments</a>
<a href="/mktg-admin/workflows/{{ $workflow->id }}/analytics" class="btn" style="font-size:var(--tx-xs);padding:6px 12px;">Analytics</a>
@endsection

@section('content')
@php
$nodes = $workflow->nodes()->orderBy('sort_order')->get();

$nodeTypeMeta = [
    'send_email'        => ['icon' => '✉',  'color' => '#1e40af', 'label' => 'E-posta Gönder'],
    'send_notification' => ['icon' => '🔔', 'color' => '#7c3aed', 'label' => 'Bildirim Gönder'],
    'wait'              => ['icon' => '⏱',  'color' => '#64748b', 'label' => 'Bekle'],
    'wait_until'        => ['icon' => '⏳', 'color' => '#64748b', 'label' => 'Koşul Bekle'],
    'condition'         => ['icon' => '⚡',  'color' => '#d97706', 'label' => 'Koşul'],
    'add_score'         => ['icon' => '★',  'color' => '#0891b2', 'label' => 'Puan Ekle'],
    'create_task'       => ['icon' => '✔',  'color' => '#16a34a', 'label' => 'Task Oluştur'],
    'update_field'      => ['icon' => '✎',  'color' => '#64748b', 'label' => 'Alan Güncelle'],
    'move_to_segment'   => ['icon' => '▶',  'color' => '#1e40af', 'label' => 'Segmente Taşı'],
    'ab_split'          => ['icon' => '⇌',  'color' => '#7c3aed', 'label' => 'A/B Bölünme'],
    'goal_check'        => ['icon' => '◎',  'color' => '#16a34a', 'label' => 'Hedef Kontrol'],
    'exit'              => ['icon' => '⏹',  'color' => '#dc2626', 'label' => 'Çıkış'],
];

$statusBadge = [
    'draft'            => ['class' => '',       'label' => 'Taslak'],
    'pending_approval' => ['class' => 'warn',   'label' => 'Onay Bekliyor'],
    'active'           => ['class' => 'ok',     'label' => 'Aktif'],
    'paused'           => ['class' => 'pending','label' => 'Duraklatıldı'],
    'archived'         => ['class' => 'danger', 'label' => 'Arşivlendi'],
];

$sb = $statusBadge[$workflow->status] ?? ['class' => '', 'label' => $workflow->status];
$exited = $totalEnrollments - $completed - $active - $errored;
$completionBar = $totalEnrollments > 0 ? round($completed / $totalEnrollments * 100) : 0;
@endphp
<style>
.pl-stats { display:flex; gap:0; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; overflow:hidden; background:var(--u-card,#fff); }
.pl-stat  { flex:1; padding:12px 16px; border-right:1px solid var(--u-line,#e2e8f0); min-width:0; }
.pl-stat:last-child { border-right:none; }
.pl-val   { font-size:24px; font-weight:700; color:var(--u-brand,#1e40af); line-height:1.1; }
.pl-lbl   { font-size:11px; color:var(--u-muted,#64748b); margin-top:2px; }

.wf-flow  { display:flex; flex-direction:column; align-items:center; gap:0; }
.wf-node  { width:100%; max-width:380px; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; background:var(--u-card,#fff); padding:10px 14px; display:flex; align-items:center; gap:10px; position:relative; transition:box-shadow .15s; }
.wf-node:hover { box-shadow:0 2px 8px rgba(0,0,0,.10); }
.wf-node-icon { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:15px; flex-shrink:0; }
.wf-node-body { flex:1; min-width:0; }
.wf-node-title { font-size:13px; font-weight:700; color:var(--u-text,#0f172a); }
.wf-node-sub   { font-size:11px; color:var(--u-muted,#64748b); margin-top:1px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.wf-node-badge { font-size:10px; }
.wf-arrow { width:2px; height:22px; background:var(--u-line,#e2e8f0); margin:0 auto; position:relative; }
.wf-arrow::after { content:'▾'; position:absolute; bottom:-8px; left:50%; transform:translateX(-50%); color:var(--u-muted,#64748b); font-size:11px; }

.stat-bar-wrap { background:var(--u-line,#e2e8f0); border-radius:999px; height:8px; overflow:hidden; }
.stat-bar-fill { height:100%; border-radius:999px; transition:width .4s; }

details summary::-webkit-details-marker { display:none; }
details summary { outline:none; list-style:none; }
.det-sum { display:flex; justify-content:space-between; align-items:center; cursor:pointer; }
.det-sum h3 { margin:0; font-size:14px; font-weight:700; }
.det-chev { font-size:11px; color:var(--u-muted,#64748b); transition:transform .2s; }
details[open] .det-chev { transform:rotate(180deg); }
details[open] .det-sum { margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--u-line,#e2e8f0); }
</style>

<div style="display:grid;gap:12px;">

    {{-- KPI Bar --}}
    <div class="pl-stats">
        <div class="pl-stat">
            <div class="pl-val">{{ $totalEnrollments }}</div>
            <div class="pl-lbl">Toplam Enrollment</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val" style="color:var(--u-ok,#16a34a);">{{ $completed }}</div>
            <div class="pl-lbl">Tamamlandı</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val" style="color:#0891b2;">{{ $active }}</div>
            <div class="pl-lbl">Aktif / Bekliyor</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val" style="color:var(--u-danger,#dc2626);">{{ $errored }}</div>
            <div class="pl-lbl">Hatalı</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val" style="font-size:var(--tx-xl);">{{ $completionRate }}%</div>
            <div class="pl-lbl">Tamamlanma Oranı</div>
        </div>
    </div>

    {{-- Ana Grid: Sol info+stats, Sağ node flow --}}
    <div style="display:grid;grid-template-columns:1fr 400px;gap:12px;align-items:start;">

        {{-- Sol: Workflow bilgisi + enrollment breakdown --}}
        <div style="display:grid;gap:12px;">

            {{-- Workflow Bilgisi --}}
            <div class="card">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
                    <div>
                        <div style="font-weight:700;font-size:var(--tx-base);">{{ $workflow->name }}</div>
                        @if($workflow->description)
                        <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);margin-top:3px;">{{ $workflow->description }}</div>
                        @endif
                    </div>
                    <span class="badge {{ $sb['class'] }}">{{ $sb['label'] }}</span>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:var(--tx-sm);">
                    <div style="padding:8px 10px;background:var(--u-bg,#f8fafc);border-radius:8px;">
                        <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);margin-bottom:2px;">Tetikleyici</div>
                        <code style="font-size:var(--tx-xs);color:var(--u-brand,#1e40af);">{{ $workflow->trigger_type }}</code>
                    </div>
                    <div style="padding:8px 10px;background:var(--u-bg,#f8fafc);border-radius:8px;">
                        <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);margin-bottom:2px;">Node Sayısı</div>
                        <strong>{{ $nodes->count() }} adım</strong>
                    </div>
                    <div style="padding:8px 10px;background:var(--u-bg,#f8fafc);border-radius:8px;">
                        <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);margin-bottom:2px;">Tekrarlayan</div>
                        <span>{{ $workflow->is_recurring ? 'Evet' : 'Hayır' }}</span>
                    </div>
                    <div style="padding:8px 10px;background:var(--u-bg,#f8fafc);border-radius:8px;">
                        <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);margin-bottom:2px;">Enrollment Limiti</div>
                        <span>{{ $workflow->enrollment_limit ?? 'Sınırsız' }}</span>
                    </div>
                    @if($workflow->approved_at)
                    <div style="padding:8px 10px;background:var(--u-bg,#f8fafc);border-radius:8px;">
                        <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);margin-bottom:2px;">Onay Tarihi</div>
                        <span>{{ $workflow->approved_at->format('d.m.Y H:i') }}</span>
                    </div>
                    @endif
                    <div style="padding:8px 10px;background:var(--u-bg,#f8fafc);border-radius:8px;">
                        <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);margin-bottom:2px;">Oluşturulma</div>
                        <span>{{ $workflow->created_at->format('d.m.Y H:i') }}</span>
                    </div>
                </div>
            </div>

            {{-- Enrollment Breakdown --}}
            <div class="card">
                <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
                    Enrollment Dağılımı
                </div>
                @php
                $breakdown = [
                    ['label' => 'Tamamlandı',     'val' => $completed,        'color' => 'var(--u-ok,#16a34a)'],
                    ['label' => 'Aktif / Bekliyor','val' => $active,           'color' => '#0891b2'],
                    ['label' => 'Hatalı',          'val' => $errored,          'color' => 'var(--u-danger,#dc2626)'],
                    ['label' => 'Diğer / Çıktı',  'val' => max(0,$exited),    'color' => 'var(--u-muted,#94a3b8)'],
                ];
                @endphp
                <div style="display:grid;gap:10px;">
                    @foreach($breakdown as $b)
                    @php $pct = $totalEnrollments > 0 ? round($b['val'] / $totalEnrollments * 100) : 0; @endphp
                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;font-size:var(--tx-sm);">
                            <span>{{ $b['label'] }}</span>
                            <span style="font-weight:700;color:{{ $b['color'] }};">{{ $b['val'] }} <span style="font-weight:400;font-size:var(--tx-xs);color:var(--u-muted,#64748b);">({{ $pct }}%)</span></span>
                        </div>
                        <div class="stat-bar-wrap">
                            <div class="stat-bar-fill" style="width:{{ $pct }}%;background:{{ $b['color'] }};"></div>
                        </div>
                    </div>
                    @endforeach
                </div>

                @if($totalEnrollments === 0)
                <div style="margin-top:14px;padding:14px;background:color-mix(in srgb,var(--u-warn,#d97706) 8%,var(--u-card,#fff));border:1px solid color-mix(in srgb,var(--u-warn,#d97706) 25%,var(--u-card,#fff));border-radius:8px;font-size:var(--tx-sm);color:var(--u-warn,#d97706);">
                    Bu workflow henüz hiç enrollment almadı. Workflow aktif olduğunda burada veriler görünecek.
                </div>
                @endif

                {{-- Tamamlanma oranı göstergesi --}}
                <div style="margin-top:16px;padding-top:12px;border-top:1px solid var(--u-line,#e2e8f0);">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                        <span style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted,#64748b);">Tamamlanma Oranı</span>
                        <span style="font-size:var(--tx-lg);font-weight:700;color:{{ $completionRate >= 70 ? 'var(--u-ok,#16a34a)' : ($completionRate >= 40 ? 'var(--u-warn,#d97706)' : 'var(--u-muted,#64748b)') }};">{{ $completionRate }}%</span>
                    </div>
                    <div class="stat-bar-wrap" style="height:10px;">
                        <div class="stat-bar-fill" style="width:{{ $completionBar }}%;background:{{ $completionRate >= 70 ? 'var(--u-ok,#16a34a)' : ($completionRate >= 40 ? 'var(--u-warn,#d97706)' : 'var(--u-muted,#94a3b8)') }};"></div>
                    </div>
                </div>
            </div>

            {{-- Hızlı Aksiyonlar --}}
            <div class="card">
                <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
                    Hızlı Aksiyonlar
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <a class="btn" href="/mktg-admin/workflows/{{ $workflow->id }}/builder" style="font-size:var(--tx-xs);padding:7px 14px;">Node Builder</a>
                    <a class="btn alt" href="/mktg-admin/workflows/{{ $workflow->id }}/enrollments" style="font-size:var(--tx-xs);padding:7px 14px;">Enrollment Listesi</a>
                    @if($workflow->status === 'active')
                    <form method="POST" action="/mktg-admin/workflows/{{ $workflow->id }}/pause" style="display:inline;">
                        @csrf @method('PUT')
                        <button type="submit" class="btn alt" style="font-size:var(--tx-xs);padding:7px 14px;">Duraklat</button>
                    </form>
                    @elseif(in_array($workflow->status, ['draft','paused','pending_approval']))
                    <form method="POST" action="/mktg-admin/workflows/{{ $workflow->id }}/activate" style="display:inline;">
                        @csrf @method('PUT')
                        <button type="submit" class="btn ok" style="font-size:var(--tx-xs);padding:7px 14px;">Aktif Et</button>
                    </form>
                    @endif
                </div>
            </div>

        </div>

        {{-- Sağ: Node Flow Görselleştirmesi --}}
        <div class="card">
            <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
                Workflow Akışı
                <span style="font-weight:400;font-size:var(--tx-xs);margin-left:6px;">{{ $nodes->count() }} adım</span>
            </div>

            {{-- Trigger --}}
            <div style="display:flex;justify-content:center;margin-bottom:0;">
                <div style="background:color-mix(in srgb,var(--u-brand,#1e40af) 10%,var(--u-card,#fff));border:2px dashed var(--u-brand,#1e40af);border-radius:10px;padding:8px 16px;font-size:var(--tx-xs);font-weight:700;color:var(--u-brand,#1e40af);text-align:center;">
                    ⚡ Trigger: <code style="font-size:var(--tx-xs);">{{ $workflow->trigger_type }}</code>
                </div>
            </div>

            <div class="wf-flow" style="margin-top:0;padding-top:0;">
                @php $prevWasCondition = false; @endphp
                @forelse($nodes as $i => $node)
                @php
                    $meta   = $nodeTypeMeta[$node->node_type] ?? ['icon'=>'●','color'=>'#64748b','label'=>ucfirst($node->node_type)];
                    $cfg    = $node->node_config ?? [];
                    $lbl    = $cfg['label'] ?? ($meta['label']);
                    $sub    = '';
                    if ($node->node_type === 'wait')              $sub = ($cfg['duration'] ?? '?') . ' ' . ($cfg['unit'] ?? 'gün');
                    elseif ($node->node_type === 'send_email')    $sub = $cfg['subject_tr'] ?? $cfg['template_key'] ?? '';
                    elseif ($node->node_type === 'send_notification') $sub = ($cfg['channel'] ?? 'inApp') . ': ' . \Illuminate\Support\Str::limit($cfg['message'] ?? '', 36);
                    elseif ($node->node_type === 'condition')     $sub = ($cfg['field'] ?? '') . ' ' . ($cfg['operator'] ?? '') . ' ' . ($cfg['value'] ?? '');
                    elseif ($node->node_type === 'add_score')     $sub = 'Puan: +' . ($cfg['score'] ?? 0);
                    elseif ($node->node_type === 'create_task')   $sub = \Illuminate\Support\Str::limit($cfg['title'] ?? '', 36);
                    $isLast = $i === $nodes->count() - 1;
                @endphp
                {{-- Connector arrow --}}
                <div class="wf-arrow"></div>
                {{-- Node --}}
                <div class="wf-node" style="border-left:3px solid {{ $meta['color'] }};">
                    <div class="wf-node-icon" style="background:color-mix(in srgb,{{ $meta['color'] }} 12%,var(--u-card,#fff));color:{{ $meta['color'] }};">
                        {{ $meta['icon'] }}
                    </div>
                    <div class="wf-node-body">
                        <div class="wf-node-title">{{ $lbl }}</div>
                        @if($sub)
                        <div class="wf-node-sub" title="{{ $sub }}">{{ \Illuminate\Support\Str::limit($sub, 48) }}</div>
                        @endif
                    </div>
                    <span class="badge wf-node-badge" style="font-size:var(--tx-xs);background:color-mix(in srgb,{{ $meta['color'] }} 10%,var(--u-card,#fff));color:{{ $meta['color'] }};border:none;">{{ $i+1 }}</span>
                </div>
                {{-- Condition yol ayrımı göstergesi --}}
                @if($node->node_type === 'condition')
                <div style="display:flex;gap:20px;justify-content:center;padding:4px 0;">
                    <span style="font-size:var(--tx-xs);color:var(--u-ok,#16a34a);font-weight:700;">✓ Evet</span>
                    <span style="font-size:var(--tx-xs);color:var(--u-danger,#dc2626);font-weight:700;">✗ Hayır</span>
                </div>
                @endif
                @empty
                <div style="text-align:center;padding:24px;color:var(--u-muted,#64748b);font-size:var(--tx-sm);">
                    Bu workflow henüz node içermiyor.<br>
                    <a href="/mktg-admin/workflows/{{ $workflow->id }}/builder" class="btn" style="font-size:var(--tx-xs);padding:6px 14px;margin-top:8px;display:inline-block;">Builder'a Git</a>
                </div>
                @endforelse
            </div>

            @if($nodes->isNotEmpty())
            <div style="margin-top:12px;padding-top:10px;border-top:1px solid var(--u-line,#e2e8f0);font-size:var(--tx-xs);color:var(--u-muted,#64748b);text-align:center;">
                <a href="/mktg-admin/workflows/{{ $workflow->id }}/builder" style="color:var(--u-brand,#1e40af);text-decoration:none;font-weight:600;">Detaylı builder için tıkla →</a>
            </div>
            @endif
        </div>

    </div>


<details class="card" style="margin-top:0;">
    <summary class="det-sum">
        <h3>📖 Kullanım Kılavuzu — Workflow Analitiği</h3>
        <span class="det-chev">▼</span>
    </summary>
    <div style="padding-top:12px;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div>
            <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">📊 Metrikler</strong>
            <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                <li><strong>Enrolled:</strong> Workflow'a giren toplam lead sayısı</li>
                <li><strong>Completed:</strong> Tüm adımları tamamlayan lead oranı</li>
                <li><strong>Exited:</strong> Orta noktada ayrılan leadler — neden ayrıldığını analiz et</li>
                <li><strong>Errored:</strong> Teknik hata ile durdurulan — log kontrolü gerekir</li>
            </ul>
        </div>
        <div>
            <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">⚡ Optimizasyon</strong>
            <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                <li>Tamamlanma oranı düşükse hangi node'da kayıp yaşandığını bul</li>
                <li>Node bazlı metrikleri Builder sayfasında görüntüle</li>
                <li>En iyi performanslı workflow'u şablon olarak kopyala</li>
                <li>Errored kayıtları → sistem log'unu kontrol et, gerekirse re-enroll</li>
            </ul>
        </div>
    </div>
</details>

</div>
@endsection
