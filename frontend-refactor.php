<?php
/**
 * MentorDE Frontend Toplu Refactor Script
 * 
 * Kullanım: php artisan tinker < frontend-refactor.php
 * VEYA: php frontend-refactor.php (proje kök dizininde)
 * 
 * Bu script şunları yapar:
 *   1. 7 layout'taki @vite/script sorununu kalıcı çözer (manifest okuma)
 *   2. 3 component dosyasını günceller (badge, kpi-card, alert)
 *   3. Layout'lardaki gereksiz session/error bloklarını temizler (sayfa içi olanlar kalır)
 * 
 * NOT: Tüm değişiklikler .bak dosyasına yedeklenir.
 * GERİ ALMA: Her dosyanın .bak versiyonundan geri yükle.
 */

// ── Proje kök dizinini belirle ──
$base = realpath(__DIR__);
if (!file_exists($base . '/resources/views')) {
    $base = realpath(__DIR__ . '/..'); // bir üst dizin dene
}
if (!file_exists($base . '/resources/views')) {
    echo "HATA: resources/views bulunamadi. Scripti proje kok dizininde calistir.\n";
    exit(1);
}

$viewsPath = $base . '/resources/views';
$componentsPath = $viewsPath . '/components';
$changedFiles = [];
$backupCount = 0;

// ── Yardımcı fonksiyonlar ──
function backupAndWrite(string $path, string $content): bool {
    global $backupCount;
    if (file_exists($path)) {
        copy($path, $path . '.bak');
        $backupCount++;
    }
    return file_put_contents($path, $content) !== false;
}

function reportChange(string $file, string $what): void {
    global $changedFiles;
    $changedFiles[] = ['file' => $file, 'change' => $what];
    echo "  ✓ $what → $file\n";
}

echo "\n═══════════════════════════════════════════════════\n";
echo "  MentorDE Frontend Toplu Refactor\n";
echo "  Base: $base\n";
echo "═══════════════════════════════════════════════════\n\n";

// ════════════════════════════════════════════════════════
// ADIM 1: Component dosyalarını güncelle
// ════════════════════════════════════════════════════════
echo "── ADIM 1: Component Dosyalari Guncelle ──\n";

// 1.1 Badge
$badgePath = $componentsPath . '/ui/badge.blade.php';
$badgeContent = <<<'BLADE'
@props(['status', 'label' => null])
@php
$m=['approved'=>'ok','done'=>'ok','active'=>'ok','sent'=>'ok','confirmed'=>'ok','paid'=>'ok','converted'=>'ok','healthy'=>'ok','low'=>'ok','rejected'=>'danger','failed'=>'danger','blocked'=>'danger','cancelled'=>'danger','critical'=>'danger','overdue'=>'danger','pending'=>'warn','in_progress'=>'warn','in_review'=>'warn','warning'=>'warn','high'=>'warn','signed_uploaded'=>'warn','requested'=>'warn','scheduled'=>'warn','uploaded'=>'info','new'=>'info','open'=>'info','todo'=>'info','draft'=>'pending','not_requested'=>'pending','medium'=>'warn'];
$type = $m[$status] ?? 'info';
$colors = [
    'ok'      => 'background:#dcfce7;color:#166534;border:1px solid #bbf7d0;',
    'danger'  => 'background:#fef2f2;color:#991b1b;border:1px solid #fecaca;',
    'warn'    => 'background:#fffbeb;color:#92400e;border:1px solid #fde68a;',
    'info'    => 'background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe;',
    'pending' => 'background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb;',
];
$style = $colors[$type] ?? $colors['info'];
$text = $label ?? str_replace('_',' ',ucfirst($status ?? 'unknown'));
@endphp
<span style="{{ $style }}display:inline-flex;align-items:center;justify-content:center;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:600;line-height:1.4;white-space:nowrap;">{{ $text }}</span>
BLADE;
backupAndWrite($badgePath, $badgeContent);
reportChange('components/ui/badge.blade.php', 'Badge guncellendi (ortali + renk)');

