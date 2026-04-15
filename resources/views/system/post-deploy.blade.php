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

</div>

<script nonce="{{ $cspNonce ?? '' }}">
document.getElementById('pdForm').addEventListener('submit', function(e){
    e.preventDefault();
    var btn = document.getElementById('pdBtn');
    var out = document.getElementById('pdOutput');
    btn.disabled = true;
    btn.textContent = '⏳ Çalışıyor...';
    out.style.display = 'block';
    out.textContent = 'Komutlar çalıştırılıyor...\n';

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
        btn.textContent = '✅ Tamamlandı';
        btn.style.background = '#16a34a';
    })
    .catch(err => {
        out.textContent = 'HATA: ' + err.message;
        btn.disabled = false;
        btn.textContent = '🚀 Tekrar Dene';
        btn.style.background = '#dc2626';
    });
});
</script>
@endsection
