@extends('manager.layouts.app')

@section('title', 'Ödeme Hatırlatmaları')
@section('page_title', 'Ödeme Hatırlatmaları')
@section('page_subtitle', 'Sözleşme onaylı + ödeme bekleyen adaylar · L1-L4 otomatik · L5 manuel')

@push('head')
<style>
.pr-warn-banner {
    background: rgba(217,119,6,.08); border: 1px solid rgba(217,119,6,.3);
    border-radius: 10px; padding: 12px 14px; margin-bottom: 16px;
    color: var(--u-text); font-size: 13px;
}
.pr-warn-banner strong { color: rgb(180,83,9); }

.pr-info-banner {
    background: rgba(37,99,235,.06); border: 1px solid rgba(37,99,235,.25);
    border-radius: 10px; padding: 10px 14px; margin-bottom: 16px;
    color: var(--u-muted); font-size: 12.5px;
}

.pr-table { width:100%; border-collapse: collapse; font-size: 13px; background: var(--u-card);
    border: 1px solid var(--u-line); border-radius: 10px; overflow: hidden; }
.pr-table th { padding: 10px 12px; text-align: left; font-size: 11px; font-weight: 700;
    color: var(--u-muted); text-transform: uppercase; letter-spacing:.4px;
    border-bottom: 2px solid var(--u-line); background: var(--u-bg); }
.pr-table td { padding: 12px; border-bottom: 1px solid var(--u-line); color: var(--u-text); vertical-align: top; }
.pr-table tr:last-child td { border-bottom: 0; }
.pr-name { font-weight: 700; font-size: 13.5px; }
.pr-meta { color: var(--u-muted); font-size: 11.5px; margin-top: 2px; }

.pr-badge { display: inline-block; padding: 2px 8px; border-radius: 999px;
    font-size: 11px; font-weight: 700; letter-spacing: .3px; }
