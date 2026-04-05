@extends('manager.layouts.app')

@section('title', 'Webhook Logları')
@section('page_title', 'Webhook Logları')
@section('page_subtitle', 'Gelen webhook olaylarını izle ve yönet')

@section('content')
<div style="display:grid;gap:14px;">

    {{-- KPI kartları --}}
    <div class="grid3">
        <div class="panel" style="text-align:center;">
            <div style="font-size:28px;font-weight:800;color:var(--u-brand);">{{ number_format($stats['total']) }}</div>
            <div style="font-size:12px;color:var(--u-muted);margin-top:4px;">Toplam Webhook</div>
        </div>
        <div class="panel" style="text-align:center;">
            <div style="font-size:28px;font-weight:800;color:var(--u-danger);">{{ number_format($stats['failed']) }}</div>
            <div style="font-size:12px;color:var(--u-muted);margin-top:4px;">Başarısız</div>
        </div>
        <div class="panel" style="text-align:center;">
            <div style="font-size:28px;font-weight:800;color:var(--u-ok);">{{ number_format($stats['today']) }}</div>
            <div style="font-size:12px;color:var(--u-muted);margin-top:4px;">Bugün</div>
        </div>
    </div>

    {{-- Filtre --}}
    <div class="panel">
        <form method="GET" action="/manager/webhooks" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
            <div style="display:flex;flex-direction:column;gap:4px;">
                <label style="font-size:11px;font-weight:600;color:var(--u-muted);">Kaynak</label>
                <select name="source" style="height:34px;padding:0 10px;border-radius:8px;font-size:13px;min-width:140px;">
                    <option value="">Tümü</option>
                    @foreach($sources as $src)
                        <option value="{{ $src }}" {{ request('source') === $src ? 'selected' : '' }}>{{ $src }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;flex-direction:column;gap:4px;">
                <label style="font-size:11px;font-weight:600;color:var(--u-muted);">Durum</label>
                <select name="status" style="height:34px;padding:0 10px;border-radius:8px;font-size:13px;min-width:130px;">
                    <option value="">Tümü</option>
                    <option value="received"  {{ request('status') === 'received'  ? 'selected' : '' }}>Alındı</option>
                    <option value="processed" {{ request('status') === 'processed' ? 'selected' : '' }}>İşlendi</option>
                    <option value="failed"    {{ request('status') === 'failed'    ? 'selected' : '' }}>Başarısız</option>
                </select>
            </div>
            <button type="submit" class="btn" style="height:34px;padding:0 18px;font-size:13px;">Filtrele</button>
            <a href="/manager/webhooks" class="btn alt" style="height:34px;padding:0 14px;font-size:13px;display:flex;align-items:center;">Temizle</a>
        </form>
    </div>

    {{-- Tablo --}}
    <div class="panel" style="padding:0;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:color-mix(in srgb,var(--u-brand) 4%,var(--u-card));">
                        <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted);text-align:left;border-bottom:1px solid var(--u-line);">ID</th>
                        <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted);text-align:left;border-bottom:1px solid var(--u-line);">Kaynak</th>
                        <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted);text-align:left;border-bottom:1px solid var(--u-line);">Olay Tipi</th>
                        <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted);text-align:left;border-bottom:1px solid var(--u-line);">Durum</th>
                        <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted);text-align:left;border-bottom:1px solid var(--u-line);max-width:200px;">Hata Mesajı</th>
                        <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted);text-align:left;border-bottom:1px solid var(--u-line);">Tarih</th>
                        <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted);text-align:right;border-bottom:1px solid var(--u-line);">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    @php
                        $statusBadge = match($log->status) {
                            'processed' => 'ok',
                            'failed'    => 'danger',
                            default     => 'info',
                        };
                        $statusLabel = match($log->status) {
                            'received'  => 'Alındı',
                            'processed' => 'İşlendi',
                            'failed'    => 'Başarısız',
                            default     => $log->status,
                        };
                    @endphp
                    <tr style="border-bottom:1px solid var(--u-line);">
                        <td style="padding:9px 14px;font-size:13px;color:var(--u-muted);">#{{ $log->id }}</td>
                        <td style="padding:9px 14px;font-size:13px;font-weight:600;">{{ $log->source }}</td>
                        <td style="padding:9px 14px;font-size:13px;">{{ $log->event_type ?? '—' }}</td>
                        <td style="padding:9px 14px;">
                            <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                        </td>
                        <td style="padding:9px 14px;font-size:12px;color:var(--u-danger);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $log->error_message }}">
                            {{ $log->error_message ? \Illuminate\Support\Str::limit($log->error_message, 60) : '—' }}
                        </td>
                        <td style="padding:9px 14px;font-size:12px;color:var(--u-muted);white-space:nowrap;">{{ $log->created_at?->format('d.m.Y H:i') }}</td>
                        <td style="padding:9px 14px;text-align:right;">
                            <div style="display:flex;gap:6px;justify-content:flex-end;">
                                @if($log->status === 'failed')
                                <form method="POST" action="/manager/webhooks/{{ $log->id }}/retry" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn alt" style="padding:4px 10px;font-size:11px;height:28px;">Yeniden Dene</button>
                                </form>
                                @endif
                                <form method="POST" action="/manager/webhooks/{{ $log->id }}" style="display:inline;"
                                      onsubmit="return confirm('Bu log silinsin mi?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn warn" style="padding:4px 10px;font-size:11px;height:28px;">Sil</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="padding:32px;text-align:center;color:var(--u-muted);font-size:13px;">
                            Henüz webhook logu yok.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div style="padding:12px 16px;border-top:1px solid var(--u-line);">
            {{ $logs->appends(request()->query())->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
