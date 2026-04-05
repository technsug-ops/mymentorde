@extends('marketing-admin.layouts.app')

@section('topbar-actions')
<a class="btn alt" href="/mktg-admin/workflows" style="font-size:var(--tx-xs);padding:6px 12px;">← Listele</a>
<span style="font-size:var(--tx-sm);font-weight:700;padding:6px 4px;">{{ $workflow->name }}</span>
<span class="badge info" style="font-size:var(--tx-xs);align-self:center;">{{ $workflow->trigger_type }}</span>
<a class="btn alt" href="/mktg-admin/workflows/{{ $workflow->id }}/enrollments" style="font-size:var(--tx-xs);padding:6px 12px;">Enrollments</a>
<a class="btn alt" href="/mktg-admin/workflows/{{ $workflow->id }}/analytics" style="font-size:var(--tx-xs);padding:6px 12px;">Analytics</a>
@if($workflow->status !== 'active')
<form method="POST" action="/mktg-admin/workflows/{{ $workflow->id }}/activate" style="display:inline;">
    @csrf @method('PUT')
    <button class="btn ok" style="font-size:var(--tx-xs);padding:6px 12px;">▶ Aktifleştir</button>
</form>
@else
<form method="POST" action="/mktg-admin/workflows/{{ $workflow->id }}/pause" style="display:inline;">
    @csrf @method('PUT')
    <button class="btn warn" style="font-size:var(--tx-xs);padding:6px 12px;">⏸ Durdur</button>
</form>
@endif
@endsection

@section('title', 'Workflow Builder — ' . $workflow->name)
@section('page_subtitle', 'Node\'lara tıklayarak içerik düzenleyebilir, yeni node ekleyebilirsin')

@section('content')

@php
// Her node'u JS'e aktar
$nodesData = $nodes->map(fn($n) => [
    'id'         => $n->id,
    'type'       => $n->node_type,
    'config'     => $n->node_config ?? [],
    'sort_order' => $n->sort_order,
])->values()->toArray();

$nodeTypeMeta = [
    'send_email'        => ['icon'=>'✉️',  'color'=>'#1e40af', 'label'=>'Email Gönder'],
    'send_notification' => ['icon'=>'🔔',  'color'=>'#d97706', 'label'=>'Bildirim Gönder'],
    'wait'              => ['icon'=>'⏳',  'color'=>'#64748b', 'label'=>'Bekle'],
    'wait_until'        => ['icon'=>'⏱',  'color'=>'#64748b', 'label'=>'Koşul Bekle'],
    'condition'         => ['icon'=>'❓',  'color'=>'#7c3aed', 'label'=>'Koşul (If/Else)'],
    'add_score'         => ['icon'=>'📈',  'color'=>'#16a34a', 'label'=>'Puan Ekle'],
    'create_task'       => ['icon'=>'📋',  'color'=>'#0891b2', 'label'=>'Task Oluştur'],
    'update_field'      => ['icon'=>'✏️',  'color'=>'#0891b2', 'label'=>'Alan Güncelle'],
    'move_to_segment'   => ['icon'=>'📂',  'color'=>'#0891b2', 'label'=>'Segmente Taşı'],
    'ab_split'          => ['icon'=>'🔀',  'color'=>'#9333ea', 'label'=>'A/B Bölünme'],
    'goal_check'        => ['icon'=>'🏁',  'color'=>'#16a34a', 'label'=>'Hedef Kontrol'],
    'exit'              => ['icon'=>'🚪',  'color'=>'#dc2626', 'label'=>'Çıkış'],
];
@endphp

<style>
/* ── Layout ── */
.bld-wrap  { display:grid; grid-template-columns:1fr 360px; gap:12px; align-items:start; }

