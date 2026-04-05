@extends('senior.layouts.app')
@section('title','Materyaller & Bilgi Bankası')
@section('page_title','Materyaller & Bilgi Bankası')

@push('head')
<style>
/* ── Form ── */
.kb-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
@media(max-width:700px){ .kb-form-grid { grid-template-columns:1fr; } }
.kb-form-group { display:flex; flex-direction:column; gap:4px; }
.kb-form-group label { font-size:11px; font-weight:700; color:var(--u-muted); text-transform:uppercase; letter-spacing:.04em; }
.kb-form-group input,
.kb-form-group select,
.kb-form-group textarea {
    padding:8px 10px; border:1px solid var(--u-line); border-radius:8px;
    font-size:13px; color:var(--u-text); background:var(--u-bg);
    outline:none; transition:border-color .15s; font-family:inherit;
}
.kb-form-group input:focus,
.kb-form-group select:focus,
.kb-form-group textarea:focus { border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,.08); }
.kb-form-group textarea { resize:vertical; min-height:72px; }
.kb-form-full { grid-column:1/-1; }
.kb-file-label {
    display:inline-flex; align-items:center; gap:8px;
    padding:8px 14px; border:1px dashed var(--u-line); border-radius:8px;
    font-size:12px; color:var(--u-muted); cursor:pointer; transition:border-color .15s, color .15s;
}
.kb-file-label:hover { border-color:#7c3aed; color:#7c3aed; }

/* ── Category columns ── */
.kb-cols { display:grid; grid-template-columns:1fr 1fr; gap:14px; align-items:start; }
@media(max-width:860px){ .kb-cols { grid-template-columns:1fr; } }

.kb-col-header {
    border-radius:12px 12px 0 0;
    padding:14px 18px;
    display:flex; align-items:center; justify-content:space-between; gap:8px;
}
.kb-col-body {
    border:1px solid var(--u-line); border-top:none; border-radius:0 0 12px 12px;
    background:var(--u-bg); padding:8px; display:flex; flex-direction:column; gap:6px;
}

/* Each item = card */
.kb-item-card {
    background:var(--u-card);
    border:1px solid var(--u-line);
    border-radius:10px;
    padding:12px 14px;
    transition:box-shadow .15s, border-color .15s;
}
.kb-item-card:hover { box-shadow:0 2px 10px rgba(0,0,0,.07); border-color:#a5b4fc; }

/* ── Edit panel ── */
.kb-edit-panel { display:none; margin-top:10px; padding:14px; border:1px solid var(--u-line); border-radius:10px; background:var(--u-bg); }
.kb-edit-panel.open { display:block; }
</style>
@endpush

@section('content')

{{-- Gradient Header --}}
<div style="background:linear-gradient(to right,#6d28d9,#7c3aed);border-radius:14px;padding:20px 24px;margin-bottom:16px;color:#fff;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
        <div>
            <div style="font-size:var(--tx-xl);font-weight:800;letter-spacing:-.3px;margin-bottom:4px;">📚 Materyaller & Bilgi Bankası</div>
            <div style="font-size:var(--tx-sm);opacity:.8;">Öğrenci ve guest'e yönelik eğitim içerikleri</div>
        </div>
    </div>
    {{-- Filter chips --}}
    @php
        $activePublished = $filters['published'] ?? 'all';
        $activeRole      = $filters['role'] ?? 'all';
        $activeQ         = $filters['q'] ?? '';
        $kFilters = [
            ['Toplam',  $kTotal ?? 0,     '📦', url('/senior/knowledge-base'),                    $activePublished==='all' && $activeRole==='all' && $activeQ===''],
            ['Yayında', $kPublished ?? 0, '✅', url('/senior/knowledge-base').'?published=yes',   $activePublished==='yes'],
            ['Öğrenci', $kStudent ?? 0,   '🎓', url('/senior/knowledge-base').'?role=student',    $activeRole==='student'],
            ['Guest',   $kGuest ?? 0,     '👤', url('/senior/knowledge-base').'?role=guest',      $activeRole==='guest'],
        ];
    @endphp
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        @foreach($kFilters as [$lbl,$val,$ic,$href,$isActive])
        <a href="{{ $href }}"
           style="display:flex;align-items:center;gap:10px;
                  background:{{ $isActive ? 'rgba(255,255,255,.28)' : 'rgba(255,255,255,.12)' }};
                  border:1.5px solid {{ $isActive ? 'rgba(255,255,255,.7)' : 'rgba(255,255,255,.25)' }};
                  border-radius:10px;padding:8px 16px;text-decoration:none;color:#fff;
                  transition:background .15s,border-color .15s;
                  {{ $isActive ? 'box-shadow:0 0 0 2px rgba(255,255,255,.2);' : '' }}">
            <span style="font-size:var(--tx-lg);line-height:1;">{{ $ic }}</span>
            <div>
                <div style="font-size:var(--tx-lg);font-weight:800;line-height:1.1;">{{ $val }}</div>
                <div style="font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.05em;opacity:{{ $isActive ? '1' : '.75' }};">{{ $lbl }}</div>
            </div>
            @if($isActive)
                <span style="font-size:var(--tx-xs);background:rgba(255,255,255,.3);border-radius:999px;padding:1px 8px;font-weight:700;margin-left:2px;">✓</span>
            @endif
        </a>
        @endforeach
    </div>
</div>

@if(session('kb_success'))
<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px 16px;margin-bottom:12px;color:#15803d;font-size:var(--tx-sm);font-weight:600;">
    ✅ {{ session('kb_success') }}
</div>
@endif

{{-- Filter Bar --}}
<form method="GET" style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:12px 14px;margin-bottom:14px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="🔍 Başlık / açıklama / kategori ara..."
        style="flex:1;min-width:160px;border:1px solid var(--u-line);border-radius:7px;padding:8px 12px;font-size:var(--tx-sm);color:var(--u-text);background:var(--u-bg);">
    <select name="category" style="border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);color:var(--u-text);background:var(--u-bg);">
        <option value="all" @selected(($filters['category']??'all')==='all')>Tüm Kategoriler</option>
        @foreach($categories ?? [] as $cat)
            <option value="{{ $cat }}" @selected(($filters['category']??'')===$cat)>{{ $cat }}</option>
        @endforeach
    </select>
    <select name="published" style="border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);color:var(--u-text);background:var(--u-bg);">
        <option value="all" @selected(($filters['published']??'all')==='all')>Tüm Durumlar</option>
        <option value="yes" @selected(($filters['published']??'')==='yes')>✅ Aktif</option>
        <option value="no"  @selected(($filters['published']??'')==='no')>⬜ Pasif</option>
    </select>
    <select name="role" style="border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);color:var(--u-text);background:var(--u-bg);">
        <option value="all"     @selected(($filters['role']??'all')==='all')>Tüm Roller</option>
        <option value="student" @selected(($filters['role']??'')==='student')>🎓 Öğrenci</option>
        <option value="guest"   @selected(($filters['role']??'')==='guest')>👤 Guest</option>
    </select>
    <select name="sort" style="border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);color:var(--u-text);background:var(--u-bg);">
        <option value="latest"  @selected(($filters['sort']??'latest')==='latest')>📅 En Yeni</option>
        <option value="popular" @selected(($filters['sort']??'')==='popular')>👁️ En Çok Görüntülenen</option>
        <option value="helpful" @selected(($filters['sort']??'')==='helpful')>👍 En Faydalı</option>
    </select>
    @if(($allTags ?? collect())->isNotEmpty())
    <select name="tag" style="border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);color:var(--u-text);background:var(--u-bg);">
        <option value="">Tüm Etiketler</option>
        @foreach($allTags as $t)
            <option value="{{ $t }}" @selected(($filters['tag']??'')===$t)>🏷️ {{ $t }}</option>
        @endforeach
    </select>
    @endif
    <button type="submit" style="background:#7c3aed;color:#fff;border:none;border-radius:7px;padding:8px 18px;font-size:var(--tx-sm);font-weight:600;cursor:pointer;">Filtrele</button>
    <a href="{{ url('/senior/knowledge-base') }}" style="color:var(--u-muted);font-size:var(--tx-sm);text-decoration:none;padding:8px 10px;border:1px solid var(--u-line);border-radius:7px;background:var(--u-bg);">Temizle</a>
</form>

{{-- Add Form --}}
<details style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;margin-bottom:16px;overflow:hidden;">
    <summary style="list-style:none;cursor:pointer;padding:13px 16px;display:flex;align-items:center;justify-content:space-between;font-size:var(--tx-sm);font-weight:700;color:var(--u-text);user-select:none;">
        <span>➕ Yeni İçerik Ekle</span>
        <span style="font-size:var(--tx-xs);color:var(--u-muted);font-weight:400;">Başlık, kategori, rol ve içerik</span>
    </summary>
    <div style="padding:16px;border-top:1px solid var(--u-line);">
        <form method="POST" action="{{ route('senior.kb.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="kb-form-grid">
                <div class="kb-form-group">
                    <label>Başlık *</label>
                    <input type="text" name="title_tr" required placeholder="Materyal başlığı">
                </div>
                <div class="kb-form-group">
                    <label>Kategori *</label>
                    <input type="text" name="category" required placeholder="vize, cv-rehberi, tanitim...">
                </div>
                <div class="kb-form-group">
                    <label>Medya Tipi</label>
                    <select name="media_type">
                        <option value="">Otomatik</option>
                        <option value="video">🎬 Video (YouTube)</option>
                        <option value="pdf">📑 PDF</option>
                        <option value="article">📄 Makale</option>
                        <option value="link">🔗 Bağlantı</option>
                        <option value="text">📝 Yazılı İçerik</option>
                    </select>
                </div>
                <div class="kb-form-group">
                    <label>YouTube / Harici URL</label>
                    <input type="url" name="source_url" placeholder="https://www.youtube.com/watch?v=...">
                </div>
                <div class="kb-form-group kb-form-full">
                    <label>Açıklama / İçerik</label>
                    <textarea name="body_tr" placeholder="Materyal hakkında kısa açıklama..."></textarea>
                </div>
                <div class="kb-form-group kb-form-full">
                    <label>Etiketler (virgülle ayır)</label>
                    <input type="text" name="tags" placeholder="vize, cv, almanya, dil-sınavı">
                </div>
                <div class="kb-form-group">
                    <label>PDF Dosyası Yükle (max 20MB)</label>
                    <input type="file" name="file" id="kb-file-new" accept=".pdf" style="display:none;"
                           onchange="document.getElementById('kb-file-label-new').textContent = this.files[0]?.name || 'Dosya seç'">
                    <label class="kb-file-label" for="kb-file-new">
                        📎 <span id="kb-file-label-new">Dosya seç (.pdf)</span>
                    </label>
                </div>
                <div class="kb-form-group" style="justify-content:flex-end;">
                    <label style="margin-bottom:8px;">Görünür Roller</label>
                    <div style="display:flex;gap:14px;flex-wrap:wrap;">
                        @foreach(['student'=>'🎓 Öğrenci','guest'=>'👤 Guest','senior'=>'🧑‍💼 Senior'] as $r=>$rl)
                        <label style="display:flex;align-items:center;gap:6px;font-size:var(--tx-sm);cursor:pointer;text-transform:none;letter-spacing:0;">
                            <input type="checkbox" name="target_roles[]" value="{{ $r }}"> {{ $rl }}
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:12px;margin-top:12px;padding-top:12px;border-top:1px solid var(--u-line);">
                <label style="display:flex;align-items:center;gap:6px;font-size:var(--tx-sm);cursor:pointer;">
                    <input type="checkbox" name="is_published" value="1" checked> ✅ Hemen yayınla
                </label>
                <button type="submit" style="background:#7c3aed;color:#fff;border:none;border-radius:8px;padding:9px 22px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;">
                    💾 İçerik Ekle
                </button>
            </div>
        </form>
    </div>
</details>

{{-- Two Category Columns --}}
@php
    $allArticles  = ($articles instanceof \Illuminate\Contracts\Pagination\Paginator)
        ? $articles->getCollection()
        : ($articles ?? collect());
    $mediaTypes   = ['video','pdf','link'];
    $materyaller  = $allArticles->filter(fn($r) => in_array($r->media_type ?? '', $mediaTypes));
    $bilgiBankasi = $allArticles->filter(fn($r) => !in_array($r->media_type ?? '', $mediaTypes));
    $mediaIcon    = ['video'=>'🎬','pdf'=>'📑','article'=>'📄','link'=>'🔗','text'=>'📝'];
    $mediaIconBg  = ['video'=>'rgba(124,58,237,.1)','pdf'=>'rgba(220,38,38,.08)','article'=>'rgba(5,150,105,.08)','link'=>'rgba(37,99,235,.08)','text'=>'rgba(5,150,105,.08)'];
@endphp

<div class="kb-cols">

    {{-- ── Sol: Materyaller ── --}}
    <div>
        <div class="kb-col-header" style="background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;">
            <div style="display:flex;align-items:center;gap:10px;">
                <span style="font-size:var(--tx-xl);">🎬</span>
                <div>
                    <div style="font-size:var(--tx-base);font-weight:800;">Materyaller</div>
                    <div style="font-size:var(--tx-xs);opacity:.75;">Video · PDF · Bağlantı</div>
                </div>
            </div>
            <span style="background:rgba(255,255,255,.25);border-radius:999px;padding:3px 12px;font-size:var(--tx-sm);font-weight:800;">{{ $materyaller->count() }}</span>
        </div>
        <div class="kb-col-body">
            @forelse($materyaller as $row)
                @php
                    $roles      = (array)($row->target_roles ?? []);
                    $hasStudent = in_array('student',$roles);
                    $hasGuest   = in_array('guest',$roles);
                    $hasSenior  = in_array('senior',$roles);
                    $mt         = $row->media_type ?? 'link';
                    $mIcon      = $mediaIcon[$mt] ?? '🔗';
                    $hasFile    = !empty($row->file_path);
                @endphp
                <div class="kb-item-card">

                    {{-- Title row --}}
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                        <span style="font-size:var(--tx-base);flex-shrink:0;">{{ $mIcon }}</span>
                        <span style="font-size:var(--tx-sm);font-weight:700;color:var(--u-text);flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            {{ $row->title_tr }}
                        </span>
                        @if(!$row->is_published)
                            <span style="font-size:var(--tx-xs);background:#fee2e2;color:#dc2626;border-radius:4px;padding:1px 6px;font-weight:700;flex-shrink:0;">TASLAK</span>
                        @endif
                    </div>

                    {{-- Meta row --}}
                    <div style="display:flex;align-items:center;gap:5px;flex-wrap:wrap;margin-bottom:8px;">
                        @if($row->category)
                            <span style="font-size:var(--tx-xs);background:var(--u-bg);border:1px solid var(--u-line);border-radius:4px;padding:1px 7px;color:var(--u-muted);">{{ $row->category }}</span>
                        @endif
                        @if($hasFile) <span style="font-size:var(--tx-xs);color:#c2410c;font-weight:700;">📎 PDF</span> @endif
                        @if($row->source_url) <span style="font-size:var(--tx-xs);color:var(--u-muted);">🔗 URL</span> @endif
                        <span style="font-size:var(--tx-xs);color:var(--u-muted);">👁 {{ $row->view_count ?? 0 }}</span>
                        @if($hasStudent) <span style="font-size:var(--tx-xs);background:#f5f3ff;color:#7c3aed;border-radius:10px;padding:1px 7px;font-weight:700;">🎓</span> @endif
                        @if($hasGuest)   <span style="font-size:var(--tx-xs);background:#fef3c7;color:#92400e;border-radius:10px;padding:1px 7px;font-weight:700;">👤</span> @endif
                        @if($hasSenior)  <span style="font-size:var(--tx-xs);background:#f3e8ff;color:#6b21a8;border-radius:10px;padding:1px 7px;font-weight:700;">🧑‍💼</span> @endif
                    </div>

                    {{-- Action row --}}
                    <div style="display:flex;gap:4px;flex-wrap:wrap;align-items:center;">
                        {{-- Role toggles --}}
                        <form method="POST" action="{{ route('senior.kb.toggle-role', $row->id) }}" style="display:inline;">
                            @csrf <input type="hidden" name="role" value="student">
                            <button type="submit" title="{{ $hasStudent ? 'Öğrenci kapalı yap' : 'Öğrenci aç' }}"
                                style="font-size:var(--tx-xs);padding:3px 9px;border-radius:5px;cursor:pointer;font-weight:700;border:1px solid {{ $hasStudent ? '#c4b5fd' : 'var(--u-line)' }};background:{{ $hasStudent ? '#f5f3ff' : 'var(--u-bg)' }};color:{{ $hasStudent ? '#7c3aed' : 'var(--u-muted)' }};">
                                🎓{{ $hasStudent ? ' ✓' : '' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('senior.kb.toggle-role', $row->id) }}" style="display:inline;">
                            @csrf <input type="hidden" name="role" value="guest">
                            <button type="submit" title="{{ $hasGuest ? 'Guest kapalı yap' : 'Guest aç' }}"
                                style="font-size:var(--tx-xs);padding:3px 9px;border-radius:5px;cursor:pointer;font-weight:700;border:1px solid {{ $hasGuest ? '#fde68a' : 'var(--u-line)' }};background:{{ $hasGuest ? '#fef3c7' : 'var(--u-bg)' }};color:{{ $hasGuest ? '#92400e' : 'var(--u-muted)' }};">
                                👤{{ $hasGuest ? ' ✓' : '' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('senior.kb.toggle', $row->id) }}" style="display:inline;">
                            @csrf
                            <button type="submit"
                                style="font-size:var(--tx-xs);padding:3px 9px;border-radius:5px;cursor:pointer;font-weight:700;border:1px solid {{ $row->is_published ? '#86efac' : '#fde68a' }};background:{{ $row->is_published ? '#f0fdf4' : '#fef3c7' }};color:{{ $row->is_published ? '#16a34a' : '#d97706' }};">
                                {{ $row->is_published ? '✓ Aktif' : '○ Pasif' }}
                            </button>
                        </form>
                        @if($hasFile)
                        <a href="{{ route('senior.kb.file', $row->id) }}" target="_blank"
                            style="font-size:var(--tx-xs);padding:3px 9px;border-radius:5px;background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;text-decoration:none;font-weight:600;">PDF</a>
                        @endif
                        @if($row->source_url)
                        <a href="{{ $row->source_url }}" target="_blank"
                            style="font-size:var(--tx-xs);padding:3px 9px;border-radius:5px;background:var(--u-bg);color:var(--u-muted);border:1px solid var(--u-line);text-decoration:none;font-weight:600;">🔗</a>
                        @endif
                        <button type="button" onclick="kbToggleEdit({{ $row->id }})"
                            style="font-size:var(--tx-xs);padding:3px 9px;border-radius:5px;background:rgba(124,58,237,.07);color:#7c3aed;border:1px solid rgba(124,58,237,.18);cursor:pointer;font-weight:600;margin-left:auto;">✏️</button>
                        <form method="POST" action="{{ route('senior.kb.delete', $row->id) }}" style="display:inline;"
                              onsubmit="return confirm('Bu materyali silmek istediğinizden emin misiniz?')">
                            @csrf
                            <button type="submit" style="font-size:var(--tx-xs);padding:3px 9px;border-radius:5px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;cursor:pointer;">🗑</button>
                        </form>
                    </div>
                    @if($row->is_published)
                    <button onclick="kbHelpful({{ $row->id }}, this)"
                            style="background:none;border:1px solid var(--u-line);border-radius:6px;padding:3px 10px;font-size:12px;color:var(--u-muted);cursor:pointer;margin-top:6px;"
                            title="Faydalı">
                        👍 <span class="hc">{{ $row->helpful_count ?? 0 }}</span>
                    </button>
                    @endif

                    {{-- Inline edit --}}
                    <div class="kb-edit-panel" id="kb-edit-{{ $row->id }}">
                        <form method="POST" action="{{ route('senior.kb.update', $row->id) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="kb-form-grid">
                                <div class="kb-form-group"><label>Başlık</label><input type="text" name="title_tr" value="{{ $row->title_tr }}" required></div>
                                <div class="kb-form-group"><label>Kategori</label><input type="text" name="category" value="{{ $row->category }}"></div>
                                <div class="kb-form-group">
                                    <label>Medya Tipi</label>
                                    <select name="media_type">
                                        <option value="">Otomatik</option>
                                        <option value="video"   @selected($row->media_type==='video')>🎬 Video</option>
                                        <option value="pdf"     @selected($row->media_type==='pdf')>📑 PDF</option>
                                        <option value="article" @selected($row->media_type==='article')>📄 Makale</option>
                                        <option value="link"    @selected($row->media_type==='link')>🔗 Bağlantı</option>
                                        <option value="text"    @selected($row->media_type==='text')>📝 Yazılı</option>
                                    </select>
                                </div>
                                <div class="kb-form-group"><label>Kaynak URL</label><input type="url" name="source_url" value="{{ $row->source_url }}"></div>
                                <div class="kb-form-group kb-form-full"><label>Açıklama</label><textarea name="body_tr">{{ $row->body_tr }}</textarea></div>
                                <div class="kb-form-group">
                                    <label>Yeni PDF</label>
                                    <input type="file" name="file" id="kb-file-{{ $row->id }}" accept=".pdf" style="display:none;"
                                           onchange="document.getElementById('kb-file-lbl-{{ $row->id }}').textContent = this.files[0]?.name || 'Dosya seç'">
                                    <label class="kb-file-label" for="kb-file-{{ $row->id }}">
                                        📎 <span id="kb-file-lbl-{{ $row->id }}">{{ $row->original_filename ?? 'Dosya seç' }}</span>
                                    </label>
                                </div>
                                <div class="kb-form-group">
                                    <label>Görünür Roller</label>
                                    <div style="display:flex;gap:10px;flex-wrap:wrap;padding-top:4px;">
                                        @foreach(['student'=>'🎓 Öğrenci','guest'=>'👤 Guest','senior'=>'🧑‍💼 Senior'] as $r=>$rl)
                                        <label style="display:flex;align-items:center;gap:5px;font-size:var(--tx-sm);cursor:pointer;text-transform:none;letter-spacing:0;">
                                            <input type="checkbox" name="target_roles[]" value="{{ $r }}" @checked(in_array($r,(array)($row->target_roles??[])))> {{ $rl }}
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div style="display:flex;align-items:center;gap:10px;margin-top:10px;padding-top:10px;border-top:1px solid var(--u-line);">
                                <label style="display:flex;align-items:center;gap:6px;font-size:var(--tx-sm);cursor:pointer;">
                                    <input type="checkbox" name="is_published" value="1" @checked($row->is_published)> ✅ Yayınla
                                </label>
                                <button type="button" onclick="kbToggleEdit({{ $row->id }})"
                                    style="padding:6px 14px;font-size:var(--tx-xs);background:var(--u-bg);color:var(--u-muted);border:1px solid var(--u-line);border-radius:7px;cursor:pointer;">İptal</button>
                                <button type="submit"
                                    style="padding:6px 16px;font-size:var(--tx-xs);background:#7c3aed;color:#fff;border:none;border-radius:7px;cursor:pointer;font-weight:700;">💾 Kaydet</button>
                            </div>
                        </form>
                    </div>
                </div>
            @empty
                <div style="padding:32px 20px;text-align:center;color:var(--u-muted);">
                    <div style="font-size:32px;margin-bottom:8px;">🎬</div>
                    <div style="font-size:var(--tx-sm);">Video, PDF veya link eklenmemiş.</div>
                </div>
            @endforelse
        </div>
    </div>

    {{-- ── Sağ: Bilgi Bankası ── --}}
    <div>
        <div class="kb-col-header" style="background:linear-gradient(135deg,#ea580c,#f97316);color:#fff;">
            <div style="display:flex;align-items:center;gap:10px;">
                <span style="font-size:var(--tx-xl);">📖</span>
                <div>
                    <div style="font-size:var(--tx-base);font-weight:800;">Bilgi Bankası</div>
                    <div style="font-size:var(--tx-xs);opacity:.75;">Makale · Yazılı İçerik</div>
                </div>
            </div>
            <span style="background:rgba(255,255,255,.25);border-radius:999px;padding:3px 12px;font-size:var(--tx-sm);font-weight:800;">{{ $bilgiBankasi->count() }}</span>
        </div>
        <div class="kb-col-body">
            @forelse($bilgiBankasi as $row)
                @php
                    $roles      = (array)($row->target_roles ?? []);
                    $hasStudent = in_array('student',$roles);
                    $hasGuest   = in_array('guest',$roles);
                    $hasSenior  = in_array('senior',$roles);
                    $mt         = $row->media_type ?? 'article';
                    $mIcon      = $mediaIcon[$mt] ?? '📄';
                    $hasFile    = !empty($row->file_path);
                @endphp
                <div class="kb-item-card">

                    {{-- Title row --}}
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                        <span style="font-size:var(--tx-base);flex-shrink:0;">{{ $mIcon }}</span>
                        <span style="font-size:var(--tx-sm);font-weight:700;color:var(--u-text);flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            {{ $row->title_tr }}
                        </span>
                        @if(!$row->is_published)
                            <span style="font-size:var(--tx-xs);background:#fee2e2;color:#dc2626;border-radius:4px;padding:1px 6px;font-weight:700;flex-shrink:0;">TASLAK</span>
                        @endif
                    </div>

                    {{-- Meta row --}}
                    <div style="display:flex;align-items:center;gap:5px;flex-wrap:wrap;margin-bottom:8px;">
                        @if($row->category)
                            <span style="font-size:var(--tx-xs);background:var(--u-bg);border:1px solid var(--u-line);border-radius:4px;padding:1px 7px;color:var(--u-muted);">{{ $row->category }}</span>
                        @endif
                        @if($hasFile) <span style="font-size:var(--tx-xs);color:#c2410c;font-weight:700;">📎 PDF</span> @endif
                        <span style="font-size:var(--tx-xs);color:var(--u-muted);">👁 {{ $row->view_count ?? 0 }}</span>
                        @if($hasStudent) <span style="font-size:var(--tx-xs);background:#f5f3ff;color:#7c3aed;border-radius:10px;padding:1px 7px;font-weight:700;">🎓</span> @endif
                        @if($hasGuest)   <span style="font-size:var(--tx-xs);background:#fef3c7;color:#92400e;border-radius:10px;padding:1px 7px;font-weight:700;">👤</span> @endif
                        @if($hasSenior)  <span style="font-size:var(--tx-xs);background:#f3e8ff;color:#6b21a8;border-radius:10px;padding:1px 7px;font-weight:700;">🧑‍💼</span> @endif
                    </div>

                    {{-- Action row --}}
                    <div style="display:flex;gap:4px;flex-wrap:wrap;align-items:center;">
                        <form method="POST" action="{{ route('senior.kb.toggle-role', $row->id) }}" style="display:inline;">
                            @csrf <input type="hidden" name="role" value="student">
                            <button type="submit" title="{{ $hasStudent ? 'Öğrenci kapalı yap' : 'Öğrenci aç' }}"
                                style="font-size:var(--tx-xs);padding:3px 9px;border-radius:5px;cursor:pointer;font-weight:700;border:1px solid {{ $hasStudent ? '#c4b5fd' : 'var(--u-line)' }};background:{{ $hasStudent ? '#f5f3ff' : 'var(--u-bg)' }};color:{{ $hasStudent ? '#7c3aed' : 'var(--u-muted)' }};">
                                🎓{{ $hasStudent ? ' ✓' : '' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('senior.kb.toggle-role', $row->id) }}" style="display:inline;">
                            @csrf <input type="hidden" name="role" value="guest">
                            <button type="submit" title="{{ $hasGuest ? 'Guest kapalı yap' : 'Guest aç' }}"
                                style="font-size:var(--tx-xs);padding:3px 9px;border-radius:5px;cursor:pointer;font-weight:700;border:1px solid {{ $hasGuest ? '#fde68a' : 'var(--u-line)' }};background:{{ $hasGuest ? '#fef3c7' : 'var(--u-bg)' }};color:{{ $hasGuest ? '#92400e' : 'var(--u-muted)' }};">
                                👤{{ $hasGuest ? ' ✓' : '' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('senior.kb.toggle', $row->id) }}" style="display:inline;">
                            @csrf
                            <button type="submit"
                                style="font-size:var(--tx-xs);padding:3px 9px;border-radius:5px;cursor:pointer;font-weight:700;border:1px solid {{ $row->is_published ? '#86efac' : '#fde68a' }};background:{{ $row->is_published ? '#f0fdf4' : '#fef3c7' }};color:{{ $row->is_published ? '#16a34a' : '#d97706' }};">
                                {{ $row->is_published ? '✓ Aktif' : '○ Pasif' }}
                            </button>
                        </form>
                        @if($hasFile)
                        <a href="{{ route('senior.kb.file', $row->id) }}" target="_blank"
                            style="font-size:var(--tx-xs);padding:3px 9px;border-radius:5px;background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;text-decoration:none;font-weight:600;">PDF</a>
                        @endif
                        @if($row->source_url)
                        <a href="{{ $row->source_url }}" target="_blank"
                            style="font-size:var(--tx-xs);padding:3px 9px;border-radius:5px;background:var(--u-bg);color:var(--u-muted);border:1px solid var(--u-line);text-decoration:none;font-weight:600;">🔗</a>
                        @endif
                        <button type="button" onclick="kbToggleEdit({{ $row->id }})"
                            style="font-size:var(--tx-xs);padding:3px 9px;border-radius:5px;background:rgba(234,88,12,.07);color:#ea580c;border:1px solid rgba(234,88,12,.2);cursor:pointer;font-weight:600;margin-left:auto;">✏️</button>
                        <form method="POST" action="{{ route('senior.kb.delete', $row->id) }}" style="display:inline;"
                              onsubmit="return confirm('Bu materyali silmek istediğinizden emin misiniz?')">
                            @csrf
                            <button type="submit" style="font-size:var(--tx-xs);padding:3px 9px;border-radius:5px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;cursor:pointer;">🗑</button>
                        </form>
                    </div>
                    @if($row->is_published)
                    <button onclick="kbHelpful({{ $row->id }}, this)"
                            style="background:none;border:1px solid var(--u-line);border-radius:6px;padding:3px 10px;font-size:12px;color:var(--u-muted);cursor:pointer;margin-top:6px;"
                            title="Faydalı">
                        👍 <span class="hc">{{ $row->helpful_count ?? 0 }}</span>
                    </button>
                    @endif

                    {{-- Inline edit --}}
                    <div class="kb-edit-panel" id="kb-edit-{{ $row->id }}">
                        <form method="POST" action="{{ route('senior.kb.update', $row->id) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="kb-form-grid">
                                <div class="kb-form-group"><label>Başlık</label><input type="text" name="title_tr" value="{{ $row->title_tr }}" required></div>
                                <div class="kb-form-group"><label>Kategori</label><input type="text" name="category" value="{{ $row->category }}"></div>
                                <div class="kb-form-group">
                                    <label>Medya Tipi</label>
                                    <select name="media_type">
                                        <option value="">Otomatik</option>
                                        <option value="video"   @selected($row->media_type==='video')>🎬 Video</option>
                                        <option value="pdf"     @selected($row->media_type==='pdf')>📑 PDF</option>
                                        <option value="article" @selected($row->media_type==='article')>📄 Makale</option>
                                        <option value="link"    @selected($row->media_type==='link')>🔗 Bağlantı</option>
                                        <option value="text"    @selected($row->media_type==='text')>📝 Yazılı</option>
                                    </select>
                                </div>
                                <div class="kb-form-group"><label>Kaynak URL</label><input type="url" name="source_url" value="{{ $row->source_url }}"></div>
                                <div class="kb-form-group kb-form-full"><label>Açıklama</label><textarea name="body_tr">{{ $row->body_tr }}</textarea></div>
                                <div class="kb-form-group">
                                    <label>Yeni PDF</label>
                                    <input type="file" name="file" id="kb-file-{{ $row->id }}" accept=".pdf" style="display:none;"
                                           onchange="document.getElementById('kb-file-lbl-{{ $row->id }}').textContent = this.files[0]?.name || 'Dosya seç'">
                                    <label class="kb-file-label" for="kb-file-{{ $row->id }}">
                                        📎 <span id="kb-file-lbl-{{ $row->id }}">{{ $row->original_filename ?? 'Dosya seç' }}</span>
                                    </label>
                                </div>
                                <div class="kb-form-group">
                                    <label>Görünür Roller</label>
                                    <div style="display:flex;gap:10px;flex-wrap:wrap;padding-top:4px;">
                                        @foreach(['student'=>'🎓 Öğrenci','guest'=>'👤 Guest','senior'=>'🧑‍💼 Senior'] as $r=>$rl)
                                        <label style="display:flex;align-items:center;gap:5px;font-size:var(--tx-sm);cursor:pointer;text-transform:none;letter-spacing:0;">
                                            <input type="checkbox" name="target_roles[]" value="{{ $r }}" @checked(in_array($r,(array)($row->target_roles??[])))> {{ $rl }}
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div style="display:flex;align-items:center;gap:10px;margin-top:10px;padding-top:10px;border-top:1px solid var(--u-line);">
                                <label style="display:flex;align-items:center;gap:6px;font-size:var(--tx-sm);cursor:pointer;">
                                    <input type="checkbox" name="is_published" value="1" @checked($row->is_published)> ✅ Yayınla
                                </label>
                                <button type="button" onclick="kbToggleEdit({{ $row->id }})"
                                    style="padding:6px 14px;font-size:var(--tx-xs);background:var(--u-bg);color:var(--u-muted);border:1px solid var(--u-line);border-radius:7px;cursor:pointer;">İptal</button>
                                <button type="submit"
                                    style="padding:6px 16px;font-size:var(--tx-xs);background:#ea580c;color:#fff;border:none;border-radius:7px;cursor:pointer;font-weight:700;">💾 Kaydet</button>
                            </div>
                        </form>
                    </div>
                </div>
            @empty
                <div style="padding:32px 20px;text-align:center;color:var(--u-muted);">
                    <div style="font-size:32px;margin-bottom:8px;">📖</div>
                    <div style="font-size:var(--tx-sm);">Makale veya yazılı içerik eklenmemiş.</div>
                </div>
            @endforelse
        </div>
    </div>

</div>{{-- /kb-cols --}}

@if(isset($articles) && $articles instanceof \Illuminate\Contracts\Pagination\Paginator && $articles->hasPages())
<div style="margin-top:16px;">{{ $articles->links() }}</div>
@endif

<script>
function kbToggleEdit(id) {
    var panel = document.getElementById('kb-edit-' + id);
    panel.classList.toggle('open');
}

function kbHelpful(id, btn) {
    fetch('/senior/knowledge-base/' + id + '/helpful', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json'}
    }).then(r => r.json()).then(d => {
        btn.querySelector('.hc').textContent = d.helpful_count;
        btn.style.borderColor = '#7c3aed';
        btn.style.color = '#7c3aed';
        btn.disabled = true;
    });
}
</script>
@endsection
