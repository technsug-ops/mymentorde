<dialog id="dam-folder-modal" style="border:none;border-radius:14px;padding:0;max-width:480px;width:90vw;box-shadow:0 20px 60px rgba(0,0,0,.25);">
    <form method="POST" action="{{ route($routePrefix . '.folder.store') }}" style="padding:24px;">
        @csrf
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
            <h2 style="margin:0;font-size:18px;font-weight:700;">Yeni Klasör</h2>
            <button type="button" onclick="document.getElementById('dam-folder-modal').close()"
                    style="background:none;border:none;font-size:24px;cursor:pointer;color:#64748b;line-height:1;">×</button>
        </div>

        @if($currentFolder)
            <input type="hidden" name="parent_id" value="{{ $currentFolder->id }}">
            <div style="font-size:13px;color:var(--text-muted,#64748b);margin-bottom:14px;">
                Üst klasör: <strong style="color:var(--text,#0f172a);">{{ $currentFolder->name }}</strong>
            </div>
        @endif

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Klasör Adı *</label>
            <input type="text" name="name" required maxlength="150" autofocus
                   style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
        </div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Açıklama (opsiyonel)</label>
            <textarea name="description" rows="3" maxlength="1000"
                      style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px;resize:vertical;"></textarea>
        </div>

        {{-- Erişim kontrolü --}}
        <div style="margin-bottom:18px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Kim erişebilsin?</label>
            <div style="padding:10px 12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;">
                @php
                    $roleOptions = [
                        'manager'         => 'Manager',
                        'marketing_admin' => 'Marketing Admin',
                        'marketing_staff' => 'Marketing Staff',
                        'senior'          => 'Eğitim Danışmanı',
                        'mentor'          => 'Mentor',
                        'dealer'          => 'Bayi',
                    ];
                @endphp
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;font-size:12px;">
                    @foreach($roleOptions as $code => $label)
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                            <input type="checkbox" name="allowed_roles[]" value="{{ $code }}">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <div style="font-size:10px;color:#94a3b8;margin-top:8px;padding-top:8px;border-top:1px solid #e2e8f0;">
                    💡 Hiçbirini işaretleme → klasör <strong>herkese</strong> açık olur (varsayılan).
                </div>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <button type="button" onclick="document.getElementById('dam-folder-modal').close()"
                    style="padding:10px 18px;border-radius:8px;border:1px solid #cbd5e1;background:#fff;cursor:pointer;font-weight:600;">
                Vazgeç
            </button>
            <button type="submit"
                    style="padding:10px 18px;border-radius:8px;border:none;background:var(--c-accent,#0f172a);color:#fff;cursor:pointer;font-weight:600;">
                Oluştur
            </button>
        </div>
    </form>
</dialog>
