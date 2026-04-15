<dialog id="dam-notify-modal" style="border:none;border-radius:14px;padding:0;max-width:520px;width:90vw;box-shadow:0 20px 60px rgba(0,0,0,.25);background:#fff;">
    <form id="dam-notify-form" method="POST" action="" style="padding:24px;">
        @csrf
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
            <h3 style="margin:0;font-size:18px;font-weight:700;color:#0f172a;">📢 Dosyayı Bildir</h3>
            <button type="button" id="dam-notify-close" style="background:none;border:none;font-size:22px;cursor:pointer;color:#64748b;line-height:1;">×</button>
        </div>

        <div style="font-size:13px;color:#64748b;margin-bottom:16px;">
            <strong id="dam-notify-asset-name" style="color:#0f172a;">...</strong> dosyasını seçilen kişilere/gruplara bildirmek için.
        </div>

        @php
            $mentionRoleGroups = $mentionRoleGroups ?? [];
            $mentionableUsers  = $mentionableUsers ?? collect();
            $hasRoleGroups     = !empty($mentionRoleGroups);
            $hasIndividuals    = !empty($mentionableUsers) && count($mentionableUsers) > 0;
        @endphp

        @if($hasRoleGroups)
        <div style="font-size:10px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px;">Toplu duyuru</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:5px;margin-bottom:12px;">
            @foreach($mentionRoleGroups as $gkey => $group)
                <label class="dam-nm-group-row"
                       style="display:flex;align-items:center;gap:6px;padding:7px 9px;border:1px solid #e2e8f0;border-radius:6px;background:#fff;cursor:pointer;font-size:11px;">
                    <input type="checkbox" name="notify_role_groups[]" value="{{ $gkey }}" class="dam-nm-group-cb"
                           data-count="{{ $group['count'] }}"
                           style="cursor:pointer;margin:0;">
                    <span style="font-size:14px;line-height:1;">{{ $group['icon'] }}</span>
                    <span style="flex:1;color:#0f172a;font-weight:600;">{{ $group['label'] }}</span>
                    <span style="font-size:9px;background:#f1f5f9;color:#64748b;padding:1px 6px;border-radius:99px;font-weight:700;">{{ $group['count'] }}</span>
                </label>
            @endforeach
        </div>
        <div id="dam-nm-warn"
             style="display:none;font-size:10px;color:#92400e;background:#fef3c7;border:1px solid #fde68a;padding:6px 8px;border-radius:6px;margin-bottom:10px;">
            ⚠ Toplu duyuru çok sayıda kişiye bildirim gönderir. Emin misin?
        </div>
        @endif

        @if($hasIndividuals)
        <div style="font-size:10px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px;">Belirli kişiler (ekip)</div>
        <input type="text" id="dam-nm-search" placeholder="🔍 İsim ara..."
               style="width:100%;padding:7px 10px;border:1px solid #cbd5e1;border-radius:6px;font-size:12px;background:#fff;margin-bottom:8px;box-sizing:border-box;">
        <div style="max-height:180px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:6px;background:#fff;">
            @foreach($mentionableUsers as $mu)
                @php $mrole = ucwords(str_replace('_', ' ', (string) $mu->role)); @endphp
                <label class="dam-nm-row" data-name="{{ strtolower($mu->name) }}"
                       style="display:flex;align-items:center;gap:8px;padding:7px 10px;cursor:pointer;border-bottom:1px solid #f1f5f9;font-size:12px;">
                    <input type="checkbox" name="notify_user_ids[]" value="{{ $mu->id }}" class="dam-nm-cb"
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

        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:18px;gap:10px;">
            <span id="dam-nm-count" style="font-size:11px;color:#64748b;">0 kişi seçildi</span>
            <div style="display:flex;gap:8px;">
                <button type="button" id="dam-notify-cancel"
                        style="padding:9px 16px;border-radius:7px;border:1px solid #e2e8f0;background:#fff;color:#64748b;cursor:pointer;font-size:13px;font-weight:600;">
                    İptal
                </button>
                <button type="submit"
                        style="padding:9px 18px;border-radius:7px;border:none;background:#3730a3;color:#fff;cursor:pointer;font-size:13px;font-weight:600;">
                    📢 Bildir
                </button>
            </div>
        </div>
    </form>
</dialog>
