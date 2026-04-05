@extends('senior.layouts.app')
@section('title', 'Pipeline Kanban')
@section('page_title', 'Pipeline Kanban')

@push('head')
<style>
/* ── Board ── */
.pipe-wrap  { overflow-x:auto; padding-bottom:24px; }
.pipe-board { display:flex; gap:10px; align-items:flex-start; padding:2px 2px 4px; width:100%; box-sizing:border-box; }

/* ── Column ── */
.pipe-col {
    flex:1; min-width:160px;
    background:var(--u-card);
    border:1px solid var(--u-line);
    border-radius:14px;
    overflow:hidden;
    box-shadow:0 2px 8px rgba(0,0,0,.05);
    display:flex; flex-direction:column;
}
.pipe-col-head {
    padding:10px 12px 8px;
    display:flex; justify-content:space-between; align-items:center;
    border-bottom:1px solid var(--u-line);
    position:relative;
}
.pipe-col-head::before {
    content:''; position:absolute; left:0; top:0; bottom:0;
    width:4px; border-radius:4px 0 0 0;
}
.pipe-col-title { font-size:12px; font-weight:800; letter-spacing:.04em; text-transform:uppercase; color:var(--u-text); }
.pipe-col-sub   { font-size:11px; color:var(--u-muted); margin-top:1px; }
.pipe-cnt {
    font-size:12px; font-weight:800;
    min-width:24px; height:24px;
    border-radius:999px;
    display:flex; align-items:center; justify-content:center;
    padding:0 8px;
}

