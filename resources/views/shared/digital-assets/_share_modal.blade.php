<dialog id="dam-share-modal" style="border:none;border-radius:14px;padding:0;max-width:500px;width:92vw;box-shadow:0 20px 60px rgba(0,0,0,.2);background:#fff;">
    <form id="dam-share-form" method="POST" action="" style="padding:24px;">
        @csrf
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
            <h3 style="margin:0;font-size:18px;font-weight:700;color:#0f172a;">🔗 Paylaşım Linki Oluştur</h3>
            <button type="button" id="dam-share-close" style="background:none;border:none;font-size:22px;cursor:pointer;color:#64748b;line-height:1;">×</button>
        </div>

        <div style="font-size:13px;color:#64748b;margin-bottom:16px;">
            <strong id="dam-share-asset-name" style="color:#0f172a;">...</strong> dosyası için harici kişilere gönderilebilir bir link oluşturun.
        </div>

        <div style="display:grid;gap:12px;">
            <label style="display:grid;gap:4px;">
                <span style="font-size:11px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:.05em;">Geçerlilik Süresi (saat)</span>
                <select name="expires_hours" style="padding:10px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;background:#fff;">
                    <option value="1">1 saat</option>
                    <option value="24" selected>24 saat (1 gün)</option>
                    <option value="72">3 gün</option>
                    <option value="168">7 gün</option>
                    <option value="720">30 gün</option>
                    <option value="">Sınırsız</option>
                </select>
            </label>

            <label style="display:grid;gap:4px;">
                <span style="font-size:11px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:.05em;">Şifre (opsiyonel)</span>
                <input type="password" name="password" autocomplete="new-password" placeholder="Boş bırakırsanız şifresiz"
                       style="padding:10px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;">
            </label>

            <label style="display:grid;gap:4px;">
                <span style="font-size:11px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:.05em;">Max İndirme Sayısı (opsiyonel)</span>
                <input type="number" name="max_downloads" min="1" max="1000" placeholder="Boş bırakırsanız sınırsız"
                       style="padding:10px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;">
            </label>
        </div>

        <div style="display:flex;gap:10px;margin-top:20px;justify-content:flex-end;">
            <button type="button" id="dam-share-cancel" style="padding:10px 18px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#64748b;cursor:pointer;font-size:13px;font-weight:600;">İptal</button>
            <button type="submit" style="padding:10px 18px;border-radius:8px;border:none;background:#16a34a;color:#fff;cursor:pointer;font-size:13px;font-weight:600;">
                Link Oluştur
            </button>
        </div>
    </form>
</dialog>
