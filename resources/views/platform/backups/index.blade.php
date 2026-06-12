@extends('platform.layouts.app')

@section('title', 'Yedekler — DGmarkt Platform')

@push('styles')
<style>
    .pback-summary {
        display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 22px;
    }
    @media (max-width: 900px) { .pback-summary { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 540px) { .pback-summary { grid-template-columns: 1fr; } }
    .pback-kpi {
        background: var(--plat-panel); border: 1px solid var(--plat-border);
        border-radius: 12px; padding: 16px 18px;
    }
    .pback-kpi-label { font-size: 11.5px; font-weight: 700; color: var(--plat-muted); text-transform: uppercase; letter-spacing: .5px; }
    .pback-kpi-val   { font-size: 22px; font-weight: 700; color: #fff; margin-top: 6px; }
    .pback-kpi-sub   { font-size: 11.5px; color: var(--plat-muted); margin-top: 4px; }

    .pback-table { width: 100%; border-collapse: collapse; }
    .pback-table th { text-align: left; padding: 10px 12px; font-size: 11.5px; font-weight: 700; color: var(--plat-muted); text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid var(--plat-border); }
    .pback-table td { padding: 12px; font-size: 13px; color: #fff; border-bottom: 1px solid var(--plat-border); }
    .pback-table tr:last-child td { border-bottom: none; }
    .pback-table tr:hover td { background: rgba(255,255,255,.02); }

    .pback-file { font-family: monospace; font-size: 12px; }
    .pback-size { font-family: monospace; font-variant-numeric: tabular-nums; }
    .pback-actions { display: flex; gap: 6px; justify-content: flex-end; }
</style>
@endpush

@section('content')
<div class="plat-page-head">
    <div>
        <h1>💾 Yedekler</h1>
        <p class="plat-muted">Veritabanı yedek listesi · {{ $scheduleInfo }}</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <form method="POST" action="{{ route('platform.backups.create') }}" style="display:inline;">
            @csrf
            <button type="submit" class="plat-btn plat-btn-primary"
                    onclick="return confirm('Manuel yedek oluştur — birkaç saniye sürer. Devam edilsin mi?');">
                💾 Şimdi Yedek Al
            </button>
        </form>
    </div>
</div>

@if(session('success'))
    <div class="plat-flash plat-flash-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="plat-flash plat-flash-danger">{{ session('error') }}</div>
@endif

{{-- Özet KPI'lar --}}
<div class="pback-summary">
    <div class="pback-kpi">
        <div class="pback-kpi-label">Toplam Yedek</div>
        <div class="pback-kpi-val">{{ $totalCount }}</div>
        <div class="pback-kpi-sub">Storage local disk</div>
    </div>
    <div class="pback-kpi">
        <div class="pback-kpi-label">Toplam Boyut</div>
        <div class="pback-kpi-val">{{ $totalSize }}</div>
        <div class="pback-kpi-sub">gzip sıkıştırılmış</div>
    </div>
    <div class="pback-kpi">
        <div class="pback-kpi-label">Son Yedek</div>
        <div class="pback-kpi-val">
            @if($latest)
                {{ $latest['age_label'] }}
            @else
                <span style="color: var(--plat-danger);">Yok</span>
            @endif
        </div>
        <div class="pback-kpi-sub">
            @if($latest)
                {{ \Carbon\Carbon::parse($latest['created_at'])->format('d.m.Y H:i') }}
            @else
                Hiç yedek alınmadı
            @endif
        </div>
    </div>
    <div class="pback-kpi">
        <div class="pback-kpi-label">Schedule</div>
        <div class="pback-kpi-val" style="font-size:14px;">03:00</div>
        <div class="pback-kpi-sub">14 gün retention</div>
    </div>
</div>

@if($latestAge !== null && $latestAge > 30)
    <div class="plat-flash plat-flash-danger" style="margin-bottom:18px;">
        ⚠️ Son yedek {{ (int) $latestAge }} saat önce alındı — daily schedule sorunlu olabilir. Hemen manuel yedek al + cron loglarını kontrol et.
    </div>
@endif

{{-- Backup listesi --}}
<div class="plat-panel" style="padding:0;">
    @if($backups->count() === 0)
        <div style="padding:40px 22px;text-align:center;color:var(--plat-muted);">
            <div style="font-size:36px;margin-bottom:10px;">💾</div>
            <div style="font-size:14px;font-weight:600;margin-bottom:4px;">Henüz yedek yok.</div>
            <div style="font-size:12px;">"Şimdi Yedek Al" butonuna basarak ilk yedeği oluştur.</div>
        </div>
    @else
        <table class="pback-table">
            <thead>
                <tr>
                    <th>Dosya</th>
                    <th>Tarih</th>
                    <th style="text-align:right;">Boyut</th>
                    <th style="text-align:right;">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @foreach($backups as $b)
                    <tr>
                        <td class="pback-file">{{ $b['name'] }}</td>
                        <td>
                            {{ \Carbon\Carbon::parse($b['created_at'])->format('d.m.Y H:i:s') }}
                            <div style="color:var(--plat-muted);font-size:11.5px;">{{ $b['age_label'] }}</div>
                        </td>
                        <td class="pback-size" style="text-align:right;">{{ $b['size_label'] }}</td>
                        <td>
                            <div class="pback-actions">
                                <a href="{{ route('platform.backups.download', $b['name']) }}"
                                   class="plat-btn plat-btn-sm plat-btn-ghost"
                                   title="İndir">
                                    ⬇ İndir
                                </a>
                                <form method="POST" action="{{ route('platform.backups.destroy', $b['name']) }}"
                                      style="display:inline;"
                                      onsubmit="return confirm('Bu yedek kalıcı olarak silinecek. Emin misin?\n\n{{ $b['name'] }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="plat-btn plat-btn-sm plat-btn-danger" title="Sil">
                                        🗑
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<div style="margin-top:22px;padding:16px 20px;background:rgba(255,255,255,.03);border:1px solid var(--plat-border);border-radius:10px;font-size:13px;color:var(--plat-muted);line-height:1.6;">
    <strong style="color:#fff;">ℹ️ Restore prosedürü:</strong><br>
    1. İndirdiğin <code>.sql.gz</code> dosyasını <code>gunzip db_YYYY-MM-DD_HHMMSS.sql.gz</code> ile aç.<br>
    2. MySQL'e <code>mysql -u USER -p DATABASE &lt; db_YYYY-MM-DD_HHMMSS.sql</code> ile yükle.<br>
    3. Cache temizle: <code>php artisan cache:clear &amp;&amp; php artisan config:clear</code>
</div>
@endsection
