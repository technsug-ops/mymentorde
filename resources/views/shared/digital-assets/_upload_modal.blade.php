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

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">Açıklama (opsiyonel)</label>
            <textarea name="description" rows="3" maxlength="2000"
                      style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px;resize:vertical;"></textarea>
        </div>

        {{-- Mention / Bildirim — opsiyonel --}}
        @php
            $hasRoleGroups   = !empty($mentionRoleGroups);
            $hasIndividuals  = !empty($mentionableUsers) && count($mentionableUsers) > 0;
        @endphp
        @if($hasRoleGroups || $hasIndividuals)
        <details id="dam-notify-block" style="margin-bottom:18px;border:1px solid #e0e7ff;border-radius:8px;background:#f8faff;">
            <summary style="padding:10px 12px;cursor:pointer;font-size:12px;font-weight:600;color:#3730a3;list-style:none;display:flex;align-items:center;gap:6px;">
                <span>🔔</span>
                <span>Kime bildirilsin? (opsiyonel)</span>
                <span style="flex:1"></span>
                <span id="dam-notify-count" style="font-size:10px;background:#e0e7ff;color:#3730a3;padding:2px 8px;border-radius:99px;font-weight:700;display:none;">0 kişi</span>
            </summary>
            <div style="padding:0 12px 12px;">
                <div style="font-size:11px;color:#64748b;margin-bottom:8px;">
                    Dosya yüklenince seçilen kişilere/gruplara bildirim gönderilir. Örn: bir sertifikayı tüm öğrencilere duyurmak, afişi isteyen kişiyi etiketlemek.
                </div>

                @if($hasRoleGroups)
                {{-- Rol bazlı toplu duyuru grupları --}}
                <div style="font-size:10px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px;">Toplu duyuru</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:5px;margin-bottom:12px;">
                    @foreach($mentionRoleGroups as $gkey => $group)
                        <label class="dam-notify-group-row"
                               style="display:flex;align-items:center;gap:6px;padding:7px 9px;border:1px solid #e2e8f0;border-radius:6px;background:#fff;cursor:pointer;font-size:11px;">
                            <input type="checkbox" name="notify_role_groups[]" value="{{ $gkey }}" class="dam-notify-group-cb"
                                   data-count="{{ $group['count'] }}"
                                   style="cursor:pointer;margin:0;">
                            <span style="font-size:14px;line-height:1;">{{ $group['icon'] }}</span>
                            <span style="flex:1;color:#0f172a;font-weight:600;">{{ $group['label'] }}</span>
                            <span style="font-size:9px;background:#f1f5f9;color:#64748b;padding:1px 6px;border-radius:99px;font-weight:700;">{{ $group['count'] }}</span>
                        </label>
                    @endforeach
                </div>
                <div id="dam-notify-warn"
                     style="display:none;font-size:10px;color:#92400e;background:#fef3c7;border:1px solid #fde68a;padding:6px 8px;border-radius:6px;margin-bottom:10px;">
                    ⚠ Toplu duyuru çok sayıda kişiye bildirim gönderir. Emin misin?
                </div>
                @endif

                @if($hasIndividuals)
                <div style="font-size:10px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px;">Belirli kişiler (ekip)</div>
                <input type="text" id="dam-notify-search" placeholder="🔍 İsim ara..."
                       style="width:100%;padding:7px 10px;border:1px solid #cbd5e1;border-radius:6px;font-size:12px;background:#fff;margin-bottom:8px;box-sizing:border-box;">
                <div id="dam-notify-list"
                     style="max-height:160px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:6px;background:#fff;">
                    @foreach($mentionableUsers as $mu)
                        @php
                            $mrole = ucwords(str_replace('_', ' ', (string) $mu->role));
                        @endphp
                        <label class="dam-notify-row" data-name="{{ strtolower($mu->name) }}"
                               style="display:flex;align-items:center;gap:8px;padding:7px 10px;cursor:pointer;border-bottom:1px solid #f1f5f9;font-size:12px;">
                            <input type="checkbox" name="notify_user_ids[]" value="{{ $mu->id }}" class="dam-notify-cb"
                                   style="cursor:pointer;margin:0;">
                            <span style="flex:1;color:#0f172a;">{{ $mu->name }}</span>
                            <span style="font-size:10px;color:#94a3b8;">{{ $mrole }}</span>
                        </label>
                    @endforeach
                </div>
                @endif

                <label style="display:block;margin-top:12px;">
                    <span style="display:block;font-size:11px;font-weight:600;color:#475569;margin-bottom:4px;">Mesaj / Not (opsiyonel)</span>
                    <input type="text" name="notify_note" maxlength="280" placeholder="örn: Yeni aldığımız sertifikayı sizlerle paylaşmak istedik 🎉"
                           style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;font-size:12px;background:#fff;box-sizing:border-box;">
                </label>
            </div>
        </details>
        <script>
        (function(){
            var block = document.getElementById('dam-notify-block');
            if (!block) return;
            var search = block.querySelector('#dam-notify-search');
            var rows   = block.querySelectorAll('.dam-notify-row');
            var counter = block.querySelector('#dam-notify-count');
            var warn    = block.querySelector('#dam-notify-warn');

            function updateCount() {
                var n = 0;
                block.querySelectorAll('.dam-notify-cb:checked').forEach(function(){ n++; });
                var groupTotal = 0;
                block.querySelectorAll('.dam-notify-group-cb:checked').forEach(function(cb){
                    groupTotal += parseInt(cb.getAttribute('data-count') || '0', 10);
                });
                var total = n + groupTotal;
                if (counter) {
                    counter.textContent = total > 0 ? ('~' + total + ' kişi') : '0 kişi';
                    counter.style.display = total > 0 ? 'inline-block' : 'none';
                }
                if (warn) {
                    warn.style.display = groupTotal >= 50 ? 'block' : 'none';
                }
            }
            if (search) {
                search.addEventListener('input', function(){
                    var q = (this.value || '').toLowerCase().trim();
                    rows.forEach(function(r){
                        var name = r.getAttribute('data-name') || '';
                        r.style.display = (q === '' || name.indexOf(q) !== -1) ? 'flex' : 'none';
                    });
                });
            }
            block.addEventListener('change', function(e){
                if (!e.target || !e.target.classList) return;
                if (e.target.classList.contains('dam-notify-cb') ||
                    e.target.classList.contains('dam-notify-group-cb')) {
                    updateCount();
                }
            });
        })();
        </script>
        @endif

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
