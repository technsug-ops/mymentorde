@extends('manager.layouts.app')
@section('title', 'Pipeline Kanban')
@section('page_subtitle', 'Adayları sürükleyip bırakarak aşamalar arasında taşıyın')

@push('head')
<style>
.pipe-wrap  { padding-bottom:24px; }
.pipe-board { display:flex; gap:8px; align-items:flex-start; padding:2px 0 4px; width:100%; box-sizing:border-box; }
.pipe-col   { flex:1; min-width:0; background:var(--u-card); border:1px solid var(--u-line); border-radius:14px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.05); display:flex; flex-direction:column; }
.pipe-col.ro{ opacity:.72; }
.pipe-col-head { padding:10px 12px 8px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--u-line); }
.pipe-col-title { font-size:11px; font-weight:800; letter-spacing:.04em; text-transform:uppercase; }
.pipe-cnt { font-size:11px; font-weight:800; min-width:22px; height:22px; border-radius:999px; display:flex; align-items:center; justify-content:center; padding:0 7px; }
.pipe-cards { min-height:80px; padding:6px; display:flex; flex-direction:column; gap:6px; flex:1; }
.pipe-cards.dov { background:#ede9fe; outline:2px dashed #7c3aed; }
.pipe-card  { background:var(--u-bg); border:1px solid var(--u-line); border-radius:10px; padding:8px 10px; cursor:grab; transition:box-shadow .15s,border-color .15s,transform .1s; user-select:none; }
.pipe-card:hover { border-color:#c4b5fd; box-shadow:0 4px 14px rgba(124,58,237,.12); transform:translateY(-1px); }
.pipe-card.dragging { opacity:.3; transform:scale(.96); cursor:grabbing; }
.ro .pipe-card { cursor:default; }
.ro .pipe-card:hover { transform:none; box-shadow:none; border-color:var(--u-line); }
.pc-row  { display:flex; align-items:center; gap:6px; margin-bottom:4px; }
.pc-av   { width:24px; height:24px; border-radius:50%; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:9px; font-weight:800; color:#fff; }
.pc-name { font-size:11px; font-weight:700; color:var(--u-text); word-break:break-word; flex:1; min-width:0; line-height:1.3; }
.pc-meta { font-size:9px; color:var(--u-muted); margin-bottom:4px; }
.pc-foot { display:flex; gap:4px; }
.pipe-empty { text-align:center; padding:20px 8px; color:var(--u-muted); font-size:11px; pointer-events:none; }
</style>
@endpush

@section('content')
@php
$avatarColors = ['#7c3aed','#0891b2','#16a34a','#d97706','#dc2626','#6366f1','#059669','#9333ea','#0ea5e9','#f43f5e'];
$tierColors = ['hot'=>['bg'=>'#fef2f2','fg'=>'#dc2626'],'warm'=>['bg'=>'#fffbeb','fg'=>'#d97706'],'cold'=>['bg'=>'#eff6ff','fg'=>'#2563eb'],'sales_ready'=>['bg'=>'#f0fdf4','fg'=>'#16a34a'],'champion'=>['bg'=>'#faf5ff','fg'=>'#7c3aed']];
$typeLabels = ['bachelor'=>'Lisans','master'=>'Y.Lisans','phd'=>'Doktora','language'=>'Dil','prep'=>'Hazırlık'];
$colColors  = ['new'=>'#6366f1','contacted'=>'#0891b2','docs_pending'=>'#d97706','in_progress'=>'#7c3aed','evaluating'=>'#9333ea','contract_signed'=>'#16a34a','converted'=>'#059669','lost'=>'#6b7280'];
@endphp

{{-- Stage Modal --}}
@include('partials.guest-pipeline-modal')

{{-- ===== DRAG-DROP GLOBAL FUNCTIONS — defined BEFORE board HTML ===== --}}
<script nonce="{{ $cspNonce ?? '' }}">
var _kd    = null;
var _kPend = null; /* {guestId, guestName, oldStage, newStage, dz} — modal açıkken */
var _kcsrf = (function(){ var m=document.querySelector('meta[name="csrf-token"]'); return m ? m.content : ''; })();

/* ── Stage form konfigürasyonu (guest-pipeline ile aynı) ── */
var STAGE_CFG = {
    new: null,
    contacted: {
        icon:'📞', title:'İletişime Geçildi', color:'#0891b2',
        fields:[
            {id:'contact_method', label:'İletişim Yöntemi', type:'radio', required:true,
             opts:[{v:'mail',l:'📧 Mail'},{v:'phone',l:'📞 Telefon'},{v:'whatsapp',l:'💬 WhatsApp'},{v:'linkedin',l:'💼 LinkedIn'}]},
            {id:'contact_result', label:'Görüşme Sonucu', type:'radio', required:true,
             opts:[{v:'reached',l:'✅ Ulaşıldı, görüşüldü'},{v:'not_available',l:'📵 Müsait değildi'},{v:'message_left',l:'📩 Mesaj bırakıldı'},{v:'callback',l:'🔄 Geri arama istedi'},{v:'appointment',l:'📅 İleri tarihe randevu verdi'}]},
            {id:'follow_up_date', label:'Takip Tarihi', type:'date', required:false,
             showIf:{id:'contact_result', vals:['not_available','callback','appointment']}},
            {id:'notes', label:'Not', type:'textarea', required:false, placeholder:'Görüşme detayları...'}
        ]
    },
    docs_pending: {
        icon:'📄', title:'Evrak Bekliyor', color:'#d97706',
        fields:[
            {id:'contact_result', label:'İstenen Evraklar', type:'checkboxgroup', required:false,
             opts:[{v:'passport',l:'🛂 Pasaport'},{v:'diploma',l:'🎓 Diploma'},{v:'transcript',l:'📋 Transkript'},{v:'language_cert',l:'🌐 Dil Sertifikası'},{v:'photo',l:'📷 Fotoğraf'},{v:'motivation',l:'✍️ Motivasyon Mektubu'},{v:'other',l:'📎 Diğer'}]},
            {id:'follow_up_date', label:'Teslim Tarihi', type:'date', required:false},
            {id:'notes', label:'Not', type:'textarea', required:false, placeholder:'Evrak talebi detayları...'}
        ]
    },
    in_progress: {
        icon:'⚙️', title:'İşlemde', color:'#7c3aed',
        fields:[
            {id:'contact_result', label:'Mevcut Durum', type:'select', required:false,
             opts:[{v:'',l:'— Seçin —'},{v:'form_filling',l:'Başvuru formu dolduruluyor'},{v:'uni_research',l:'Üniversite araştırması'},{v:'lang_exam',l:'Dil sınavı bekleniyor'},{v:'advisor_review',l:'Danışman değerlendirmesi'},{v:'doc_collection',l:'Evrak toplama aşaması'}]},
            {id:'notes', label:'Not', type:'textarea', required:false, placeholder:'İşlem detayları...'}
        ]
    },
    evaluating: {
        icon:'🔍', title:'Değerlendiriliyor', color:'#9333ea',
        fields:[
            {id:'contact_result', label:'Değerlendirme Konusu', type:'select', required:false,
             opts:[{v:'',l:'— Seçin —'},{v:'uni_match',l:'Üniversite eşleşmesi'},{v:'financial',l:'Mali durum değerlendirmesi'},{v:'visa',l:'Vize uygunluğu'},{v:'package',l:'Paket seçimi'},{v:'doc_review',l:'Evrak değerlendirmesi'}]},
            {id:'notes', label:'Not', type:'textarea', required:false, placeholder:'Değerlendirme detayları...'}
        ]
    },
    contract_signed: {
        icon:'✍️', title:'Sözleşme İmzalandı', color:'#16a34a',
        fields:[
            {id:'contact_method', label:'İmza Yöntemi', type:'radio', required:false,
             opts:[{v:'digital',l:'💻 Dijital imza'},{v:'physical',l:'📝 Fiziksel imza'}]},
            {id:'notes', label:'Not', type:'textarea', required:false, placeholder:'Sözleşme detayları...'}
        ]
    },
    converted: {
        icon:'🎉', title:'Dönüştürüldü — Tebrikler!', color:'#059669',
        fields:[
            {id:'notes', label:'Dönüşüm Notu', type:'textarea', required:false, placeholder:'Öğrenciye dönüştürme detayları, başvurulan program...'}
        ]
    },
    lost: {
        icon:'😞', title:'Kaybedildi', color:'#6b7280',
        fields:[
            {id:'lost_reason', label:'Kayıp Nedeni', type:'select', required:true,
             opts:[{v:'',l:'— Neden seçin (zorunlu) —'},{v:'price',l:'💸 Fiyat yüksek buldu'},{v:'competitor',l:'🏢 Rakip firmayı tercih etti'},{v:'no_contact',l:'📵 İletişim kesildi'},{v:'self_withdrew',l:'🚶 Kendi isteğiyle vazgeçti'},{v:'visa_rejected',l:'🚫 Vize reddedildi'},{v:'academic',l:'📚 Akademik yetersizlik'},{v:'family',l:'👨‍👩‍👧 Aile kararı'},{v:'financial',l:'💰 Maddi yetersizlik'},{v:'other',l:'❓ Diğer'}]},
            {id:'follow_up_date', label:'Tekrar Aranacak Tarih (Opsiyonel)', type:'date', required:false},
            {id:'notes', label:'Yorum', type:'textarea', required:false, placeholder:'Kayıp detayları...'}
        ]
    }
};

function buildField(f, idx) {
    var html = '<div class="gpm-field" id="gpm-wrap-' + f.id + '" style="margin-bottom:14px;">';
    html += '<label style="display:block;font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">' + f.label + (f.required ? ' <span style="color:#dc2626;">*</span>' : '') + '</label>';
    if (f.type === 'radio') {
        html += '<div style="display:flex;flex-wrap:wrap;gap:6px;">';
        f.opts.forEach(function(o) {
            html += '<label style="display:flex;align-items:center;gap:6px;padding:6px 12px;border:1.5px solid var(--u-line);border-radius:8px;cursor:pointer;font-size:var(--tx-sm);font-weight:600;background:var(--u-bg);">'
                + '<input type="radio" name="gpm_' + f.id + '" value="' + o.v + '" style="margin:0;accent-color:#7c3aed;"> ' + o.l + '</label>';
        });
        html += '</div>';
    } else if (f.type === 'select') {
        html += '<select id="gpm_' + f.id + '" style="width:100%;padding:9px 12px;border:1.5px solid var(--u-line);border-radius:9px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);outline:none;">';
        f.opts.forEach(function(o) { html += '<option value="' + o.v + '">' + o.l + '</option>'; });
        html += '</select>';
    } else if (f.type === 'checkboxgroup') {
        html += '<div style="display:flex;flex-wrap:wrap;gap:6px;">';
        f.opts.forEach(function(o) {
            html += '<label style="display:flex;align-items:center;gap:6px;padding:5px 10px;border:1.5px solid var(--u-line);border-radius:8px;cursor:pointer;font-size:var(--tx-sm);font-weight:600;background:var(--u-bg);">'
                + '<input type="checkbox" name="gpm_' + f.id + '[]" value="' + o.v + '" style="margin:0;accent-color:#7c3aed;"> ' + o.l + '</label>';
        });
        html += '</div>';
    } else if (f.type === 'textarea') {
        html += '<textarea id="gpm_' + f.id + '" placeholder="' + (f.placeholder||'') + '" rows="3"'
            + ' style="width:100%;padding:9px 12px;border:1.5px solid var(--u-line);border-radius:9px;font-size:var(--tx-sm);resize:vertical;box-sizing:border-box;background:var(--u-bg);color:var(--u-text);font-family:inherit;outline:none;"></textarea>';
    } else if (f.type === 'date') {
        html += '<input type="date" id="gpm_' + f.id + '"'
            + ' style="width:100%;padding:9px 12px;border:1.5px solid var(--u-line);border-radius:9px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);outline:none;">';
    }
    html += '</div>';
    return html;
}

function showPipelineModal(guestId, guestName, oldStage, newStage, dz) {
    var cfg = STAGE_CFG[newStage];
    if (!cfg) {
        doMove(guestId, guestName, oldStage, newStage, dz, {});
        return;
    }
    _kPend = {guestId:guestId, guestName:guestName, oldStage:oldStage, newStage:newStage, dz:dz};

    document.getElementById('gpm-icon').textContent = cfg.icon;
    document.getElementById('gpm-icon').style.background = 'linear-gradient(135deg,' + cfg.color + ',' + cfg.color + 'cc)';
    document.getElementById('gpm-title').textContent = cfg.title;
    document.getElementById('gpm-subtitle').textContent = guestName + ' adayının aşamasını güncelliyorsunuz';

    var ini = guestName.replace(/[^A-Za-z\u00C0-\u024F]/gu,'').substring(0,2).toUpperCase();
    document.getElementById('gpm-guest-av').textContent = ini || '?';
    document.getElementById('gpm-guest-name').textContent = guestName;
    document.getElementById('gpm-stage-change').textContent = (oldStage||'—') + ' → ' + newStage;

    var fieldsHtml = '';
    cfg.fields.forEach(function(f, i) { fieldsHtml += buildField(f, i); });
    document.getElementById('gpm-fields').innerHTML = fieldsHtml;

    cfg.fields.forEach(function(f) {
        if (!f.showIf) return;
        var wrap = document.getElementById('gpm-wrap-' + f.id);
        if (wrap) wrap.style.display = 'none';
        document.querySelectorAll('input[name="gpm_' + f.showIf.id + '"]').forEach(function(r) {
            r.addEventListener('change', function() {
                if (wrap) wrap.style.display = f.showIf.vals.indexOf(r.value) >= 0 ? 'block' : 'none';
            });
        });
    });

    var ov = document.getElementById('gpm-overlay');
    ov.style.display = 'flex';
    setTimeout(function(){ var ta = document.querySelector('#gpm-fields textarea'); if(ta) ta.focus(); }, 80);
}

function hidePipelineModal() {
    document.getElementById('gpm-overlay').style.display = 'none';
    _kPend = null;
    if (_kd) { _kd.classList.remove('dragging'); _kd = null; }
}

function collectModalData() {
    var data = {};
    document.querySelectorAll('#gpm-fields input[type=radio]:checked').forEach(function(r) {
        data[r.name.replace('gpm_','')] = r.value;
    });
    document.querySelectorAll('#gpm-fields select').forEach(function(s) {
        data[s.id.replace('gpm_','')] = s.value;
    });
    document.querySelectorAll('#gpm-fields textarea').forEach(function(t) {
        data[t.id.replace('gpm_','')] = t.value.trim();
    });
    document.querySelectorAll('#gpm-fields input[type=date]').forEach(function(d) {
        data[d.id.replace('gpm_','')] = d.value;
    });
    var cbGroups = {};
    document.querySelectorAll('#gpm-fields input[type=checkbox]:checked').forEach(function(cb) {
        var key = cb.name.replace('gpm_','').replace('[]','');
        if (!cbGroups[key]) cbGroups[key] = [];
        cbGroups[key].push(cb.value);
    });
    if (Object.keys(cbGroups).length) data.meta = cbGroups;
    return data;
}

function validateModal(cfg) {
    for (var i = 0; i < cfg.fields.length; i++) {
        var f = cfg.fields[i];
        if (!f.required) continue;
        if (f.type === 'radio') {
            if (!document.querySelector('input[name="gpm_' + f.id + '"]:checked')) {
                alert(f.label + ' seçimi zorunludur.'); return false;
            }
        } else if (f.type === 'select') {
            var sel = document.getElementById('gpm_' + f.id);
            if (!sel || !sel.value) { alert(f.label + ' seçimi zorunludur.'); return false; }
        }
    }
    return true;
}

function updateMovedBy(card, movedBy) {
    if (!movedBy) return;
    var el = card.querySelector('.pc-moved-by');
    if (el) {
        el.textContent = '↔ ' + movedBy;
    } else {
        var foot = card.querySelector('.pc-foot');
        if (foot) {
            var d = document.createElement('div');
            d.className = 'pc-moved-by';
            d.style.cssText = 'font-size:8px;color:#7c3aed;font-weight:600;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;';
            d.textContent = '↔ ' + movedBy;
            card.insertBefore(d, foot);
        }
    }
}

function doMove(guestId, guestName, oldStage, newStage, dz, formData) {
    var card = document.querySelector('#kanbanBoard .pipe-card[data-id="' + guestId + '"]');
    if (card) {
        var emp = dz.querySelector('.pipe-empty');
        dz.insertBefore(card, emp || null);
        if (emp) emp.remove();
        card.dataset.stage = newStage;
        card.classList.remove('dragging');
    }
    var oldDz = document.getElementById('kc-' + oldStage);
    if (oldDz && !oldDz.querySelector('.pipe-card')) {
        oldDz.insertAdjacentHTML('beforeend','<div class="pipe-empty"><div style="font-size:22px;opacity:.4;">📭</div><div>Aday yok</div></div>');
    }
    function bump(stg, d) {
        var z = document.getElementById('kc-' + stg);
        if (!z || !z.parentElement) return;
        var c = z.parentElement.querySelector('.pipe-cnt');
        if (c) c.textContent = Math.max(0, (parseInt(c.textContent)||0) + d);
    }
    bump(oldStage, -1);
    bump(newStage, +1);

    fetch('/manager/pipeline/kanban/' + guestId + '/move', {
        method:'PATCH',
        headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':_kcsrf,'X-Requested-With':'XMLHttpRequest'},
        body:JSON.stringify(Object.assign({stage:newStage}, formData))
    }).then(function(r) {
        var t = document.createElement('div');
        t.style.cssText = 'position:fixed;top:18px;right:18px;z-index:99999;padding:10px 18px;border-radius:9px;font-size:13px;font-weight:700;color:#fff;background:' + (r.ok?'#16a34a':'#dc2626');
        t.textContent = r.ok ? 'Kaydedildi' : 'Hata ' + r.status;
        document.body.appendChild(t);
        setTimeout(function(){ if(t.parentNode) t.parentNode.removeChild(t); }, 3000);
        if (!r.ok) setTimeout(function(){ location.reload(); }, 2500);
    }).catch(function(){ location.reload(); });
}

function kStart(card, e) {
    _kd = card;
    card.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', card.dataset.id || '');
}
function kOver(col, e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    var dz = col.querySelector('.pipe-cards');
    if (dz) dz.classList.add('dov');
}
function kLeave(col, e) {
    if (!col.contains(e.relatedTarget)) {
        var dz = col.querySelector('.pipe-cards');
        if (dz) dz.classList.remove('dov');
    }
}
function kDrop(col, e) {
    e.preventDefault();
    var dz = col.querySelector('.pipe-cards');
    if (dz) dz.classList.remove('dov');
    if (!_kd) return;

    var guestId   = _kd.dataset.id;
    var guestName = _kd.dataset.name || '';
    var oldStage  = _kd.dataset.stage;
    var newStage  = dz ? dz.dataset.stage : '';

    if (!guestId || !newStage || newStage === oldStage) {
        _kd.classList.remove('dragging'); _kd = null; return;
    }

    var card = _kd;
    _kd = null;
    showPipelineModal(guestId, guestName, oldStage, newStage, dz);
}
document.addEventListener('dragend', function() {
    if (_kd) { _kd.classList.remove('dragging'); _kd = null; }
    document.querySelectorAll('.pipe-cards.dov').forEach(function(el){ el.classList.remove('dov'); });
});
</script>

{{-- Header --}}
<div style="background:linear-gradient(to right,#4f46e5,#7c3aed);border-radius:14px;padding:16px 20px;margin-bottom:16px;color:#fff;">
    <div style="font-size:18px;font-weight:800;margin-bottom:4px;">🌀 Aday Öğrenci Pipeline Kanban</div>
    <div style="font-size:11px;opacity:.8;margin-bottom:12px;">Sürükle &amp; bırak ile aşamaları güncelleyin</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        @foreach([['Toplam',$stats['total'],'rgba(255,255,255,.15)'],['Aktif',$stats['open'],'rgba(255,255,255,.1)'],['HOT',$stats['hot'],'rgba(220,38,38,.25)'],['Dönüştürülen',$stats['converted'],'rgba(5,150,105,.25)'],['Kaybedilen',$stats['lost'],'rgba(107,114,128,.25)']] as [$lbl,$val,$bg])
        <div style="background:{{ $bg }};border-radius:8px;padding:5px 12px;text-align:center;">
            <div style="font-size:17px;font-weight:800;line-height:1;">{{ $val }}</div>
            <div style="font-size:9px;opacity:.8;margin-top:1px;">{{ $lbl }}</div>
        </div>
        @endforeach
    </div>
</div>

{{-- Tabs (Manager) — premium analiz tabları Marketing Admin'de --}}
<div style="display:flex;gap:6px;margin-bottom:12px;flex-wrap:wrap;align-items:center;">
    <a href="/manager/guests" style="padding:5px 12px;font-size:11px;font-weight:600;border-radius:7px;border:1.5px solid var(--u-line);background:var(--u-card);color:var(--u-muted);text-decoration:none;">👤 Aday Listesi</a>
    <a href="/manager/pipeline/kanban" style="padding:5px 12px;font-size:11px;font-weight:600;border-radius:7px;border:1.5px solid #7c3aed;background:#ede9fe;color:#7c3aed;text-decoration:none;">🗂 Kanban</a>
    @if(\App\Support\ModuleAccess::enabled('marketing_admin'))
    <a href="/mktg-admin/pipeline" style="padding:5px 12px;font-size:11px;font-weight:600;border-radius:7px;border:1.5px solid var(--u-line);background:var(--u-card);color:var(--u-muted);text-decoration:none;">📊 Premium Analiz →</a>
    @endif
</div>


{{-- Board --}}
<div class="pipe-wrap">
    <div class="pipe-board" id="kanbanBoard">
        @foreach($columns as $col)
        @php $cc = $colColors[$col['code']] ?? '#7c3aed'; $ro = $col['readonly']; @endphp
        <div class="pipe-col {{ $ro ? 'ro' : '' }}">

            <div class="pipe-col-head" style="border-top:3px solid {{ $cc }};">
                <div>
                    <div class="pipe-col-title" style="color:{{ $cc }};">{{ $col['label'] }}</div>
                    @if($ro)<div style="font-size:8px;color:var(--u-muted);margin-top:1px;">salt okunur</div>@endif
                </div>
                <span class="pipe-cnt" style="background:{{ $cc }}18;color:{{ $cc }};">{{ count($col['cards']) }}</span>
            </div>

            <div class="pipe-cards" id="kc-{{ $col['code'] }}" data-stage="{{ $col['code'] }}">
                @foreach($col['cards'] as $ci => $card)
                @php
                    $fn  = trim($card->first_name.' '.$card->last_name);
                    $ini = strtoupper(mb_substr(preg_replace('/[^A-Za-zÇçĞğİıÖöŞşÜü]/u','',$fn),0,2));
                    $avc = $avatarColors[$ci % count($avatarColors)];
                    $tc  = $tierColors[strtolower((string)$card->lead_score_tier)] ?? null;
                    $tl  = $typeLabels[$card->application_type] ?? $card->application_type;
                @endphp
                <div class="pipe-card"
                     draggable="true"
                     data-id="{{ $card->id }}"
                     data-name="{{ $fn }}"
                     data-stage="{{ $col['code'] }}">
                    <div class="pc-row">
                        <div class="pc-av" style="background:{{ $avc }};">{{ $ini ?: '?' }}</div>
                        <div class="pc-name">{{ $fn }}</div>
                    </div>
                    <div class="pc-meta">{{ $card->application_country ?? '—' }} · {{ $tl }}</div>
                    @if($tc)
                    <div style="margin-bottom:4px;">
                        <span style="font-size:9px;font-weight:700;padding:1px 6px;border-radius:4px;background:{{ $tc['bg'] }};color:{{ $tc['fg'] }};">{{ strtoupper(strtolower((string)$card->lead_score_tier)) }}</span>
                    </div>
                    @endif
                    <div style="font-size:8px;color:var(--u-muted);margin-bottom:3px;">{{ $card->updated_at?->diffForHumans() ?? '—' }}</div>
                    @if($card->pipeline_moved_by)
                    <div style="font-size:8px;color:#7c3aed;font-weight:600;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="Son taşıma: {{ $card->pipeline_moved_by }}">
                        ↔ {{ $card->pipeline_moved_by }}
                    </div>
                    @endif
                    <div class="pc-foot">
                        <a href="/manager/guests/{{ $card->id }}"
                           style="font-size:10px;padding:3px 8px;border:1px solid var(--u-line);border-radius:6px;background:var(--u-card);color:var(--u-text);text-decoration:none;font-weight:600;flex:1;text-align:center;">Detay &rarr;</a>
                    </div>
                </div>
                @endforeach

                @if(count($col['cards']) === 0)
                <div class="pipe-empty"><div style="font-size:22px;opacity:.4;">📭</div><div>Aday yok</div></div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var board = document.getElementById('kanbanBoard');
    if (!board) return;

    board.addEventListener('dragover',  function(e){ e.preventDefault(); });
    board.addEventListener('dragenter', function(e){ e.preventDefault(); });

    board.querySelectorAll('.pipe-card[draggable="true"]').forEach(function(card) {
        card.addEventListener('dragover',  function(e){ e.preventDefault(); });
        card.addEventListener('dragstart', function(e){ kStart(card, e); });
    });

    board.querySelectorAll('.pipe-col').forEach(function(col) {
        col.addEventListener('dragover',  function(e){ kOver(col, e); });
        col.addEventListener('dragleave', function(e){ kLeave(col, e); });
        col.addEventListener('drop',      function(e){ kDrop(col, e); });
    });

    /* ── Modal butonları ── */
    document.getElementById('gpm-cancel').addEventListener('click', hidePipelineModal);
    document.getElementById('gpm-overlay').addEventListener('click', function(e){
        if (e.target === this) hidePipelineModal();
    });
    document.getElementById('gpm-confirm').addEventListener('click', function(){
        if (!_kPend) return;
        var cfg = STAGE_CFG[_kPend.newStage];
        if (cfg && !validateModal(cfg)) return;
        var data = collectModalData();
        var pend = _kPend;
        hidePipelineModal();
        doMove(pend.guestId, pend.guestName, pend.oldStage, pend.newStage, pend.dz, data);
    });

    /* ── Polling: 30s'de bir DB'den stage değişikliklerini çek ── */
    var _pollActive = false;
    function pollPipeline() {
        if (_kd || _kPend) return;
        if (_pollActive) return;
        _pollActive = true;
        fetch('/manager/pipeline/kanban/poll', {
            headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}
        }).then(function(r){ return r.ok ? r.json() : null; }).then(function(data) {
            if (!data) return;
            data.forEach(function(g) {
                var card = board.querySelector('.pipe-card[data-id="' + g.id + '"]');
                if (!card) return;
                var currentStage = card.dataset.stage;
                if (currentStage === g.lead_status) {
                    updateMovedBy(card, g.pipeline_moved_by);
                    return;
                }
                var newDz = document.getElementById('kc-' + g.lead_status);
                if (!newDz) return;
                var oldDz = document.getElementById('kc-' + currentStage);

                updateMovedBy(card, g.pipeline_moved_by);

                var emp = newDz.querySelector('.pipe-empty');
                newDz.insertBefore(card, emp || null);
                if (emp) emp.remove();
                card.dataset.stage = g.lead_status;

                if (oldDz && !oldDz.querySelector('.pipe-card')) {
                    oldDz.insertAdjacentHTML('beforeend','<div class="pipe-empty"><div style="font-size:22px;opacity:.4;">📭</div><div>Aday yok</div></div>');
                }

                function bumpCol(dzId, delta) {
                    var dz = document.getElementById(dzId);
                    if (!dz) return;
                    var col = dz.closest('.pipe-col');
                    if (!col) return;
                    var cnt = col.querySelector('.pipe-cnt');
                    if (cnt) cnt.textContent = Math.max(0, (parseInt(cnt.textContent)||0) + delta);
                }
                bumpCol('kc-' + currentStage, -1);
                bumpCol('kc-' + g.lead_status, +1);
            });
        }).catch(function(){}).finally(function(){ _pollActive = false; });
    }

    setInterval(pollPipeline, 30000);
}());
</script>
@endsection