/* ── Cards area ── */
.pipe-cards {
    min-height:80px; padding:6px;
    display:flex; flex-direction:column; gap:6px;
    flex:1;
}
.pipe-cards.drag-over { background:#ede9fe; }

/* ── Card ── */
.pipe-card {
    background:var(--u-bg);
    border:1px solid var(--u-line);
    border-radius:10px;
    padding:8px 10px;
    cursor:grab;
    transition:box-shadow .15s, border-color .15s, transform .1s;
    user-select:none;
    position:relative;
}
.pipe-card::before {
    content:''; position:absolute; left:0; top:4px; bottom:4px;
    width:3px; border-radius:3px;
}
.pipe-card.risk-high::before   { background:#dc2626; }
.pipe-card.risk-medium::before { background:#f59e0b; }
.pipe-card.risk-low::before    { background:#16a34a; }

.pipe-card:hover   { border-color:#c4b5fd; box-shadow:0 4px 14px rgba(124,58,237,.12); transform:translateY(-1px); }
.pipe-card:active  { cursor:grabbing; }
.pipe-card.dragging { opacity:.35; transform:scale(.97); }

/* ── Card internals ── */
.pc-row    { display:flex; align-items:center; gap:7px; margin-bottom:5px; }
.pc-avatar {
    width:26px; height:26px; border-radius:50%; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-size:10px; font-weight:800; color:#fff;
}
.pc-name   { font-size:12px; font-weight:700; color:var(--u-text); white-space:normal; word-break:break-word; flex:1; min-width:0; line-height:1.3; }
.pc-badges { display:flex; gap:3px; flex-wrap:wrap; margin-bottom:5px; }
.pc-foot   { display:flex; gap:5px; align-items:center; }

/* ── Empty ── */
.pipe-empty {
    text-align:center; padding:28px 12px;
    color:var(--u-muted); font-size:12px;
}
.pipe-empty-icon { font-size:28px; margin-bottom:6px; opacity:.4; }

/* ── Note modal ── */
#pipe-note-modal {
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,.5); z-index:2000;
    align-items:center; justify-content:center;
}
.pnm-box {
    background:var(--u-card); border-radius:16px;
    width:440px; max-width:94vw; padding:28px;
    box-shadow:0 20px 60px rgba(0,0,0,.25);
    border:1px solid var(--u-line);
    animation:pnm-in .18s ease;
}
@keyframes pnm-in { from { transform:scale(.94); opacity:0; } to { transform:scale(1); opacity:1; } }
</style>
@endpush

@section('content')

@php
/* Per-step color config */
$stepColors = [
    'application_prep'  => ['bg'=>'#f59e0b','text'=>'#92400e','cnt_bg'=>'#fef3c7','cnt_fg'=>'#92400e','bar'=>'#f59e0b'],
    'uni_assist'        => ['bg'=>'#6366f1','text'=>'#fff','cnt_bg'=>'rgba(99,102,241,.15)','cnt_fg'=>'#6366f1','bar'=>'#6366f1'],
    'visa_application'  => ['bg'=>'#7c3aed','text'=>'#fff','cnt_bg'=>'rgba(124,58,237,.15)','cnt_fg'=>'#7c3aed','bar'=>'#7c3aed'],
    'language_course'   => ['bg'=>'#0891b2','text'=>'#fff','cnt_bg'=>'rgba(8,145,178,.15)','cnt_fg'=>'#0891b2','bar'=>'#0891b2'],
    'residence'         => ['bg'=>'#d97706','text'=>'#fff','cnt_bg'=>'rgba(217,119,6,.15)','cnt_fg'=>'#d97706','bar'=>'#d97706'],
    'official_services' => ['bg'=>'#059669','text'=>'#fff','cnt_bg'=>'rgba(5,150,105,.15)','cnt_fg'=>'#059669','bar'=>'#059669'],
    'completed'         => ['bg'=>'#16a34a','text'=>'#fff','cnt_bg'=>'rgba(22,163,74,.15)','cnt_fg'=>'#16a34a','bar'=>'#16a34a'],
];
$avatarColors = ['#7c3aed','#6366f1','#0891b2','#059669','#d97706','#dc2626','#0f766e','#6d28d9'];
@endphp

{{-- Gradient Header --}}
<div style="background:linear-gradient(to right,#6d28d9,#7c3aed);border-radius:14px;padding:20px 24px;margin-bottom:16px;color:#fff;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
        <div>
            <div style="font-size:var(--tx-xl);font-weight:800;letter-spacing:-.3px;margin-bottom:4px;">🗂 Öğrenci Pipeline</div>
            <div style="font-size:var(--tx-sm);opacity:.8;">Drag & drop ile öğrenci aşamalarını yönetin</div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <a href="/senior/students" style="background:rgba(255,255,255,.15);border:1.5px solid rgba(255,255,255,.3);border-radius:8px;padding:7px 14px;font-size:var(--tx-xs);font-weight:700;color:#fff;text-decoration:none;">☰ Liste</a>
            <a href="/senior/process-tracking" style="background:rgba(255,255,255,.15);border:1.5px solid rgba(255,255,255,.3);border-radius:8px;padding:7px 14px;font-size:var(--tx-xs);font-weight:700;color:#fff;text-decoration:none;">🗂 Süreç Takibi</a>
        </div>
    </div>

    {{-- Summary chips --}}
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <div style="background:rgba(255,255,255,.18);border-radius:10px;padding:8px 16px;display:flex;align-items:center;gap:8px;">
            <span style="font-size:var(--tx-lg);">🎓</span>
            <div>
                <div style="font-size:var(--tx-xl);font-weight:800;line-height:1;">{{ $totalStudents }}</div>
                <div style="font-size:var(--tx-xs);opacity:.75;text-transform:uppercase;letter-spacing:.04em;">Toplam</div>
            </div>
        </div>
        @php
            $highRisk = 0; $withContract = 0;
            foreach($columns as $col) {
                foreach($col['cards'] as $c) {
                    if(($c['risk'] ?? '') === 'high') $highRisk++;
                    if($c['contract'] ?? false) $withContract++;
                }
            }
        @endphp
        <div style="background:rgba(220,38,38,.25);border:1px solid rgba(220,38,38,.4);border-radius:10px;padding:8px 16px;display:flex;align-items:center;gap:8px;">
            <span style="font-size:var(--tx-lg);">⚠️</span>
            <div>
                <div style="font-size:var(--tx-xl);font-weight:800;line-height:1;">{{ $highRisk }}</div>
                <div style="font-size:var(--tx-xs);opacity:.75;text-transform:uppercase;letter-spacing:.04em;">Yüksek Risk</div>
            </div>
        </div>
        <div style="background:rgba(22,163,74,.2);border:1px solid rgba(22,163,74,.35);border-radius:10px;padding:8px 16px;display:flex;align-items:center;gap:8px;">
            <span style="font-size:var(--tx-lg);">✅</span>
            <div>
                <div style="font-size:var(--tx-xl);font-weight:800;line-height:1;">{{ $withContract }}</div>
                <div style="font-size:var(--tx-xs);opacity:.75;text-transform:uppercase;letter-spacing:.04em;">Sözleşmeli</div>
            </div>
        </div>
        <div style="background:rgba(255,255,255,.12);border-radius:10px;padding:8px 14px;display:flex;gap:6px;flex-wrap:wrap;">
            @foreach($columns as $col)
            <div style="text-align:center;min-width:32px;">
                <div style="font-size:var(--tx-sm);font-weight:800;">{{ count($col['cards']) }}</div>
                <div style="font-size:var(--tx-xs);opacity:.7;white-space:nowrap;">{{ mb_substr($col['label'],0,6) }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Status message --}}
<div id="pipeline-msg" style="display:none;padding:10px 16px;border-radius:8px;margin-bottom:12px;font-size:var(--tx-sm);font-weight:600;border-left:4px solid;"></div>

{{-- ===== DRAG-DROP GLOBAL FUNCTIONS — defined BEFORE board HTML ===== --}}
<script nonce="{{ $cspNonce ?? '' }}">
var _sd   = null;
var _scsrf = (function(){ var m=document.querySelector('meta[name="csrf-token"]'); return m ? m.content : ''; })();

function sStart(card, e) {
    _sd = { studentId: card.dataset.student, name: card.dataset.name, fromStep: card.dataset.step, fromCard: card, target: null };
    card.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', card.dataset.student || '');
}
function sOver(col, e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    var dz = col.querySelector('.pipe-cards');
    if (dz) dz.classList.add('dov');
}
function sLeave(col, e) {
    if (!col.contains(e.relatedTarget)) {
        var dz = col.querySelector('.pipe-cards');
        if (dz) dz.classList.remove('dov');
    }
}
function sDrop(col, e) {
    e.preventDefault();
    var dz = col.querySelector('.pipe-cards');
    if (dz) dz.classList.remove('dov');
    if (!_sd) return;

    var newStep = dz ? dz.dataset.step : null;
    if (!newStep || newStep === _sd.fromStep) {
        _sd.fromCard.classList.remove('dragging'); _sd = null; return;
    }

    _sd.target = { step: newStep, col: dz };
    var titleEl = col.querySelector('.pipe-col-title');
    document.getElementById('pnm-name').textContent = _sd.name;
    document.getElementById('pnm-step').textContent = titleEl ? titleEl.textContent : newStep;
    document.getElementById('pnm-note').value = '';
    document.getElementById('pipe-note-modal').style.display = 'flex';
    setTimeout(function(){ document.getElementById('pnm-note').focus(); }, 80);
}
function sMoveConfirm() {
    if (!_sd || !_sd.target) return;
    var note    = document.getElementById('pnm-note').value.trim();
    var newStep = _sd.target.step;
    var col     = _sd.target.col;
    fetch('/senior/student-pipeline/advance', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':_scsrf,'Accept':'application/json'},
        body: JSON.stringify({ student_id: _sd.studentId, new_step: newStep, note: note || null }),
    }).then(function(r){ return r.json(); }).then(function(d){
        if (d.ok) {
            _sd.fromCard.dataset.step = newStep;
            _sd.fromCard.classList.remove('dragging');
            var ph = col.querySelector('.pipe-empty');
            if (ph) ph.remove();
            col.appendChild(_sd.fromCard);
            sCnt(_sd.fromStep); sCnt(newStep);
            sMsg('Ogrenci tasindi', 'ok');
        } else {
            sMsg(d.error || 'Hata', 'danger');
            _sd.fromCard.classList.remove('dragging');
        }
    }).catch(function(){
        sMsg('Baglanti hatasi', 'danger');
        if (_sd) _sd.fromCard.classList.remove('dragging');
    }).finally(function(){
        _sd = null;
        document.getElementById('pipe-note-modal').style.display = 'none';
    });
}
function sMoveCancel() {
    if (_sd && _sd.fromCard) _sd.fromCard.classList.remove('dragging');
    _sd = null;
    document.getElementById('pipe-note-modal').style.display = 'none';
}
function sCnt(step) {
    var col = document.getElementById('col-' + step);
    if (!col || !col.closest('.pipe-col')) return;
    var pc = col.closest('.pipe-col');
    var cnt = col.querySelectorAll('.pipe-card').length;
    var ce = pc.querySelector('.pipe-cnt'); var se = pc.querySelector('.pipe-col-sub');
    if (ce) ce.textContent = cnt;
    if (se) se.textContent = cnt > 0 ? cnt + ' ogrenci' : 'bos';
}
function sMsg(text, type) {
    var el = document.getElementById('pipeline-msg');
    if (!el) return;
    el.textContent = text;
    el.style.display = 'block';
    el.style.background = type === 'ok' ? '#f0fdf4' : '#fef2f2';
    el.style.color = type === 'ok' ? '#16a34a' : '#dc2626';
    el.style.borderLeftColor = type === 'ok' ? '#16a34a' : '#dc2626';
    setTimeout(function(){ el.style.display = 'none'; }, 3000);
}
/* wire modal button onclick handlers */
window.pipeNoteConfirm = sMoveConfirm;
window.pipeNoteCancel  = sMoveCancel;
document.addEventListener('dragend', function() {
    document.querySelectorAll('.pipe-cards.dov').forEach(function(el){ el.classList.remove('dov'); });
    if (_sd && _sd.fromCard) { _sd.fromCard.classList.remove('dragging'); }
});
</script>

{{-- Kanban Board --}}
<div class="pipe-wrap">
    <div class="pipe-board" id="pipelineBoard">
        @foreach($columns as $col)
        @php
            $sc      = $stepColors[$col['step']] ?? ['bg'=>'#7c3aed','text'=>'#fff','cnt_bg'=>'rgba(124,58,237,.15)','cnt_fg'=>'#7c3aed','bar'=>'#7c3aed'];
            $cardCnt = count($col['cards']);
        @endphp
        <div class="pipe-col" id="scol-{{ $col['step'] }}" data-step="{{ $col['step'] }}">
            {{-- Column header --}}
            <div class="pipe-col-head" style="background:var(--u-card);">
                <div style="position:absolute;left:0;top:0;bottom:0;width:4px;background:{{ $sc['bar'] }};border-radius:4px 0 0 4px;"></div>
                <div style="padding-left:8px;">
                    <div class="pipe-col-title" style="color:{{ $sc['bar'] }};">{{ $col['label'] }}</div>
                    <div class="pipe-col-sub">{{ $cardCnt > 0 ? $cardCnt.' öğrenci' : 'boş' }}</div>
                </div>
                <span class="pipe-cnt" style="background:{{ $sc['cnt_bg'] }};color:{{ $sc['cnt_fg'] }};">{{ $cardCnt }}</span>
            </div>

            {{-- Cards --}}
            <div class="pipe-cards" id="col-{{ $col['step'] }}"
                 data-step="{{ $col['step'] }}">

                @foreach($col['cards'] as $ci => $card)
                @php
                    $initials  = strtoupper(mb_substr(preg_replace('/[^A-Za-zÇçĞğİıÖöŞşÜü]/u','', $card['name']),0,2));
                    $avatarClr = $avatarColors[$ci % count($avatarColors)];
                    $riskClass = 'risk-' . ($card['risk'] ?? 'low');
                @endphp
                <div class="pipe-card {{ $riskClass }}"
                     draggable="true"
                     data-student="{{ $card['student_id'] }}"
                     data-name="{{ $card['name'] }}"
                     data-step="{{ $col['step'] }}">

                    {{-- Name row with avatar --}}
                    <div class="pc-row">
                        <div class="pc-avatar" style="background:{{ $avatarClr }};">{{ $initials ?: '?' }}</div>
                        <div class="pc-name" title="{{ $card['name'] }}">{{ $card['name'] }}</div>
                    </div>

                    {{-- Badges --}}
                    @if($card['tier'] || ($card['risk'] ?? '') === 'high' || $card['contract'])
                    <div class="pc-badges">
                        @if($card['tier'])
                            <span style="font-size:var(--tx-xs);background:rgba(8,145,178,.1);color:#0891b2;border-radius:4px;padding:1px 7px;font-weight:700;">{{ strtoupper((string)$card['tier']) }}</span>
                        @endif
                        @if(($card['risk'] ?? '') === 'high')
                            <span style="font-size:var(--tx-xs);background:#fef2f2;color:#dc2626;border-radius:4px;padding:1px 7px;font-weight:700;">⚠ Risk</span>
                        @elseif(($card['risk'] ?? '') === 'medium')
                            <span style="font-size:var(--tx-xs);background:#fefce8;color:#ca8a04;border-radius:4px;padding:1px 7px;font-weight:700;">⚡ Orta</span>
                        @endif
                        @if($card['contract'])
                            <span style="font-size:var(--tx-xs);background:#f0fdf4;color:#16a34a;border-radius:4px;padding:1px 7px;font-weight:700;">✓ Sözl.</span>
                        @endif
                    </div>
                    @endif

                    {{-- Paralel Aktif Süreçler --}}
                    @if(!empty($card['parallel_steps']))
                    @php
                        $parallelLabels = ['application_prep'=>'Başvuru','uni_assist'=>'Uni Assist',
                            'visa_application'=>'Vize','language_course'=>'Dil Kursu',
                            'residence'=>'İkamet','official_services'=>'Resmi Evrak'];
                        $parallelColors = ['application_prep'=>'#f59e0b','uni_assist'=>'#6366f1',
                            'visa_application'=>'#7c3aed','language_course'=>'#0891b2',
                            'residence'=>'#d97706','official_services'=>'#059669'];
                    @endphp
                    <div style="display:flex;flex-wrap:wrap;gap:3px;margin-bottom:5px;">
                        @foreach($card['parallel_steps'] as $ps)
                        <span style="font-size:9px;font-weight:700;padding:1px 6px;border-radius:4px;background:rgba(0,0,0,.05);color:{{ $parallelColors[$ps] ?? '#7c3aed' }};border:1px solid {{ $parallelColors[$ps] ?? '#7c3aed' }}40;">
                            +{{ $parallelLabels[$ps] ?? $ps }}
                        </span>
                        @endforeach
                    </div>
                    @endif

                    {{-- Footer --}}
                    <div class="pc-foot">
                        <a href="{{ $card['detail_url'] }}"
                           style="font-size:var(--tx-xs);padding:4px 10px;border:1px solid var(--u-line);border-radius:6px;background:var(--u-card);color:var(--u-text);text-decoration:none;font-weight:600;flex:1;text-align:center;">
                            360° Detay →
                        </a>
                    </div>
                </div>
                @endforeach

                @if($cardCnt === 0)
                <div class="pipe-empty">
                    <div class="pipe-empty-icon">📭</div>
                    <div>Öğrenci yok</div>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Note Modal --}}
