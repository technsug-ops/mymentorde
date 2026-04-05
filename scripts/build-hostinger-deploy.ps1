# =============================================================================
# build-hostinger-deploy.ps1
# MentorDE — Hostinger Deploy Paket Oluşturucu
# =============================================================================
# Çalıştır: scripts klasöründe sağ tık > PowerShell ile çalıştır
# veya: HOSTING_DEPLOY.bat
# =============================================================================

$ErrorActionPreference = 'Stop'
$ProjectRoot = Split-Path -Parent $PSScriptRoot

$ExportBase = Join-Path $ProjectRoot "exports\hostinger"
$Timestamp  = Get-Date -Format "yyyyMMdd_HHmmss"
$ExportDir  = Join-Path $ExportBase $Timestamp

Write-Host ""
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "  MentorDE Hostinger Deploy Builder" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

# Çıktı klasörünü oluştur
if (-not (Test-Path $ExportBase)) {
    New-Item -ItemType Directory -Path $ExportBase -Force | Out-Null
}
New-Item -ItemType Directory -Path $ExportDir -Force | Out-Null

Write-Host "Çıktı klasörü: $ExportDir" -ForegroundColor Gray
Write-Host ""

# =============================================================================
# PAKET 1: public_html.zip
# İçerik: public/ klasörünün TÜM içeriği + değiştirilmiş index.php
# Yükleme yeri: Hostinger -> public_html/ (içeriği direkt oraya)
# =============================================================================
Write-Host "[1/3] public_html.zip hazırlanıyor..." -ForegroundColor Yellow

$TempPub   = Join-Path $ExportDir "_tmp_pub"
$PubSource = Join-Path $ProjectRoot "public"
$PubZip    = Join-Path $ExportDir "1_public_html.zip"

New-Item -ItemType Directory -Path $TempPub -Force | Out-Null

# public/ içeriğini kopyala (index.php hariç)
Get-ChildItem -Path $PubSource | Where-Object { $_.Name -ne 'index.php' } | ForEach-Object {
    if ($_.PSIsContainer) {
        Copy-Item $_.FullName (Join-Path $TempPub $_.Name) -Recurse -Force
    } else {
        Copy-Item $_.FullName (Join-Path $TempPub $_.Name) -Force
    }
}

# Değiştirilmiş index.php'yi kopyala
$ModifiedIndex = Join-Path $PSScriptRoot "hostinger\public_html_index.php"
if (Test-Path $ModifiedIndex) {
    Copy-Item $ModifiedIndex (Join-Path $TempPub "index.php") -Force
} else {
    Write-Host "  UYARI: hostinger\public_html_index.php bulunamadı, orijinal index.php kullanılıyor" -ForegroundColor Red
    Copy-Item (Join-Path $PubSource "index.php") (Join-Path $TempPub "index.php") -Force
}

# ZIP oluştur
Compress-Archive -Path "$TempPub\*" -DestinationPath $PubZip -CompressionLevel Optimal
Remove-Item $TempPub -Recurse -Force

$PubSize = [math]::Round((Get-Item $PubZip).Length / 1MB, 2)
Write-Host "  OK: 1_public_html.zip ($PubSize MB)" -ForegroundColor Green

# =============================================================================
# PAKET 2: mentorde_app.zip
# İçerik: Laravel root (vendor ve .env HARİÇ)
# Yükleme yeri: Hostinger -> mentorde_app/ klasörü (public_html DIŞINDA)
# =============================================================================
Write-Host "[2/3] mentorde_app.zip hazırlanıyor (vendor hariç)..." -ForegroundColor Yellow

$TempApp = Join-Path $ExportDir "_tmp_app"
$AppZip  = Join-Path $ExportDir "2_mentorde_app_no_vendor.zip"

New-Item -ItemType Directory -Path $TempApp -Force | Out-Null

