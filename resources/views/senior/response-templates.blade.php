@extends('senior.layouts.app')
@section('title','Şablon Yanıtlar')
@section('page_title','Şablon Yanıtlar')

@push('head')
<style>
/* ── Layout ── */
.rt-wrap    { display:grid; grid-template-columns:220px 1fr; gap:16px; align-items:start; }
.rt-sidebar { position:sticky; top:16px; }
.rt-main    { min-width:0; }

/* ── Sidebar ── */
.rt-side-card { background:var(--u-card); border:1px solid var(--u-line); border-radius:12px; overflow:hidden; }
.rt-side-head { padding:12px 14px; border-bottom:1px solid var(--u-line); font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:var(--u-muted); }
.rt-nav-item  { display:flex; align-items:center; justify-content:space-between; padding:9px 14px; cursor:pointer; border-left:3px solid transparent; transition:all .12s; font-size:13px; font-weight:600; color:var(--u-text); }
.rt-nav-item:hover  { background:var(--u-bg); }
.rt-nav-item.active { background:var(--u-bg); border-left-color:var(--rt-color); color:var(--rt-color); }
.rt-nav-cnt   { font-size:11px; font-weight:700; background:var(--u-line); border-radius:20px; padding:1px 7px; color:var(--u-muted); }
.rt-nav-item.active .rt-nav-cnt { background:color-mix(in srgb,var(--rt-color) 15%,transparent); color:var(--rt-color); }

.rt-side-stats { padding:12px 14px; border-top:1px solid var(--u-line); display:flex; gap:8px; }
.rt-mini-stat  { flex:1; text-align:center; }
.rt-mini-n     { font-size:18px; font-weight:800; color:var(--u-brand); line-height:1; }
.rt-mini-l     { font-size:10px; color:var(--u-muted); font-weight:600; text-transform:uppercase; margin-top:2px; }

/* ── Toolbar ── */
.rt-toolbar { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px; flex-wrap:wrap; }
.rt-search  { flex:1; min-width:160px; font-size:13px; }

/* ── Add panel ── */
.rt-add-panel { background:var(--u-card); border:1.5px solid var(--u-brand); border-radius:12px; padding:16px; margin-bottom:16px; animation:rtSlide .16s ease; }
@keyframes rtSlide { from{opacity:0;transform:translateY(-6px)} to{opacity:1;transform:translateY(0)} }

/* ── Section header ── */
.rt-section-head { display:flex; align-items:center; gap:8px; margin-bottom:10px; padding:8px 12px; border-radius:8px; border-left:3px solid var(--rt-color); background:color-mix(in srgb,var(--rt-color) 6%,transparent); }
.rt-section-label{ font-size:12px; font-weight:800; letter-spacing:.03em; color:var(--rt-color); }
.rt-section-count{ font-size:11px; font-weight:700; color:color-mix(in srgb,var(--rt-color) 80%,transparent); }

/* ── Grid ── */
.rt-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:24px; }

