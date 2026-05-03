# ════════════════════════════════════════════════════════════════════
# MentorDE — OneDrive Backup Script
# ════════════════════════════════════════════════════════════════════
# Yedeklenen 4 grup:
#   1. Claude konuşma geçmişi (.jsonl) — bu Claude ile çalışmamızın hafızası
#   2. Claude memory/ — user memory + bu seansın learning'leri
#   3. .env — prod credentials (KAS DB, Resend, Stripe, OAuth, AI keys)
#   4. mentorde_local MySQL — wizard sessions + dev data
#
# Kullanım:
#   pwsh.exe -ExecutionPolicy Bypass -File scripts\backup-mentorde.ps1
#
# Otomasyon: Task Scheduler (her gün 23:00)
#   schtasks /create /tn "MentorDE Backup" /tr "pwsh.exe -ExecutionPolicy Bypass -File 'C:\Users\User\Desktop\PHP -LARAVEL Mentorde\mentorde\scripts\backup-mentorde.ps1'" /sc daily /st 23:00
# ════════════════════════════════════════════════════════════════════

$ErrorActionPreference = 'Continue'  # Tek bir hatada tüm akış durmasın
$now = Get-Date -Format 'yyyy-MM-dd_HHmm'
$dateOnly = Get-Date -Format 'yyyy-MM-dd'

# ── Sabitler ────────────────────────────────────────────────────────
$onedrive    = "C:\Users\User\OneDrive\mentorde-backup"
$claudeSrc   = "C:\Users\User\.claude\projects\c--Users-User-Desktop-PHP--LARAVEL-Mentorde-mentorde"
$envFile     = "C:\Users\User\Desktop\PHP -LARAVEL Mentorde\mentorde\.env"
$mysqlDump   = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe"
$dbName      = "mentorde_local"
$dbUser      = "root"
$logFile     = "$onedrive\backup-log.txt"

# ── Hedef dizinler ──────────────────────────────────────────────────
@(
    "$onedrive",
    "$onedrive\claude",
    "$onedrive\env-history",
    "$onedrive\db",
    "$onedrive\repo-memory"
) | ForEach-Object {
    if (-not (Test-Path $_)) { New-Item -ItemType Directory -Force $_ | Out-Null }
}

function Log($msg) {
    $line = "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] $msg"
    Write-Host $line
    Add-Content -Path $logFile -Value $line
}

Log "═══ Backup başladı ═══"

# ── 1. Claude konuşma geçmişi + memory ──────────────────────────────
Log "1/4 Claude konuşma + memory yedekleniyor..."
$rcArgs = @(
    "$claudeSrc",
    "$onedrive\claude",
    "/MIR",      # Mirror — silinen dosyaları da silsin
    "/R:2",      # Retry 2 kere
    "/W:5",      # 5sn bekle
    "/XO",       # Sadece daha yeni dosyaları kopyala
    "/NFL",      # File listesi loglama
    "/NDL",      # Dir listesi loglama
    "/NP"        # Progress bar yok
)
& robocopy @rcArgs | Out-Null
$rc = $LASTEXITCODE
if ($rc -lt 8) {
    Log "  ✓ Claude OK (robocopy exit $rc)"
} else {
    Log "  ⚠ Claude HATA (robocopy exit $rc)"
}

# ── 2. .env (tarihli arşiv — eski sürümleri tut) ────────────────────
Log "2/4 .env yedekleniyor..."
if (Test-Path $envFile) {
    Copy-Item $envFile "$onedrive\env-history\.env-$dateOnly" -Force
    Copy-Item $envFile "$onedrive\.env-latest" -Force  # Hep güncel kopya
    Log "  ✓ .env OK"
} else {
    Log "  ⚠ .env bulunamadı: $envFile"
}

# ── 3. Repo memory/ (lessons.md + todo.md) ──────────────────────────
Log "3/4 Repo memory/ yedekleniyor..."
$repoMemory = "C:\Users\User\Desktop\PHP -LARAVEL Mentorde\mentorde\memory"
if (Test-Path $repoMemory) {
    & robocopy "$repoMemory" "$onedrive\repo-memory" /MIR /R:2 /W:5 /NFL /NDL /NP | Out-Null
    Log "  ✓ Repo memory OK"
} else {
    Log "  ⚠ Repo memory yok"
}

# ── 4. MySQL dump ───────────────────────────────────────────────────
Log "4/4 MySQL dump alınıyor..."
if (Test-Path $mysqlDump) {
    $dumpFile = "$onedrive\db\$dbName-$dateOnly.sql"
    $errFile  = "$onedrive\db\$dbName-$dateOnly.err"
    # ASCII encoding (UTF-8 BOM mysqldump'ı bozar)
    & $mysqlDump -u $dbUser --single-transaction --quick --skip-lock-tables --default-character-set=utf8mb4 $dbName 2>$errFile | Out-File -FilePath $dumpFile -Encoding default

    if (Test-Path $dumpFile) {
        $sizeKB = (Get-Item $dumpFile).Length / 1KB
        if ($sizeKB -lt 5) {
            # Boş/çok küçük dump → MySQL muhtemelen kapalı
            $errMsg = if (Test-Path $errFile) { (Get-Content $errFile -Raw -ErrorAction SilentlyContinue) } else { "(no error log)" }
            Log "  ⚠ DB dump çok küçük ($([math]::Round($sizeKB, 1)) KB) — MySQL kapalı olabilir"
            Log "    Hata: $($errMsg.Trim())"
            Remove-Item $dumpFile -Force
        } else {
            $sizeMB = [math]::Round($sizeKB / 1024, 2)
            Log "  ✓ DB dump OK ($sizeMB MB) → $dumpFile"

            # Eski dump'ları temizle (30 günden eski)
            Get-ChildItem "$onedrive\db" -Filter "$dbName-*.sql" |
                Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-30) } |
                Remove-Item -Force
        }
    } else {
        Log "  ⚠ DB dump dosyası oluşmadı"
    }
    if (Test-Path $errFile) { Remove-Item $errFile -Force }
} else {
    Log "  ⚠ mysqldump bulunamadı: $mysqlDump"
}

# ── 5. Eski .env arşivlerini 30 günden sonra temizle ────────────────
Get-ChildItem "$onedrive\env-history" -Filter ".env-*" |
    Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-30) } |
    Remove-Item -Force

# ── Özet ────────────────────────────────────────────────────────────
$totalSize = (Get-ChildItem $onedrive -Recurse -File | Measure-Object -Property Length -Sum).Sum / 1MB
Log "═══ Backup bitti — toplam boyut: $([math]::Round($totalSize, 1)) MB ═══`n"