# Dahil edilecek klasörler
$IncludeDirs = @('app', 'bootstrap', 'config', 'database', 'resources', 'routes', 'storage')
foreach ($dir in $IncludeDirs) {
    $src = Join-Path $ProjectRoot $dir
    if (Test-Path $src) {
        Copy-Item $src (Join-Path $TempApp $dir) -Recurse -Force
    }
}

# Dahil edilecek dosyalar
$IncludeFiles = @('artisan', 'composer.json', 'composer.lock')
foreach ($file in $IncludeFiles) {
    $src = Join-Path $ProjectRoot $file
    if (Test-Path $src) {
        Copy-Item $src (Join-Path $TempApp $file) -Force
    }
}

# Storage altındaki gerçek içerikleri temizle (sadece klasör yapısını bırak)
$StorageClean = @(
    (Join-Path $TempApp "storage\framework\views"),
    (Join-Path $TempApp "storage\framework\cache\data"),
    (Join-Path $TempApp "storage\logs")
)
foreach ($dir in $StorageClean) {
    if (Test-Path $dir) {
        Get-ChildItem -Path $dir -File | Where-Object { $_.Name -ne '.gitignore' } | Remove-Item -Force
    }
}

# .env.example kopyala (NOT: .env değil! Sunucuda .env ayrıca oluşturulacak)
$EnvExample = Join-Path $ProjectRoot ".env.example"
if (Test-Path $EnvExample) {
    Copy-Item $EnvExample (Join-Path $TempApp ".env.example") -Force
}

# ZIP oluştur
Compress-Archive -Path "$TempApp\*" -DestinationPath $AppZip -CompressionLevel Optimal
Remove-Item $TempApp -Recurse -Force

$AppSize = [math]::Round((Get-Item $AppZip).Length / 1MB, 2)
Write-Host "  OK: 2_mentorde_app_no_vendor.zip ($AppSize MB)" -ForegroundColor Green

# =============================================================================
# PAKET 3: vendor.zip
# İçerik: vendor/ klasörünün tamamı
# Yükleme yeri: mentorde_app/vendor/ (içeriği oraya)
# =============================================================================
Write-Host "[3/3] vendor.zip hazırlanıyor (bu büyük olabilir, bekleniyor)..." -ForegroundColor Yellow

$VendorSource = Join-Path $ProjectRoot "vendor"
$VendorZip    = Join-Path $ExportDir "3_vendor.zip"

if (Test-Path $VendorSource) {
    Compress-Archive -Path "$VendorSource\*" -DestinationPath $VendorZip -CompressionLevel Optimal
    $VendorSize = [math]::Round((Get-Item $VendorZip).Length / 1MB, 2)
    Write-Host "  OK: 3_vendor.zip ($VendorSize MB)" -ForegroundColor Green
} else {
    Write-Host "  UYARI: vendor/ klasörü yok! Sunucuda 'composer install' çalıştırılmalı." -ForegroundColor Red
}

# =============================================================================
# ÖZET
# =============================================================================
Write-Host ""
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "  TAMAMLANDI!" -ForegroundColor Green
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Dosyalar: $ExportDir" -ForegroundColor White
Write-Host ""
Write-Host "YÜKLEME SIRASI:" -ForegroundColor Yellow
Write-Host "  Adım 1: Hostinger hPanel -> File Manager aç" -ForegroundColor White
Write-Host "  Adım 2: 'mentorde_app' klasörü oluştur (public_html ile AYNI seviyede)" -ForegroundColor White
Write-Host "  Adım 3: 2_mentorde_app_no_vendor.zip -> mentorde_app/ içine yükle ve çıkart" -ForegroundColor White
Write-Host "  Adım 4: 3_vendor.zip -> mentorde_app/vendor/ içine yükle ve çıkart" -ForegroundColor White
Write-Host "  Adım 5: 1_public_html.zip -> public_html/ içine yükle ve çıkart" -ForegroundColor White
Write-Host "  Adım 6: mentorde_app/ içinde .env dosyası oluştur (DEPLOY_README.txt'e bak)" -ForegroundColor White
Write-Host "  Adım 7: SSH Terminal'den: php artisan migrate --force" -ForegroundColor White
Write-Host ""
Write-Host "README: $ExportDir\DEPLOY_README.txt" -ForegroundColor Gray
Write-Host ""

