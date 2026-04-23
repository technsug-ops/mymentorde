@extends('manager.layouts.app')
@section('title', ($aiLabsName ?? 'AI Labs') . ' — Kaynaklar')
@section('page_title','🧠 ' . ($aiLabsName ?? 'MentorDE AI Labs') . ' — Bilgi Havuzu')

@section('content')
<style>
.als-wrap { max-width:1200px; margin:20px auto; padding:0 16px; }
.als-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:22px; margin-bottom:18px; }
.als-card h2 { margin:0 0 6px; font-size:17px; color:#0f172a; display:flex; align-items:center; gap:8px; }
.als-card p.hint { margin:0 0 16px; font-size:12px; color:#64748b; line-height:1.65; }
.als-msg-ok { background:#dcfce7; border:1px solid #86efac; color:#166534; padding:10px 14px; border-radius:8px; font-size:13px; margin-bottom:12px; }
.als-msg-warn { background:#fef3c7; border:1px solid #fcd34d; color:#92400e; padding:10px 14px; border-radius:8px; font-size:12px; margin-bottom:12px; line-height:1.6; }
.als-field label { display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:4px; }
.als-field input, .als-field select, .als-field textarea {
    width:100%; padding:9px 11px; border:1px solid #cbd5e1; border-radius:8px;
    font-size:13px; background:#fff; box-sizing:border-box; font-family:inherit;
}
.als-field textarea { min-height:120px; resize:vertical; }
.als-btn { padding:10px 18px; border:none; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; }
.als-btn-primary { background:#5b2e91; color:#fff; }
.als-btn-primary:hover { background:#4a2578; }
.als-btn-ghost { background:#f1f5f9; color:#0f172a; border:1px solid #e2e8f0; }
.als-btn-danger { background:#dc2626; color:#fff; }
.als-btn-sm { padding:5px 10px; font-size:11px; }
.als-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.als-grid-3 { display:grid; grid-template-columns:2fr 1fr 1fr; gap:14px; }
@media(max-width:640px){ .als-grid-2, .als-grid-3 { grid-template-columns:1fr; } }
.als-type-tabs { display:flex; gap:6px; margin-bottom:16px; }
.als-type-tab {
    flex:1; padding:10px 14px; text-align:center; border:1px solid #e2e8f0;
    border-radius:8px; cursor:pointer; font-size:13px; font-weight:600; color:#64748b;
    background:#fff; transition:all .15s;
}
.als-type-tab.active { background:#5b2e91; color:#fff; border-color:#5b2e91; }
.als-type-panel { display:none; }
.als-type-panel.active { display:block; }
.als-list { width:100%; border-collapse:collapse; font-size:13px; }
.als-list th { text-align:left; padding:10px 12px; background:#f8fafc; color:#64748b; font-weight:600; font-size:11px; text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid #e2e8f0; }
.als-list td { padding:12px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.als-list tr:hover { background:#fafbfc; }
.als-badge { display:inline-block; padding:3px 8px; border-radius:10px; font-size:11px; font-weight:700; }
.als-badge.green { background:#dcfce7; color:#166534; }
.als-badge.gray { background:#f1f5f9; color:#64748b; }
.als-badge.purple { background:#ede9fe; color:#5b2e91; }
.als-badge.blue { background:#dbeafe; color:#1e40af; }
.als-empty { text-align:center; padding:40px 20px; color:#94a3b8; font-size:14px; }
.als-header-row { display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; }
.als-header-row a { color:#5b2e91; text-decoration:none; font-size:13px; font-weight:600; }
.als-stat-pills { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; }
.als-stat-pill { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:10px 16px; font-size:12px; }
.als-stat-pill strong { display:block; font-size:20px; color:#5b2e91; font-weight:800; margin-bottom:2px; }
.als-role-grid { display:grid; grid-template-columns:repeat(5, 1fr); gap:8px; }
@media(max-width:900px){ .als-role-grid { grid-template-columns:repeat(2, 1fr); } }
.als-role-chip {
    display:flex; align-items:center; gap:6px; padding:10px 12px; border:2px solid #e2e8f0;
    border-radius:8px; cursor:pointer; background:#fff; transition:all .15s;
    font-size:12px; font-weight:600; color:#334155; user-select:none;
}
.als-role-chip input[type=checkbox] { margin:0; accent-color:#5b2e91; }
.als-role-chip:has(input:checked) { border-color:#5b2e91; background:#faf7ff; color:#5b2e91; }
.als-role-chip .emoji { font-size:16px; }
.als-role-presets { display:flex; gap:6px; margin-bottom:10px; flex-wrap:wrap; }
.als-role-preset {
    padding:4px 10px; border:1px solid #ddd; border-radius:12px; background:#fff;
    font-size:11px; cursor:pointer; color:#64748b;
}
.als-role-preset:hover { border-color:#5b2e91; color:#5b2e91; }
.als-role-tags { display:flex; flex-wrap:wrap; gap:3px; }
.als-role-tag { background:#ede9fe; color:#5b2e91; padding:2px 6px; border-radius:6px; font-size:10px; font-weight:600; }

/* Bulk toolbar */
.als-bulk-bar {
    position:sticky; top:0; z-index:5;
    background:#5b2e91; color:#fff; padding:12px 16px; border-radius:10px;
    margin-bottom:12px; display:none; align-items:center; gap:12px; flex-wrap:wrap;
}
.als-bulk-bar.active { display:flex; }
.als-bulk-bar .count { font-weight:700; }
.als-bulk-bar select, .als-bulk-bar button {
    padding:6px 10px; border:none; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer;
}
.als-bulk-bar select { background:#fff; color:#0f172a; }
.als-bulk-bar button { background:#fff; color:#5b2e91; }
.als-bulk-bar button.danger { background:#fee2e2; color:#991b1b; }
.als-bulk-bar button:hover { opacity:.85; }
.als-bulk-bar .spacer { flex:1; }
.als-bulk-roles { display:flex; gap:6px; flex-wrap:wrap; align-items:center; }
.als-bulk-roles label { display:flex; align-items:center; gap:4px; font-size:11px; cursor:pointer; }
.als-bulk-roles input { accent-color:#fff; }

/* Edit modal */
.als-modal-backdrop {
    display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:100;
    align-items:center; justify-content:center; padding:20px;
}
.als-modal-backdrop.active { display:flex; }
.als-modal {
    background:#fff; border-radius:14px; max-width:600px; width:100%;
    max-height:90vh; overflow-y:auto; padding:24px;
}
.als-modal h3 { margin:0 0 14px; font-size:17px; color:#0f172a; }
.als-modal-close { float:right; background:transparent; border:none; font-size:22px; color:#64748b; cursor:pointer; line-height:1; }
</style>

<div class="als-wrap">

    @if (session('status'))
        <div class="als-msg-ok">✅ {{ session('status') }}</div>
    @endif

    <div class="als-header-row">
        <div class="als-stat-pills">
            <div class="als-stat-pill"><strong>{{ $sources->count() }}</strong>Toplam kaynak</div>
            <div class="als-stat-pill"><strong>{{ $sources->where('is_active', true)->count() }}</strong>Aktif</div>
            <div class="als-stat-pill"><strong>{{ $sources->where('type', 'pdf')->count() }}</strong>PDF</div>
            <div class="als-stat-pill"><strong>{{ $sources->where('type', 'url')->count() }}</strong>URL</div>
            <div class="als-stat-pill"><strong>{{ $sources->where('type', 'text')->count() }}</strong>Metin</div>
        </div>
        <a href="{{ url('/manager/ai-labs/settings') }}">⚙️ Ayarlar →</a>
    </div>

    {{-- Yeni kaynak ekle --}}
    <div class="als-card">
        <h2>➕ Yeni Kaynak Ekle</h2>
        <p class="hint">
            AI asistan ve içerik üretici yalnızca burada aktif olan kaynaklardan yararlanır.
            PDF, web URL veya düz metin olarak ekleyebilirsin.
        </p>

        <div class="als-type-tabs" id="als-tabs">
            <div class="als-type-tab active" data-type="file">📎 Dosya Yükle</div>
            <div class="als-type-tab" data-type="url">🌐 URL Ekle</div>
            <div class="als-type-tab" data-type="bulk_urls">📋 Toplu URL</div>
            <div class="als-type-tab" data-type="text">✏️ Düz Metin</div>
        </div>

        <form method="POST" action="{{ url('/manager/ai-labs/sources') }}" enctype="multipart/form-data" id="als-form">
            @csrf
            <input type="hidden" name="type" id="als-type-input" value="file">

            <div class="als-grid-2" style="margin-bottom:14px;">
                <div class="als-field">
                    <label>Başlık *</label>
                    <input type="text" name="title" required maxlength="200" placeholder="örn: Uni-Assist Başvuru Rehberi">
                </div>
                <div class="als-field">
                    <label>Kategori</label>
                    <input type="text" name="category" maxlength="80" placeholder="örn: uni-assist, vize, sperrkonto">
                </div>
            </div>

            <div class="als-field" style="margin-bottom:14px;">
                <label>Görünürlük — Hangi rollere açık olacak? *</label>
                <div class="als-role-presets">
                    <button type="button" class="als-role-preset" data-preset="external">👥 Dış roller (Aday + Öğrenci)</button>
                    <button type="button" class="als-role-preset" data-preset="internal">🏢 İç roller (Senior + Manager + Admin)</button>
                    <button type="button" class="als-role-preset" data-preset="all">🌐 Hepsi</button>
                    <button type="button" class="als-role-preset" data-preset="none">Temizle</button>
                </div>
                <div class="als-role-grid" id="als-role-grid">
                    <label class="als-role-chip"><input type="checkbox" name="visible_to_roles[]" value="guest" checked> <span class="emoji">🙋</span> Aday Öğrenci</label>
                    <label class="als-role-chip"><input type="checkbox" name="visible_to_roles[]" value="student" checked> <span class="emoji">🎓</span> Öğrenci</label>
                    <label class="als-role-chip"><input type="checkbox" name="visible_to_roles[]" value="senior"> <span class="emoji">👨‍🏫</span> Eğitim Danışmanı</label>
                    <label class="als-role-chip"><input type="checkbox" name="visible_to_roles[]" value="manager"> <span class="emoji">👔</span> Yönetici</label>
                    <label class="als-role-chip"><input type="checkbox" name="visible_to_roles[]" value="admin_staff"> <span class="emoji">🏢</span> Admin Personel</label>
                </div>
                <small style="font-size:11px; color:#64748b; display:block; margin-top:6px;">
                    Dış roller: müşteri-yüzlü kaynaklar (vize, Sperrkonto, Uni-Assist).
                    İç roller: operasyonel bilgi (senior prosedürleri, ödeme kuralları, yönetim raporları).
                </small>
            </div>

            {{-- Dosya panel (PDF / Word / Excel / TXT) --}}
            <div class="als-type-panel active" data-panel="file">
                <div class="als-field">
                    <label>Dosya (max 15 MB) — Desteklenen: PDF, DOCX, XLSX, XLS, TXT, MD</label>
                    <input type="file" name="doc_file" accept=".pdf,.docx,.xlsx,.xls,.txt,.md,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/plain,text/markdown">
                    <small style="font-size:11px; color:#64748b; display:block; margin-top:6px;">
                        📄 <strong>PDF:</strong> Gemini File API'ye yüklenir (görsel + tablo içerik dahil).<br>
                        📝 <strong>Word/Excel/TXT:</strong> Metin içeriği otomatik çıkartılır, AI'a inline gönderilir.
                    </small>
                </div>
            </div>

            {{-- URL panel --}}
            <div class="als-type-panel" data-panel="url">
                <div class="als-field">
                    <label>URL</label>
                    <input type="url" name="url" placeholder="https://www.uni-assist.de/..." maxlength="500">
                </div>
            </div>

            {{-- Text panel --}}
            <div class="als-type-panel" data-panel="text">
                <div class="als-field">
                    <label>İçerik (Markdown destekli)</label>
                    <textarea name="content_text" placeholder="Yapıştırılacak metin... Max 60.000 karakter."></textarea>
                </div>
            </div>

            <div style="margin-top:14px;" id="als-single-submit">
                <button type="submit" class="als-btn als-btn-primary">Kaynağı Kaydet</button>
            </div>
        </form>

        {{-- Bulk URLs panel — ayrı form (farklı endpoint) --}}
        <div class="als-type-panel" data-panel="bulk_urls" style="display:none;">
            <form method="POST" action="{{ url('/manager/ai-labs/sources/bulk-urls') }}" id="bulk-urls-form">
                @csrf

                <div class="als-grid-2" style="margin-bottom:14px;">
                    <div class="als-field">
                        <label>Kategori (hepsine uygulanır)</label>
                        <input type="text" name="category" maxlength="80" placeholder="örn: eğitim, konaklama, vize">
                    </div>
                </div>

                <div class="als-field" style="margin-bottom:14px;">
                    <label>Görünürlük — Hangi rollere açık olacak? *</label>
                    <div class="als-role-presets">
                        <button type="button" class="als-role-preset" data-bulk-url-preset="external">👥 Dış roller</button>
                        <button type="button" class="als-role-preset" data-bulk-url-preset="internal">🏢 İç roller</button>
                        <button type="button" class="als-role-preset" data-bulk-url-preset="all">🌐 Hepsi</button>
                        <button type="button" class="als-role-preset" data-bulk-url-preset="none">Temizle</button>
                    </div>
                    <div class="als-role-grid" id="bulk-urls-role-grid">
                        <label class="als-role-chip"><input type="checkbox" name="visible_to_roles[]" value="guest" checked> <span class="emoji">🙋</span> Aday</label>
                        <label class="als-role-chip"><input type="checkbox" name="visible_to_roles[]" value="student" checked> <span class="emoji">🎓</span> Öğrenci</label>
                        <label class="als-role-chip"><input type="checkbox" name="visible_to_roles[]" value="senior"> <span class="emoji">👨‍🏫</span> Senior</label>
                        <label class="als-role-chip"><input type="checkbox" name="visible_to_roles[]" value="manager"> <span class="emoji">👔</span> Yönetici</label>
                        <label class="als-role-chip"><input type="checkbox" name="visible_to_roles[]" value="admin_staff"> <span class="emoji">🏢</span> Admin</label>
                    </div>
                </div>

                <div class="als-field">
                    <label>URL'ler — her satıra bir URL (veya virgülle ayırarak)</label>
                    <textarea name="urls_text" rows="10" required placeholder="https://www.mentorde.com/tr/almanya-universiteleri&#10;https://mentorde.com/tr/almanya-da-erasmus-sureci&#10;https://www.wg-gesucht.de/&#10;..." style="font-family:monospace; font-size:12px;"></textarea>
                    <small style="font-size:11px; color:#64748b; display:block; margin-top:6px;">
                        📋 Max 50 URL. Her biri için içerik otomatik fetch edilir (HTML → text).
                        Duplicate URL'ler (zaten eklenmiş) atlanır. Fetch başarısız olanlar raporlanır.
                        İşlem 10-60 saniye sürebilir, sayfa yenilenmesini bekle.
                    </small>
                </div>

                <div style="background:#fef3c7; border:1px solid #fcd34d; color:#92400e; padding:10px 14px; border-radius:8px; font-size:11.5px; margin:12px 0;">
                    ⏳ Fetch süreci: ~2-5 saniye/URL. 20 URL için yaklaşık 1-2 dakika.
                </div>

                <div style="margin-top:14px;">
                    <button type="submit" class="als-btn als-btn-primary" id="bulk-urls-submit">📋 Toplu URL Ekle</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Bulk action toolbar (sticky, sadece seçim olunca görünür) --}}
    <form method="POST" action="{{ url('/manager/ai-labs/sources/bulk') }}" id="bulk-form">
        @csrf
        <div class="als-bulk-bar" id="bulk-bar">
            <span class="count"><span id="bulk-count">0</span> kaynak seçildi</span>

            @php $bulkCanDelete = in_array(auth()->user()?->role, \App\Models\User::ADMIN_PANEL_ROLES, true); @endphp
            <select name="action" id="bulk-action">
                <option value="">— İşlem seç —</option>
                <option value="add_role">➕ Rol ekle</option>
                <option value="remove_role">➖ Rol çıkar</option>
                <option value="replace_roles">🔄 Rolleri yenile (sıfırla + ekle)</option>
                <option value="activate">✅ Aktifleştir</option>
                <option value="deactivate">⏸ Pasifleştir</option>
                @if ($bulkCanDelete)
                    <option value="delete">🗑 Sil</option>
                @endif
            </select>

            <div class="als-bulk-roles" id="bulk-roles" style="display:none;">
                <label><input type="checkbox" name="roles[]" value="guest"> 🙋 Aday</label>
                <label><input type="checkbox" name="roles[]" value="student"> 🎓 Öğrenci</label>
                <label><input type="checkbox" name="roles[]" value="senior"> 👨‍🏫 Senior</label>
                <label><input type="checkbox" name="roles[]" value="manager"> 👔 Yönetici</label>
                <label><input type="checkbox" name="roles[]" value="admin_staff"> 🏢 Admin</label>
            </div>

            <div class="spacer"></div>
            <button type="submit" id="bulk-apply">Uygula</button>
            <button type="button" id="bulk-cancel" style="background:transparent; color:#fff; border:1px solid rgba(255,255,255,.4);">İptal</button>
        </div>
    </form>

    {{-- Mevcut kaynaklar --}}
    <div class="als-card">
        <h2>📚 Mevcut Kaynaklar ({{ $sources->count() }})</h2>
        <p class="hint">Aktif kaynaklar AI asistan tarafından kullanılır. Pasif kaynaklar kaydedilir ama sorgulanmaz. <strong>Satır başındaki checkbox'la çoklu seçim + toplu işlem yapabilirsin.</strong></p>

        @if ($sources->isEmpty())
            <div class="als-empty">
                Henüz kaynak eklenmemiş. Yukarıdaki formdan ilk kaynağını ekle.
            </div>
        @else
            <table class="als-list">
                <thead>
                <tr>
                    <th style="width:30px;"><input type="checkbox" id="select-all" title="Hepsini seç"></th>
                    <th>Başlık</th>
                    <th>Tip</th>
                    <th>Kategori</th>
                    <th>Görünürlük</th>
                    <th>Durum</th>
                    <th>Sync</th>
                    <th>Eklendi</th>
                    <th style="text-align:right;">İşlem</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($sources as $s)
                    <tr data-source-id="{{ $s->id }}"
                        data-title="{{ e($s->title) }}"
                        data-category="{{ e($s->category ?? '') }}"
                        data-roles="{{ implode(',', $s->visible_to_roles ?? []) }}">
                        <td>
                            <input type="checkbox" class="row-check" value="{{ $s->id }}">
                        </td>
                        <td>
                            <strong>{{ $s->title }}</strong>
                            @if ($s->type === 'url' && $s->url)
                                <br><a href="{{ $s->url }}" target="_blank" style="font-size:11px; color:#64748b;">🔗 {{ \Illuminate\Support\Str::limit($s->url, 60) }}</a>
                            @elseif (in_array($s->type, ['pdf', 'document']) && $s->file_path)
                                <br><span style="font-size:11px; color:#64748b;">📎 {{ basename($s->file_path) }}</span>
                            @endif
                        </td>
                        <td>
                            @if ($s->type === 'pdf')         <span class="als-badge blue">📄 PDF</span>
                            @elseif ($s->type === 'document')
                                @php $ext = strtolower(pathinfo($s->file_path ?? '', PATHINFO_EXTENSION)); @endphp
                                @if (in_array($ext, ['docx','doc']))  <span class="als-badge blue" style="background:#dbeafe; color:#1e40af;">📝 Word</span>
                                @elseif (in_array($ext, ['xlsx','xls'])) <span class="als-badge" style="background:#dcfce7; color:#166534;">📊 Excel</span>
                                @elseif (in_array($ext, ['txt','md'])) <span class="als-badge gray">📜 Metin</span>
                                @else                                   <span class="als-badge gray">📄 Belge</span>
                                @endif
                            @elseif ($s->type === 'url')     <span class="als-badge purple">🌐 URL</span>
                            @else                            <span class="als-badge gray">✏️ Metin</span>
                            @endif
                        </td>
                        <td style="font-size:12px; color:#64748b;">{{ $s->category ?: '—' }}</td>
                        <td>
                            @php
                                $roleIcons = [
                                    'guest' => '🙋', 'student' => '🎓',
                                    'senior' => '👨‍🏫', 'manager' => '👔', 'admin_staff' => '🏢',
                                ];
                                $sourceRoles = $s->visible_to_roles ?: [];
                            @endphp
                            <div class="als-role-tags">
                                @foreach ($sourceRoles as $r)
                                    <span class="als-role-tag" title="{{ \App\Models\KnowledgeSource::ROLE_LABELS[$r] ?? $r }}">{{ $roleIcons[$r] ?? '•' }}</span>
                                @endforeach
                                @if (empty($sourceRoles))
                                    <span style="font-size:10px; color:#94a3b8;">— yok —</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if ($s->is_active)
                                <span class="als-badge green">Aktif</span>
                            @else
                                <span class="als-badge gray">Pasif</span>
                            @endif
                        </td>
                        <td style="font-size:11px;">
                            @if ($s->type === 'pdf')
                                @if ($s->gemini_file_id)
                                    <span class="als-badge green" title="Gemini'ye yüklendi: {{ $s->gemini_uploaded_at?->format('d.m.Y H:i') }}">☁️ Synced</span>
                                @else
                                    <span class="als-badge" style="background:#fef3c7; color:#92400e;">⏳ Bekliyor</span>
                                @endif
                            @elseif ($s->type === 'url')
                                @if ($s->content_markdown && strlen($s->content_markdown) > 200)
                                    <span class="als-badge green" title="İçerik çekildi ({{ number_format(strlen($s->content_markdown)/1024, 1) }} KB)">📥 Çekildi</span>
                                @else
                                    <span class="als-badge" style="background:#fee2e2; color:#991b1b;" title="URL içeriği boş">⚠️ Boş</span>
                                @endif
                            @else
                                <span style="color:#94a3b8;">—</span>
                            @endif
                        </td>
                        <td style="font-size:11px; color:#64748b;">{{ $s->created_at?->format('d.m.Y') }}</td>
                        @php $canDelete = in_array(auth()->user()?->role, \App\Models\User::ADMIN_PANEL_ROLES, true); @endphp
                        <td style="text-align:right; white-space:nowrap;">
                            <button type="button" class="als-btn als-btn-ghost als-btn-sm edit-btn" title="Görünürlük/başlık düzenle">✏️</button>
                            @if ($s->type === 'url')
                                <form method="POST" action="{{ url('/manager/ai-labs/sources/' . $s->id . '/refetch') }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="als-btn als-btn-ghost als-btn-sm" title="URL içeriğini yeniden çek">🔄</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ url('/manager/ai-labs/sources/' . $s->id . '/toggle') }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="als-btn als-btn-ghost als-btn-sm">
                                    {{ $s->is_active ? 'Pasifleştir' : 'Aktifleştir' }}
                                </button>
                            </form>
                            @if ($canDelete)
                                <form method="POST" action="{{ url('/manager/ai-labs/sources/' . $s->id) }}" style="display:inline;" onsubmit="return confirm('Silinecek — emin misin?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="als-btn als-btn-danger als-btn-sm">Sil</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

</div>

{{-- Edit Modal: tek kaynak görünürlük/başlık düzenleme --}}
<div class="als-modal-backdrop" id="edit-modal">
    <div class="als-modal">
        <button type="button" class="als-modal-close" id="edit-close">×</button>
        <h3>✏️ Kaynağı Düzenle</h3>
        <form method="POST" id="edit-form">
            @csrf
            @method('PUT')
            <div class="als-field">
                <label>Başlık *</label>
                <input type="text" name="title" id="edit-title" required maxlength="200">
            </div>
            <div class="als-field">
                <label>Kategori</label>
                <input type="text" name="category" id="edit-category" maxlength="80">
            </div>
            <div class="als-field">
                <label>Görünürlük — Hangi roller bu kaynağı görsün?</label>
                <div class="als-role-presets">
                    <button type="button" class="als-role-preset" data-edit-preset="external">👥 Dış roller</button>
                    <button type="button" class="als-role-preset" data-edit-preset="internal">🏢 İç roller</button>
                    <button type="button" class="als-role-preset" data-edit-preset="all">🌐 Hepsi</button>
                    <button type="button" class="als-role-preset" data-edit-preset="none">Temizle</button>
                </div>
                <div class="als-role-grid" id="edit-role-grid">
                    <label class="als-role-chip"><input type="checkbox" name="visible_to_roles[]" value="guest"> <span class="emoji">🙋</span> Aday</label>
                    <label class="als-role-chip"><input type="checkbox" name="visible_to_roles[]" value="student"> <span class="emoji">🎓</span> Öğrenci</label>
                    <label class="als-role-chip"><input type="checkbox" name="visible_to_roles[]" value="senior"> <span class="emoji">👨‍🏫</span> Senior</label>
                    <label class="als-role-chip"><input type="checkbox" name="visible_to_roles[]" value="manager"> <span class="emoji">👔</span> Yönetici</label>
                    <label class="als-role-chip"><input type="checkbox" name="visible_to_roles[]" value="admin_staff"> <span class="emoji">🏢</span> Admin</label>
                </div>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:14px;">
                <button type="button" class="als-btn als-btn-ghost" id="edit-cancel">İptal</button>
                <button type="submit" class="als-btn als-btn-primary">💾 Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    const tabs = document.querySelectorAll('#als-tabs .als-type-tab');
    const panels = document.querySelectorAll('.als-type-panel');
    const typeInput = document.getElementById('als-type-input');

    const mainForm = document.getElementById('als-form');
    const bulkUrlsPanel = document.querySelector('.als-type-panel[data-panel="bulk_urls"]');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const t = tab.dataset.type;
            tabs.forEach(x => x.classList.toggle('active', x === tab));

            if (t === 'bulk_urls') {
                // Ana form'u gizle, bulk panel'i göster
                mainForm.style.display = 'none';
                if (bulkUrlsPanel) bulkUrlsPanel.style.display = 'block';
                // Diğer panel'leri kapat
                panels.forEach(p => p.classList.remove('active'));
            } else {
                mainForm.style.display = '';
                if (bulkUrlsPanel) bulkUrlsPanel.style.display = 'none';
                panels.forEach(p => p.classList.toggle('active', p.dataset.panel === t));
                typeInput.value = t;
            }
        });
    });

    // Bulk URLs role presets
    const bulkUrlPresets = {
        external: ['guest', 'student'],
        internal: ['senior', 'manager', 'admin_staff'],
        all:      ['guest', 'student', 'senior', 'manager', 'admin_staff'],
        none:     [],
    };
    document.querySelectorAll('.als-role-preset[data-bulk-url-preset]').forEach(btn => {
        btn.addEventListener('click', () => {
            const selected = bulkUrlPresets[btn.dataset.bulkUrlPreset] || [];
            document.querySelectorAll('#bulk-urls-role-grid input[type=checkbox]').forEach(cb => {
                cb.checked = selected.includes(cb.value);
            });
        });
    });

    // Bulk URLs submit — loading state
    document.getElementById('bulk-urls-form')?.addEventListener('submit', () => {
        const btn = document.getElementById('bulk-urls-submit');
        btn.disabled = true;
        btn.textContent = '⏳ URL\'ler çekiliyor... (1-2 dk sürebilir)';
    });

    // Rol preset butonları (create form)
    const presets = {
        external: ['guest', 'student'],
        internal: ['senior', 'manager', 'admin_staff'],
        all:      ['guest', 'student', 'senior', 'manager', 'admin_staff'],
        none:     [],
    };
    document.querySelectorAll('.als-role-preset[data-preset]').forEach(btn => {
        btn.addEventListener('click', () => {
            const selected = presets[btn.dataset.preset] || [];
            document.querySelectorAll('#als-role-grid input[type=checkbox]').forEach(cb => {
                cb.checked = selected.includes(cb.value);
            });
        });
    });

    // ── Bulk select ───────────────────────────────────────────────
    const bulkBar = document.getElementById('bulk-bar');
    const bulkCount = document.getElementById('bulk-count');
    const bulkForm = document.getElementById('bulk-form');
    const selectAll = document.getElementById('select-all');
    const rowChecks = document.querySelectorAll('.row-check');
    const bulkAction = document.getElementById('bulk-action');
    const bulkRoles = document.getElementById('bulk-roles');

    function updateBulk() {
        const checked = Array.from(rowChecks).filter(c => c.checked);
        const n = checked.length;
        bulkCount.textContent = n;
        bulkBar.classList.toggle('active', n > 0);
    }

    selectAll?.addEventListener('change', () => {
        rowChecks.forEach(c => c.checked = selectAll.checked);
        updateBulk();
    });
    rowChecks.forEach(c => c.addEventListener('change', updateBulk));

    bulkAction?.addEventListener('change', () => {
        const needsRoles = ['add_role', 'remove_role', 'replace_roles'].includes(bulkAction.value);
        bulkRoles.style.display = needsRoles ? 'flex' : 'none';
    });

    document.getElementById('bulk-cancel')?.addEventListener('click', () => {
        rowChecks.forEach(c => c.checked = false);
        selectAll && (selectAll.checked = false);
        updateBulk();
    });

    bulkForm?.addEventListener('submit', (e) => {
        const checked = Array.from(rowChecks).filter(c => c.checked).map(c => c.value);
        if (checked.length === 0) { e.preventDefault(); alert('En az 1 kaynak seç.'); return; }
        if (!bulkAction.value) { e.preventDefault(); alert('Bir işlem seç.'); return; }
        if (bulkAction.value === 'delete' && !confirm(`${checked.length} kaynak silinecek — emin misin?`)) { e.preventDefault(); return; }

        // Gizli input'larla seçili ID'leri forma ekle
        bulkForm.querySelectorAll('input[name="ids[]"]').forEach(n => n.remove());
        checked.forEach(id => {
            const hi = document.createElement('input');
            hi.type = 'hidden'; hi.name = 'ids[]'; hi.value = id;
            bulkForm.appendChild(hi);
        });
    });

    // ── Edit Modal ─────────────────────────────────────────────────
    const modal = document.getElementById('edit-modal');
    const editForm = document.getElementById('edit-form');
    const editTitle = document.getElementById('edit-title');
    const editCategory = document.getElementById('edit-category');
    const editGrid = document.getElementById('edit-role-grid');

    function openEditModal(row) {
        const id = row.dataset.sourceId;
        const title = row.dataset.title || '';
        const cat = row.dataset.category || '';
        const roles = (row.dataset.roles || '').split(',').filter(Boolean);

        editForm.action = '/manager/ai-labs/sources/' + id;
        editTitle.value = title;
        editCategory.value = cat;
        editGrid.querySelectorAll('input[type=checkbox]').forEach(cb => {
            cb.checked = roles.includes(cb.value);
        });

        modal.classList.add('active');
    }

    function closeEditModal() {
        modal.classList.remove('active');
    }

    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const row = btn.closest('tr[data-source-id]');
            if (row) openEditModal(row);
        });
    });

    document.getElementById('edit-close')?.addEventListener('click', closeEditModal);
    document.getElementById('edit-cancel')?.addEventListener('click', closeEditModal);
    modal?.addEventListener('click', (e) => { if (e.target === modal) closeEditModal(); });

    // Edit modal preset butonları
    document.querySelectorAll('.als-role-preset[data-edit-preset]').forEach(btn => {
        btn.addEventListener('click', () => {
            const selected = presets[btn.dataset.editPreset] || [];
            editGrid.querySelectorAll('input[type=checkbox]').forEach(cb => {
                cb.checked = selected.includes(cb.value);
            });
        });
    });
})();
</script>
@endsection
