<dialog id="dam-upload-modal" style="border:none;border-radius:14px;padding:0;max-width:520px;width:90vw;box-shadow:0 20px 60px rgba(0,0,0,.25);">
    <form method="POST" action="{{ route($routePrefix . '.store') }}" enctype="multipart/form-data" style="padding:24px;">
        @csrf
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
            <h2 style="margin:0;font-size:18px;font-weight:700;">Dosya Yükle</h2>
            <button type="button" onclick="document.getElementById('dam-upload-modal').close()"
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
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">
                Dosyalar * <span style="font-weight:400;color:#64748b;">(birden fazla seçebilirsiniz)</span>
            </label>
            <input type="file" name="files[]" multiple required
                   style="display:block;width:100%;padding:10px;border:1px dashed #cbd5e1;border-radius:8px;background:#f8fafc;">
            <div style="font-size:11px;color:#64748b;margin-top:4px;">
                Maks {{ (int) config('dam.bulk_upload_max_files', 20) }} dosya, her biri en fazla
                {{ round(((int) config('dam.max_size_bytes', 50 * 1024 * 1024)) / 1024 / 1024) }} MB.
            </div>
        </div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">
                Etiketler (opsiyonel)
            </label>
            <div id="dam-tag-chips" style="display:flex;flex-wrap:wrap;gap:6px;min-height:36px;padding:6px;border:1px solid #cbd5e1;border-radius:8px;align-items:center;">
                <input type="text" id="dam-tag-input" placeholder="Etiket yazıp Enter..."
                       style="flex:1;min-width:120px;border:none;outline:none;padding:4px 6px;font-size:13px;background:transparent;">
            </div>
            <div style="font-size:10px;color:#94a3b8;margin-top:4px;">
                Örnek: logo, 2026, kurumsal. Enter ile ekle, × ile sil.
            </div>
        </div>

        <div style="margin-bottom:18px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Açıklama (opsiyonel)</label>
            <textarea name="description" rows="3" maxlength="2000"
                      style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px;resize:vertical;"></textarea>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <button type="button" onclick="document.getElementById('dam-upload-modal').close()"
                    style="padding:10px 18px;border-radius:8px;border:1px solid #cbd5e1;background:#fff;cursor:pointer;font-weight:600;">
                Vazgeç
            </button>
            <button type="submit"
                    style="padding:10px 18px;border-radius:8px;border:none;background:var(--c-accent,#0f172a);color:#fff;cursor:pointer;font-weight:600;">
                Yükle
            </button>
        </div>
    </form>
</dialog>
