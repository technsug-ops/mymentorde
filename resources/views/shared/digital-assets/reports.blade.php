@extends($layout)

@section('title', 'DAM Raporları')
@section('page_title', 'Dijital Varlık Raporları')

@section('content')
@php
    $fmtSize = function (int $bytes): string {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024*1024) return round($bytes/1024, 1) . ' KB';
        if ($bytes < 1024*1024*1024) return round($bytes/1024/1024, 1) . ' MB';
        return round($bytes/1024/1024/1024, 2) . ' GB';
    };
    $categoryEmoji = [
        'image' => '🖼️', 'video' => '🎬', 'audio' => '🎵',
        'document' => '📄', 'archive' => '🗜️', 'other' => '📎',
    ];
    $actionLabels = [
        'upload' => '📤 Yüklendi', 'download' => '⬇️ İndirildi', 'delete' => '🗑 Silindi',
        'update' => '✎ Güncellendi', 'move' => '➜ Taşındı', 'share' => '🔗 Paylaşıldı',
        'folder_create' => '📁 Klasör oluşturuldu', 'folder_rename' => '✎ Klasör adı değişti',
        'folder_move' => '➜ Klasör taşındı', 'folder_delete' => '🗑 Klasör silindi',
    ];
@endphp

<div style="max-width:1280px;margin:0 auto;padding:18px 24px;">
    {{-- Back link --}}
    <a href="{{ route($routePrefix . '.index') }}" style="display:inline-flex;align-items:center;gap:6px;color:var(--u-muted,#64748b);text-decoration:none;font-size:13px;margin-bottom:14px">
        ← Dosyalara Dön
    </a>

    <h1 style="font-size:22px;font-weight:700;margin:0 0 18px">📊 Dijital Varlık Raporları</h1>

    {{-- KPI Kartları --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:22px">
        <div style="background:#fff;border:1px solid var(--u-line,#e2e8f0);border-radius:12px;padding:18px 20px">
            <div style="font-size:11px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.06em">Toplam Varlık</div>
            <div style="font-size:28px;font-weight:700;color:var(--u-text);margin-top:4px">{{ number_format($totalCount) }}</div>
        </div>
        <div style="background:#fff;border:1px solid var(--u-line,#e2e8f0);border-radius:12px;padding:18px 20px">
            <div style="font-size:11px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.06em">Toplam Boyut</div>
            <div style="font-size:28px;font-weight:700;color:var(--u-text);margin-top:4px">{{ $fmtSize($totalSize) }}</div>
        </div>
        @foreach($byCategory as $cat)
            @php $label = ($categoryEmoji[$cat->category] ?? '📎') . ' ' . ucfirst($cat->category); @endphp
            <div style="background:#fff;border:1px solid var(--u-line,#e2e8f0);border-radius:12px;padding:18px 20px">
                <div style="font-size:11px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.06em">{{ $label }}</div>
                <div style="font-size:20px;font-weight:700;color:var(--u-text);margin-top:4px">{{ number_format($cat->count) }} <span style="font-size:11px;color:var(--u-muted);font-weight:500">/ {{ $fmtSize((int) $cat->total_size) }}</span></div>
            </div>
        @endforeach
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:22px">
        {{-- En çok indirilen --}}
        <div style="background:#fff;border:1px solid var(--u-line);border-radius:12px;padding:18px">
            <h3 style="font-size:14px;font-weight:700;margin:0 0 12px;color:var(--u-text)">🏆 En Çok İndirilen</h3>
            @forelse($topDownloaded as $i => $asset)
                <div style="display:flex;align-items:center;gap:10px;padding:8px 0;{{ !$loop->last ? 'border-bottom:1px solid #f1f5f9;' : '' }}">
                    <span style="background:#f1f5f9;color:#475569;font-size:11px;font-weight:700;padding:3px 9px;border-radius:99px;min-width:26px;text-align:center">{{ $i + 1 }}</span>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:13px;font-weight:600;color:var(--u-text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $asset->name }}</div>
                        <div style="font-size:11px;color:var(--u-muted)">{{ $categoryEmoji[$asset->category] ?? '📎' }} {{ $fmtSize((int) $asset->size_bytes) }}</div>
                    </div>
                    <span style="font-size:13px;font-weight:700;color:#1e40af">{{ $asset->download_count }}↓</span>
                </div>
            @empty
                <div style="color:var(--u-muted);font-size:12px;text-align:center;padding:20px">Henüz indirme yok</div>
            @endforelse
        </div>

        {{-- 30 gün aktivite özeti --}}
        <div style="background:#fff;border:1px solid var(--u-line);border-radius:12px;padding:18px">
            <h3 style="font-size:14px;font-weight:700;margin:0 0 12px;color:var(--u-text)">📈 Son 30 Gün Aktivite</h3>
            @forelse($activitySummary as $entry)
                <div style="display:flex;align-items:center;justify-content:space-between;padding:7px 0;{{ !$loop->last ? 'border-bottom:1px solid #f1f5f9;' : '' }}">
                    <span style="font-size:13px;color:var(--u-text)">{{ $actionLabels[$entry->action] ?? $entry->action }}</span>
                    <span style="font-size:13px;font-weight:700;color:var(--u-text)">{{ number_format($entry->count) }}</span>
                </div>
            @empty
                <div style="color:var(--u-muted);font-size:12px;text-align:center;padding:20px">Kayıt yok</div>
            @endforelse
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 2fr;gap:18px">
        {{-- En aktif kullanıcılar --}}
        <div style="background:#fff;border:1px solid var(--u-line);border-radius:12px;padding:18px">
            <h3 style="font-size:14px;font-weight:700;margin:0 0 12px;color:var(--u-text)">👥 En Aktif Kullanıcılar (30gün)</h3>
            @forelse($topUsers as $i => $u)
                <div style="display:flex;align-items:center;gap:10px;padding:7px 0;{{ !$loop->last ? 'border-bottom:1px solid #f1f5f9;' : '' }}">
                    <span style="background:#fef3c7;color:#92400e;font-size:11px;font-weight:700;padding:3px 9px;border-radius:99px;min-width:26px;text-align:center">{{ $i + 1 }}</span>
                    <span style="flex:1;font-size:13px;color:var(--u-text)">{{ $u->user_name ?: 'Bilinmeyen' }}</span>
                    <span style="font-size:12px;font-weight:700;color:var(--u-muted)">{{ $u->count }}</span>
                </div>
            @empty
                <div style="color:var(--u-muted);font-size:12px;text-align:center;padding:20px">Kayıt yok</div>
            @endforelse
        </div>

        {{-- Son aktiviteler --}}
        <div style="background:#fff;border:1px solid var(--u-line);border-radius:12px;padding:18px">
            <h3 style="font-size:14px;font-weight:700;margin:0 0 12px;color:var(--u-text)">🕑 Son Aktiviteler</h3>
            <div style="max-height:420px;overflow-y:auto">
                @forelse($recentActivity as $log)
                    <div style="display:flex;gap:10px;padding:9px 0;border-bottom:1px solid #f1f5f9;font-size:12px">
                        <span style="color:var(--u-muted);flex-shrink:0;min-width:80px">{{ $log->created_at->diffForHumans() }}</span>
                        <span style="color:var(--u-text);font-weight:600;min-width:140px">{{ $actionLabels[$log->action] ?? $log->action }}</span>
                        <span style="color:var(--u-muted);flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $log->target_name ?: '—' }}</span>
                        <span style="color:var(--u-muted);flex-shrink:0">{{ $log->user_name ?: 'Sistem' }}</span>
                    </div>
                @empty
                    <div style="color:var(--u-muted);font-size:12px;text-align:center;padding:20px">Kayıt yok</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