# README dosyası oluştur
$ReadmeContent = @"
MentorDE — Hostinger Deploy Rehberi
=====================================
Oluşturulma: $Timestamp

KLASÖR YAPISI (Hostinger'da olması gereken)
--------------------------------------------
/home/KULLANICI/
├── mentorde_app/          <- Laravel uygulaması (public_html DIŞINDA)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── artisan
│   ├── composer.json
│   └── .env               <- Sunucuda ELLE oluşturulur (aşağıya bak)
└── public_html/           <- Web kökü
    ├── index.php          <- Değiştirilmiş versiyon (1_public_html.zip'ten gelir)
    ├── .htaccess
    ├── css/
    ├── js/
    └── favicon.ico

ADIM ADIM YÜKLEME
------------------
1. Hostinger hPanel > Files > File Manager aç
2. public_html ile AYNI SEVİYEDE 'mentorde_app' klasörü oluştur
   (sol panelde ana klasörde sağ tık > Yeni Klasör > mentorde_app)
3. mentorde_app klasörüne gir
4. 2_mentorde_app_no_vendor.zip dosyasını yükle -> 'Çıkart' (Extract)
5. 3_vendor.zip dosyasını yükle -> mentorde_app/ içinde 'vendor' klasörü oluşur
   NOT: vendor.zip büyükse FileZilla FTP ile yüklemek daha hızlı olabilir
6. public_html klasörüne gir
7. 1_public_html.zip dosyasını yükle -> 'Çıkart'
   (mevcut index.php'yi DEĞİŞTİRMESİNE izin ver)

.ENV DOSYASI OLUŞTURMA (ÇOK ÖNEMLİ)
--------------------------------------
mentorde_app/ klasörüne '.env' adında dosya oluştur, içeriği:

APP_NAME=MentorDE
APP_ENV=production
APP_KEY=            <- php artisan key:generate ile oluşturulacak
APP_DEBUG=false
APP_URL=https://netsparen.de

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=db5019270447.hosting-data.io
DB_PORT=3306
DB_DATABASE=dbs15110928
DB_USERNAME=dbu2613625
DB_PASSWORD=mJbJ2g0L2026*!

CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database

FIREBASE_PROJECT_ID=mentorde-app-de
FIREBASE_PRIVATE_KEY_ID=...     <- Firebase console'dan
FIREBASE_PRIVATE_KEY="..."      <- Firebase console'dan
FIREBASE_CLIENT_EMAIL=...       <- Firebase console'dan
FIREBASE_CLIENT_ID=...          <- Firebase console'dan

TERMINAL / SSH KOMUTLARI
--------------------------
Hostinger hPanel > Advanced > SSH veya Terminal:

cd ~/mentorde_app
php artisan key:generate
php artisan migrate --force
php artisan queue:table && php artisan migrate --force   <- queue:database için
php artisan storage:link
php artisan config:cache
php -d memory_limit=256M artisan route:cache
php artisan view:cache
php artisan optimize

DOSYA İZİNLERİ
---------------
chmod -R 755 ~/mentorde_app/storage
chmod -R 755 ~/mentorde_app/bootstrap/cache

SORUN GİDERME
--------------
- 500 hatası: mentorde_app/storage/logs/laravel.log dosyasına bak
- DB bağlantı hatası: .env DB bilgilerini kontrol et
- Sayfa bulunamadı (404): public_html/.htaccess dosyasının varlığını kontrol et
"@

$ReadmeContent | Out-File -FilePath (Join-Path $ExportDir "DEPLOY_README.txt") -Encoding utf8

# Klasörü aç
Start-Process explorer.exe $ExportDir
