@extends('platform.layouts.app')

@section('title', 'Altyapı — MentorDE Platform')

@push('styles')
<style>
    .pinfra-status-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 22px; }
    @media (max-width: 900px) { .pinfra-status-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 540px) { .pinfra-status-grid { grid-template-columns: 1fr; } }

    .pinfra-status { background: var(--plat-panel); border: 1px solid var(--plat-border); border-radius: 12px; padding: 16px 18px; }
    .pinfra-status-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
    .pinfra-status-title { font-size: 12px; font-weight: 700; color: var(--plat-muted); text-transform: uppercase; letter-spacing: .5px; display: flex; align-items: center; gap: 6px; }
    .pinfra-status-val { font-size: 18px; font-weight: 700; color: #fff; }
    .pinfra-status-sub { font-size: 12px; color: var(--plat-muted); margin-top: 4px; }

    .pinfra-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; }
    .pinfra-dot-ok   { background: var(--plat-ok); box-shadow: 0 0 0 4px rgba(74,222,128,.16); }
    .pinfra-dot-warn { background: var(--plat-warn); box-shadow: 0 0 0 4px rgba(251,191,36,.16); }
    .pinfra-dot-bad  { background: var(--plat-danger); box-shadow: 0 0 0 4px rgba(248,113,113,.16); }

    .pinfra-metric { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed var(--plat-border); font-size: 13px; }
    .pinfra-metric:last-child { border-bottom: none; }
    .pinfra-metric-label { color: var(--plat-muted); font-weight: 600; }
    .pinfra-metric-val   { color: #fff; font-weight: 700; font-family: monospace; }

    .pinfra-bar { height: 8px; background: var(--plat-panel-2); border-radius: 999px; overflow: hidden; margin-top: 6px; }
    .pinfra-bar-fill { height: 100%; background: linear-gradient(90deg, var(--plat-accent), var(--plat-accent-2)); border-radius: 999px; }

    .pinfra-actions { display: flex; gap: 10px; flex-wrap: wrap; }

    /* Confirm modal */
    .pinfra-modal-bg { position: fixed; inset: 0; background: rgba(0,0,0,.6); display: none; align-items: center; justify-content: center; z-index: 100; }
    .pinfra-modal-bg.open { display: flex; }
    .pinfra-modal { background: var(--plat-panel); border: 1px solid var(--plat-border); border-radius: 12px; padding: 22px; max-width: 420px; width: 92%; }
    .pinfra-modal h3 { margin: 0 0 10px; color: #fff; font-size: 16px; }
    .pinfra-modal p { color: var(--plat-muted); font-size: 13px; margin: 0 0 18px; }
    .pinfra-modal-actions { display: flex; justify-content: flex-end; gap: 8px; }

    .pinfra-ext-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 6px; }
    .pinfra-ext-pill { background: var(--plat-panel-2); border: 1px solid var(--plat-border); padding: 4px 8px; border-radius: 6px; font-size: 11px; color: var(--plat-muted); font-family: monospace; text-align: center; }
</style>
@endpush

@section('content')

<div class="plat-page-header">
    <div>
        <h1 class="plat-page-title">Altyapı</h1>
        <p class="plat-page-sub">Sunucu, veritabanı, cache ve queue sağlığı + sistem operasyonları.</p>
    </div>
    <div class="pinfra-actions">
        <button type="button" class="plat-btn plat-btn-ghost" data-confirm="cache">
            <x-icon name="refresh-cw" size="14" /> Cache flush
        </button>
        <button type="button" class="plat-btn plat-btn-ghost" data-confirm="migrate">
            <x-icon name="database" size="14" /> Migrate
        </button>
        <button type="button" class="plat-btn plat-btn-ghost" data-confirm="autoload">
            <x-icon name="refresh-cw" size="14" /> Autoload refresh
        </button>
    </div>
</div>

{{-- ── 4 status card ─────────────────────────────────────────────── --}}
<div class="pinfra-status-grid">

    <div class="pinfra-status">
        <div class="pinfra-status-head">
            <span class="pinfra-status-title"><x-icon name="server" size="14" /> PHP</span>
            <span class="pinfra-dot pinfra-dot-ok"></span>
        </div>
        <div class="pinfra-status-val">{{ $php['version'] }}</div>
        <div class="pinfra-status-sub">SAPI: {{ $php['sapi'] }} · Memory: {{ $php['memory'] }}</div>
    </div>

    <div class="pinfra-status">
        <div class="pinfra-status-head">
            <span class="pinfra-status-title"><x-icon name="database" size="14" /> MySQL</span>
            <span class="pinfra-dot {{ $mysql['connected'] ? 'pinfra-dot-ok' : 'pinfra-dot-bad' }}"></span>
        </div>
        <div class="pinfra-status-val">{{ $mysql['connected'] ? ($mysql['version'] ?? 'OK') : 'Bağlantı yok' }}</div>
        <div class="pinfra-status-sub">DB: <code>{{ $mysql['database'] }}</code></div>
    </div>

    <div class="pinfra-status">
        <div class="pinfra-status-head">
            <span class="pinfra-status-title"><x-icon name="gauge" size="14" /> Cache</span>
            <span class="pinfra-dot {{ $cache['status'] === 'ok' ? 'pinfra-dot-ok' : ($cache['status'] === 'fail' ? 'pinfra-dot-bad' : 'pinfra-dot-warn') }}"></span>
        </div>
        <div class="pinfra-status-val">{{ strtoupper($cache['driver']) }}</div>
        <div class="pinfra-status-sub">
            Test: {{ $cache['status'] === 'ok' ? 'çalışıyor' : ($cache['status'] === 'fail' ? 'başarısız' : 'bilinmiyor') }}
            @if ($redis['enabled'])
                · Redis: {{ $redis['status'] ?? '—' }}
            @endif
        </div>
    </div>

    <div class="pinfra-status">
        <div class="pinfra-status-head">
            <span class="pinfra-status-title"><x-icon name="refresh-cw" size="14" /> Queue</span>
            <span class="pinfra-dot {{ ($queue['failed'] ?? 0) > 0 ? 'pinfra-dot-warn' : 'pinfra-dot-ok' }}"></span>
        </div>
        <div class="pinfra-status-val">{{ strtoupper($queue['driver']) }}</div>
        <div class="pinfra-status-sub">
            Pending: {{ $queue['pending'] ?? '—' }} ·
            Failed: {{ $queue['failed'] ?? '—' }}
        </div>
    </div>

</div>

{{-- ── System metrics + disk ─────────────────────────────────────── --}}
<div class="plat-grid plat-grid-2" style="margin-bottom: 22px;">

    <div class="plat-card">
        <h3 class="plat-card-title"><x-icon name="gauge" size="16" /> Sistem Metrikleri</h3>

        <div class="pinfra-metric">
            <span class="pinfra-metric-label">Laravel</span>
            <span class="pinfra-metric-val">{{ $laravel }}</span>
        </div>
        <div class="pinfra-metric">
            <span class="pinfra-metric-label">Environment</span>
            <span class="pinfra-metric-val">{{ $env }}</span>
        </div>
        <div class="pinfra-metric">
            <span class="pinfra-metric-label">PHP memory (peak)</span>
            <span class="pinfra-metric-val">{{ $php['memory_peak'] }}</span>
        </div>

        @if (is_array($load))
            <div class="pinfra-metric">
                <span class="pinfra-metric-label">Server load (1/5/15 dk)</span>
                <span class="pinfra-metric-val">
                    {{ number_format($load[0] ?? 0, 2) }} /
                    {{ number_format($load[1] ?? 0, 2) }} /
                    {{ number_format($load[2] ?? 0, 2) }}
                </span>
            </div>
        @endif

        @if ($opcache['enabled'])
            <div class="pinfra-metric">
                <span class="pinfra-metric-label">Opcache hit-rate</span>
                <span class="pinfra-metric-val">{{ $opcache['hit_rate'] }}%</span>
            </div>
            <div class="pinfra-metric">
                <span class="pinfra-metric-label">Opcache scripts / mem</span>
                <span class="pinfra-metric-val">{{ $opcache['cached_scripts'] }} · {{ $opcache['memory_used'] }}</span>
            </div>
        @else
            <div class="pinfra-metric">
                <span class="pinfra-metric-label">Opcache</span>
                <span class="pinfra-metric-val">devre dışı</span>
            </div>
        @endif
    </div>

    <div class="plat-card">
        <h3 class="plat-card-title"><x-icon name="database" size="16" /> Disk Kullanımı</h3>
        @if (!empty($disk['pct']))
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 4px;">
                <span class="plat-card-sub">{{ $disk['used_h'] }} / {{ $disk['total_h'] }}</span>
                <span style="font-size: 20px; font-weight: 800; color: #fff;">{{ $disk['pct'] }}%</span>
            </div>
            <div class="pinfra-bar"><div class="pinfra-bar-fill" style="width: {{ min(100, $disk['pct']) }}%;"></div></div>
            <div class="pinfra-metric" style="margin-top: 14px;">
                <span class="pinfra-metric-label">Yol</span>
                <span class="pinfra-metric-val" style="font-size: 11px;">{{ $disk['path'] }}</span>
            </div>
            <div class="pinfra-metric">
                <span class="pinfra-metric-label">Boş</span>
                <span class="pinfra-metric-val">{{ $disk['free_h'] }}</span>
            </div>
        @else
            <p class="plat-card-sub">Disk bilgisi alınamadı (storage_path okuma yetkisi yok olabilir).</p>
        @endif
    </div>
</div>

{{-- ── Migrations + Packages ─────────────────────────────────────── --}}
<div class="plat-grid plat-grid-2" style="margin-bottom: 22px;">

    <div class="plat-card">
        <h3 class="plat-card-title"><x-icon name="database" size="16" /> Son Migrations (15)</h3>
        @if (count($migrations) > 0)
            <table class="plat-table">
                <thead>
                    <tr><th>Migration</th><th style="width: 60px;">Batch</th></tr>
                </thead>
                <tbody>
                    @foreach ($migrations as $m)
                        <tr>
                            <td style="font-family: monospace; font-size: 11px;">{{ $m->migration }}</td>
                            <td>{{ $m->batch }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="plat-card-sub">migrations tablosu okunamadı.</p>
        @endif
    </div>

    <div class="plat-card">
        <h3 class="plat-card-title"><x-icon name="server" size="16" /> Composer Paketler (top 20)</h3>
        @if (count($packages) > 0)
            <table class="plat-table">
                <thead>
                    <tr><th>Paket</th><th style="width: 100px;">Versiyon</th></tr>
                </thead>
                <tbody>
                    @foreach ($packages as $pkg)
                        <tr>
                            <td style="font-family: monospace; font-size: 11px;">{{ $pkg['name'] }}</td>
                            <td style="font-family: monospace; font-size: 11px;">{{ $pkg['version'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="plat-card-sub">vendor/composer/installed.json okunamadı.</p>
        @endif
    </div>
</div>

{{-- ── PHP extensions ─────────────────────────────────────────────── --}}
<div class="plat-card">
    <h3 class="plat-card-title"><x-icon name="server" size="16" /> Yüklü PHP Uzantıları (ilk 40)</h3>
    <div class="pinfra-ext-grid">
        @foreach ($php['extensions'] as $ext)
            <div class="pinfra-ext-pill">{{ $ext }}</div>
        @endforeach
    </div>
</div>

{{-- ── Confirm modals ─────────────────────────────────────────────── --}}
<div class="pinfra-modal-bg" id="pinfra-modal-cache">
    <div class="pinfra-modal">
        <h3><x-icon name="refresh-cw" size="16" /> Cache flush onayı</h3>
        <p>Tüm Laravel cache (data + config + route + view) temizlenecek. Sonraki istek biraz yavaş olabilir. Devam edilsin mi?</p>
        <div class="pinfra-modal-actions">
            <button type="button" class="plat-btn plat-btn-ghost" data-modal-cancel>İptal</button>
            <form method="POST" action="{{ route('platform.infrastructure.flush-cache') }}" style="margin:0;">
                @csrf
                <button type="submit" class="plat-btn plat-btn-primary"><x-icon name="refresh-cw" size="14" /> Cache temizle</button>
            </form>
        </div>
    </div>
</div>

<div class="pinfra-modal-bg" id="pinfra-modal-migrate">
    <div class="pinfra-modal">
        <h3><x-icon name="database" size="16" /> Migration onayı</h3>
        <p>Pending migration'lar üretim DB üzerinde çalıştırılacak. Backup aldığınızdan emin olun. Devam edilsin mi?</p>
        <div class="pinfra-modal-actions">
            <button type="button" class="plat-btn plat-btn-ghost" data-modal-cancel>İptal</button>
            <form method="POST" action="{{ route('platform.infrastructure.migrate') }}" style="margin:0;">
                @csrf
                <button type="submit" class="plat-btn plat-btn-primary"><x-icon name="database" size="14" /> Migrate</button>
            </form>
        </div>
    </div>
</div>

<div class="pinfra-modal-bg" id="pinfra-modal-autoload">
    <div class="pinfra-modal">
        <h3><x-icon name="refresh-cw" size="16" /> Autoload yenile</h3>
        <p>composer dump-autoload --optimize çalıştırılacak. Yeni Class'lar tanınmıyorsa kullanın.</p>
        <div class="pinfra-modal-actions">
            <button type="button" class="plat-btn plat-btn-ghost" data-modal-cancel>İptal</button>
            <form method="POST" action="{{ route('platform.infrastructure.dump-autoload') }}" style="margin:0;">
                @csrf
                <button type="submit" class="plat-btn plat-btn-primary"><x-icon name="refresh-cw" size="14" /> Yenile</button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function() {
    const openers = document.querySelectorAll('[data-confirm]');
    openers.forEach(b => {
        b.addEventListener('click', () => {
            const id = 'pinfra-modal-' + b.dataset.confirm;
            const el = document.getElementById(id);
            if (el) el.classList.add('open');
        });
    });
    document.querySelectorAll('[data-modal-cancel]').forEach(b => {
        b.addEventListener('click', () => {
            b.closest('.pinfra-modal-bg')?.classList.remove('open');
        });
    });
    // ESC close
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.pinfra-modal-bg.open').forEach(m => m.classList.remove('open'));
        }
    });
})();
</script>
@endpush

@endsection
