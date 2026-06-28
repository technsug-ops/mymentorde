@extends('senior.layouts.app')
@section('title','Kayıt Süreci')
@section('page_title','Kayıt Süreci')

@section('content')
@php
    $docs     = $documents ?? collect();
    $total    = $docs->count();
    $approved = $docs->where('status','approved')->count();
    $rejected = $docs->where('status','rejected')->count();
    $uploaded = $docs->where('status','uploaded')->count();
    $activeQ  = $filters['q']      ?? '';
    $activeSt = $filters['status'] ?? 'all';
@endphp

{{-- Gradient header --}}
<div style="background:linear-gradient(to right,#6d28d9,#7c3aed);border-radius:14px;padding:20px 24px;margin-bottom:16px;color:#fff;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
    <div>
        <div style="font-size:var(--tx-xl);font-weight:800;letter-spacing:-.3px;">📋 Belge Onayları</div>
        <div style="font-size:var(--tx-sm);opacity:.8;margin-top:2px;">Atanan öğrencilerin yüklediği belgeler</div>
    </div>
    <div style="text-align:right;">
        <div style="font-size:32px;font-weight:800;line-height:1;">{{ $total }}</div>
        <div style="font-size:var(--tx-xs);opacity:.7;text-transform:uppercase;letter-spacing:.06em;">Toplam Belge</div>
    </div>
</div>

{{-- KPI strip --}}
<div class="kpi-row" style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:16px;">
    @foreach([
        ['label'=>'Toplam',     'val'=>$total,    'color'=>'#7c3aed','icon'=>'📋','st'=>'all'],
        ['label'=>'Onaylandı',  'val'=>$approved, 'color'=>'#16a34a','icon'=>'✅','st'=>'approved'],
        ['label'=>'Yüklendi',   'val'=>$uploaded, 'color'=>'#7c3aed','icon'=>'📤','st'=>'uploaded'],
        ['label'=>'Reddedildi', 'val'=>$rejected, 'color'=>'#dc2626','icon'=>'❌','st'=>'rejected'],
    ] as $k)
    @php $isA = $activeSt === $k['st']; @endphp
    <a href="?status={{ $k['st'] }}{{ $activeQ ? '&q='.urlencode($activeQ) : '' }}"
       style="background:{{ $isA ? $k['color'].'18' : 'var(--u-card)' }};border:{{ $isA ? '2px solid '.$k['color'] : '1px solid var(--u-line)' }};border-radius:10px;padding:12px 14px;text-align:center;text-decoration:none;display:block;transition:all .15s;"
       onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(0,0,0,.08)'"
       onmouseout="this.style.transform='';this.style.boxShadow=''">
        <div style="font-size:var(--tx-lg);line-height:1;">{{ $k['icon'] }}</div>
        <div style="font-size:var(--tx-xl);font-weight:800;color:{{ $k['color'] }};margin:4px 0 2px;line-height:1;">{{ $k['val'] }}</div>
        <div style="font-size:var(--tx-xs);color:{{ $isA ? $k['color'] : 'var(--u-muted)' }};font-weight:700;text-transform:uppercase;letter-spacing:.04em;">{{ $k['label'] }}</div>
        @if($isA)<div style="width:24px;height:3px;background:{{ $k['color'] }};border-radius:2px;margin:6px auto 0;"></div>@endif
    </a>
    @endforeach
</div>

