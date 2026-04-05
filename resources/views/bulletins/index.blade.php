@extends('layouts.staff')
@section('title', 'Duyurular')
@section('page_title', 'Duyuru Panosu')

@push('head')
<style>
/* ── Board zemin ──────────────────────────────────────────────────── */
.board-wrap {
    border-radius: 16px;
    padding: 20px 20px 36px;
    background: #f0ebe3;
    background-image:
        url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='60'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.65' numOctaves='3' stitchTiles='stitch'/%3E%3CfeColorMatrix type='saturate' values='0'/%3E%3C/filter%3E%3Crect width='60' height='60' filter='url(%23n)' opacity='.04'/%3E%3C/svg%3E");
    border: 1px solid #ddd5c8;
    box-shadow: 0 2px 16px rgba(0,0,0,.07), inset 0 1px 0 rgba(255,255,255,.6);
}

/* ── Toolbar ─────────────────────────────────────────────────────── */
.board-toolbar {
    display:flex; align-items:center; gap:8px;
    flex-wrap:wrap; margin-bottom:16px;
}
.board-title {
    font-size:15px; font-weight:800; color:#5c4a38;
    display:flex; align-items:center; gap:6px;
}
.board-pills { display:flex; gap:5px; flex-wrap:wrap; margin-left:auto; }
.board-pill {
    padding:4px 13px; border-radius:999px; font-size:11px; font-weight:700;
    background:rgba(92,74,56,.08); color:#5c4a38;
    border:1.5px solid rgba(92,74,56,.15); cursor:pointer; transition:all .15s;
}
.board-pill:hover  { background:rgba(92,74,56,.16); }
.board-pill.active { background:#5c4a38; color:#fff; border-color:#5c4a38; }

/* ── Masonry ─────────────────────────────────────────────────────── */
.postit-grid { columns:4 260px; column-gap:18px; }
@media (max-width:1100px) { .postit-grid { columns:3 240px; } }
@media (max-width:760px)  { .postit-grid { columns:2 220px; } }
@media (max-width:480px)  { .postit-grid { columns:1; } }

/* ── Post-it ─────────────────────────────────────────────────────── */
.postit {
    break-inside:avoid; display:inline-block; width:100%;
    margin-bottom:18px; padding:22px 15px 13px;
    border-radius:2px 2px 7px 7px; position:relative;
    box-shadow:2px 3px 0 rgba(0,0,0,.10), 4px 7px 16px rgba(0,0,0,.13);
    transition:transform .18s, box-shadow .18s; cursor:default;
    background-image:linear-gradient(135deg,transparent 89%,rgba(0,0,0,.07) 89%,rgba(0,0,0,.07) 93%,rgba(0,0,0,.03) 93%);
}
.postit:hover {
    transform:scale(1.03) rotate(0deg)!important;
    box-shadow:3px 5px 0 rgba(0,0,0,.12), 7px 12px 24px rgba(0,0,0,.18);
    z-index:20;
}
.postit.unread {
    box-shadow:2px 3px 0 rgba(0,0,0,.10), 4px 7px 16px rgba(0,0,0,.13),
               0 0 0 2.5px rgba(255,255,255,.85);
}
.postit.genel      { background-color:#fef9c3; }
.postit.duyuru     { background-color:#dbeafe; }
.postit.acil       { background-color:#fee2e2; }
.postit.ik         { background-color:#dcfce7; }
.postit.kutlama    { background-color:#ffedd5; }
.postit.motivasyon { background-color:#ede9fe; }

/* ── Raptiye ─────────────────────────────────────────────────────── */
.pin {
    position:absolute; top:-10px; left:50%; transform:translateX(-50%);
    width:19px; height:19px; border-radius:50%; z-index:3;
    box-shadow:0 2px 5px rgba(0,0,0,.28), inset 2px 2px 3px rgba(255,255,255,.35);
}
.pin::before { content:''; position:absolute; top:3px; left:4px; width:4px; height:4px; border-radius:50%; background:rgba(255,255,255,.55); }
.pin::after  { content:''; position:absolute; bottom:-12px; left:50%; transform:translateX(-50%); width:2.5px; height:13px; border-radius:0 0 2px 2px; background:rgba(0,0,0,.16); }
.postit.unread .pin { background:radial-gradient(circle at 35% 30%,#f87171,#dc2626,#991b1b); }
.postit.read   .pin { background:radial-gradient(circle at 35% 30%,#d1d5db,#9ca3af,#6b7280); }
.postit.acil   .pin { background:radial-gradient(circle at 35% 30%,#f87171,#b91c1c,#7f1d1d)!important; }
.postit.kutlama .pin{ background:radial-gradient(circle at 35% 30%,#fb923c,#ea580c,#9a3412)!important; }
.postit.motivasyon .pin{ background:radial-gradient(circle at 35% 30%,#a78bfa,#7c3aed,#4c1d95)!important; }

/* ── İşaretler ───────────────────────────────────────────────────── */
.pi-new  { position:absolute; top:9px; right:10px; width:8px; height:8px; border-radius:50%; background:#ef4444; box-shadow:0 0 0 2px rgba(255,255,255,.9); }
.pi-done { position:absolute; top:8px; right:10px; font-size:11px; color:rgba(0,0,0,.28); font-weight:700; }
.pi-pin-badge { position:absolute; top:8px; left:9px; font-size:11px; color:rgba(0,0,0,.35); }

/* ── İçerik ──────────────────────────────────────────────────────── */
.pi-cat   { font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.7px; color:rgba(0,0,0,.45); margin-bottom:6px; }
.pi-title { font-size:18px; font-weight:800; color:rgba(0,0,0,.82); line-height:1.4; margin-bottom:10px; word-break:break-word; }
.postit.read .pi-title { opacity:.55; }
.pi-body  { font-size:15px; color:rgba(0,0,0,.72); line-height:1.75; white-space:pre-wrap; word-break:break-word; overflow:hidden; display:-webkit-box; -webkit-line-clamp:5; -webkit-box-orient:vertical; }
.pi-body.expanded { -webkit-line-clamp:unset; display:block; }
.pi-expand { font-size:13px; font-weight:700; color:rgba(0,0,0,.40); background:none; border:none; padding:3px 0 0; cursor:pointer; text-decoration:underline dotted; }

.pi-meta { font-size:12px; color:rgba(0,0,0,.45); margin-top:10px; display:flex; align-items:center; gap:5px; flex-wrap:wrap; }
.pi-av   { width:22px; height:22px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:9px; font-weight:900; color:#fff; background:rgba(0,0,0,.28); flex-shrink:0; }

/* ── Reaksiyonlar ────────────────────────────────────────────────── */
.pi-rxns { display:flex; gap:5px; flex-wrap:wrap; margin-top:12px; padding-top:10px; border-top:1px solid rgba(0,0,0,.10); }
.pi-rxn  { display:inline-flex; align-items:center; gap:4px; padding:4px 10px; border-radius:999px; font-size:15px; border:1.5px solid rgba(0,0,0,.10); background:rgba(255,255,255,.55); cursor:pointer; transition:all .12s; user-select:none; font-family:inherit; }
.pi-rxn:hover { background:rgba(255,255,255,.88); transform:scale(1.12); }
.pi-rxn.mine  { background:rgba(0,0,0,.12); border-color:rgba(0,0,0,.25); font-weight:800; }
.pi-rxn .rc   { font-size:12px; color:rgba(0,0,0,.45); font-weight:700; min-width:6px; }

/* ── Acil ────────────────────────────────────────────────────────── */
.acil-band {
    background:#fef2f2; border:1.5px solid #fca5a5; border-radius:10px;
    padding:9px 14px; margin-bottom:14px;
    display:flex; align-items:center; gap:8px;
    font-weight:700; font-size:12px; color:#dc2626;
}
.acil-band a { color:#dc2626; margin-left:auto; text-decoration:underline; font-weight:600; }
</style>
@endpush

@section('content')
@php
use App\Models\CompanyBulletin;
$catLabels = CompanyBulletin::$categoryLabels;
$catEmojis = ['genel'=>'📢','duyuru'=>'📋','acil'=>'🚨','ik'=>'🌿','kutlama'=>'🎉','motivasyon'=>'✨'];
$rotations = [-3,-1,-2,1,2,1,-1,3,0,-2];
$unreadSet = array_flip($readIds);
$acilList  = $bulletins->where('category','acil');
$totalUnread = count(array_diff($bulletins->pluck('id')->all(), $readIds));
@endphp

<div class="board-wrap">

    {{-- Toolbar --}}
    <div class="board-toolbar">
        <div class="board-title">
            📌
            @if($totalUnread > 0)
            <span>{{ $totalUnread }} yeni duyuru</span>
            <span style="background:#ef4444;color:#fff;font-size:10px;font-weight:800;border-radius:999px;padding:1px 7px;">{{ $totalUnread }}</span>
            @else
            <span style="color:#9b8c7e;">Tümü okundu</span>
            @endif
        </div>
        <div class="board-pills">
            <span class="board-pill active" onclick="filterBlt('all',this)">Tümü <span style="opacity:.6;">{{ $bulletins->count() }}</span></span>
            @foreach($catLabels as $key => $label)
            @php $cnt = $bulletins->where('category',$key)->count(); @endphp
            @if($cnt > 0)
            <span class="board-pill" onclick="filterBlt('{{ $key }}',this)">{{ $catEmojis[$key]??'' }} {{ $label }} <span style="opacity:.6;">{{ $cnt }}</span></span>
            @endif
            @endforeach
        </div>
    </div>

    {{-- Acil --}}
    @if($acilList->isNotEmpty())
    <div class="acil-band">
        <span>🚨</span>
        <span>{{ $acilList->count() }} acil duyuru!</span>
        <a href="#" onclick="filterBlt('acil',document.querySelector('[onclick*=acil]'));return false;">Sadece göster →</a>
    </div>
    @endif

    {{-- Grid --}}
    @if($bulletins->isEmpty())
    <div style="text-align:center;padding:60px 20px;color:#9b8c7e;">
        <div style="font-size:40px;margin-bottom:10px;">📭</div>
        <div style="font-size:14px;">Henüz duyuru yok.</div>
    </div>
    @else
    <div class="postit-grid" id="blt-grid">
    @foreach($bulletins as $b)
    @php
        $isUnread  = !isset($unreadSet[$b->id]);
        $rot       = $rotations[$b->id % 10];
        $myEmoji   = $myReactions[$b->id] ?? null;
        $rxnCounts = $b->reactions->groupBy('emoji')->map->count();
        $rxnAll    = CompanyBulletin::REACTIONS;
        $aName     = $b->author?->name ?? 'Yönetim';
        $ini       = collect(explode(' ',$aName))->take(2)->map(fn($w)=>mb_strtoupper(mb_substr($w,0,1)))->implode('');
    @endphp
    <div class="postit {{ $b->category }} {{ $isUnread?'unread':'read' }}"
         id="blt-{{ $b->id }}" data-cat="{{ $b->category }}"
         style="transform:rotate({{ $rot }}deg);">
        <div class="pin"></div>
        @if($isUnread)<div class="pi-new" id="dot-{{ $b->id }}"></div>
        @else<div class="pi-done" id="dot-{{ $b->id }}">✓</div>@endif
        @if($b->is_pinned)<div class="pi-pin-badge">📌</div>@endif
        <div class="pi-cat">{{ $catEmojis[$b->category]??'📢' }} {{ $catLabels[$b->category]??$b->category }}</div>
        <div class="pi-title" onclick="markRead({{ $b->id }},this.closest('.postit'))">{{ $b->title }}</div>
        <div class="pi-body" id="bc-{{ $b->id }}" onclick="markRead({{ $b->id }},this.closest('.postit'))">{{ $b->body }}</div>
        @if(mb_strlen($b->body)>180)
        <button class="pi-expand" onclick="event.stopPropagation();expandNote({{ $b->id }},this)">devamı ▾</button>
        @endif
        <div class="pi-meta">
            <span class="pi-av">{{ $ini }}</span>
            <span>{{ $aName }}</span>
            <span style="margin-left:auto;">{{ $b->published_at->format('d.m.Y') }}</span>
        </div>
        <div class="pi-rxns" id="rxn-{{ $b->id }}">
            @foreach($rxnAll as $i => $rxnEmo)
            @php $cnt=$rxnCounts[$rxnEmo]??0; @endphp
            <button class="pi-rxn {{ $myEmoji===$rxnEmo?'mine':'' }}" data-emoji="{{ $rxnEmo }}"
                    onclick="doReact({{ $b->id }},'{{ $rxnEmo }}',this)">
                <span>{{ $rxnEmo }}</span><span class="rc">{{ $cnt>0?$cnt:'' }}</span>
            </button>
            @endforeach
        </div>
    </div>
    @endforeach
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
var _csrf=document.querySelector('meta[name=csrf-token]')?.content??'';
function filterBlt(cat,pill){
    document.querySelectorAll('.board-pill').forEach(p=>p.classList.remove('active'));
    if(pill&&pill.classList)pill.classList.add('active');
    document.querySelectorAll('#blt-grid .postit').forEach(function(c){ c.style.display=(cat==='all'||c.dataset.cat===cat)?'':'none'; });
}
function expandNote(id,btn){
    var el=document.getElementById('bc-'+id);if(!el)return;
    el.classList.toggle('expanded');btn.textContent=el.classList.contains('expanded')?'daha az ▴':'devamı ▾';
}
function markRead(id,card){
    if(!card||card.classList.contains('read'))return;
    fetch('/bulletins/'+id+'/read',{method:'POST',headers:{'X-CSRF-TOKEN':_csrf,'Accept':'application/json'}})
    .then(function(r){
        if(!r.ok)return;
        card.classList.remove('unread');card.classList.add('read');
        var d=document.getElementById('dot-'+id);if(d){d.className='pi-done';d.textContent='✓';}
        var b=document.querySelector('.sidebar-bulletin-badge');
        if(b){var n=parseInt(b.textContent)-1;n<=0?b.remove():b.textContent=n;}
    });
}
function doReact(id,emoji,btn){
    fetch('/bulletins/'+id+'/react',{method:'POST',
        headers:{'X-CSRF-TOKEN':_csrf,'Accept':'application/json','Content-Type':'application/json'},
        body:JSON.stringify({emoji:emoji})
    }).then(r=>r.json()).then(function(data){
        if(!data.ok)return;
        document.querySelectorAll('#rxn-'+id+' .pi-rxn').forEach(function(b){
            var e=b.dataset.emoji;b.classList.toggle('mine',data.myEmoji===e);
            var rc=b.querySelector('.rc');if(rc)rc.textContent=(data.counts&&data.counts[e])?data.counts[e]:'';
        });
        var card=document.getElementById('blt-'+id);if(card)markRead(id,card);
    });
}
setTimeout(function(){
    document.querySelectorAll('.postit.unread').forEach(function(c){ markRead(parseInt(c.id.replace('blt-','')),c); });
},4000);
</script>
@endpush