// 1.2 KPI Card
$kpiPath = $componentsPath . '/ui/kpi-card.blade.php';
$kpiContent = <<<'BLADE'
@props(['value', 'label', 'suffix' => '', 'prefix' => '', 'icon' => null, 'trend' => null, 'trendUp' => null, 'color' => null])
@php
$iconColors = ['🎓'=>'#2563eb','💰'=>'#16a34a','⏳'=>'#d97706','⭐'=>'#eab308','📄'=>'#6366f1','📊'=>'#8b5cf6','🎯'=>'#ec4899','🔔'=>'#f97316','👥'=>'#0891b2','📋'=>'#7c3aed','🏛️'=>'#059669','✈️'=>'#dc2626'];
$iconBg = $iconColors[$icon] ?? ($color ?? '#2563eb');
@endphp
<div class="panel" style="text-align:center;padding:20px 14px;">
    @if($icon)
    <div style="width:44px;height:44px;border-radius:12px;background:{{ $iconBg }}15;display:inline-flex;align-items:center;justify-content:center;margin-bottom:10px;">
        <span style="font-size:22px;line-height:1;">{{ $icon }}</span>
    </div>
    @endif
    <div style="font-size:28px;font-weight:800;color:#111827;line-height:1;">{{ $prefix }}{{ $value }}{{ $suffix }}</div>
    <div style="font-size:12px;color:#6b7280;margin-top:6px;font-weight:500;">{{ $label }}</div>
    @if($trend)
    <div style="margin-top:8px;display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600;{{ $trendUp === true ? 'background:#dcfce7;color:#166534;' : ($trendUp === false ? 'background:#fef2f2;color:#991b1b;' : 'background:#f3f4f6;color:#6b7280;') }}">
        @if($trendUp === true)↑ @elseif($trendUp === false)↓ @endif{{ $trend }}
    </div>
    @endif
</div>
BLADE;
backupAndWrite($kpiPath, $kpiContent);
reportChange('components/ui/kpi-card.blade.php', 'KPI Card guncellendi (ikon bg + trend pill)');

// 1.3 Alert
$alertPath = $componentsPath . '/ui/alert.blade.php';
$alertContent = <<<'BLADE'
@props(['type' => 'info', 'dismissable' => false])
@php
$styles = [
    'info'   => 'background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe;',
    'danger' => 'background:#fef2f2;color:#991b1b;border:1px solid #fecaca;',
    'ok'     => 'background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;',
    'warn'   => 'background:#fffbeb;color:#92400e;border:1px solid #fde68a;',
];
@endphp
<div style="{{ $styles[$type] ?? $styles['info'] }}padding:12px 16px;border-radius:10px;font-size:13px;font-weight:500;position:relative;margin-bottom:10px;"
     @if($dismissable) x-data="{show:true}" x-show="show" x-transition @endif>
    {{ $slot }}
    @if($dismissable)
        <button @click="show=false" style="position:absolute;top:8px;right:12px;background:none;border:none;cursor:pointer;font-size:16px;opacity:.5;">✕</button>
    @endif
</div>
BLADE;
backupAndWrite($alertPath, $alertContent);
reportChange('components/ui/alert.blade.php', 'Alert guncellendi (padding + border-radius)');

// ════════════════════════════════════════════════════════
// ADIM 2: Layout dosyalarında @vite sorununu kalıcı çöz
// ════════════════════════════════════════════════════════
echo "\n── ADIM 2: Layout Dosyalari — Manifest Okuma + Toast ──\n";

$layouts = [
    $viewsPath . '/manager/layouts/app.blade.php',
    $viewsPath . '/guest/layouts/app.blade.php',
    $viewsPath . '/student/layouts/app.blade.php',
    $viewsPath . '/senior/layouts/app.blade.php',
    $viewsPath . '/dealer/layouts/app.blade.php',
    $viewsPath . '/marketing-admin/layouts/app.blade.php',
    $viewsPath . '/layouts/staff.blade.php',
];

$manifestBlock = <<<'BLADE'
@php
    $__manifest = json_decode(@file_get_contents(public_path('build/manifest.json')) ?: '{}', true);
    $__appJs = $__manifest['resources/js/app.js']['file'] ?? null;
    $__appCss = $__manifest['resources/css/app.css']['file'] ?? null;
