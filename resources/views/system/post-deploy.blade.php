@extends('manager.layouts.app')
@section('title', 'Post-Deploy — Sistem Bakımı')
@section('page_title', '🚀 Post-Deploy Bakımı')

@section('content')
<div style="max-width:720px;margin:24px auto;padding:0 16px;">

    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;">
        <h2 style="margin:0 0 12px;font-size:18px;color:#0f172a;">Deploy sonrası bakım</h2>
        <p style="font-size:13px;color:#64748b;line-height:1.6;margin:0 0 16px;">
            Bu sayfa yeni deploy sonrası bekleyen migration'ları çalıştırır ve tüm cache'leri temizler.
            KASSERVER shared hosting'te SSH yok, bu yüzden artisan komutları buradan tetiklenir.
        </p>

        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px;margin-bottom:16px;font-size:12px;color:#1e40af;">
            <strong>Çalıştırılacak komutlar:</strong>
            <ul style="margin:6px 0 0 18px;padding:0;">
                <li><code>php artisan migrate --force</code></li>
                <li><code>php artisan cache:clear</code></li>
                <li><code>php artisan view:clear</code></li>
                <li><code>php artisan config:clear</code></li>
                <li><code>php artisan route:clear</code></li>
            </ul>
        </div>

        <form id="pdForm" method="POST" action="{{ route('system.post-deploy') }}">
            @csrf
            <button type="submit" id="pdBtn"
                    style="padding:12px 24px;background:#0f172a;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;">
                🚀 Bakımı Başlat
            </button>
        </form>

        <div id="pdOutput" style="display:none;margin-top:20px;background:#0f172a;color:#e2e8f0;padding:14px;border-radius:8px;font-family:monospace;font-size:11px;line-height:1.6;white-space:pre-wrap;max-height:400px;overflow:auto;"></div>
    </div>

    {{-- Demo Student Zenginleştirme --}}
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;margin-top:20px;">
        <h2 style="margin:0 0 12px;font-size:18px;color:#0f172a;">🎓 Demo Student Hesabı</h2>
        <p style="font-size:13px;color:#64748b;line-height:1.6;margin:0 0 16px;">
            <code>student@my.mentorde.com</code> (veya local'de <code>student@mentorde.local</code>) hesabını demo sırasında zengin görünsün diye
            dolu bir pipeline ile besler: imzalı sözleşme, 3 üniversite başvurusu (TU Berlin kabul), vize hazırlık, konut, 9 checklist, 5 randevu, 4 ödeme.
        </p>
        <div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;padding:10px;margin-bottom:16px;font-size:12px;color:#92400e;">
            ⚠️ <strong>updateOrInsert</strong> kullanır — mevcut demo verileri günceller, kullanıcının gerçek kayıtlarını ezmez. Ama test kullanıcısı üzerinde çalışır, <strong>canlı müşteride kullanma</strong>.
        </div>

        <form id="sdForm" method="POST" action="{{ route('system.seed-demo-student') }}">
            @csrf
            <button type="submit" id="sdBtn"
                    style="padding:12px 24px;background:#7c3aed;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;">
                🎓 Demo Verisini Yükle
            </button>
        </form>

        <div id="sdOutput" style="display:none;margin-top:20px;background:#0f172a;color:#e2e8f0;padding:14px;border-radius:8px;font-family:monospace;font-size:11px;line-height:1.6;white-space:pre-wrap;max-height:300px;overflow:auto;"></div>
    </div>

    {{-- Registration Fields Repair --}}
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;margin-top:20px;">
        <h2 style="margin:0 0 12px;font-size:18px;color:#0f172a;">🧩 Kayıt Formu Alanlarını Tamamla</h2>
        <p style="font-size:13px;color:#64748b;line-height:1.6;margin:0 0 16px;">
            <code>guest_registration_fields</code> tablosunda eksik section/field'ları default katalogdan tamamlar (örn. Adım 2 "Adres ve Başvuru" eksikse ekler).
            <strong>Mevcut satırlara dokunmaz</strong> — sadece (section_key, field_key) kombosu yoksa ekler. İdempotent, birden çok kez çalıştırmak zararsız.
        </p>

        <form id="rfForm" method="POST" action="{{ route('system.repair-registration-fields') }}">
            @csrf
            <button type="submit" id="rfBtn"
                    style="padding:12px 24px;background:#0ea5e9;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;">
                🧩 Eksik Alanları Tamamla
            </button>
        </form>

        <div id="rfOutput" style="display:none;margin-top:20px;background:#0f172a;color:#e2e8f0;padding:14px;border-radius:8px;font-family:monospace;font-size:11px;line-height:1.6;white-space:pre-wrap;max-height:300px;overflow:auto;"></div>
    </div>

</div>

<script nonce="{{ $cspNonce ?? '' }}">
function __postJson(formId, btnId, outId, doneLabel, retryLabel){
    document.getElementById(formId).addEventListener('submit', function(e){
        e.preventDefault();
        var btn = document.getElementById(btnId);
        var out = document.getElementById(outId);
        var originalBg = btn.style.background;
        btn.disabled = true;
        btn.textContent = '⏳ Çalışıyor...';
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
            btn.textContent = doneLabel;
            btn.style.background = '#16a34a';
        })
        .catch(err => {
            out.textContent = 'HATA: ' + err.message;
            btn.disabled = false;
            btn.textContent = retryLabel;
            btn.style.background = '#dc2626';
        });
    });
}

__postJson('pdForm', 'pdBtn', 'pdOutput', '✅ Tamamlandı', '🚀 Tekrar Dene');
__postJson('sdForm', 'sdBtn', 'sdOutput', '✅ Demo Yüklendi', '🎓 Tekrar Dene');
__postJson('rfForm', 'rfBtn', 'rfOutput', '✅ Alanlar Tamamlandı', '🧩 Tekrar Dene');
</script>
@endsection