.pr-badge.l0 { background: var(--u-bg); color: var(--u-muted); border:1px solid var(--u-line); }
.pr-badge.l1, .pr-badge.l2 { background: rgba(37,99,235,.1); color:#1d4ed8; }
.pr-badge.l3 { background: rgba(217,119,6,.12); color: rgb(180,83,9); }
.pr-badge.l4 { background: rgba(217,119,6,.2); color: rgb(154,52,18); }
.pr-badge.l5 { background: rgba(220,38,38,.12); color: rgb(185,28,28); }
.pr-badge.paused { background: rgba(100,116,139,.15); color: #475569; }
.pr-badge.active { background: rgba(22,163,74,.12); color: #15803d; }
.pr-badge.paid   { background: rgba(22,163,74,.18); color: #14532d; }

.pr-actions { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
.pr-actions form { display: inline; margin: 0; }
.pr-btn {
    display: inline-block; padding: 5px 10px; font-size: 11.5px; font-weight: 600;
    border-radius: 6px; border: 1px solid var(--u-line); background: var(--u-bg);
    color: var(--u-text); cursor: pointer; text-decoration: none;
}
.pr-btn:hover { background: var(--u-card); border-color: var(--u-brand); }
.pr-btn.primary { background: var(--u-brand, #2563eb); color: white; border-color: var(--u-brand); }
.pr-btn.primary:hover { filter: brightness(.92); background: var(--u-brand); color: white; }
.pr-btn.warn { background: rgba(217,119,6,.1); color: rgb(180,83,9); border-color: rgba(217,119,6,.3); }
.pr-btn.warn:hover { background: rgba(217,119,6,.18); }
.pr-btn.danger { background: rgba(220,38,38,.08); color: rgb(185,28,28); border-color: rgba(220,38,38,.3); }
.pr-btn.danger:hover { background: rgba(220,38,38,.15); }
.pr-btn.success { background: rgba(22,163,74,.08); color: #15803d; border-color: rgba(22,163,74,.3); }
.pr-btn.success:hover { background: rgba(22,163,74,.15); }

.pr-empty { padding: 40px 20px; text-align: center; color: var(--u-muted); }
.pr-amt { font-weight: 700; }
</style>
@endpush

@section('content')
<div class="container-fluid">

    @if(session('success'))
        <div class="pr-info-banner" style="border-color:rgba(22,163,74,.3); background:rgba(22,163,74,.08); color:#15803d;">
            ✅ {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="pr-info-banner" style="border-color:rgba(220,38,38,.3); background:rgba(220,38,38,.08); color:rgb(185,28,28);">
            @foreach($errors->all() as $err)
                ⚠ {{ $err }}<br>
            @endforeach
        </div>
    @endif

    @if(empty($bankInfo['iban'] ?? ''))
        <div class="pr-warn-banner">
            <strong>⚠ Banka bilgisi (IBAN) yapılandırılmamış.</strong>
            Hatırlatma gönderilemez. Lütfen <code>.env</code>'de <code>BRAND_BANK_IBAN</code> ve diğer <code>BRAND_BANK_*</code> değerlerini ayarlayın.
        </div>
    @endif

    <div class="pr-info-banner">
        <strong>Akış:</strong>
        Cron her gün 09:15'te L1-L4'ü otomatik gönderir
        (varsayılan eşikler: {{ $reminderDays[1] ?? 7 }}/{{ $reminderDays[2] ?? 14 }}/{{ $reminderDays[3] ?? 21 }}/{{ $reminderDays[4] ?? 28 }} gün).
        L5 (iptal uyarısı) sadece manuel — bu mailden sonra <strong>{{ $finalGraceDays }} gün</strong> ek süre tanınır,
        ödeme yapılmazsa sözleşme iptal + kısmi ödemeler servis bedeli olarak alıkonur.
    </div>

    @if($rows->isEmpty())
        <div class="pr-empty">
            🎉 Şu an ödeme bekleyen aday yok.
        </div>
    @else
        <div style="overflow-x:auto;">
            <table class="pr-table">
                <thead>
                    <tr>
                        <th style="width:24%">Aday</th>
                        <th>Sözleşme onayı</th>
                        <th>Son hatırlatma</th>
                        <th>Durum</th>
                        <th style="min-width:340px;">Aksiyonlar</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($rows as $g)
                    @php
                        $approvedAt = $g->contract_approved_at;
                        $daysSince = $approvedAt ? (int) $approvedAt->diffInDays(now()) : 0;
                        $level = (int) ($g->payment_reminder_level ?? 0);
                        $paused = !empty($g->payment_reminders_paused_at);
                        $studentRef = trim((string) ($g->converted_student_id ?? '')) !== ''
                            ? (string) $g->converted_student_id
                            : 'GST-' . str_pad((string) $g->id, 8, '0', STR_PAD_LEFT);
                        $fullName = trim(((string) ($g->first_name ?? '')).' '.((string) ($g->last_name ?? '')));
                    @endphp
                    <tr>
                        <td>
                            <div class="pr-name">{{ $fullName !== '' ? $fullName : '—' }}</div>
                            <div class="pr-meta">{{ $g->email ?? '' }}</div>
                            <div class="pr-meta">#{{ $studentRef }}</div>
                        </td>
                        <td>
                            <div>{{ $approvedAt?->format('d.m.Y') ?? '—' }}</div>
                            <div class="pr-meta">{{ $daysSince }} gün önce</div>
                        </td>
                        <td>
                            <span class="pr-badge l{{ $level }}">L{{ $level }}</span>
                            @if($g->payment_reminder_last_sent_at)
                                <div class="pr-meta" style="margin-top:4px;">
                                    {{ $g->payment_reminder_last_sent_at->format('d.m.Y H:i') }}
                                </div>
                            @else
                                <div class="pr-meta" style="margin-top:4px;">—</div>
                            @endif
                        </td>
                        <td>
                            @if($paused)
                                <span class="pr-badge paused">⏸ Duraklatıldı</span>
                                @if(!empty($g->payment_reminders_paused_reason))
                                    <div class="pr-meta" style="margin-top:4px;">{{ $g->payment_reminders_paused_reason }}</div>
                                @endif
                            @else
                                <span class="pr-badge active">⏱ Aktif</span>
                            @endif
                        </td>
                        <td>
                            <div class="pr-actions">
                                {{-- L1-L4 manuel "şimdi gönder" --}}
                                @for($lvl = 1; $lvl <= 4; $lvl++)
                                    <form method="POST" action="{{ route('manager.payments.reminders.send', $g) }}"
                                          onsubmit="return confirm('L{{ $lvl }} hatırlatması {{ $g->email }} adresine gönderilsin mi?');">
                                        @csrf
                                        <input type="hidden" name="level" value="{{ $lvl }}">
                                        <button class="pr-btn" type="submit" {{ empty($bankInfo['iban'] ?? '') ? 'disabled' : '' }}>
                                            L{{ $lvl }} gönder
                                        </button>
                                    </form>
                                @endfor

                                {{-- L5 — iptal uyarısı (özel teyit) --}}
                                <form method="POST" action="{{ route('manager.payments.reminders.send', $g) }}"
                                      onsubmit="return confirm('⚠ DİKKAT: L5 (Son Bildirim) iptal uyarısıdır.\n\nBu mail {{ $finalGraceDays }} gün ek süre tanır; ödeme yapılmazsa sözleşme iptal edilir ve kısmi ödemeler servis bedeli olarak alıkonur.\n\nGöndermek istediğinden emin misin?');">
                                    @csrf
                                    <input type="hidden" name="level" value="5">
                                    <button class="pr-btn danger" type="submit" {{ empty($bankInfo['iban'] ?? '') ? 'disabled' : '' }}>
                                        ⚠ L5 İptal uyarısı
                                    </button>
                                </form>

                                {{-- Pause / Resume --}}
                                @if($paused)
                                    <form method="POST" action="{{ route('manager.payments.reminders.resume', $g) }}">
                                        @csrf
                                        <button class="pr-btn" type="submit">▶ Devam ettir</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('manager.payments.reminders.pause', $g) }}"
                                          onsubmit="var r=prompt('Duraklatma sebebi (banka sorunu, mücbir sebep vb.):'); if(!r){ return false; } this.querySelector('input[name=reason]').value=r; return true;">
                                        @csrf
                                        <input type="hidden" name="reason" value="">
                                        <button class="pr-btn warn" type="submit">⏸ Duraklat</button>
                                    </form>
                                @endif

                                {{-- Manuel ödeme alındı --}}
                                <form method="POST" action="{{ route('manager.payments.reminders.mark-received', $g) }}"
                                      onsubmit="var n=prompt('İsteğe bağlı not (banka, transfer ref vb.) — boş bırakılabilir:'); this.querySelector('input[name=notes]').value=(n||''); return confirm('Bu adayın ödemesi alındı olarak işaretlensin mi?\n\nAdaya «Ödeme ulaştı, sürecinize başladık» bildirimi maili gönderilecek.');">
                                    @csrf
                                    <input type="hidden" name="notes" value="">
                                    <button class="pr-btn success" type="submit">✓ Ödeme alındı</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
