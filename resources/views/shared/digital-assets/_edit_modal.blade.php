<dialog id="dam-edit-modal" style="border:none;border-radius:14px;padding:0;max-width:520px;width:90vw;box-shadow:0 20px 60px rgba(0,0,0,.25);">
    <form id="dam-edit-form" method="POST" action="" style="padding:24px;">
        @csrf
        @method('PUT')
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
            <h2 style="margin:0;font-size:18px;font-weight:700;">Varlığı Düzenle</h2>
            <button type="button" onclick="document.getElementById('dam-edit-modal').close()"
                    style="background:none;border:none;font-size:24px;cursor:pointer;color:#64748b;line-height:1;">×</button>
        </div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Görünen Ad *</label>
            <input type="text" name="name" id="dam-edit-name" required maxlength="200"
                   style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;">
        </div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Açıklama</label>
            <textarea name="description" id="dam-edit-description" rows="3" maxlength="2000"
                      style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px;resize:vertical;font-size:13px;"></textarea>
        </div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Etiketler</label>
            <div id="dam-edit-tag-chips" style="display:flex;flex-wrap:wrap;gap:6px;min-height:36px;padding:6px;border:1px solid #cbd5e1;border-radius:8px;align-items:center;">
                <input type="text" id="dam-edit-tag-input" placeholder="Etiket yazıp Enter..."
                       style="flex:1;min-width:120px;border:none;outline:none;padding:4px 6px;font-size:13px;background:transparent;">
            </div>
        </div>

        <div style="margin-bottom:18px;">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:#334155;">
                <input type="checkbox" name="is_pinned" id="dam-edit-pinned" value="1">
                <span><strong>📌 Sabitle</strong> — bu dosyayı listenin başında tut</span>
            </label>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <button type="button" onclick="document.getElementById('dam-edit-modal').close()"
                    style="padding:10px 18px;border-radius:8px;border:1px solid #cbd5e1;background:#fff;cursor:pointer;font-weight:600;">
                Vazgeç
            </button>
            <button type="submit"
                    style="padding:10px 18px;border-radius:8px;border:none;background:var(--c-accent,#0f172a);color:#fff;cursor:pointer;font-weight:600;">
                Kaydet
            </button>
        </div>
    </form>
</dialog>