/* ── Flow Diyagram ── */
.wf-flow   { display:flex; flex-direction:column; align-items:center; padding:12px 0; }
.wf-trigger { display:inline-block; padding:9px 20px; background:var(--u-brand,#1e40af); color:#fff;
              border-radius:8px; font-weight:700; font-size:13px; cursor:default; }
.wf-arrow  { width:2px; height:20px; background:var(--u-line,#e2e8f0); position:relative; margin:0 auto; }
.wf-arrow::after { content:'▼'; position:absolute; bottom:-10px; left:50%; transform:translateX(-50%);
                   font-size:10px; color:var(--u-muted,#64748b); line-height:1; }
.wf-node   { width:300px; border:1px solid var(--u-line,#e2e8f0); border-radius:10px;
             background:var(--u-card,#fff); cursor:pointer; transition:all .15s;
             padding:10px 14px; display:flex; align-items:flex-start; gap:10px; }
.wf-node:hover  { border-color:var(--u-brand,#1e40af); box-shadow:0 2px 8px rgba(30,64,175,.1); }
.wf-node.active { border-color:var(--u-brand,#1e40af); border-width:2px;
                  box-shadow:0 0 0 3px rgba(30,64,175,.12); }
.wf-node-icon  { width:32px; height:32px; border-radius:8px; display:flex; align-items:center;
                 justify-content:center; font-size:16px; flex-shrink:0; }
.wf-node-body  { flex:1; min-width:0; }
.wf-node-title { font-size:13px; font-weight:700; color:var(--u-text,#0f172a); }
.wf-node-sub   { font-size:11px; color:var(--u-muted,#64748b); margin-top:2px;
                 white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:220px; }
.wf-branch  { display:flex; gap:24px; justify-content:center; margin:0 auto; }
.wf-branch-lbl { font-size:10px; color:var(--u-muted,#64748b); text-align:center; margin-bottom:2px; }

/* ── Right Panel ── */
.bld-panel { position:sticky; top:20px; }
.wf-field  { display:flex; flex-direction:column; gap:3px; }
.wf-field label { font-size:12px; font-weight:700; color:var(--u-text,#0f172a); }
.wf-field input, .wf-field select, .wf-field textarea {
    padding:7px 10px; border:1px solid var(--u-line,#e2e8f0); border-radius:8px;
    font-size:13px; background:var(--u-card,#fff); color:var(--u-text,#0f172a);
    outline:none; width:100%; box-sizing:border-box; }
.wf-field input:focus, .wf-field select:focus, .wf-field textarea:focus {
    border-color:var(--u-brand,#1e40af); box-shadow:0 0 0 2px rgba(30,64,175,.08); }
.wf-field textarea { resize:vertical; min-height:70px; }
.wf-field-row { display:grid; grid-template-columns:1fr 1fr; gap:8px; }

/* ── Add node type list ── */
.nt-list { display:grid; gap:5px; }
.nt-btn  { display:flex; align-items:center; gap:8px; padding:7px 10px; border:1px solid var(--u-line,#e2e8f0);
           border-radius:8px; cursor:pointer; background:var(--u-card,#fff); font-size:12px;
           font-weight:600; color:var(--u-text,#0f172a); transition:all .12s; text-align:left; }
.nt-btn:hover { border-color:var(--u-brand,#1e40af); background:color-mix(in srgb,var(--u-brand,#1e40af) 5%,var(--u-card,#fff)); }
.nt-icon { font-size:16px; }

/* ── Flash ── */
.bld-flash { padding:10px 14px; border-radius:8px; font-size:13px; margin-bottom:10px; }
.bld-flash.ok  { background:color-mix(in srgb,var(--u-ok,#16a34a) 10%,var(--u-card,#fff)); color:var(--u-ok,#16a34a); }
.bld-flash.err { background:color-mix(in srgb,var(--u-danger,#dc2626) 10%,var(--u-card,#fff)); color:var(--u-danger,#dc2626); }
</style>

<div class="bld-wrap">

    {{-- ── Sol: Akış Diyagramı ── --}}
    <div class="card">
        @if(session('success'))
        <div class="bld-flash ok">✓ {{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="bld-flash err">✕ {{ session('error') }}</div>
        @endif

        <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
            Akış Diyagramı
            <span style="font-weight:400;margin-left:8px;font-size:var(--tx-xs);">Düzenlemek için bir node'a tıkla</span>
        </div>

        <div class="wf-flow">
            {{-- Trigger --}}
            <div class="wf-trigger">🎯 TRIGGER: {{ $workflow->trigger_type }}</div>

            @if($nodes->isEmpty())
            <div style="text-align:center;padding:40px;color:var(--u-muted,#64748b);">
                <div style="font-size:var(--tx-2xl);margin-bottom:8px;">➕</div>
                Henüz node yok. Sağ panelden ekle.
            </div>
            @else
            @foreach($nodes as $node)
            @php
                $meta   = $nodeTypeMeta[$node->node_type] ?? ['icon'=>'⚙️','color'=>'#64748b','label'=>$node->node_type];
                $cfg    = $node->node_config ?? [];
                $sublbl = match($node->node_type) {
                    'send_email'        => $cfg['subject_tr'] ?? ($cfg['template_key'] ?? '—'),
                    'send_notification' => ($cfg['channel'] ?? '?') . ': ' . \Illuminate\Support\Str::limit($cfg['message'] ?? '—', 40),
                    'wait'              => ($cfg['duration'] ?? '?') . ' ' . ($cfg['unit'] ?? 'gün') . ' bekle',
                    'condition'         => ($cfg['field'] ?? '?') . ' ' . ($cfg['operator'] ?? '') . ' ' . ($cfg['value'] ?? ''),
                    'add_score'         => 'Puan: +' . ($cfg['score'] ?? $cfg['points'] ?? '?') . ' — ' . ($cfg['reason'] ?? ''),
                    'create_task'       => ($cfg['title'] ?? '—') . ' [' . ($cfg['priority'] ?? 'medium') . ']',
                    'exit'              => $cfg['reason'] ?? 'workflow_completed',
                    default             => $cfg['label'] ?? '',
                };
            @endphp
            <div class="wf-arrow" style="height:24px;"></div>
            <div class="wf-node" id="node-{{ $node->id }}" onclick="selectNode({{ $node->id }})">
                <div class="wf-node-icon" style="background:color-mix(in srgb,{{ $meta['color'] }} 12%,var(--u-card,#fff));">
                    {{ $meta['icon'] }}
                </div>
                <div class="wf-node-body">
                    <div class="wf-node-title">{{ $meta['label'] }}</div>
                    @if($sublbl)
                    <div class="wf-node-sub" title="{{ $sublbl }}">{{ $sublbl }}</div>
                    @endif
                </div>
                <div style="display:flex;align-items:center;gap:4px;flex-shrink:0;">
                    <span style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">#{{ $node->sort_order + 1 }}</span>
                    <form method="POST" action="/mktg-admin/workflows/{{ $workflow->id }}/nodes/{{ $node->id }}"
                          style="display:inline;" onsubmit="return confirm('Bu node\'u sil?')">
                        @csrf @method('DELETE')
                        <button type="submit" onclick="event.stopPropagation();"
                                style="border:none;background:none;cursor:pointer;color:var(--u-muted,#64748b);font-size:var(--tx-sm);padding:2px 4px;border-radius:4px;"
                                title="Sil">✕</button>
                    </form>
                </div>
            </div>
            @if($node->node_type === 'condition')
            <div style="width:2px;height:16px;background:var(--u-line,#e2e8f0);margin:0 auto;"></div>
            <div style="display:flex;gap:32px;justify-content:center;">
                <div style="text-align:center;">
                    <span class="badge ok" style="font-size:var(--tx-xs);">✓ Evet</span>
                </div>
                <div style="text-align:center;">
                    <span class="badge danger" style="font-size:var(--tx-xs);">✕ Hayır</span>
                </div>
            </div>
            @endif
            @endforeach
            @endif
        </div>
    </div>

    {{-- ── Sağ: Düzenle / Ekle Paneli ── --}}
    <div class="bld-panel">

        {{-- Edit Paneli (gizli, node seçilince açılır) --}}
        <div class="card" id="editPanel" style="display:none;margin-bottom:12px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
                <div>
                    <div style="font-weight:700;font-size:var(--tx-sm);" id="editPanelTitle">Node Düzenle</div>
                    <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);" id="editPanelType"></div>
                </div>
                <button onclick="closeEdit()" style="border:none;background:none;cursor:pointer;font-size:var(--tx-base);color:var(--u-muted,#64748b);">✕</button>
            </div>
            <form id="editForm" method="POST" style="display:flex;flex-direction:column;gap:10px;">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <div id="editConfigArea"></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:4px;">
                    <button type="submit" class="btn">💾 Kaydet</button>
                    <button type="button" onclick="closeEdit()" class="btn alt">İptal</button>
                </div>
            </form>
        </div>

        {{-- Yeni Node Ekle --}}
        <div class="card" id="addPanel">
            <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
                ➕ Node Ekle
            </div>

            <form method="POST" action="/mktg-admin/workflows/{{ $workflow->id }}/nodes"
                  style="display:flex;flex-direction:column;gap:10px;">
                @csrf
                <div class="wf-field">
                    <label>Node Türü *</label>
                    <select name="node_type" required id="addTypeSel" onchange="updateAddConfig(this.value)"
                            style="padding:7px 10px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;font-size:var(--tx-sm);background:var(--u-card,#fff);outline:none;">
                        <option value="">— Seç —</option>
                        @foreach($nodeTypes as $type => $lbl)
                        <option value="{{ $type }}">{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="addConfigArea"></div>
                <input type="hidden" name="sort_order" value="{{ $nodes->count() }}">
                <button type="submit" class="btn">Node Ekle</button>
            </form>
        </div>

    </div>
</div>

<script>
// Tüm node verisi
const __nodes = {!! json_encode($nodesData) !!};

// Node tipi config şemaları
function buildConfigFields(type, cfg) {
    cfg = cfg || {};
    const fi = (name, label, type2, val, extra) =>
        `<div class="wf-field"><label>${label}</label><input type="${type2 || 'text'}" name="node_config[${name}]" value="${escHtml(val ?? '')}" ${extra || ''}></div>`;
    const sel = (name, label, opts, val) =>
        `<div class="wf-field"><label>${label}</label><select name="node_config[${name}]">${opts.map(([v,l])=>`<option value="${v}"${val==v?' selected':''}>${l}</option>`).join('')}</select></div>`;
    const ta = (name, label, val) =>
        `<div class="wf-field"><label>${label}</label><textarea name="node_config[${name}]">${escHtml(val ?? '')}</textarea></div>`;

    switch (type) {
        case 'send_email':
            return [
                fi('template_key',  'Template Key (örn: welcome)',      'text', cfg.template_key),
                fi('subject_tr',    'Konu (TR)',                         'text', cfg.subject_tr),
                fi('subject_de',    'Konu (DE)',                         'text', cfg.subject_de),
                fi('delay_minutes', 'Gecikme (dakika, 0 = hemen)',       'number', cfg.delay_minutes ?? 0, 'min="0"'),
                fi('label',         'Etiket (akış diyagramında görünür)','text', cfg.label),
            ].join('');

        case 'send_notification':
            return [
                sel('channel', 'Kanal', [['inApp','Uygulama İçi'],['email','E-posta'],['whatsapp','WhatsApp'],['sms','SMS']], cfg.channel || 'inApp'),
                ta('message', 'Mesaj (@{{name}} gibi değişken kullanılabilir)', cfg.message),
                fi('label', 'Etiket', 'text', cfg.label),
            ].join('');

        case 'wait':
            return `<div class="wf-field-row">
                ${fi('duration', 'Süre', 'number', cfg.duration ?? 1, 'min="1" style="width:100%"')}
                ${sel('unit', 'Birim', [['minutes','Dakika'],['hours','Saat'],['days','Gün'],['weeks','Hafta']], cfg.unit || 'days')}
            </div>${fi('label', 'Etiket', 'text', cfg.label)}`;

        case 'condition':
            return [
                sel('field', 'Alan', [
                    ['documents_uploaded_count','documents_uploaded_count'],
                    ['email_opened','email_opened'],
                    ['lead_score','lead_score'],
                    ['contract_status','contract_status'],
                    ['has_advisor','has_advisor'],
                ], cfg.field),
                sel('operator', 'Operatör', [['equals','='],['not_equals','≠'],['greater_than','>'],['less_than','<'],['contains','İçeriyor']], cfg.operator || 'equals'),
                fi('value', 'Değer', 'text', cfg.value),
                fi('label', 'Etiket', 'text', cfg.label),
            ].join('');

        case 'add_score':
            return [
                fi('score', 'Puan Değeri (+ veya -)', 'number', cfg.score ?? cfg.points ?? 5),
                fi('reason', 'Sebep (log için)', 'text', cfg.reason),
                fi('label', 'Etiket', 'text', cfg.label),
            ].join('');

        case 'create_task':
            return [
                fi('title', 'Task Başlığı', 'text', cfg.title),
                sel('assigned_to', 'Atanacak Kişi', [['senior','Senior Danışman'],['manager','Manager'],['marketing','Marketing']], cfg.assigned_to || 'senior'),
                sel('priority', 'Öncelik', [['low','Düşük'],['medium','Orta'],['high','Yüksek'],['urgent','Acil']], cfg.priority || 'medium'),
                fi('label', 'Etiket', 'text', cfg.label),
            ].join('');

        case 'update_field':
            return [
                fi('field', 'Alan Adı', 'text', cfg.field),
                fi('value', 'Yeni Değer', 'text', cfg.value),
                fi('label', 'Etiket', 'text', cfg.label),
            ].join('');

        case 'move_to_segment':
            return [
                fi('segment_id', 'Segment ID', 'number', cfg.segment_id),
                fi('label', 'Etiket', 'text', cfg.label),
            ].join('');

        case 'ab_split':
            return `<div class="wf-field-row">
                ${fi('split_a', 'A Grubu (%)', 'number', cfg.split?.A ?? cfg.split_a ?? 50, 'min="1" max="99" style="width:100%"')}
                ${fi('split_b', 'B Grubu (%)', 'number', cfg.split?.B ?? cfg.split_b ?? 50, 'min="1" max="99" style="width:100%"')}
            </div>${fi('label', 'Etiket', 'text', cfg.label)}`;

        case 'exit':
            return [
                fi('reason', 'Çıkış Sebebi', 'text', cfg.reason || 'workflow_completed'),
                fi('label', 'Etiket', 'text', cfg.label),
            ].join('');

        default:
            return `<div class="wf-field"><label>Config (JSON)</label><textarea name="node_config[raw]">${escHtml(JSON.stringify(cfg, null, 2))}</textarea></div>`;
    }
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

const nodeTypeLabelMap = {!! json_encode(array_map(fn($m) => $m['label'], $nodeTypeMeta)) !!};

function selectNode(id) {
    // Tüm node highlight temizle
    document.querySelectorAll('.wf-node').forEach(el => el.classList.remove('active'));
    document.getElementById('node-' + id)?.classList.add('active');

    const nd = __nodes.find(n => n.id === id);
    if (!nd) return;

    const panel     = document.getElementById('editPanel');
    const title     = document.getElementById('editPanelTitle');
    const typeLabel = document.getElementById('editPanelType');
    const area      = document.getElementById('editConfigArea');
    const form      = document.getElementById('editForm');

    form.action = `/mktg-admin/workflows/{{ $workflow->id }}/nodes/${id}`;
    title.textContent    = nodeTypeLabelMap[nd.type] || nd.type;
    typeLabel.textContent = 'node_type: ' + nd.type + ' · #' + nd.sort_order;
    area.innerHTML        = buildConfigFields(nd.type, nd.config);

    panel.style.display = 'block';
    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function closeEdit() {
    document.getElementById('editPanel').style.display = 'none';
    document.querySelectorAll('.wf-node').forEach(el => el.classList.remove('active'));
}

function updateAddConfig(type) {
    document.getElementById('addConfigArea').innerHTML = buildConfigFields(type, {});
}
</script>

<details class="card" style="margin-top:12px;">
    <summary class="det-sum">
        <h3>📖 Kullanım Kılavuzu — Workflow Builder</h3>
        <span class="det-chev">▼</span>
    </summary>
    <div style="padding-top:12px;">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:14px;">
            <div>
                <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">🗺 Sayfa Yapısı</strong>
                <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                    <li><strong>Sol Panel:</strong> Akış diyagramı — workflow'un görsel haritası</li>
                    <li><strong>Sağ Üst:</strong> Node'a tıklayınca açılan düzenleme formu</li>
                    <li><strong>Sağ Alt:</strong> Yeni node ekle formu</li>
                </ul>
            </div>
            <div>
                <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">🎯 Trigger (Tetikleyici)</strong>
                <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                    <li><code>guest_registered</code> → Yeni aday kaydında başlar</li>
                    <li><code>document_deadline_approaching</code> → Belge deadline yaklaşınca</li>
                    <li><code>lead_inactive</code> → X gün hareketsiz kalınca</li>
                </ul>
            </div>
        </div>

        <strong style="font-size:var(--tx-xs);display:block;margin-bottom:8px;">⚙️ Node Tipleri ve Alanları</strong>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:14px;">
            <div style="border:1px solid var(--u-line,#e2e8f0);border-radius:8px;padding:10px;">
                <div style="font-size:var(--tx-xs);font-weight:700;margin-bottom:5px;">✉️ Email Gönder</div>
                <ul style="margin:0;padding-left:14px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.7;">
                    <li><strong>Template Key:</strong> welcome, reminder, re_engagement</li>
                    <li><strong>Konu TR/DE:</strong> @{{name}} değişkeni kullanılabilir</li>
                    <li><strong>Gecikme:</strong> 0 = hemen, 60 = 1 saat sonra</li>
                </ul>
            </div>
            <div style="border:1px solid var(--u-line,#e2e8f0);border-radius:8px;padding:10px;">
                <div style="font-size:var(--tx-xs);font-weight:700;margin-bottom:5px;">🔔 Bildirim Gönder</div>
                <ul style="margin:0;padding-left:14px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.7;">
                    <li><strong>Kanal:</strong> inApp, email, whatsapp, sms</li>
                    <li><strong>Mesaj:</strong> @{{name}}, @{{advisor_name}} kullanılabilir</li>
                </ul>
            </div>
            <div style="border:1px solid var(--u-line,#e2e8f0);border-radius:8px;padding:10px;">
                <div style="font-size:var(--tx-xs);font-weight:700;margin-bottom:5px;">⏳ Bekle</div>
                <ul style="margin:0;padding-left:14px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.7;">
                    <li><strong>Süre + Birim:</strong> 3 Gün, 12 Saat, 1 Hafta</li>
                    <li>Sonraki adıma bu süre dolduktan sonra geçer</li>
                </ul>
            </div>
            <div style="border:1px solid var(--u-line,#e2e8f0);border-radius:8px;padding:10px;">
                <div style="font-size:var(--tx-xs);font-weight:700;margin-bottom:5px;">❓ Koşul (If/Else)</div>
                <ul style="margin:0;padding-left:14px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.7;">
                    <li><strong>Alan:</strong> documents_uploaded_count, email_opened, lead_score</li>
                    <li>Evet dalı veya Hayır dalından devam eder</li>
                </ul>
            </div>
            <div style="border:1px solid var(--u-line,#e2e8f0);border-radius:8px;padding:10px;">
                <div style="font-size:var(--tx-xs);font-weight:700;margin-bottom:5px;">📈 Puan Ekle</div>
                <ul style="margin:0;padding-left:14px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.7;">
                    <li><strong>Puan:</strong> +10 ekle, -5 çıkar</li>
                    <li><strong>Sebep:</strong> Log için referans kodu</li>
                </ul>
            </div>
            <div style="border:1px solid var(--u-line,#e2e8f0);border-radius:8px;padding:10px;">
                <div style="font-size:var(--tx-xs);font-weight:700;margin-bottom:5px;">📋 Task Oluştur</div>
                <ul style="margin:0;padding-left:14px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.7;">
                    <li><strong>Başlık:</strong> @{{name}} değişkeni kullanılabilir</li>
                    <li><strong>Atanacak:</strong> Senior, Manager, Marketing</li>
                </ul>
            </div>
        </div>

        <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">🔄 Adım Adım Workflow Oluşturma</strong>
        <ol style="margin:0 0 12px;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.9;">
            <li>Sağ alttaki formdan <strong>Node Türü</strong> seç → alanlar belirir → doldur → <strong>Node Ekle</strong></li>
            <li>Eklenen node diyagramda görünür. Düzenlemek için <strong>node kutusuna tıkla</strong> → sağ üstte edit paneli açılır</li>
            <li>Alanları düzenle → <strong>💾 Kaydet</strong>. Node altındaki özet bilgi güncellenir.</li>
            <li>Silmek için node'un sağ köşesindeki <strong>✕</strong> butonuna tıkla → onayla.</li>
            <li>Tüm adımlar hazır → üst bardaki <strong>▶ Aktifleştir</strong> → sistem çalışmaya başlar.</li>
        </ol>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <div style="padding:10px;background:color-mix(in srgb,var(--u-warn,#d97706) 6%,var(--u-card,#fff));border-radius:8px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
                ⚠️ <strong>Önemli:</strong> Aktif workflow'un node'larını düzenleyebilirsin, ancak değişiklikler sadece yeni enrollmentlara uygulanır. Mevcut aktif adaylar eski yapıyla devam eder.
            </div>
            <div style="padding:10px;background:color-mix(in srgb,var(--u-ok,#16a34a) 6%,var(--u-card,#fff));border-radius:8px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
                💡 <strong>İpucu:</strong> Workflow'u yayına almadan önce Analytics sayfasında simülasyon çalıştır. Enrollments → enrollment sayısı arttıkça performansı buradan izle.
            </div>
        </div>

    </div>
</details>
@endsection