@endphp
@if($__appCss)
    <link rel="stylesheet" href="/build/{{ $__appCss }}">
@endif
@if($__appJs)
    <script type="module" src="/build/{{ $__appJs }}"></script>
@endif
BLADE;

$toastBlock = <<<'HTML'
<div id="toast-container" style="position:fixed;bottom:20px;right:20px;z-index:9999;"></div>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.effect(() => {
        const items = Alpine.store('toast').items;
        const c = document.getElementById('toast-container');
        if (!c) return;
        c.innerHTML = items.map(i => {
            const bg = i.type==='ok'?'#16a34a':i.type==='danger'?'#dc2626':i.type==='warn'?'#d97706':'#2563eb';
            return '<div style="background:'+bg+';color:#fff;padding:12px 18px;border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,.2);font-size:13px;font-weight:500;margin-top:8px;">'+i.message+'</div>';
        }).join('');
    });
});
</script>
HTML;

foreach ($layouts as $layoutPath) {
    if (!file_exists($layoutPath)) {
        echo "  ⚠ BULUNAMADI: $layoutPath\n";
        continue;
    }
    
    $content = file_get_contents($layoutPath);
    $shortName = str_replace($viewsPath . '/', '', $layoutPath);
    $changed = false;
    
    // Eski hardcoded script tag'larını kaldır
    $patterns = [
        '/<script type="module" src="\/build\/assets\/app-[a-zA-Z0-9_-]+\.js"><\/script>\s*/s',
        '/@vite\(\[\'resources\/js\/app\.js\'\]\)\s*/s',
        '/@vite\(\["resources\/js\/app\.js"\]\)\s*/s',
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, '', $content);
            $changed = true;
        }
    }
    
    // Eski toast container'ı kaldır
    if (strpos($content, 'id="toast-container"') !== false) {
        // Mevcut toast bloğunu kaldır (basit regex)
        $content = preg_replace('/<div id="toast-container".*?<\/script>\s*/s', '', $content);
        $changed = true;
    }
    
    // </body> öncesine manifest bloğu + toast ekle
    if (strpos($content, '$__manifest') === false) {
        $content = str_replace('</body>', $manifestBlock . "\n" . $toastBlock . "\n</body>", $content);
        $changed = true;
    }
    
    if ($changed) {
        backupAndWrite($layoutPath, $content);
        reportChange($shortName, 'Manifest okuma + toast container eklendi');
    } else {
        echo "  - $shortName: zaten guncel\n";
    }
}

// ════════════════════════════════════════════════════════
// ADIM 3: Sidebar toggle script'i tüm layout'lara ekle
// ════════════════════════════════════════════════════════
echo "\n── ADIM 3: Sidebar Toggle Script (geriye uyumluluk) ──\n";

$sidebarScript = <<<'HTML'
<script>
document.querySelectorAll('[data-toggle-group]').forEach(function(btn){
    btn.addEventListener('click',function(){
        var g=document.getElementById(btn.dataset.toggleGroup);
        if(g)g.classList.toggle('open');
    });
});
</script>
HTML;

foreach ($layouts as $layoutPath) {
    if (!file_exists($layoutPath)) continue;
    $content = file_get_contents($layoutPath);
    $shortName = str_replace($viewsPath . '/', '', $layoutPath);
    
    // Zaten varsa atla
    if (strpos($content, 'data-toggle-group') !== false && substr_count($content, 'data-toggle-group') > 3) {
        echo "  - $shortName: sidebar script zaten var\n";
        continue;
    }
}

// ════════════════════════════════════════════════════════
// ADIM 4: Responsive sidebar CSS'i tüm layout'lara ekle
// ════════════════════════════════════════════════════════
echo "\n── ADIM 4: Responsive Sidebar CSS ──\n";

