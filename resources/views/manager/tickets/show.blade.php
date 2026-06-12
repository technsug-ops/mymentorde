@extends('manager.layouts.app')
@section('title', 'Ticket #'.$ticket->id)
@section('page_title', 'Ticket Detay')

@php
    $priorityColors = ['low'=>'#94a3b8','medium'=>'#3b82f6','high'=>'#f59e0b','urgent'=>'#dc2626'];
    $statusColors   = ['open'=>'#3b82f6','in_progress'=>'#f59e0b','resolved'=>'#16a34a','closed'=>'#94a3b8'];
    $statusLabels   = ['open'=>'Açık','in_progress'=>'İşlemde','resolved'=>'Çözüldü','closed'=>'Kapatıldı'];
@endphp

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <div>
        <h1 style="margin:0 0 4px;font-size:20px;">
            <x-icon name="message-square" size="20" />
            Ticket #{{ $ticket->id }}
        </h1>
        <div class="u-muted" style="font-size:13px;line-height:1.4;">
            {{ $ticket->subject }}
        </div>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
        <a href="{{ route('manager.ticket-analytics') }}" class="btn btn-ghost">
            <x-icon name="arrow-left" size="14" /> Listeye dön
        </a>
        @if($docModuleOn)
            <button type="button" id="docReqOpenBtn_ticket" class="btn btn-primary">
                <x-icon name="file-plus" size="14" /> Belge Talep Et
            </button>
        @endif
    </div>
</div>

{{-- Üst meta blok --}}
<div class="card" style="margin-bottom:18px;padding:18px 22px;">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;">
        <div>
            <div class="u-muted" style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;">Durum</div>
            <div style="margin-top:6px;">
                <span style="background:{{ $statusColors[$ticket->status] ?? '#94a3b8' }};color:#fff;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700;">
                    {{ $statusLabels[$ticket->status] ?? $ticket->status }}
                </span>
            </div>
        </div>
        <div>
            <div class="u-muted" style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;">Öncelik</div>
            <div style="margin-top:6px;">
                <span style="background:{{ $priorityColors[$ticket->priority] ?? '#94a3b8' }};color:#fff;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700;">
                    {{ strtoupper($ticket->priority ?? 'normal') }}
                </span>
            </div>
        </div>
        <div>
            <div class="u-muted" style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;">Departman</div>
            <div style="margin-top:6px;font-size:13px;font-weight:600;">
                {{ $ticket->department ?? '—' }}
            </div>
        </div>
        <div>
            <div class="u-muted" style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;">Açan</div>
            <div style="margin-top:6px;font-size:13px;font-weight:600;">
                {{ $ticket->created_by_email ?? '—' }}
            </div>
        </div>
        <div>
            <div class="u-muted" style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;">Açılış</div>
            <div style="margin-top:6px;font-size:13px;font-weight:600;">
                {{ optional($ticket->created_at)->format('d.m.Y H:i') }}
            </div>
        </div>
        @if($ticket->sla_due_at)
            <div>
                <div class="u-muted" style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;">SLA</div>
                <div style="margin-top:6px;font-size:13px;font-weight:600;color:{{ $ticket->sla_due_at->isPast() && !$ticket->closed_at ? '#dc2626' : '#0f172a' }};">
                    {{ $ticket->sla_due_at->format('d.m.Y H:i') }}
                </div>
            </div>
        @endif
    </div>
</div>

{{-- İlk mesaj --}}
<div class="card" style="margin-bottom:14px;padding:18px 22px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
        <div style="font-weight:700;font-size:14px;">İlk mesaj</div>
        <div class="u-muted" style="font-size:11px;">{{ optional($ticket->created_at)->format('d.m.Y H:i') }}</div>
    </div>
    <div style="white-space:pre-wrap;font-size:13.5px;line-height:1.55;color:#1f2937;">{{ $ticket->message }}</div>

    @if($ticket->attachment_path)
        <div style="margin-top:12px;padding:10px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;font-size:12.5px;display:inline-flex;align-items:center;gap:8px;">
            <x-icon name="paperclip" size="13" />
            <span>{{ $ticket->attachment_name ?? basename($ticket->attachment_path) }}</span>
        </div>
    @endif
</div>

