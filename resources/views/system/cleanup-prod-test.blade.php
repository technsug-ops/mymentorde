@extends('manager.layouts.app')
@section('title', 'Prod Test Temizliği')
@section('page_title', '🧹 Prod Test Temizliği')

@section('content')
<div style="max-width:960px;margin:24px auto;padding:0 16px;">

    <div style="background:#fff1f2;border:2px solid #fecaca;border-radius:12px;padding:20px;margin-bottom:20px;">
        <h2 style="margin:0 0 8px;font-size:18px;color:#7f1d1d;">⚠️ Geri alınamaz işlem</h2>
        <p style="font-size:13px;color:#7f1d1d;line-height:1.6;margin:0;">
            Bu komut prod veritabanındaki test kullanıcılarını ve bağlı kayıtları kalıcı olarak siler.
            Çalıştırmadan önce <strong>mysqldump</strong> al (phpMyAdmin → Dışa Aktar). Sadece SaaS teslimi öncesi son temizlik adımı olarak kullan.
        </p>
    </div>

    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;margin-bottom:20px;">
        <h2 style="margin:0 0 12px;font-size:16px;color:#0f172a;">📋 Ne olacak?</h2>
        <ul style="font-size:13px;color:#334155;line-height:1.8;margin:0 0 12px;padding-left:20px;">
            <li><strong>11 canonical test user</strong> korunur: manager, senior, student, guest, marketing_admin, marketing_staff, sales_admin, sales_staff, dealer×3 (LEA/FRE/B2B tier)</li>
            <li>Diğer tüm user kalıcı silinir (cascade: bağlı tablolar otomatik temizlenir)</li>
            <li><code>student_id</code> string-FK tabloları (documents, student_appointments, payments, risk_scores, vb.) keep listesi dışı kayıtlardan temizlenir</li>
            <li><code>guest_user_id</code> tablolarında keep listesi dışı kayıtlar silinir</li>
            <li>Kalan 11 user'ın email'leri <code>{rol}@panel.mentorde.com</code> formatına çevrilir</li>
            <li>Kalan 11 user'ın şifreleri <code>Mentorde2026!</code> olarak sıfırlanır</li>
        </ul>
    </div>

    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;margin-bottom:20px;">
        <h2 style="margin:0 0 12px;font-size:16px;color:#0f172a;">🔍 Dry-Run Raporu (şu an prod'da ne silinecek)</h2>
        <p style="font-size:12px;color:#64748b;margin:0 0 12px;">
            Bu rapor <strong>prod veritabanından</strong> gerçek sorgu sonuçlarını gösterir. Rakamları doğrula, sonra aşağıdaki butonla gerçek silme yapılır.
        </p>
        <pre style="background:#0f172a;color:#e2e8f0;padding:14px;border-radius:8px;font-family:monospace;font-size:11px;line-height:1.6;white-space:pre-wrap;max-height:500px;overflow:auto;margin:0;">{{ $dryRunOutput ?? '(dry-run çıktısı alınamadı)' }}</pre>
    </div>

    <div style="background:#fff;border:2px solid #dc2626;border-radius:12px;padding:24px;">
        <h2 style="margin:0 0 12px;font-size:16px;color:#7f1d1d;">🔥 Gerçek Temizlik</h2>
        <p style="font-size:13px;color:#334155;line-height:1.6;margin:0 0 16px;">
            Silmeyi başlatmak için aşağıdaki kutuya <code>DELETE_ALL_TEST_DATA</code> yaz ve butona bas.
        </p>
        <form id="cleanupForm" method="POST" action="{{ route('system.cleanup-prod-test') }}" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            @csrf
            <input type="text" name="confirm" placeholder="DELETE_ALL_TEST_DATA yaz"
                   style="padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-family:monospace;font-size:13px;min-width:260px;" />
            <button type="submit" id="cleanupBtn"
                    style="padding:12px 24px;background:#dc2626;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;">
                🔥 TEMİZLEMEYİ BAŞLAT
            </button>
        </form>

        <div id="cleanupOutput" style="display:none;margin-top:20px;background:#0f172a;color:#e2e8f0;padding:14px;border-radius:8px;font-family:monospace;font-size:11px;line-height:1.6;white-space:pre-wrap;max-height:500px;overflow:auto;"></div>
    </div>

</div>

<script nonce="{{ $cspNonce ?? '' }}">
document.getElementById('cleanupForm').addEventListener('submit', function(e){
    e.preventDefault();
    var btn = document.getElementById('cleanupBtn');
    var out = document.getElementById('cleanupOutput');

    if (!confirm('Son onay: Prod test verileri silinecek. Devam?')) {
        return;
    }

    btn.disabled = true;
    btn.textContent = '⏳ Siliniyor...';
    out.style.display = 'block';
    out.textContent = 'Çalıştırılıyor...\n';

    var fd = new FormData(e.target);
    fetch(e.target.action, {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        out.textContent = JSON.stringify(data, null, 2);
        if (data.ok) {
            btn.textContent = '✅ Tamamlandı';
            btn.style.background = '#16a34a';
        } else {
            btn.disabled = false;
            btn.textContent = '🔥 Tekrar Dene';
        }
    })
    .catch(err => {
        out.textContent = 'HATA: ' + err.message;
        btn.disabled = false;
        btn.textContent = '🔥 Tekrar Dene';
    });
});
</script>
@endsection