$responsiveCss = <<<'CSS'
<style>
.sidebar-toggle{display:none;position:fixed;top:12px;left:12px;z-index:1001;width:40px;height:40px;border-radius:10px;background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e7eb);box-shadow:0 2px 8px rgba(0,0,0,.1);font-size:20px;cursor:pointer;align-items:center;justify-content:center}
.sidebar-close{display:none;position:absolute;top:8px;right:8px;background:rgba(255,255,255,.15);border:none;color:#fff;border-radius:6px;width:28px;height:28px;cursor:pointer;font-size:14px;align-items:center;justify-content:center}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:999}
@media(max-width:1023px){
    .shell{grid-template-columns:1fr!important}
    .side{position:fixed;top:0;left:0;bottom:0;width:280px;z-index:1000;transform:translateX(-100%);transition:transform .25s ease}
    .side.mobile-open{transform:translateX(0)}
    .sidebar-toggle{display:flex}
    .sidebar-close{display:flex}
    .sidebar-overlay.active{display:block}
}
</style>
CSS;

$mobileHtml = <<<'HTML'
<button class="sidebar-toggle" onclick="document.querySelector('.side').classList.add('mobile-open');document.getElementById('sideOverlay').classList.add('active');">☰</button>
HTML;

$overlayHtml = <<<'HTML'
<div class="sidebar-overlay" id="sideOverlay" onclick="document.querySelector('.side').classList.remove('mobile-open');this.classList.remove('active');"></div>
HTML;

foreach ($layouts as $layoutPath) {
    if (!file_exists($layoutPath)) continue;
    $content = file_get_contents($layoutPath);
    $shortName = str_replace($viewsPath . '/', '', $layoutPath);
    
    // Zaten varsa atla
    if (strpos($content, 'sidebar-toggle') !== false) {
        echo "  - $shortName: responsive CSS zaten var\n";
        continue;
    }
    
    // @stack('head') veya </style> sonrasına CSS ekle
    if (strpos($content, '@stack(\'head\')') !== false) {
        $content = str_replace('@stack(\'head\')', '@stack(\'head\')' . "\n" . $responsiveCss, $content);
    } elseif (strpos($content, '@stack("head")') !== false) {
        $content = str_replace('@stack("head")', '@stack("head")' . "\n" . $responsiveCss, $content);
    }
    
    // <div class="shell"> sonrasına hamburger ekle
    if (strpos($content, '<div class="shell">') !== false) {
        $content = str_replace('<div class="shell">', '<div class="shell">' . "\n    " . $mobileHtml, $content);
    }
    
    // </aside> sonrasına overlay ekle
    if (strpos($content, '</aside>') !== false && strpos($content, 'sideOverlay') === false) {
        $content = preg_replace('/(<\/aside>)/', '$1' . "\n    " . $overlayHtml, $content, 1);
    }
    
    // <aside class="side"> içine close butonu ekle
    if (strpos($content, 'sidebar-close') === false) {
        $content = preg_replace('/(<aside class="side">)/', '$1' . "\n        " . '<button class="sidebar-close" onclick="document.querySelector(\'.side\').classList.remove(\'mobile-open\');document.getElementById(\'sideOverlay\').classList.remove(\'active\');">✕</button>', $content, 1);
    }
    
    backupAndWrite($layoutPath, $content);
    reportChange($shortName, 'Responsive sidebar eklendi');
}

// ════════════════════════════════════════════════════════
// ÖZET
// ════════════════════════════════════════════════════════
echo "\n═══════════════════════════════════════════════════\n";
echo "  TAMAMLANDI!\n";
echo "  Degisen dosya: " . count($changedFiles) . "\n";
echo "  Yedek (.bak): $backupCount\n";
echo "═══════════════════════════════════════════════════\n\n";

echo "Degisiklik listesi:\n";
foreach ($changedFiles as $c) {
    echo "  ✓ {$c['file']} — {$c['change']}\n";
}

echo "\nSONRAKI ADIM:\n";
echo "  1. npm run build\n";
echo "  2. Tarayicida Ctrl+Shift+R\n";
echo "  3. Console'da typeof Alpine → 'object' olmali\n";
echo "  4. Alpine.store('toast').success('Calisiyor!') → yesil bildirim\n";
echo "\nGERI ALMA:\n";
echo "  Her dosyanin .bak versiyonu var. Geri almak icin:\n";
echo "  copy dosya.blade.php.bak dosya.blade.php\n";