{{-- Reply'lar --}}
@if($replies->count() > 0)
    <div class="card" style="margin-bottom:14px;padding:18px 22px;">
        <div style="font-weight:700;font-size:14px;margin-bottom:14px;">💬 Yanıtlar ({{ $replies->count() }})</div>
        <div style="display:flex;flex-direction:column;gap:12px;">
            @foreach($replies as $reply)
                @php
                    $isInternal = in_array($reply->author_role, ['manager', 'senior', 'system_admin', 'system'], true);
                @endphp
                <div style="background:{{ $isInternal ? '#eff6ff' : '#f8fafc' }};border:1px solid {{ $isInternal ? '#bfdbfe' : '#e2e8f0' }};border-radius:10px;padding:12px 14px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;font-size:11.5px;">
                        <strong>
                            @if($isInternal)
                                <span style="color:#1e40af;">🛡 {{ ucfirst((string) $reply->author_role) }}</span>
                            @else
                                <span style="color:#475569;">👤 {{ $reply->author_email ?? 'Aday' }}</span>
                            @endif
                        </strong>
                        <span class="u-muted">{{ optional($reply->created_at)->format('d.m.Y H:i') }}</span>
                    </div>
                    <div style="white-space:pre-wrap;font-size:13px;line-height:1.55;color:#1f2937;">{{ $reply->message }}</div>
                    @if($reply->attachment_path)
                        <div style="margin-top:8px;font-size:11.5px;display:inline-flex;align-items:center;gap:6px;color:#475569;">
                            <x-icon name="paperclip" size="12" />
                            {{ $reply->attachment_name ?? basename($reply->attachment_path) }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- D3 — doc_request tokens (varsa) --}}
@if($docModuleOn && $docTokens->count() > 0)
    <div class="card" style="margin-bottom:14px;padding:18px 22px;">
        <div style="font-weight:700;font-size:14px;margin-bottom:12px;display:flex;align-items:center;gap:8px;">
            <x-icon name="paperclip" size="16" /> Belge Talep Linkleri ({{ $docTokens->count() }})
        </div>
        <div style="display:flex;flex-direction:column;gap:8px;">
            @foreach($docTokens as $tok)
                <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 12px;background:{{ $tok->isExhausted() ? '#f0fdf4' : ($tok->isExpired() ? '#fef2f2' : '#fffbeb') }};border:1px solid {{ $tok->isExhausted() ? '#bbf7d0' : ($tok->isExpired() ? '#fecaca' : '#fde68a') }};border-radius:8px;font-size:12.5px;">
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:700;color:#0f172a;">
                            @if($tok->isMultiCategory())
                                📋 {{ $tok->category_name ?? (count($tok->category_codes ?? []).' belge') }}
                                <span style="color:#64748b;font-weight:500;font-size:11px;">— {{ $tok->used_count }}/{{ $tok->max_uses }} yüklendi</span>
                            @else
                                {{ $tok->category_name ?? $tok->category_code }}
                            @endif
                        </div>
                        <div class="u-muted" style="font-size:10.5px;">
                            {{ optional($tok->created_at)->format('d.m.Y H:i') }}
                            • Bitiş: {{ optional($tok->expires_at)->format('d.m.Y H:i') }}
                        </div>
                    </div>
                    <div style="display:flex;gap:6px;align-items:center;">
                        @if($tok->isExhausted())
                            <span style="background:#16a34a;color:#fff;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;">✓ Yüklendi</span>
                        @elseif($tok->isExpired())
                            <span style="background:#dc2626;color:#fff;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;">⏱ Süresi dolmuş</span>
                        @else
                            <span style="background:#f59e0b;color:#fff;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;">⏳ Bekliyor</span>
                            <input type="text" value="{{ url('/u/'.$tok->token) }}" readonly
                                   style="padding:5px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:11px;width:200px;"
                                   onclick="this.select()">
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- D3 modal include --}}
@if($docModuleOn)
    @include('partials.doc-request-modal', [
        'modalId'     => 'docReqModal_ticket',
        'btnId'       => 'docReqOpenBtn_ticket',
        'indexRoute'  => 'manager.ticket.document-tokens.index',
        'storeRoute'  => 'manager.ticket.document-tokens.store',
        'routeParam'  => $ticket->id,
        'targetLabel' => $targetLabel,
        'sendIntro'   => "Merhaba, MentorDE'den destek talebine ek belge talebimiz var. Lütfen aşağıdaki linke tıklayıp belgeyi yükleyin:",
    ])
@endif

@endsection