<div id="pipe-note-modal">
    <div class="pnm-box">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
            <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#7c3aed,#6d28d9);display:flex;align-items:center;justify-content:center;font-size:var(--tx-lg);">🚀</div>
            <div>
                <div style="font-size:var(--tx-base);font-weight:800;color:var(--u-text);">Aşama Değişikliği</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);">Öğrenciyi yeni aşamaya taşı</div>
            </div>
        </div>
        <div style="background:var(--u-bg);border:1px solid var(--u-line);border-radius:10px;padding:12px 14px;margin-bottom:16px;font-size:var(--tx-sm);">
            <span style="color:var(--u-muted);">Öğrenci:</span>
            <strong id="pnm-name" style="color:var(--u-text);margin-left:6px;"></strong>
            <span style="color:var(--u-muted);margin:0 6px;">→</span>
            <strong id="pnm-step" style="color:#7c3aed;"></strong>
        </div>
        <div style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Not (opsiyonel)</div>
        <textarea id="pnm-note" placeholder="Aşama değişikliği için not ekleyin… (Ctrl+Enter ile kaydet)"
                  rows="3"
                  style="width:100%;padding:10px 12px;border:1.5px solid var(--u-line);border-radius:9px;font-size:var(--tx-sm);resize:none;box-sizing:border-box;background:var(--u-bg);color:var(--u-text);font-family:inherit;outline:none;transition:border-color .15s;"></textarea>
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:14px;">
            <button id="pnm-cancel" type="button"
                    style="background:var(--u-bg);color:var(--u-text);border:1px solid var(--u-line);border-radius:8px;padding:9px 18px;font-size:var(--tx-sm);font-weight:600;cursor:pointer;">
                İptal
            </button>
            <button id="pnm-confirm" type="button"
                    style="background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:8px;padding:9px 20px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;">
                ✓ Onayla & Taşı
            </button>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    /* Board: allow drop everywhere */
    var board = document.getElementById('pipelineBoard');
    if (board) {
        board.addEventListener('dragover',  function(e){ e.preventDefault(); });
        board.addEventListener('dragenter', function(e){ e.preventDefault(); });
    }

    /* Cards: dragstart */
    document.querySelectorAll('#pipelineBoard .pipe-card[draggable="true"]').forEach(function(card) {
        card.addEventListener('dragover',  function(e){ e.preventDefault(); });
        card.addEventListener('dragstart', function(e){ sStart(card, e); });
    });

    /* Columns: dragover / dragleave / drop */
    document.querySelectorAll('#pipelineBoard .pipe-col').forEach(function(col) {
        col.addEventListener('dragover',  function(e){ sOver(col, e); });
        col.addEventListener('dragleave', function(e){ sLeave(col, e); });
        col.addEventListener('drop',      function(e){ sDrop(col, e); });
    });

    /* Modal buttons */
    var btnCancel  = document.getElementById('pnm-cancel');
    var btnConfirm = document.getElementById('pnm-confirm');
    if (btnCancel)  btnCancel.addEventListener('click',  sMoveCancel);
    if (btnConfirm) btnConfirm.addEventListener('click', sMoveConfirm);

    /* Textarea focus styling */
    var ta = document.getElementById('pnm-note');
    if (ta) {
        ta.addEventListener('focus', function(){ ta.style.borderColor = '#7c3aed'; });
        ta.addEventListener('blur',  function(){ ta.style.borderColor = 'var(--u-line)'; });
        ta.addEventListener('keydown', function(e){
            if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); sMoveConfirm(); }
            if (e.key === 'Escape') sMoveCancel();
        });
    }
}());
</script>
@endsection
