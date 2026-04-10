<dialog id="dam-link-modal" style="border:none;border-radius:14px;padding:0;max-width:520px;width:90vw;box-shadow:0 20px 60px rgba(0,0,0,.25);">
    <form method="POST" action="{{ route($routePrefix . '.links.store') }}" style="padding:24px;">
        @csrf
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
            <h2 style="margin:0;font-size:18px;font-weight:700;">🔗 Harici Link Ekle</h2>
            <button type="button" onclick="document.getElementById('dam-link-modal').close()"
                    style="background:none;border:none;font-size:24px;cursor:pointer;color:#64748b;line-height:1;">×</button>
        </div>

        @if($currentFolder)
            <input type="hidden" name="folder_id" value="{{ $currentFolder->id }}">
            <div style="font-size:13px;color:var(--text-muted,#64748b);margin-bottom:14px;">
                Hedef klasör: <strong style="color:var(--text,#0f172a);">{{ $currentFolder->name }}</strong>
            </div>
        @else
            <div style="font-size:13px;color:var(--text-muted,#64748b);margin-bottom:14px;">
                Hedef: <strong style="color:var(--text,#0f172a);">Kök</strong>
            </div>
        @endif

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">URL *</label>
            <input type="url" name="external_url" required maxlength="1000"
                   placeholder="https://drive.google.com/..."
                   style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px;font-size:13px;">
            <div style="font-size:11px;color:#94a3b8;margin-top:4px;">
                Google Drive, YouTube, Notion, Dropbox vb. herhangi bir public link.
            </div>
        </div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Görünen Ad (opsiyonel)</label>
            <input type="text" name="name" maxlength="200"
                   placeholder="Boş bırakırsanız URL'den otomatik üretilir"
                   style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
        </div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Kategori</label>
            <select name="category"
                    style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;font-size:13px;">
                <option value="">Otomatik tespit et (önerilen)</option>
                <option value="image">🖼️ Görsel</option>
                <option value="video">🎬 Video</option>
                <option value="audio">🎵 Ses</option>
                <option value="document">📄 Doküman</option>
                <option value="archive">🗜️ Arşiv</option>
                <option value="other">📎 Diğer</option>
            </select>
        </div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Etiketler (opsiyonel)</label>
            <div id="dam-link-tag-chips" style="display:flex;flex-wrap:wrap;gap:6px;min-height:36px;padding:6px;border:1px solid #cbd5e1;border-radius:8px;align-items:center;">
                <input type="text" id="dam-link-tag-input" placeholder="Etiket yazıp Enter..."
                       style="flex:1;min-width:120px;border:none;outline:none;padding:4px 6px;font-size:13px;background:transparent;">
            </div>
        </div>

        <div style="margin-bottom:18px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Açıklama (opsiyonel)</label>
            <textarea name="description" rows="3" maxlength="2000"
                      style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px;resize:vertical;font-size:13px;"></textarea>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <button type="button" onclick="document.getElementById('dam-link-modal').close()"
                    style="padding:10px 18px;border-radius:8px;border:1px solid #cbd5e1;background:#fff;cursor:pointer;font-weight:600;">
                Vazgeç
            </button>
            <button type="submit"
                    style="padding:10px 18px;border-radius:8px;border:none;background:var(--c-accent,#0f172a);color:#fff;cursor:pointer;font-weight:600;">
                Linki Ekle
            </button>
        </div>
    </form>
</dialog>