/* ── Card ── */
.rt-card { background:var(--u-card); border:1px solid var(--u-line); border-radius:10px; overflow:hidden; display:flex; flex-direction:column; transition:box-shadow .15s,border-color .15s,transform .12s; }
.rt-card:hover { box-shadow:0 3px 14px rgba(0,0,0,.08); border-color:#c7d2e8; transform:translateY(-1px); }
.rt-card-accent { height:3px; }
.rt-card-body { padding:12px 14px; flex:1; cursor:pointer; }
.rt-card-title{ font-weight:700; font-size:13px; line-height:1.4; margin-bottom:5px; }
.rt-card-preview { font-size:11.5px; color:var(--u-muted); line-height:1.5; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }
.rt-card-footer { padding:8px 12px; border-top:1px solid var(--u-line); background:var(--u-bg); display:flex; align-items:center; justify-content:space-between; }
.rt-usage { font-size:10px; color:var(--u-muted); font-weight:600; }
.rt-actions { display:flex; gap:4px; }
.rt-btn     { padding:3px 9px; border-radius:6px; font-size:11px; font-weight:700; cursor:pointer; border:1.5px solid var(--u-line); background:var(--u-card); color:var(--u-text); transition:all .12s; }
.rt-btn:hover  { background:#f0f4ff; border-color:#a5b4fc; color:#3b1a6e; }
.rt-btn.copy   { border-color:#7c3aed33; background:#7c3aed08; color:#7c3aed; }
.rt-btn.copy:hover { background:#7c3aed18; }
.rt-btn.del    { color:#dc2626; border-color:#fca5a533; background:#fca5a508; }
.rt-btn.del:hover  { background:#fca5a518; }

/* ── Expanded / Edit ── */
.rt-body-panel { display:none; padding:12px 14px; border-top:1px solid var(--u-line); background:var(--u-bg); }
.rt-body-text  { font-size:12px; white-space:pre-wrap; line-height:1.6; background:#fff; border:1px solid var(--u-line); border-radius:7px; padding:10px 12px; max-height:160px; overflow-y:auto; }
.rt-edit-form  { display:none; padding:12px 14px; border-top:1px solid var(--u-line); background:var(--u-bg); }

/* ── Empty ── */
.rt-empty { text-align:center; padding:48px 20px; color:var(--u-muted); }

@media(max-width:960px) { .rt-wrap { grid-template-columns:1fr; } .rt-sidebar { position:static; } .rt-grid { grid-template-columns:repeat(2,1fr); } }
@media(max-width:560px) { .rt-grid { grid-template-columns:1fr; } }
</style>
@endpush

@php
$catColors  = ['document'=>'#7c3aed','visa'=>'#2563eb','language'=>'#059669','housing'=>'#d97706','payment'=>'#dc2626','general'=>'#6b7280'];
$catIcons   = ['document'=>'📄','visa'=>'🛂','language'=>'🗣','housing'=>'🏠','payment'=>'💳','general'=>'💬'];
$total      = $grouped->flatten()->count();
$totalUsage = $grouped->flatten()->sum('usage_count');
@endphp

@section('content')
<div class="rt-wrap">

    {{-- ── Sidebar ── --}}
    <aside class="rt-sidebar">
        <div class="rt-side-card">
            <div class="rt-side-head">Kategoriler</div>
            <button class="rt-nav-item active" data-cat="all" style="width:100%;text-align:left;background:none;border:none;" onclick="filterCat('all',this)">
                <span>Tümü</span>
                <span class="rt-nav-cnt">{{ $total }}</span>
            </button>
            @foreach($categories as $key => $label)
                @php $cnt = isset($grouped[$key]) ? $grouped[$key]->count() : 0; @endphp
                @if($cnt > 0)
                <button class="rt-nav-item" data-cat="{{ $key }}" style="--rt-color:{{ $catColors[$key] }};width:100%;text-align:left;background:none;border:none;" onclick="filterCat('{{ $key }}',this)">
                    <span>{{ $catIcons[$key] ?? '' }} {{ $label }}</span>
                    <span class="rt-nav-cnt">{{ $cnt }}</span>
                </button>
                @endif
            @endforeach
            <div class="rt-side-stats">
                <div class="rt-mini-stat"><div class="rt-mini-n">{{ $total }}</div><div class="rt-mini-l">Şablon</div></div>
                <div class="rt-mini-stat"><div class="rt-mini-n">{{ $totalUsage }}</div><div class="rt-mini-l">Kullanım</div></div>
            </div>
        </div>
        <button class="btn" style="width:100%;margin-top:10px;" id="btnNew" onclick="toggleAddForm()">+ Yeni Şablon</button>
    </aside>

    {{-- ── Main ── --}}
    <div class="rt-main">

        {{-- Add form --}}
        <div id="addForm" style="display:none;">
            <div class="rt-add-panel">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                    <span style="font-weight:800;font-size:14px;color:var(--u-brand);">✨ Yeni Şablon</span>
                    <button class="btn alt" style="padding:3px 10px;font-size:12px;" onclick="toggleAddForm()">✕</button>
                </div>
                <form id="frmAdd" onsubmit="addTemplate(event)">
                    @csrf
                    <div class="grid2" style="gap:10px;margin-bottom:10px;">
                        <div>
                            <label style="font-size:11px;font-weight:700;display:block;margin-bottom:4px;text-transform:uppercase;color:var(--u-muted);">Başlık</label>
                            <input name="title" required maxlength="180" placeholder="Şablon başlığı" style="width:100%;box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="font-size:11px;font-weight:700;display:block;margin-bottom:4px;text-transform:uppercase;color:var(--u-muted);">Kategori</label>
                            <select name="category" required style="width:100%;box-sizing:border-box;">
                                @foreach($categories as $key => $label)
                                    <option value="{{ $key }}">{{ $catIcons[$key] ?? '' }} {{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div style="margin-bottom:10px;">
                        <label style="font-size:11px;font-weight:700;display:block;margin-bottom:4px;text-transform:uppercase;color:var(--u-muted);">
                            İçerik &nbsp;<span style="font-weight:400;text-transform:none;font-size:11px;">Değişkenler: <code style="background:#f1f5f9;padding:1px 5px;border-radius:4px;">@{{student_name}}</code> <code style="background:#f1f5f9;padding:1px 5px;border-radius:4px;">@{{university}}</code> <code style="background:#f1f5f9;padding:1px 5px;border-radius:4px;">@{{deadline}}</code></span>
                        </label>
                        <textarea name="body" required maxlength="2000" rows="4" placeholder="Mesaj metni..." style="width:100%;box-sizing:border-box;resize:vertical;"></textarea>
                    </div>
                    <button type="submit" class="btn" id="btnAddSave">Kaydet</button>
                </form>
            </div>
        </div>

        {{-- Toolbar --}}
        <div class="rt-toolbar">
            <input type="search" class="rt-search" id="rtSearch" placeholder="🔍 Şablon ara..." oninput="searchTemplates(this.value)">
        </div>

        {{-- Sections --}}
        @forelse($categories as $key => $label)
            @if(isset($grouped[$key]) && $grouped[$key]->count())
            <div class="rt-section" id="sec-{{ $key }}" data-section="{{ $key }}" style="--rt-color:{{ $catColors[$key] ?? '#6b7280' }};">
                <div class="rt-section-head">
                    <span style="font-size:15px;">{{ $catIcons[$key] ?? '' }}</span>
                    <span class="rt-section-label">{{ $label }}</span>
                    <span class="rt-section-count">{{ $grouped[$key]->count() }} şablon</span>
                </div>
                <div class="rt-grid">
                @foreach($grouped[$key] as $tpl)
                <div class="rt-card" id="tpl-{{ $tpl->id }}" data-cat="{{ $tpl->category }}" data-title="{{ strtolower($tpl->title) }}" data-body="{{ strtolower($tpl->body) }}">
                    <div class="rt-card-accent" style="background:{{ $catColors[$tpl->category] ?? '#6b7280' }};"></div>
                    <div class="rt-card-body" onclick="toggleBody({{ $tpl->id }})">
                        <div class="rt-card-title">{{ $tpl->title }}</div>
                        <div class="rt-card-preview">{{ $tpl->body }}</div>
                    </div>
                    <div class="rt-card-footer" onclick="event.stopPropagation()">
                        <span class="rt-usage">{{ $tpl->usage_count > 0 ? $tpl->usage_count.'× kullanıldı' : '' }}</span>
                        <div class="rt-actions">
                            <button class="rt-btn copy" id="copy-{{ $tpl->id }}" onclick="copyTemplate({{ $tpl->id }}, {{ json_encode($tpl->body) }})">📋 Kopyala</button>
                            <button class="rt-btn" onclick="toggleEdit({{ $tpl->id }})">✏️</button>
                            <button class="rt-btn del" onclick="deleteTemplate({{ $tpl->id }})">🗑</button>
                        </div>
                    </div>
                    <div class="rt-body-panel" id="body-{{ $tpl->id }}">
                        <div class="rt-body-text">{{ $tpl->body }}</div>
                        <div style="margin-top:8px;">
                            <button class="rt-btn copy" onclick="copyTemplate({{ $tpl->id }}, {{ json_encode($tpl->body) }})">📋 Kopyala</button>
                        </div>
                    </div>
                    <div class="rt-edit-form" id="edit-{{ $tpl->id }}">
                        <div style="display:flex;flex-direction:column;gap:7px;">
                            <input id="edit-title-{{ $tpl->id }}" value="{{ $tpl->title }}" maxlength="180" placeholder="Başlık" style="width:100%;box-sizing:border-box;">
                            <select id="edit-cat-{{ $tpl->id }}" style="width:100%;box-sizing:border-box;">
                                @foreach($categories as $ck => $cl)
                                    <option value="{{ $ck }}" {{ $tpl->category===$ck?'selected':'' }}>{{ $cl }}</option>
                                @endforeach
                            </select>
                            <textarea id="edit-body-{{ $tpl->id }}" rows="3" maxlength="2000" style="width:100%;box-sizing:border-box;resize:vertical;">{{ $tpl->body }}</textarea>
                            <div style="display:flex;gap:6px;">
                                <button class="btn" style="font-size:12px;padding:4px 12px;" onclick="saveEdit({{ $tpl->id }})">Kaydet</button>
                                <button class="btn alt" style="font-size:12px;padding:4px 12px;" onclick="toggleEdit({{ $tpl->id }})">İptal</button>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
                </div>
            </div>
            @endif
        @empty
        @endforelse

        <div id="rtNoResult" style="display:none;" class="rt-empty">
            <div style="font-size:40px;margin-bottom:10px;">🔍</div>
            <div style="font-weight:700;">Sonuç bulunamadı</div>
        </div>

        @if($grouped->isEmpty())
        <div class="rt-empty">
            <div style="font-size:40px;margin-bottom:10px;">📋</div>
            <div style="font-weight:700;">Henüz şablon yok</div>
            <div style="font-size:13px;margin-top:4px;">Sol taraftaki butona tıklayın.</div>
        </div>
        @endif
    </div>
</div>

<script>
const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

function toggleAddForm() {
    const f = document.getElementById('addForm');
    const open = f.style.display !== 'none';
    f.style.display = open ? 'none' : 'block';
    document.getElementById('btnNew').textContent = open ? '+ Yeni Şablon' : '✕ İptal';
    if (!open) f.querySelector('input[name=title]')?.focus();
}

async function addTemplate(e) {
    e.preventDefault();
    const btn = document.getElementById('btnAddSave');
    btn.textContent = 'Kaydediliyor…'; btn.disabled = true;
    const fd = new FormData(e.target);
    const res = await fetch('/senior/response-templates', {
        method:'POST',
        headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json','Content-Type':'application/json'},
        body: JSON.stringify(Object.fromEntries(fd))
    });
    if (res.ok) location.reload();
    else { btn.textContent='Kaydet'; btn.disabled=false; }
}

async function deleteTemplate(id) {
    if (!confirm('Bu şablonu silmek istiyor musunuz?')) return;
    const res = await fetch('/senior/response-templates/'+id, {
        method:'DELETE', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'}
    });
    if (res.ok) {
        const el = document.getElementById('tpl-'+id);
        el.style.transition='opacity .2s'; el.style.opacity='0';
        setTimeout(()=>el.remove(), 200);
    }
}

function toggleBody(id) {
    const b = document.getElementById('body-'+id);
    if (document.getElementById('edit-'+id).style.display!=='none') return;
    b.style.display = b.style.display==='none' ? 'block' : 'none';
}

function toggleEdit(id) {
    const e = document.getElementById('edit-'+id);
    document.getElementById('body-'+id).style.display = 'none';
    e.style.display = e.style.display==='none' ? 'block' : 'none';
}

async function saveEdit(id) {
    const res = await fetch('/senior/response-templates/'+id, {
        method:'PUT',
        headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json','Content-Type':'application/json'},
        body: JSON.stringify({
            title:    document.getElementById('edit-title-'+id).value,
            category: document.getElementById('edit-cat-'+id).value,
            body:     document.getElementById('edit-body-'+id).value,
        })
    });
    if (res.ok) location.reload();
}

function copyTemplate(id, body) {
    navigator.clipboard.writeText(body).then(() => {
        fetch('/senior/response-templates/'+id+'/use', {method:'POST', headers:{'X-CSRF-TOKEN':csrf}});
        const btn = document.getElementById('copy-'+id);
        if (btn) { const t=btn.textContent; btn.textContent='✅ Kopyalandı'; setTimeout(()=>btn.textContent=t,1800); }
        document.getElementById('body-'+id).style.display='none';
    });
}

function filterCat(cat, el) {
    document.querySelectorAll('.rt-nav-item').forEach(b=>b.classList.remove('active'));
    el.classList.add('active');
    document.querySelectorAll('.rt-section').forEach(s=>{
        s.style.display = (cat==='all' || s.dataset.section===cat) ? '' : 'none';
    });
    document.getElementById('rtSearch').value = '';
    checkEmpty();
}

function searchTemplates(q) {
    q = q.toLowerCase().trim();
    document.querySelectorAll('.rt-nav-item').forEach(b=>b.classList.remove('active'));
    document.querySelectorAll('.rt-card').forEach(c=>{
        c.style.display = (!q || c.dataset.title.includes(q) || c.dataset.body.includes(q)) ? '' : 'none';
    });
    document.querySelectorAll('.rt-section').forEach(s=>{
        s.style.display = [...s.querySelectorAll('.rt-card')].some(c=>c.style.display!=='none') ? '' : 'none';
    });
    checkEmpty();
}

function checkEmpty() {
    const any = [...document.querySelectorAll('.rt-card')].some(c=>c.style.display!=='none');
    document.getElementById('rtNoResult').style.display = any ? 'none' : 'block';
}
</script>
@endsection
