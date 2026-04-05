@php
use App\Models\CompanyBulletin;
$catLabels = CompanyBulletin::$categoryLabels;
$catEmojis = ['genel'=>'📢','duyuru'=>'📋','acil'=>'🚨','ik'=>'🌿','kutlama'=>'🎉','motivasyon'=>'✨'];
$rotations = [-3,-1,-2,1,2,1,-1,3,0,-2];
$unreadSet = array_flip($readIds);
$acilList  = $bulletins->where('category','acil');
@endphp

<style>
/* ── Board zemin — portal renkleriyle uyumlu açık linen ─────────── */
.emb-board {
    border-radius: 16px;
    padding: 20px 20px 32px;
    background: #f0ebe3;
    background-image:
        url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='60'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.65' numOctaves='3' stitchTiles='stitch'/%3E%3CfeColorMatrix type='saturate' values='0'/%3E%3C/filter%3E%3Crect width='60' height='60' filter='url(%23n)' opacity='.04'/%3E%3C/svg%3E");
    border: 1px solid #ddd5c8;
    box-shadow: 0 2px 16px rgba(0,0,0,.08), inset 0 1px 0 rgba(255,255,255,.6);
}

/* ── Toolbar ─────────────────────────────────────────────────────── */
.emb-toolbar {
    display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:16px;
}
.emb-title {
    font-size:14px; font-weight:800; color:#5c4a38;
    display:flex; align-items:center; gap:6px;
}
.emb-pills  { display:flex; gap:5px; flex-wrap:wrap; margin-left:auto; }
.emb-pill {
    padding:3px 11px; border-radius:999px; font-size:11px; font-weight:700;
    background:rgba(92,74,56,.08); color:#5c4a38;
    border:1.5px solid rgba(92,74,56,.15); cursor:pointer; transition:all .15s;
}
.emb-pill:hover  { background:rgba(92,74,56,.15); }
.emb-pill.active { background:#5c4a38; color:#fff; border-color:#5c4a38; }

/* ── Masonry ─────────────────────────────────────────────────────── */
.emb-grid { columns:3 260px; column-gap:16px; }
@media (max-width:900px) { .emb-grid { columns:2 240px; } }
@media (max-width:520px) { .emb-grid { columns:1; } }

/* ── Post-it ─────────────────────────────────────────────────────── */
.epi {
    break-inside:avoid; display:inline-block; width:100%;
    margin-bottom:16px; padding:20px 14px 12px;
    border-radius:2px 2px 6px 6px; position:relative;
    box-shadow:2px 3px 0 rgba(0,0,0,.10), 4px 6px 14px rgba(0,0,0,.14);
    transition:transform .18s, box-shadow .18s; cursor:default;
    background-image:linear-gradient(135deg,transparent 89%,rgba(0,0,0,.07) 89%,rgba(0,0,0,.07) 93%,rgba(0,0,0,.03) 93%);
}
.epi:hover {
    transform:scale(1.03) rotate(0deg)!important;
    box-shadow:3px 5px 0 rgba(0,0,0,.12), 6px 10px 22px rgba(0,0,0,.20);
    z-index:20;
}
.epi.unread {
    box-shadow:2px 3px 0 rgba(0,0,0,.10), 4px 6px 14px rgba(0,0,0,.14),
               0 0 0 2.5px rgba(255,255,255,.8);
}

.epi.genel      { background-color:#fef9c3; }
.epi.duyuru     { background-color:#dbeafe; }
.epi.acil       { background-color:#fee2e2; }
.epi.ik         { background-color:#dcfce7; }
.epi.kutlama    { background-color:#ffedd5; }
.epi.motivasyon { background-color:#ede9fe; }

/* ── Raptiye ─────────────────────────────────────────────────────── */
.epi-pin {
    position:absolute; top:-10px; left:50%; transform:translateX(-50%);
    width:18px; height:18px; border-radius:50%; z-index:3;
    box-shadow:0 2px 4px rgba(0,0,0,.3), inset 2px 2px 3px rgba(255,255,255,.35);
}
.epi-pin::before {
    content:''; position:absolute; top:3px; left:4px;
    width:4px; height:4px; border-radius:50%; background:rgba(255,255,255,.55);
}
.epi-pin::after {
    content:''; position:absolute; bottom:-11px; left:50%;
    transform:translateX(-50%); width:2.5px; height:12px;
    border-radius:0 0 2px 2px; background:rgba(0,0,0,.18);
}
.epi.unread .epi-pin { background:radial-gradient(circle at 35% 30%,#f87171,#dc2626,#991b1b); }
.epi.read   .epi-pin { background:radial-gradient(circle at 35% 30%,#d1d5db,#9ca3af,#6b7280); }
.epi.acil   .epi-pin { background:radial-gradient(circle at 35% 30%,#f87171,#b91c1c,#7f1d1d)!important; }
.epi.kutlama .epi-pin{ background:radial-gradient(circle at 35% 30%,#fb923c,#ea580c,#9a3412)!important; }
.epi.motivasyon .epi-pin{ background:radial-gradient(circle at 35% 30%,#a78bfa,#7c3aed,#4c1d95)!important; }

/* ── İşaretler ───────────────────────────────────────────────────── */
.epi-new  { position:absolute; top:8px; right:9px; width:7px; height:7px; border-radius:50%; background:#ef4444; box-shadow:0 0 0 2px rgba(255,255,255,.8); }
.epi-done { position:absolute; top:7px; right:9px; font-size:9px; color:rgba(0,0,0,.25); font-weight:700; }
.epi-pin-badge { position:absolute; top:7px; left:8px; font-size:9px; color:rgba(0,0,0,.30); }

/* ── İçerik ──────────────────────────────────────────────────────── */
.epi-cat   { font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.7px; color:rgba(0,0,0,.48); margin-bottom:6px; }
.epi-title { font-size:16px; font-weight:800; color:rgba(0,0,0,.82); line-height:1.4; margin-bottom:10px; word-break:break-word; }
.epi.read .epi-title { opacity:.52; }
.epi-body  { font-size:14px; color:rgba(0,0,0,.72); line-height:1.75; white-space:pre-wrap; word-break:break-word; overflow:hidden; display:-webkit-box; -webkit-line-clamp:4; -webkit-box-orient:vertical; }
.epi-body.expanded { -webkit-line-clamp:unset; display:block; }
.epi-more  { font-size:13px; font-weight:700; color:rgba(0,0,0,.40); background:none; border:none; padding:3px 0 0; cursor:pointer; text-decoration:underline dotted; }

.epi-meta  { font-size:12px; color:rgba(0,0,0,.45); margin-top:10px; display:flex; align-items:center; gap:5px; flex-wrap:wrap; }
.epi-av    { width:22px; height:22px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:9px; font-weight:900; color:#fff; background:rgba(0,0,0,.28); flex-shrink:0; }

/* ── Reaksiyonlar ────────────────────────────────────────────────── */
.epi-rxns { display:flex; gap:5px; flex-wrap:wrap; margin-top:12px; padding-top:10px; border-top:1px solid rgba(0,0,0,.09); }
.epi-rxn  { display:inline-flex; align-items:center; gap:4px; padding:4px 10px; border-radius:999px; font-size:14px; border:1.5px solid rgba(0,0,0,.10); background:rgba(255,255,255,.55); cursor:pointer; transition:all .12s; user-select:none; font-family:inherit; }
.epi-rxn:hover { background:rgba(255,255,255,.88); transform:scale(1.12); }
.epi-rxn.mine  { background:rgba(0,0,0,.12); border-color:rgba(0,0,0,.25); font-weight:800; }
.epi-rxn .erc  { font-size:11px; color:rgba(0,0,0,.35); font-weight:700; min-width:5px; }

/* ── Acil şeridi ─────────────────────────────────────────────────── */
.emb-acil {
    background:#fef2f2; border:1.5px solid #fca5a5; border-radius:10px;
    padding:9px 14px; margin-bottom:14px;
    display:flex; align-items:center; gap:8px;
    font-weight:700; font-size:12px; color:#dc2626;
}
.emb-acil a { color:#dc2626; margin-left:auto; font-weight:600; text-decoration:underline; }
</style>

<div class="emb-board">

    {{-- Toolbar --}}
    <div class="emb-toolbar">
        @php $totalUnread = count(array_diff($bulletins->pluck('id')->all(), $readIds)); @endphp
        <span class="emb-title">
            📌
            @if($totalUnread > 0)
                <span>{{ $totalUnread }} yeni duyuru</span>
                <span style="background:#ef4444;color:#fff;font-size:10px;font-weight:800;border-radius:999px;padding:1px 7px;">{{ $totalUnread }}</span>
            @else
                <span style="color:#9b8c7e;">Tümü okundu</span>
            @endif
        </span>
        <div class="emb-pills">
            <span class="emb-pill active" onclick="embFilter('all',this)">Tümü</span>
            @foreach($catLabels as $key => $label)
            @if($bulletins->where('category',$key)->isNotEmpty())
            <span class="emb-pill" onclick="embFilter('{{ $key }}',this)">{{ $catEmojis[$key]??'' }} {{ $label }}</span>
            @endif
            @endforeach
        </div>
    </div>

    {{-- Acil --}}
    @if($acilList->isNotEmpty())
    <div class="emb-acil">
        <span>🚨</span>
        <span>{{ $acilList->count() }} acil duyuru!</span>
        <a href="#" onclick="embFilter('acil',event.target);return false;">Sadece göster →</a>
    </div>
    @endif

    {{-- Grid --}}
    @if($bulletins->isEmpty())
    <div style="text-align:center;padding:40px;color:#9b8c7e;">
        <div style="font-size:32px;margin-bottom:8px;">📭</div>
        <div style="font-size:13px;">Henüz duyuru yok.</div>
    </div>
    @else
    <div class="emb-grid" id="emb-grid">
    @foreach($bulletins as $b)
    @php
        $isUnread = !isset($unreadSet[$b->id]);
        $rot      = $rotations[$b->id % 10];
        $myEmoji  = $myReactions[$b->id] ?? null;
        $rxnCounts = $b->reactions->groupBy('emoji')->map->count();
        $rxnAll   = CompanyBulletin::REACTIONS;
        $aName    = $b->author?->name ?? 'Yönetim';
        $ini      = collect(explode(' ',$aName))->take(2)->map(fn($w)=>mb_strtoupper(mb_substr($w,0,1)))->implode('');
    @endphp
    <div class="epi {{ $b->category }} {{ $isUnread?'unread':'read' }}"
         id="emb-{{ $b->id }}" data-cat="{{ $b->category }}"
         style="transform:rotate({{ $rot }}deg);">
        <div class="epi-pin"></div>
        @if($isUnread)<div class="epi-new" id="embdot-{{ $b->id }}"></div>
        @else<div class="epi-done" id="embdot-{{ $b->id }}">✓</div>@endif
        @if($b->is_pinned)<div class="epi-pin-badge">📌</div>@endif
        <div class="epi-cat">{{ $catEmojis[$b->category]??'📢' }} {{ $catLabels[$b->category]??$b->category }}</div>
        <div class="epi-title" onclick="embRead({{ $b->id }},this.closest('.epi'))">{{ $b->title }}</div>
        <div class="epi-body" id="embc-{{ $b->id }}" onclick="embRead({{ $b->id }},this.closest('.epi'))">{{ $b->body }}</div>
        @if(mb_strlen($b->body)>160)
        <button class="epi-more" onclick="event.stopPropagation();embExpand({{ $b->id }},this)">devamı ▾</button>
        @endif
        <div class="epi-meta">
            <span class="epi-av">{{ $ini }}</span>
            <span>{{ $aName }}</span>
            <span style="margin-left:auto;">{{ $b->published_at->format('d.m') }}</span>
        </div>
        <div class="epi-rxns" id="embr-{{ $b->id }}">
            @foreach($rxnAll as $i => $rxnEmo)
            @php $cnt=$rxnCounts[$rxnEmo]??0; @endphp
            <button class="epi-rxn {{ $myEmoji===$rxnEmo?'mine':'' }}" data-emoji="{{ $rxnEmo }}"
                    onclick="embReact({{ $b->id }},'{{ $rxnEmo }}',this)">
                <span>{{ $rxnEmo }}</span><span class="erc">{{ $cnt>0?$cnt:'' }}</span>
            </button>
            @endforeach
        </div>
    </div>
    @endforeach
    </div>
    @endif
</div>

<script>
(function(){
var _c=document.querySelector('meta[name=csrf-token]')?.content??'';
window.embFilter=function(cat,pill){
    document.querySelectorAll('.emb-pill').forEach(p=>p.classList.remove('active'));
    if(pill&&pill.classList&&pill.classList.contains('emb-pill'))pill.classList.add('active');
    document.querySelectorAll('#emb-grid .epi').forEach(function(c){
        c.style.display=(cat==='all'||c.dataset.cat===cat)?'':'none';
    });
};
window.embExpand=function(id,btn){
    var el=document.getElementById('embc-'+id);if(!el)return;
    el.classList.toggle('expanded');
    btn.textContent=el.classList.contains('expanded')?'daha az ▴':'devamı ▾';
};
window.embRead=function(id,card){
    if(!card||card.classList.contains('read'))return;
    fetch('/bulletins/'+id+'/read',{method:'POST',headers:{'X-CSRF-TOKEN':_c,'Accept':'application/json'}})
    .then(function(r){
        if(!r.ok)return;
        card.classList.remove('unread');card.classList.add('read');
        var d=document.getElementById('embdot-'+id);
        if(d){d.className='epi-done';d.textContent='✓';}
        var b=document.querySelector('.sidebar-bulletin-badge');
        if(b){var n=parseInt(b.textContent)-1;n<=0?b.remove():b.textContent=n;}
    });
};
window.embReact=function(id,emoji,btn){
    fetch('/bulletins/'+id+'/react',{method:'POST',
        headers:{'X-CSRF-TOKEN':_c,'Accept':'application/json','Content-Type':'application/json'},
        body:JSON.stringify({emoji:emoji})
    }).then(r=>r.json()).then(function(data){
        if(!data.ok)return;
        document.querySelectorAll('#embr-'+id+' .epi-rxn').forEach(function(b){
            var e=b.dataset.emoji;b.classList.toggle('mine',data.myEmoji===e);
            var rc=b.querySelector('.erc');if(rc)rc.textContent=(data.counts&&data.counts[e])?data.counts[e]:'';
        });
        var card=document.getElementById('emb-'+id);if(card)embRead(id,card);
    });
};
})();
</script>
