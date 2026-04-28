<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="theme-color" content="#1e40af">
<title>Belge Yükleme — MentorDE</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
    background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #3b5fcc 100%);
    min-height: 100vh; padding: 16px; color: #0f172a;
    display: flex; align-items: flex-start; justify-content: center;
}
.card {
    background: #fff; border-radius: 18px; max-width: 480px; width: 100%;
    box-shadow: 0 20px 60px rgba(0,0,0,.25); overflow: hidden;
    margin: 12px auto;
}
.head {
    background: linear-gradient(135deg, #1e40af, #3b5fcc); color: #fff;
    padding: 24px 22px 18px; text-align: center;
}
.head .logo { font-size: 11px; font-weight: 700; letter-spacing: 2px; opacity: .85; margin-bottom: 6px; }
.head h1 { font-size: 19px; font-weight: 800; margin-bottom: 4px; }
.head p { font-size: 13px; opacity: .9; }

.body { padding: 22px; }

.greeting {
    background: #eff6ff; border-left: 3px solid #3b82f6; border-radius: 8px;
    padding: 12px 14px; margin-bottom: 16px; font-size: 13.5px; line-height: 1.5;
}
.greeting strong { color: #1e40af; }

.doc-info {
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;
    padding: 14px 16px; margin-bottom: 18px;
}
.doc-info .label { font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
.doc-info .name { font-size: 17px; font-weight: 700; color: #0f172a; line-height: 1.3; }
.doc-info .name-de { font-size: 12px; color: #6d28d9; margin-top: 4px; font-weight: 600; }
.doc-info .custom-msg {
    font-size: 12.5px; color: #475569; line-height: 1.5; margin-top: 10px;
    padding-top: 10px; border-top: 1px dashed #e2e8f0;
}

.expiry {
    font-size: 11.5px; color: #d97706; background: #fef3c7;
    padding: 8px 12px; border-radius: 8px; margin-bottom: 18px;
    text-align: center; font-weight: 600;
}

.upload-zone {
    border: 2.5px dashed #cbd5e1; border-radius: 14px;
    padding: 28px 18px; text-align: center; transition: all .2s;
    cursor: pointer; background: #fafbfc; margin-bottom: 14px;
}
.upload-zone:hover { border-color: #3b5fcc; background: #f0f7ff; }
.upload-zone.has-file { border-color: #16a34a; background: #f0fdf4; }
.upload-zone .icon { font-size: 40px; margin-bottom: 10px; }
.upload-zone .title { font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 4px; }
.upload-zone .hint { font-size: 11.5px; color: #64748b; line-height: 1.5; }

.preview {
    margin-bottom: 14px; display: none;
    border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,.08);
}
.preview.show { display: block; }
.preview img { width: 100%; display: block; }
.preview .filename {
    padding: 10px 14px; background: #f8fafc; font-size: 12px; color: #475569;
    border-top: 1px solid #e2e8f0; display: flex; align-items: center; gap: 6px; justify-content: space-between;
}
.preview .filename .retake {
    background: none; border: none; color: #dc2626; cursor: pointer;
    font-size: 12px; font-weight: 600;
}

.action-buttons { display: flex; flex-direction: column; gap: 10px; }
.btn {
    width: 100%; padding: 14px 18px; border-radius: 12px; border: none;
    font-size: 14.5px; font-weight: 700; cursor: pointer; font-family: inherit;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: all .15s;
}
.btn:disabled { opacity: .5; cursor: not-allowed; }
.btn-primary {
    background: linear-gradient(135deg, #1e40af, #3b5fcc); color: #fff;
    box-shadow: 0 4px 14px rgba(30,64,175,.3);
}
.btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(30,64,175,.4); }
.btn-secondary {
    background: #fff; color: #1e40af; border: 2px solid #cbd5e1;
}
.btn-secondary:hover { border-color: #3b5fcc; background: #f0f7ff; }

.tips {
    margin-top: 18px; font-size: 11.5px; color: #64748b;
    line-height: 1.6; background: #f8fafc; padding: 12px 14px; border-radius: 8px;
}
.tips ul { padding-left: 18px; margin-top: 6px; }

.errors {
    background: #fef2f2; border: 1px solid #fecaca; color: #991b1b;
    padding: 12px 14px; border-radius: 10px; margin-bottom: 14px;
    font-size: 12.5px; line-height: 1.5;
}

.foot {
    text-align: center; padding: 16px; border-top: 1px solid #f1f5f9;
    font-size: 10.5px; color: #94a3b8;
}
.foot a { color: #1e40af; text-decoration: none; font-weight: 600; }

#file-input { display: none; }

@media (min-width: 600px) {
    .head { padding: 30px 28px 22px; }
    .head h1 { font-size: 21px; }
    .body { padding: 28px; }
}
</style>
</head>
<body>

<div class="card">
    <div class="head">
        <div class="logo">📚 MENTORDE</div>
        <h1>Belge Yükleme</h1>
        <p>Aşağıdaki belgeyi telefonunuzla çekip yükleyin</p>
    </div>

    <div class="body">

        <div class="greeting">
            👋 Merhaba <strong>{{ $guestName }}</strong>! Danışmanın senden aşağıdaki belgeyi istedi:
        </div>

        <div class="doc-info">
            <div class="label">📄 İstenilen Belge</div>
            <div class="name">{{ $docName }}</div>
            @if($docNameDe)
                <div class="name-de">🇩🇪 {{ $docNameDe }}</div>
            @endif
            @if($message)
                <div class="custom-msg">💬 {{ $message }}</div>
            @endif
        </div>

        @if($expiresIn)
            <div class="expiry">⏱️ Bu link {{ $expiresIn }} sonra geçerliliğini yitirir</div>
        @endif

        @if($errors->any())
            <div class="errors">
                ⚠️ {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ route('public.document-upload.store', $token->token) }}" enctype="multipart/form-data" id="upload-form">
            @csrf

            <div class="upload-zone" id="upload-zone">
                <div class="icon">📷</div>
                <div class="title">Fotoğraf Çek veya Dosya Seç</div>
                <div class="hint">Telefonda kamera açılır.<br>PDF / JPG / PNG — maks 10MB</div>
            </div>

            <div class="preview" id="preview">
                <img id="preview-img" alt="Önizleme">
                <div class="filename">
                    <span id="preview-name"></span>
                    <button type="button" class="retake" id="retake-btn">↺ Yeniden Çek</button>
                </div>
            </div>

            <input type="file" name="file" id="file-input" accept="image/*,application/pdf" capture="environment" required>

            <div class="action-buttons">
                <button type="submit" class="btn btn-primary" id="submit-btn" disabled>
                    📤 Belgeyi Gönder
                </button>
            </div>
        </form>

        <div class="tips">
            <strong>💡 İpuçları:</strong>
            <ul>
                <li>Belgeyi düz, iyi aydınlatılmış bir yüzeye koy</li>
                <li>Tüm köşelerin görüntüde olduğundan emin ol</li>
                <li>Yazılar net okunabilmeli</li>
                <li>Birden fazla sayfa varsa tek tek çekip ayrı ayrı gönderebilirsin (her gönderim için ayrı link gerekir)</li>
            </ul>
        </div>

    </div>

    <div class="foot">
        🔒 Bu link tek-kullanımlık ve güvenlidir. Sadece istenilen belgeyi yüklemek için kullanılır.<br>
        Sorularınız için <a href="https://mentorde.com">MentorDE</a> ile iletişime geçin.
    </div>
</div>

<script>
(function(){
    var zone = document.getElementById('upload-zone');
    var input = document.getElementById('file-input');
    var preview = document.getElementById('preview');
    var previewImg = document.getElementById('preview-img');
    var previewName = document.getElementById('preview-name');
    var retakeBtn = document.getElementById('retake-btn');
    var submitBtn = document.getElementById('submit-btn');

    zone.addEventListener('click', function(){ input.click(); });

    input.addEventListener('change', function(){
        var file = this.files[0];
        if (!file) return;
        previewName.textContent = file.name;
        zone.classList.add('has-file');
        submitBtn.disabled = false;

        if (file.type.indexOf('image/') === 0) {
            var reader = new FileReader();
            reader.onload = function(e){
                previewImg.src = e.target.result;
                preview.classList.add('show');
                zone.style.display = 'none';
            };
            reader.readAsDataURL(file);
        } else {
            // PDF — show generic preview
            previewImg.src = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="200" height="120" viewBox="0 0 200 120"><rect fill="%23e2e8f0" width="200" height="120"/><text x="100" y="65" font-size="24" text-anchor="middle" fill="%2364748b" font-family="sans-serif">📄 PDF</text></svg>';
            preview.classList.add('show');
            zone.style.display = 'none';
        }
    });

    retakeBtn.addEventListener('click', function(){
        input.value = '';
        preview.classList.remove('show');
        zone.style.display = 'block';
        zone.classList.remove('has-file');
        submitBtn.disabled = true;
    });

    document.getElementById('upload-form').addEventListener('submit', function(){
        submitBtn.disabled = true;
        submitBtn.innerHTML = '⏳ Yükleniyor...';
    });
})();
</script>

</body>
</html>