{{-- Filter bar --}}
<form method="GET" style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:12px 14px;margin-bottom:14px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
    <input type="text" name="q" value="{{ $activeQ }}" placeholder="🔍  Öğrenci ID / doküman / dosya adı"
        style="flex:1;min-width:200px;border:1px solid var(--u-line);border-radius:7px;padding:8px 12px;font-size:var(--tx-sm);color:var(--u-text);background:var(--u-bg);">
    <select name="status" style="border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);color:var(--u-text);background:var(--u-bg);">
        <option value="all"      @selected($activeSt==='all')>Tüm Durumlar</option>
        <option value="uploaded" @selected($activeSt==='uploaded')>📤 Yüklendi</option>
        <option value="approved" @selected($activeSt==='approved')>✅ Onaylandı</option>
        <option value="rejected" @selected($activeSt==='rejected')>❌ Reddedildi</option>
    </select>
    <button type="submit" style="background:#7c3aed;color:#fff;border:none;border-radius:7px;padding:8px 18px;font-size:var(--tx-sm);font-weight:600;cursor:pointer;">Filtrele</button>
    <a href="{{ url('/senior/registration-documents') }}" style="color:var(--u-muted);font-size:var(--tx-sm);text-decoration:none;padding:8px 10px;border:1px solid var(--u-line);border-radius:7px;background:var(--u-bg);">Temizle</a>
</form>

