param(
    [string]$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path,
    [string]$OutputDir = "",
    [switch]$IncludeVendor,
    [switch]$IncludeEnv,
    [switch]$OpenFolder
)

$ErrorActionPreference = "Stop"

if ([string]::IsNullOrWhiteSpace($OutputDir)) {
    $OutputDir = Join-Path $ProjectRoot "exports"
}

New-Item -ItemType Directory -Force -Path $OutputDir | Out-Null

$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$zipName = "mentorde_code_export_$timestamp.zip"
$zipPath = Join-Path $OutputDir $zipName
$tempRoot = Join-Path $env:TEMP ("mentorde_export_" + [guid]::NewGuid().ToString("N"))
$staging = Join-Path $tempRoot "mentorde"

New-Item -ItemType Directory -Force -Path $staging | Out-Null

$excludedDirs = @(
    "node_modules",
    "storage\framework\cache",
    "storage\framework\sessions",
    "storage\framework\views",
    "storage\logs"
)
if (-not $IncludeVendor) {
    $excludedDirs += "vendor"
}

$excludedFilePatterns = @(
    "*.sqlite",
    "*.zip"
)

if (-not $IncludeEnv) {
    $excludedFilePatterns += ".env"
    $excludedFilePatterns += "*service-account*.json"
    $excludedFilePatterns += "*firebase*service*.json"
}

$allFiles = Get-ChildItem -Path $ProjectRoot -Recurse -File -Force

foreach ($file in $allFiles) {
    $fullPath = $file.FullName
    $relative = $fullPath.Substring($ProjectRoot.Length).TrimStart('\')
    if ([string]::IsNullOrWhiteSpace($relative)) {
        continue
    }

    $skip = $false
    foreach ($dir in $excludedDirs) {
        $dirNorm = $dir.Replace('/', '\').Trim('\')
        if ($relative.StartsWith($dirNorm + "\", [System.StringComparison]::OrdinalIgnoreCase)) {
            $skip = $true
            break
        }
    }
    if ($skip) { continue }

    foreach ($pattern in $excludedFilePatterns) {
        if ($file.Name -like $pattern) {
            $skip = $true
            break
        }
        if ($relative -like $pattern) {
            $skip = $true
            break
        }
    }
    if ($skip) { continue }

    $destPath = Join-Path $staging $relative
    $destDir = Split-Path -Path $destPath -Parent
    if (-not (Test-Path $destDir)) {
        New-Item -ItemType Directory -Path $destDir -Force | Out-Null
    }
    Copy-Item -Path $fullPath -Destination $destPath -Force
}

if (Test-Path $zipPath) {
    Remove-Item -Path $zipPath -Force
}

Compress-Archive -Path (Join-Path $staging "*") -DestinationPath $zipPath -CompressionLevel Optimal -Force
Remove-Item -Path $tempRoot -Recurse -Force

Write-Host "EXPORT_OK $zipPath"

if ($OpenFolder) {
    Start-Process explorer.exe $OutputDir
}

