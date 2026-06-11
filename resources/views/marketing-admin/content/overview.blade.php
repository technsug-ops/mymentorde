@extends('marketing-admin.layouts.app')

@section('topbar-actions')
<a class="btn" style="font-size:var(--tx-xs);padding:6px 12px;background:#7e58bf;color:#fff;border-color:transparent;" href="/mktg-admin/content/overview">İçerik Tablosu</a>
<a class="btn" style="font-size:var(--tx-xs);padding:6px 12px;background:#10b981;color:#fff;border-color:transparent;" href="/mktg-admin/content/create">+ Yeni İçerik</a>
<a class="btn alt" style="font-size:var(--tx-xs);padding:6px 12px;" href="/mktg-admin/categories">Kategoriler</a>
<a class="btn alt" style="font-size:var(--tx-xs);padding:6px 12px;" href="/mktg-admin/media">Medya</a>
@endsection

@section('title', 'İçerik Tablosu')
@section('page_subtitle', 'Tüm içerikler — kategori bazlı ID kod, kapak görseli, yazar ve önizleme')

@section('content')
<style>
.cot-wrap { padding: 0 4px; }
.cot-filters { display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-bottom:14px; padding:12px; background:var(--u-card,#fff); border:1px solid var(--u-line,#e2e8f0); border-radius:10px; }
.cot-filters input, .cot-filters select { padding:7px 10px; border:1px solid var(--u-line,#cbd5e1); border-radius:7px; font-size:12.5px; background:var(--u-bg,#fff); color:var(--u-text); }
.cot-filters input { min-width:240px; }
.cot-filters .cot-stat { font-size:11.5px; color:var(--u-muted,#64748b); padding:4px 10px; background:var(--u-bg,#f1f5f9); border-radius:6px; }

.cot-table { width:100%; border-collapse:collapse; background:var(--u-card,#fff); border:1px solid var(--u-line,#e2e8f0); border-radius:10px; overflow:hidden; }
.cot-table th { padding:10px 12px; text-align:left; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:var(--u-muted,#64748b); background:var(--u-bg,#f8fafc); border-bottom:1px solid var(--u-line,#e2e8f0); white-space:nowrap; }
.cot-table td { padding:10px 12px; font-size:13px; color:var(--u-text); border-bottom:1px solid var(--u-line,#f1f5f9); vertical-align:middle; }
.cot-table tr:hover td { background:var(--u-bg,#f8fafc); }
.cot-table tr:last-child td { border-bottom:none; }
.cot-table tr.cot-selected td { background:rgba(126,88,191,.08) !important; }

.cot-cb { width:16px; height:16px; cursor:pointer; accent-color:#7e58bf; }
.cot-code { font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; font-size:12px; font-weight:700; color:#7e58bf; background:rgba(126,88,191,.08); padding:3px 7px; border-radius:5px; display:inline-block; letter-spacing:.5px; white-space:nowrap; }
.cot-thumb { width:60px; height:42px; border-radius:5px; background:#e2e8f0 center/cover no-repeat; border:1px solid var(--u-line,#cbd5e1); flex-shrink:0; }
.cot-thumb-ph { background:linear-gradient(135deg,#94a3b8,#cbd5e1); display:flex; align-items:center; justify-content:center; color:#fff; font-size:8px; font-weight:700; }
.cot-title-link { color:var(--u-brand,#2563eb); text-decoration:none; font-weight:600; cursor:pointer; background:none; border:none; padding:0; font-size:13px; text-align:left; line-height:1.4; }
.cot-title-link:hover { text-decoration:underline; }
.cot-author { font-size:11px; color:var(--u-muted,#64748b); margin-top:2px; }
.cot-author strong { color:var(--u-text); font-weight:600; }

.cot-cat { font-size:11px; padding:3px 8px; border-radius:999px; background:rgba(99,102,241,.12); color:#4338ca; font-weight:600; white-space:nowrap; }
.cot-type { font-size:11px; padding:3px 8px; border-radius:999px; font-weight:600; white-space:nowrap; display:inline-flex; align-items:center; gap:4px; }
.cot-type-blog          { background:rgba(37,99,235,.12);  color:#1d4ed8; }
.cot-type-landing       { background:rgba(14,165,233,.12); color:#0369a1; }
.cot-type-guide         { background:rgba(124,58,237,.12); color:#6d28d9; }
.cot-type-faq           { background:rgba(20,184,166,.12); color:#0f766e; }
.cot-type-event         { background:rgba(244,114,182,.12);color:#be185d; }
.cot-type-video_feature { background:rgba(220,38,38,.12);  color:#b91c1c; }
.cot-type-podcast       { background:rgba(168,85,247,.14); color:#6b21a8; }
.cot-type-presentation  { background:rgba(245,158,11,.14); color:#b45309; }
.cot-type-experience    { background:rgba(236,72,153,.12); color:#9d174d; }
.cot-type-career_guide  { background:rgba(16,185,129,.14); color:#047857; }
.cot-type-tip           { background:rgba(234,179,8,.18);  color:#92400e; }
.cot-status-chip { font-size:11px; padding:3px 9px; border-radius:999px; font-weight:600; cursor:pointer; border:none; transition:opacity .15s; }
.cot-status-chip:hover { opacity:.85; }
.cot-status-published { background:rgba(16,185,129,.15); color:#047857; }
.cot-status-draft { background:rgba(245,158,11,.15); color:#b45309; }
.cot-status-scheduled { background:rgba(59,130,246,.15); color:#1d4ed8; }
.cot-status-archived { background:rgba(100,116,139,.15); color:#475569; }

.cot-star { background:none; border:none; cursor:pointer; font-size:16px; padding:2px 4px; color:#cbd5e1; transition:color .15s, transform .15s; }
.cot-star.active { color:#f59e0b; }
.cot-star:hover { transform:scale(1.15); }

.cot-actions { display:flex; gap:4px; }
.cot-icon-btn { background:none; border:none; cursor:pointer; padding:5px 7px; border-radius:5px; font-size:14px; color:var(--u-muted,#64748b); transition:background .15s, color .15s; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; }
.cot-icon-btn:hover { background:var(--u-bg,#f1f5f9); }
.cot-icon-btn.del:hover { background:rgba(220,38,38,.1); color:#dc2626; }
.cot-icon-btn.edit:hover { color:#2563eb; }
.cot-icon-btn.preview:hover { color:#7e58bf; }
.cot-icon-btn.pdf:hover { color:#dc2626; background:rgba(220,38,38,.08); }

/* Bulk action bar (sticky bottom, sadece seçim varken) */
.cot-bulkbar { position:fixed; bottom:24px; left:50%; transform:translateX(-50%); background:#0f172a; color:#fff; padding:10px 16px; border-radius:10px; box-shadow:0 12px 40px rgba(0,0,0,.4); z-index:50; display:none; align-items:center; gap:10px; font-size:13px; flex-wrap:wrap; }
.cot-bulkbar.active { display:flex; }
.cot-bulkbar .cot-bb-count { background:#7e58bf; padding:3px 10px; border-radius:999px; font-weight:700; }
.cot-bulkbar button { background:rgba(255,255,255,.1); color:#fff; border:1px solid rgba(255,255,255,.2); padding:6px 12px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; }
.cot-bulkbar button:hover { background:rgba(255,255,255,.2); }
.cot-bulkbar button.danger { background:rgba(220,38,38,.5); border-color:rgba(220,38,38,.7); }
.cot-bulkbar button.danger:hover { background:rgba(220,38,38,.8); }

/* Modal */
.cot-modal-backdrop { position:fixed; inset:0; background:rgba(15,23,42,.6); backdrop-filter:blur(4px); z-index:9000; display:none; align-items:flex-start; justify-content:center; padding:40px 16px; overflow-y:auto; }
.cot-modal-backdrop.active { display:flex; }
.cot-modal { width:100%; max-width:820px; background:var(--u-card,#fff); border-radius:14px; box-shadow:0 20px 60px rgba(0,0,0,.3); overflow:hidden; }
.cot-modal-header { display:flex; align-items:center; gap:12px; padding:16px 22px; background:var(--u-bg,#f8fafc); border-bottom:1px solid var(--u-line,#e2e8f0); }
.cot-modal-header h3 { margin:0; font-size:16px; color:var(--u-text); flex:1; line-height:1.4; }
.cot-modal-close { background:none; border:none; font-size:24px; cursor:pointer; color:var(--u-muted,#64748b); padding:0 6px; line-height:1; }
.cot-modal-close:hover { color:#dc2626; }
.cot-modal-body { padding:22px; max-height:70vh; overflow-y:auto; }
.cot-modal-cover { width:100%; max-height:280px; object-fit:cover; border-radius:10px; margin-bottom:14px; background:#e2e8f0; }
.cot-modal-meta { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:14px; font-size:12px; }
.cot-modal-meta span { background:var(--u-bg,#f1f5f9); padding:4px 10px; border-radius:6px; color:var(--u-muted,#64748b); }
.cot-modal-author { display:flex; gap:10px; align-items:center; padding:10px 14px; background:rgba(126,88,191,.06); border-radius:8px; margin-bottom:14px; font-size:13px; }
.cot-modal-summary { font-size:14px; line-height:1.55; color:var(--u-muted,#475569); margin-bottom:18px; padding:12px 14px; background:rgba(126,88,191,.05); border-left:3px solid #7e58bf; border-radius:6px; }
.cot-modal-content { font-size:14px; line-height:1.7; color:var(--u-text); }
.cot-modal-content h2 { font-size:18px; margin:18px 0 8px; }
.cot-modal-content h3 { font-size:15px; margin:14px 0 6px; }
.cot-modal-content p { margin:0 0 10px; }
.cot-modal-content ul { margin:6px 0 12px 20px; }
.cot-modal-content li { margin-bottom:4px; }
.cot-modal-footer { display:flex; justify-content:flex-end; gap:8px; padding:14px 22px; background:var(--u-bg,#f8fafc); border-top:1px solid var(--u-line,#e2e8f0); }
.cot-modal-footer .btn-edit { background:var(--u-brand,#2563eb); color:#fff; padding:7px 16px; border-radius:7px; font-size:12.5px; font-weight:600; text-decoration:none; }
.cot-modal-loading { text-align:center; padding:60px 0; color:var(--u-muted,#64748b); font-size:13px; }
</style>

<div class="cot-wrap">
    @php
        $typeLabels = [
            'blog' => 'Blog', 'landing' => 'Landing', 'guide' => 'Rehber', 'faq' => 'SSS', 'event' => 'Etkinlik',
            'video_feature' => 'Video', 'podcast' => 'Podcast', 'presentation' => 'Sunum',
            'experience' => 'Deneyim', 'career_guide' => 'Kariyer', 'tip' => 'İpucu',
        ];
        $typeIcons = [
            'blog' => '📝', 'landing' => '🌐', 'guide' => '📖', 'faq' => '❓', 'event' => '📅',
            'video_feature' => '▶️', 'podcast' => '🎙', 'presentation' => '📊',
            'experience' => '💬', 'career_guide' => '🗺', 'tip' => '💡',
        ];
    @endphp

    {{-- Filtre Bar --}}
    <form method="GET" action="{{ url('/mktg-admin/content/overview') }}" class="cot-filters">
        <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="🔍 ID kod, başlık veya slug ara...">
        <select name="category">
            <option value="all">📚 Tüm Kategoriler</option>
            @foreach($categoryPrefixes as $cat => $prefix)
                <option value="{{ $cat }}" @selected($filters['category'] === $cat)>{{ $prefix }} — {{ $cat }} ({{ $categoryCounts[$cat] ?? 0 }})</option>
            @endforeach
        </select>
        <select name="type">
            <option value="all">🔖 Tüm Türler</option>
            @foreach(($typeOptions ?? []) as $tp)
                <option value="{{ $tp }}" @selected(($filters['type'] ?? 'all') === $tp)>{{ $typeIcons[$tp] ?? '·' }} {{ $typeLabels[$tp] ?? $tp }} ({{ $typeCounts[$tp] ?? 0 }})</option>
            @endforeach
        </select>
        <select name="status">
            <option value="all">⚪ Tüm Durumlar</option>
            @foreach($statusOptions as $st)
                <option value="{{ $st }}" @selected($filters['status'] === $st)>{{ ucfirst($st) }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn" style="padding:7px 14px;font-size:12.5px;background:var(--u-brand,#2563eb);color:#fff;border:none;border-radius:7px;cursor:pointer;">Filtrele</button>
        @if($filters['q'] || $filters['category'] !== 'all' || $filters['status'] !== 'all' || ($filters['type'] ?? 'all') !== 'all')
            <a href="{{ url('/mktg-admin/content/overview') }}" class="cot-stat" style="text-decoration:none;">✕ Temizle</a>
        @endif
        <span class="cot-stat">Toplam: <strong>{{ $rows->total() }}</strong> kayıt</span>
    </form>

    {{-- Tablo --}}
    <div style="overflow-x:auto;">
    <table class="cot-table">
        <thead>
            <tr>
                <th style="width:32px;"><input type="checkbox" id="cot-cb-all" class="cot-cb" title="Tümünü seç"></th>
                <th style="width:30px;"></th>
                <th style="width:90px;">ID Kod</th>
                <th style="width:80px;">Kapak</th>
                <th>Blog İsmi / Yazar</th>
                <th style="width:130px;">Kategori</th>
                <th style="width:120px;">Tür</th>
                <th style="width:110px;">Durum</th>
                <th style="width:100px;">Tarih</th>
                <th style="width:110px; text-align:center;">İşlemler</th>
            </tr>
        </thead>
        <tbody id="cot-tbody">
            @forelse($rows as $row)
            <tr data-row-id="{{ $row->id }}">
                <td><input type="checkbox" class="cot-cb cot-cb-row" data-id="{{ $row->id }}"></td>
                <td>
                    <button type="button" class="cot-star {{ $row->is_featured ? 'active' : '' }}" data-feature-id="{{ $row->id }}" title="{{ $row->is_featured ? 'Featured\'dan çıkar' : 'Featured yap' }}">★</button>
                </td>
                <td><span class="cot-code">{{ $row->content_code ?: '—' }}</span></td>
                <td>
                    @if($row->cover_image_url)
                        <div class="cot-thumb" style="background-image:url('{{ $row->cover_image_url }}');"></div>
                    @else
                        <div class="cot-thumb cot-thumb-ph">YOK</div>
                    @endif
                </td>
                <td>
                    <button type="button" class="cot-title-link" data-preview-id="{{ $row->id }}" data-preview-code="{{ $row->content_code }}">
                        {{ $row->title_tr }}
                    </button>
                    @if($row->author_name)
                        <div class="cot-author">
                            ✍️ <strong>{{ $row->author_name }}</strong>
                            @if($row->author_role) <span style="opacity:.7;">— {{ $row->author_role }}</span> @endif
                        </div>
                    @endif
                </td>
                <td>
                    @if($row->category)<span class="cot-cat">{{ $row->category }}</span>@else<span style="color:#cbd5e1;">—</span>@endif
                </td>
                <td>
                    @if($row->type)
                        <span class="cot-type cot-type-{{ $row->type }}">
                            <span>{{ $typeIcons[$row->type] ?? '·' }}</span>
                            <span>{{ $typeLabels[$row->type] ?? $row->type }}</span>
                        </span>
                    @else
                        <span style="color:#cbd5e1;">—</span>
                    @endif
                </td>
                <td>
                    <button type="button" class="cot-status-chip cot-status-{{ $row->status }}" data-status-id="{{ $row->id }}" data-current-status="{{ $row->status }}" title="Durumu değiştir">
                        {{ ucfirst($row->status) }}
                    </button>
                </td>
                <td style="font-size:11.5px; color:var(--u-muted,#64748b);">
                    {{ $row->published_at ? \Illuminate\Support\Carbon::parse($row->published_at)->format('d.m.Y') : ($row->created_at ? $row->created_at->format('d.m.Y') : '—') }}
                </td>
                <td>
                    <div class="cot-actions">
                        <button type="button" class="cot-icon-btn preview" data-preview-id="{{ $row->id }}" data-preview-code="{{ $row->content_code }}" title="Önizle">👁</button>
                        <a href="{{ url('/mktg-admin/content/'.$row->id.'/edit') }}" class="cot-icon-btn edit" title="Düzenle">✎</a>
                        <a href="{{ url('/mktg-admin/content/'.$row->id.'/pdf') }}" class="cot-icon-btn pdf" title="PDF olarak indir" target="_blank">📄</a>
                        <button type="button" class="cot-icon-btn del" data-delete-id="{{ $row->id }}" data-delete-title="{{ $row->title_tr }}" title="Sil">🗑</button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" style="text-align:center; padding:40px 0; color:var(--u-muted,#64748b);">
                    Sonuç bulunamadı.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>

    @if($rows->hasPages())
    <div style="margin-top:14px;">
        {{ $rows->links() }}
    </div>
    @endif
</div>

{{-- Bulk Action Bar --}}
<div class="cot-bulkbar" id="cot-bulkbar">
    <span class="cot-bb-count"><span id="cot-bb-num">0</span> seçildi</span>
    <button type="button" id="cot-bulk-pdf" data-bulk-pdf-url="{{ url('/mktg-admin/content/bulk-pdf') }}">📄 PDF İndir</button>
    <button type="button" data-bulk-action="publish">✓ Yayınla</button>
    <button type="button" data-bulk-action="unpublish">📝 Drafta Çek</button>
    <button type="button" data-bulk-action="feature">⭐ Featured</button>
    <button type="button" data-bulk-action="unfeature">☆ Featured Kaldır</button>
    <button type="button" data-bulk-action="delete" class="danger">🗑 Sil</button>
    <button type="button" id="cot-bb-clear" style="background:transparent; border-color:rgba(255,255,255,.3);">✕ Temizle</button>
</div>

{{-- Preview Modal --}}
<div class="cot-modal-backdrop" id="cot-modal-backdrop">
    <div class="cot-modal" id="cot-modal" role="dialog" aria-modal="true">
        <div class="cot-modal-header">
            <span class="cot-code" id="cot-modal-code" style="font-size:13px;">—</span>
            <h3 id="cot-modal-title">Yükleniyor…</h3>
            <button type="button" class="cot-modal-close" id="cot-modal-close" aria-label="Kapat">×</button>
        </div>
        <div class="cot-modal-body" id="cot-modal-body">
            <div class="cot-modal-loading">İçerik yükleniyor…</div>
        </div>
        <div class="cot-modal-footer">
            <a href="#" id="cot-modal-edit" class="btn-edit">✎ Düzenle</a>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var bulkBar  = document.getElementById('cot-bulkbar');
    var bulkNum  = document.getElementById('cot-bb-num');
    var cbAll    = document.getElementById('cot-cb-all');

    function escapeHtml(s){
        return String(s||'').replace(/[&<>"']/g, function(c){
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
        });
    }

    function getSelectedIds(){
        return Array.from(document.querySelectorAll('.cot-cb-row:checked')).map(function(cb){
            return parseInt(cb.dataset.id);
        });
    }

    function updateBulkBar(){
        var ids = getSelectedIds();
        bulkNum.textContent = ids.length;
        bulkBar.classList.toggle('active', ids.length > 0);
        document.querySelectorAll('.cot-cb-row').forEach(function(cb){
            cb.closest('tr').classList.toggle('cot-selected', cb.checked);
        });
    }

    // Tümünü seç
    cbAll.addEventListener('change', function(){
        document.querySelectorAll('.cot-cb-row').forEach(function(cb){ cb.checked = cbAll.checked; });
        updateBulkBar();
    });

    document.querySelectorAll('.cot-cb-row').forEach(function(cb){
        cb.addEventListener('change', updateBulkBar);
    });

    // Bulk action
    document.querySelectorAll('[data-bulk-action]').forEach(function(btn){
        btn.addEventListener('click', function(){
            var ids = getSelectedIds();
            if (ids.length === 0) return;
            var action = btn.dataset.bulkAction;
            var labels = { delete: 'silmek', publish: 'yayınlamak', unpublish: 'drafta çekmek', feature: 'featured yapmak', unfeature: 'featured\'dan kaldırmak' };
            if (action === 'delete' && !confirm(ids.length + ' içeriği KALICI olarak silmek üzeresin. Emin misin?')) return;
            if (action !== 'delete' && !confirm(ids.length + ' içeriği ' + (labels[action] || action) + ' istediğine emin misin?')) return;

            var fd = new FormData();
            fd.append('action', action);
            ids.forEach(function(id){ fd.append('ids[]', id); });
            fetch('/mktg-admin/content/bulk-action', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: fd,
            })
            .then(function(r){ return r.json(); })
            .then(function(res){
                if (res.ok) {
                    location.reload();
                } else {
                    alert('⚠️ İşlem başarısız');
                }
            })
            .catch(function(){ alert('⚠️ Bağlantı hatası'); });
        });
    });

    document.getElementById('cot-bb-clear').addEventListener('click', function(){
        document.querySelectorAll('.cot-cb-row, #cot-cb-all').forEach(function(cb){ cb.checked = false; });
        updateBulkBar();
    });

    // Bulk PDF — hidden form ile POST + browser download (fetch ile binary blob da olur, ama form daha temiz)
    var bulkPdfBtn = document.getElementById('cot-bulk-pdf');
    if (bulkPdfBtn) {
        bulkPdfBtn.addEventListener('click', function(){
            var ids = getSelectedIds();
            if (ids.length === 0) return;
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = bulkPdfBtn.dataset.bulkPdfUrl;
            form.target = '_blank';
            var token = document.createElement('input');
            token.type = 'hidden'; token.name = '_token'; token.value = csrfToken;
            form.appendChild(token);
            ids.forEach(function(id){
                var inp = document.createElement('input');
                inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = id;
                form.appendChild(inp);
            });
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        });
    }

    // Tekil sil
    document.querySelectorAll('[data-delete-id]').forEach(function(btn){
        btn.addEventListener('click', function(){
            var id = btn.dataset.deleteId;
            var title = btn.dataset.deleteTitle;
            if (!confirm('"' + title + '" içeriğini silmek üzeresin. Bu işlem geri alınamaz. Emin misin?')) return;
            fetch('/mktg-admin/content/' + id, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
                body: '_method=DELETE',
            })
            .then(function(r){ return r.json(); })
            .then(function(res){
                if (res.ok) {
                    btn.closest('tr').remove();
                } else { alert('⚠️ Silinemedi'); }
            })
            .catch(function(){ alert('⚠️ Bağlantı hatası'); });
        });
    });

    // Featured toggle (yıldız)
    document.querySelectorAll('[data-feature-id]').forEach(function(btn){
        btn.addEventListener('click', function(){
            var id = btn.dataset.featureId;
            fetch('/mktg-admin/content/' + id + '/feature', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
                body: '_method=PUT',
            })
            .then(function(r){ return r.json(); })
            .then(function(res){
                if (res.ok) {
                    btn.classList.toggle('active', !!res.featured);
                    btn.title = res.featured ? 'Featured\'dan çıkar' : 'Featured yap';
                }
            });
        });
    });

    // Status chip toggle — Draft ↔ Published
    document.querySelectorAll('[data-status-id]').forEach(function(chip){
        chip.addEventListener('click', function(){
            var id = chip.dataset.statusId;
            var current = chip.dataset.currentStatus;
            var next = (current === 'published') ? 'draft' : 'published';
            if (!confirm('Durumu "' + current + '" → "' + next + '" değiştir?')) return;

            var fd = new FormData(); fd.append('status', next);
            fetch('/mktg-admin/content/' + id + '/toggle-status', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'X-HTTP-Method-Override': 'PATCH' },
                body: fd,
            })
            .then(function(r){ return r.json(); })
            .then(function(res){
                if (res.ok) {
                    chip.textContent = next.charAt(0).toUpperCase() + next.slice(1);
                    chip.className = 'cot-status-chip cot-status-' + next;
                    chip.dataset.currentStatus = next;
                }
            });
        });
    });

    // Preview modal
    var backdrop = document.getElementById('cot-modal-backdrop');
    var btnClose = document.getElementById('cot-modal-close');
    var elTitle  = document.getElementById('cot-modal-title');
    var elCode   = document.getElementById('cot-modal-code');
    var elBody   = document.getElementById('cot-modal-body');
    var elEdit   = document.getElementById('cot-modal-edit');

    function openModal(id, code){
        elTitle.textContent = 'Yükleniyor…';
        elCode.textContent = code || '—';
        elBody.innerHTML = '<div class="cot-modal-loading">İçerik yükleniyor…</div>';
        elEdit.href = '#';
        backdrop.classList.add('active');
        document.body.style.overflow = 'hidden';

        fetch('/mktg-admin/content/' + encodeURIComponent(id) + '/preview', {
            headers: { 'Accept': 'application/json' }
        })
        .then(function(r){ return r.json(); })
        .then(function(res){
            if (!res.ok || !res.item) {
                elBody.innerHTML = '<div class="cot-modal-loading" style="color:#dc2626;">⚠️ İçerik yüklenemedi.</div>';
                return;
            }
            var it = res.item;
            elTitle.textContent = it.title_tr;
            elCode.textContent  = it.content_code || '—';
            elEdit.href = it.edit_url;

            var html = '';
            if (it.cover_image_url) {
                html += '<img class="cot-modal-cover" src="' + escapeHtml(it.cover_image_url) + '" alt="' + escapeHtml(it.cover_image_alt || '') + '">';
                if (it.cover_image_alt) {
                    html += '<div style="font-size:11px; color:var(--u-muted,#64748b); margin-top:-8px; margin-bottom:14px; font-style:italic;">' + escapeHtml(it.cover_image_alt) + '</div>';
                }
            }

            if (it.author_name) {
                html += '<div class="cot-modal-author">';
                html += '<span style="font-size:18px;">✍️</span>';
                html += '<div><strong>' + escapeHtml(it.author_name) + '</strong>';
                if (it.author_role) html += ' <span style="color:var(--u-muted,#64748b); font-size:12px;">— ' + escapeHtml(it.author_role) + '</span>';
                html += '</div></div>';
            }

            html += '<div class="cot-modal-meta">';
            html += '<span>📂 ' + escapeHtml(it.category || '—') + '</span>';
            html += '<span>🔖 ' + escapeHtml(it.type || '—') + '</span>';
            html += '<span>⚪ ' + escapeHtml(it.status || '—') + '</span>';
            if (it.published_at) html += '<span>📅 ' + escapeHtml(it.published_at) + '</span>';
            if (it.metric_total_views) html += '<span>👁 ' + it.metric_total_views + '</span>';
            if (it.is_featured) html += '<span style="background:rgba(245,158,11,.2); color:#b45309;">⭐ Featured</span>';
            html += '</div>';

            if (it.summary_tr) html += '<div class="cot-modal-summary">' + escapeHtml(it.summary_tr) + '</div>';
            html += '<div class="cot-modal-content">' + (it.content_tr || '<em style="color:#94a3b8;">İçerik boş.</em>') + '</div>';
            elBody.innerHTML = html;
        })
        .catch(function(){
            elBody.innerHTML = '<div class="cot-modal-loading" style="color:#dc2626;">⚠️ Bağlantı hatası.</div>';
        });
    }

    function closeModal(){
        backdrop.classList.remove('active');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('[data-preview-id]').forEach(function(btn){
        btn.addEventListener('click', function(){
            openModal(btn.dataset.previewId, btn.dataset.previewCode);
        });
    });

    btnClose.addEventListener('click', closeModal);
    backdrop.addEventListener('click', function(e){ if (e.target === backdrop) closeModal(); });
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && backdrop.classList.contains('active')) closeModal(); });
})();
</script>
@endsection
