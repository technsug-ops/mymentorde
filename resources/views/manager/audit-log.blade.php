@extends('manager.layouts.app')
@section('title', 'Audit Log')
@section('page_title', 'Audit Log')

@section('content')
<div class="page-header">
    <h1>Audit Log</h1>
    <div class="u-muted" style="font-size:var(--tx-sm);">GDPR Madde 30 — Kişisel veriye erişim ve sistem olayı kayıtları</div>
</div>

<form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
    <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Mesaj veya entity ID ara..." style="flex:2;min-width:200px;padding:7px 10px;border:1px solid var(--u-line);border-radius:4px;">
    <select name="event_type" style="flex:1;min-width:160px;padding:7px 8px;border:1px solid var(--u-line);border-radius:4px;">
        <option value="">Tüm Olay Tipleri</option>
        @foreach($eventTypes as $et)
        <option value="{{ $et }}" {{ $et === $filters['eventType'] ? 'selected' : '' }}>{{ $et }}</option>
        @endforeach
    </select>
    <input type="text" name="actor" value="{{ $filters['actor'] }}" placeholder="Aktör e-posta..." style="flex:1;min-width:160px;padding:7px 10px;border:1px solid var(--u-line);border-radius:4px;">
    <button class="btn" type="submit">Filtrele</button>
    <a href="/manager/audit-log" class="btn alt">Temizle</a>
</form>

<div class="card" style="padding:0;">
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:var(--tx-sm);">
            <thead>
                <tr style="border-bottom:2px solid var(--u-line);background:var(--u-bg);">
                    <th style="padding:10px 12px;text-align:left;white-space:nowrap;">Tarih</th>
                    <th style="padding:10px 12px;text-align:left;white-space:nowrap;">Olay Tipi</th>
                    <th style="padding:10px 12px;text-align:left;">Aktör</th>
                    <th style="padding:10px 12px;text-align:left;">Entity</th>
                    <th style="padding:10px 12px;text-align:left;">Mesaj</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr style="border-bottom:1px solid var(--u-line);" class="{{ str_starts_with($log->event_type, 'gdpr') ? 'gdpr-row' : '' }}">
                    <td style="padding:8px 12px;white-space:nowrap;color:var(--u-muted);font-size:var(--tx-xs);">{{ $log->created_at->format('d.m.Y H:i') }}</td>
                    <td style="padding:8px 12px;white-space:nowrap;">
                        <span class="badge {{ str_starts_with($log->event_type, 'gdpr') ? 'warn' : 'info' }}" style="font-size:var(--tx-xs);">{{ $log->event_type }}</span>
                    </td>
                    <td style="padding:8px 12px;font-size:var(--tx-xs);">{{ $log->actor_email ?: '—' }}</td>
                    <td style="padding:8px 12px;font-size:var(--tx-xs);">
                        @if($log->entity_type) <span class="u-muted">{{ $log->entity_type }}</span> @endif
                        @if($log->entity_id) #{{ $log->entity_id }} @endif
                    </td>
                    <td style="padding:8px 12px;">{{ $log->message }}</td>
                </tr>
                @empty
                <tr><td colspan="5" style="padding:20px;text-align:center;color:var(--u-muted);">Kayıt bulunamadı.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:12px;">
    {{ $logs->withQueryString()->links('partials.pagination') }}
</div>
@endsection