{{-- Document list --}}
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;overflow:hidden;">
    @forelse($docs as $doc)
    @php
        $st      = (string) ($doc->status ?? '');
        $stColor = match($st) { 'approved' => '#16a34a', 'rejected' => '#dc2626', default => '#7c3aed' };
        $stBadge = match($st) { 'approved' => 'ok', 'rejected' => 'danger', default => 'info' };
        $stIcon  = match($st) { 'approved' => '✅', 'rejected' => '❌', default => '📤' };
    @endphp
    <div id="doc-row-{{ $doc->id }}" data-status="{{ $st }}" style="padding:14px 16px;border-bottom:1px solid var(--u-line);{{ $st==='rejected' ? 'border-left:3px solid #dc2626;' : '' }}transition:background .12s;"
         onmouseover="this.style.background='var(--u-bg)'" onmouseout="this.style.background=''">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:0;">
                <div class="doc-row-ico" style="width:36px;height:36px;border-radius:50%;background:{{ $stColor }}18;display:flex;align-items:center;justify-content:center;font-size:var(--tx-base);flex-shrink:0;">{{ $stIcon }}</div>
                <div style="min-width:0;">
                    <div style="font-weight:700;font-size:var(--tx-sm);color:var(--u-text);">🎓 {{ $doc->student_id }}</div>
                    <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:2px;">
                        Doküman: <span style="color:var(--u-text);font-weight:600;">{{ $doc->document_id ?? '—' }}</span>
                        @if($doc->standard_file_name)
                            &nbsp;·&nbsp;<span style="font-family:monospace;">{{ $doc->standard_file_name }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
                <div style="text-align:right;">
                    <span class="badge {{ $stBadge }} doc-row-badge">{{ ucfirst($st ?: '—') }}</span>
                    <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:4px;">{{ optional($doc->updated_at)->format('d.m.Y H:i') }}</div>
                </div>
                <a href="/senior/registration/documents/{{ $doc->id }}/download"
                   style="font-size:var(--tx-xs);padding:6px 13px;border:1px solid var(--u-line);border-radius:7px;background:var(--u-bg);color:var(--u-text);text-decoration:none;font-weight:600;white-space:nowrap;">⬇ İndir</a>
                {{-- #15: satır içi Onayla / Reddet (yüklenen veya red'den dönen için) --}}
                <div class="doc-row-actions" style="display:{{ $st==='approved' ? 'none' : 'flex' }};gap:6px;">
                    <button type="button" data-doc-approve="{{ $doc->id }}"
                        style="font-size:var(--tx-xs);padding:6px 13px;border:none;border-radius:7px;background:#16a34a;color:#fff;font-weight:700;cursor:pointer;white-space:nowrap;">✅ Onayla</button>
                    <button type="button" data-doc-reject="{{ $doc->id }}"
                        style="font-size:var(--tx-xs);padding:6px 13px;border:1px solid #dc2626;border-radius:7px;background:#fff;color:#dc2626;font-weight:700;cursor:pointer;white-space:nowrap;">❌ Reddet</button>
                </div>
            </div>
        </div>
        <div class="doc-row-note" style="margin-top:8px;font-size:var(--tx-xs);color:#b91c1c;{{ $doc->review_note ? '' : 'display:none;' }}">
            📝 <span class="doc-row-note-text">{{ $doc->review_note }}</span>
        </div>
    </div>
    @empty
    <div style="padding:56px 20px;text-align:center;color:var(--u-muted);">
        <div style="font-size:44px;margin-bottom:12px;">📋</div>
        <div style="font-size:var(--tx-base);font-weight:700;margin-bottom:4px;">Belge bulunamadı</div>
        <div style="font-size:var(--tx-sm);">Filtre kriterlerinizi değiştirmeyi deneyin.</div>
    </div>
    @endforelse
</div>

@if($total > 0)
<div style="text-align:right;font-size:var(--tx-xs);color:var(--u-muted);margin-top:8px;">
    {{ $total }} belge gösteriliyor
    @if($activeQ || $activeSt !== 'all')
        — <a href="{{ url('/senior/registration-documents') }}" style="color:#7c3aed;text-decoration:none;">filtreyi temizle</a>
    @endif
</div>
@endif

{{-- #15 — satır içi belge onay/red (AJAX, /senior/batch-review/{id}/action) --}}
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var CSRF = '{{ csrf_token() }}';
    function action(id, act, note){
        return fetch('/senior/batch-review/' + id + '/action', {
            method: 'POST',
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json' },
            body: JSON.stringify({ action: act, note: note || null })
        }).then(function(r){ return r.json(); });
    }
    function applyState(id, status, note){
        var row = document.getElementById('doc-row-' + id);
        if(!row) return;
        row.dataset.status = status;
        var badge = row.querySelector('.doc-row-badge');
        var ico   = row.querySelector('.doc-row-ico');
        var acts  = row.querySelector('.doc-row-actions');
        var noteWrap = row.querySelector('.doc-row-note');
        var noteText = row.querySelector('.doc-row-note-text');
        if(status === 'approved'){
            if(badge){ badge.textContent='Approved'; badge.className='badge ok doc-row-badge'; }
            if(ico) ico.textContent='✅';
            if(acts) acts.style.display='none';
            row.style.borderLeft='';
        } else if(status === 'rejected'){
            if(badge){ badge.textContent='Rejected'; badge.className='badge danger doc-row-badge'; }
            if(ico) ico.textContent='❌';
            if(acts) acts.style.display='flex';
            row.style.borderLeft='3px solid #dc2626';
        }
        if(note && noteWrap && noteText){ noteText.textContent=note; noteWrap.style.display=''; }
    }
    document.querySelectorAll('[data-doc-approve]').forEach(function(btn){
        btn.addEventListener('click', function(){
            var id = btn.getAttribute('data-doc-approve');
            btn.disabled = true; btn.textContent = '...';
            action(id, 'approve').then(function(res){
                if(res && res.ok){ applyState(id, 'approved'); }
                else { btn.disabled=false; btn.textContent='✅ Onayla'; alert('Onaylanamadı.'); }
            }).catch(function(){ btn.disabled=false; btn.textContent='✅ Onayla'; alert('Bağlantı hatası.'); });
        });
    });
    document.querySelectorAll('[data-doc-reject]').forEach(function(btn){
        btn.addEventListener('click', function(){
            var id = btn.getAttribute('data-doc-reject');
            var reason = prompt('Reddetme sebebi (öğrenci görecek):', '');
            if(reason === null) return;
            btn.disabled = true;
            action(id, 'reject', reason).then(function(res){
                if(res && res.ok){ applyState(id, 'rejected', reason); }
                else { btn.disabled=false; alert('Reddedilemedi.'); }
            }).catch(function(){ btn.disabled=false; alert('Bağlantı hatası.'); });
        });
    });
}());
</script>

@endsection
