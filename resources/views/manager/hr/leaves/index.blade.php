@extends('manager.layouts.app')

@section('title', 'İzin Yönetimi')
@section('page_title', 'İzin Yönetimi')

@section('content')

@if(session('status'))
<div style="margin-bottom:12px;padding:10px 16px;border-radius:8px;background:#dcfce7;color:#166534;font-weight:600;font-size:13px;border:1px solid #bbf7d0;">{{ session('status') }}</div>
@endif

{{-- ─── BÖLÜM 1: Onay Bekleyen Talepler ──────────────────────────────────── --}}
<div style="margin-bottom:6px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
    <div style="font-size:15px;font-weight:700;color:var(--u-text);">
        ⏳ Onay Bekleyen Talepler
        @if($pendingCount > 0)
        <span style="background:#dc2626;color:#fff;font-size:11px;font-weight:700;border-radius:999px;padding:2px 8px;margin-left:6px;">{{ $pendingCount }}</span>
        @endif
    </div>
    <div style="display:flex;gap:6px;flex-wrap:wrap;">
        <form method="GET" style="display:flex;gap:4px;align-items:center;">
            <select name="type" style="padding:5px 8px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
                <option value="">Tüm Türler</option>
                @foreach(\App\Models\Hr\HrLeaveRequest::$typeLabels as $v => $l)
                <option value="{{ $v }}" {{ $typeFilter===$v ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
            <select name="user_id" style="padding:5px 8px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
                <option value="">Tüm Çalışanlar</option>
                @foreach($employees as $emp)
                <option value="{{ $emp->id }}" {{ $userFilter==(string)$emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn alt" style="font-size:11px;padding:5px 12px;">Filtrele</button>
        </form>
    </div>
</div>

<section class="panel" style="padding:0;overflow:hidden;margin-bottom:24px;">
    @if($pendingLeaves->isEmpty())
    <div style="padding:32px;text-align:center;color:var(--u-muted);font-size:13px;">✅ Onay bekleyen izin talebi yok.</div>
    @else
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:12px;">
            <thead><tr style="background:var(--u-bg);">
                <th style="padding:8px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Çalışan</th>
                <th style="padding:8px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Tür</th>
                <th style="padding:8px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Tarih Aralığı</th>
                <th style="padding:8px 12px;text-align:center;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Gün</th>
                <th style="padding:8px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Ekler</th>
                <th style="padding:8px 12px;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">İşlem</th>
            </tr></thead>
            <tbody>
            @foreach($pendingLeaves as $lv)
            <tr style="border-bottom:1px solid var(--u-line);">
                <td style="padding:8px 12px;">
                    <a href="/manager/hr/persons/{{ $lv->user_id }}?tab=leaves" style="font-weight:600;color:var(--u-text);text-decoration:none;">{{ $lv->user?->name ?: '—' }}</a>
                    @if($lv->reason)
                    <div style="font-size:11px;color:var(--u-muted);margin-top:2px;">{{ Str::limit($lv->reason, 50) }}</div>
                    @endif
                </td>
                <td style="padding:8px 12px;">{{ \App\Models\Hr\HrLeaveRequest::$typeLabels[$lv->leave_type] ?? $lv->leave_type }}</td>
                <td style="padding:8px 12px;white-space:nowrap;">{{ $lv->start_date->format('d.m.Y') }} – {{ $lv->end_date->format('d.m.Y') }}</td>
                <td style="padding:8px 12px;text-align:center;font-weight:700;">{{ $lv->days_count }}</td>
                <td style="padding:8px 12px;max-width:160px;">
                    @if($lv->attachments->isNotEmpty())
                    <div style="display:flex;flex-wrap:wrap;gap:4px;">
                        @foreach($lv->attachments as $att)
                            @if($att->type === 'file')
                            <a href="{{ route('hr.my.leave-attachment.download', $att) }}"
                               style="display:inline-flex;align-items:center;gap:3px;background:#f5f3ff;border:1px solid #c4b5fd;border-radius:5px;padding:2px 8px;font-size:10px;color:#7c3aed;text-decoration:none;font-weight:600;max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                📎 {{ $att->original_name }}
                            </a>
                            @else
                            <a href="{{ $att->url }}" target="_blank" rel="noopener"
                               style="display:inline-flex;align-items:center;gap:3px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:5px;padding:2px 8px;font-size:10px;color:#1d4ed8;text-decoration:none;font-weight:600;">
                                🔗 {{ parse_url($att->url, PHP_URL_HOST) ?: 'Link' }}
                            </a>
                            @endif
                        @endforeach
                    </div>
                    @else
                    <span style="font-size:11px;color:var(--u-muted);">—</span>
                    @endif
                </td>
                <td style="padding:8px 12px;">
                    <div style="display:flex;gap:4px;">
                        <form method="POST" action="/manager/hr/leaves/{{ $lv->id }}/approve">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn ok" style="font-size:10px;padding:3px 10px;" title="Onayla">✓ Onayla</button>
                        </form>
                        <button type="button" onclick="showReject({{ $lv->id }})" class="btn warn" style="font-size:10px;padding:3px 10px;" title="Reddet">✗ Reddet</button>
                    </div>
                </td>
            </tr>
            <tr id="reject-row-{{ $lv->id }}" style="display:none;background:#fef2f2;">
                <td colspan="6" style="padding:8px 12px;">
                    <form method="POST" action="/manager/hr/leaves/{{ $lv->id }}/reject" style="display:flex;gap:6px;align-items:center;">
                        @csrf @method('PATCH')
                        <input type="text" name="rejection_note" placeholder="Red gerekçesi (opsiyonel)"
                               style="flex:1;padding:5px 10px;border:1.5px solid #fca5a5;border-radius:7px;font-size:12px;background:#fff;color:var(--u-text);">
                        <button type="submit" class="btn warn" style="font-size:11px;padding:5px 14px;">Reddet</button>
                        <button type="button" onclick="document.getElementById('reject-row-{{ $lv->id }}').style.display='none'" class="btn" style="font-size:11px;padding:5px 14px;">İptal</button>
                    </form>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
</section>

{{-- ─── BÖLÜM 2: Ekip İzin Takvimi ───────────────────────────────────────── --}}
<div style="font-size:15px;font-weight:700;color:var(--u-text);margin-bottom:8px;">
    📅 Ekip İzin Takvimi
    <span style="font-size:12px;font-weight:400;color:var(--u-muted);margin-left:8px;">Onaylanmış — bugün ve sonrası</span>
</div>

<section class="panel" style="padding:0;overflow:hidden;margin-bottom:24px;">
    @if($upcomingLeaves->isEmpty())
    <div style="padding:32px;text-align:center;color:var(--u-muted);font-size:13px;">Yaklaşan onaylı izin bulunmuyor.</div>
    @else
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:12px;">
            <thead><tr style="background:var(--u-bg);">
                <th style="padding:8px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Çalışan</th>
                <th style="padding:8px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">İzin Türü</th>
                <th style="padding:8px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Başlangıç</th>
                <th style="padding:8px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Bitiş</th>
                <th style="padding:8px 12px;text-align:center;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Gün</th>
                <th style="padding:8px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Vekil</th>
            </tr></thead>
            <tbody>
            @foreach($upcomingLeaves as $lv)
            @php
                $isToday  = $lv->start_date->isToday() || ($lv->start_date->isPast() && $lv->end_date->isFuture());
                $rowBg    = $isToday ? 'background:#fffbeb;' : '';
            @endphp
            <tr style="border-bottom:1px solid var(--u-line);{{ $rowBg }}">
                <td style="padding:8px 12px;">
                    <div style="font-weight:600;color:var(--u-text);">{{ $lv->user?->name ?: '—' }}</div>
                    <div style="font-size:11px;color:var(--u-muted);text-transform:capitalize;">{{ $lv->user?->role ?? '' }}</div>
                </td>
                <td style="padding:8px 12px;">{{ \App\Models\Hr\HrLeaveRequest::$typeLabels[$lv->leave_type] ?? $lv->leave_type }}</td>
                <td style="padding:8px 12px;font-weight:{{ $isToday ? '700' : '400' }};color:{{ $isToday ? '#d97706' : 'var(--u-text)' }};">
                    {{ $lv->start_date->format('d.m.Y') }}
                    @if($isToday)<span style="font-size:10px;background:#fef3c7;color:#d97706;border-radius:4px;padding:1px 5px;margin-left:4px;">Bugün</span>@endif
                </td>
                <td style="padding:8px 12px;">{{ $lv->end_date->format('d.m.Y') }}</td>
                <td style="padding:8px 12px;text-align:center;font-weight:700;">{{ $lv->days_count }}</td>
                <td style="padding:8px 12px;">
                    @if($lv->deputy)
                        <span style="display:inline-flex;align-items:center;gap:4px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;padding:2px 8px;font-size:11px;color:#166534;font-weight:600;">
                            👤 {{ $lv->deputy->name }}
                        </span>
                    @else
                        <span style="font-size:11px;color:var(--u-muted);">—</span>
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
</section>

{{-- ─── BÖLÜM 3: Manager Yokluk Planla ───────────────────────────────────── --}}
<div style="font-size:15px;font-weight:700;color:var(--u-text);margin-bottom:8px;">🗓 Kendi Yokluğumu Planla</div>
<section class="panel" style="padding:16px 20px;margin-bottom:24px;">
    <p style="font-size:13px;color:var(--u-muted);margin:0 0 14px;">
        Yönetici olarak kendi yokluğunuzu doğrudan takvime ekleyebilirsiniz. Vekil atadığınızda ekip bu bilgiye erişebilir.
    </p>
    <form method="POST" action="/manager/hr/leaves/own" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
        @csrf
        <div>
            <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">İzin Türü</label>
            <select name="leave_type" required style="padding:6px 10px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
                @foreach(\App\Models\Hr\HrLeaveRequest::$typeLabels as $v => $l)
                <option value="{{ $v }}">{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Başlangıç</label>
            <input type="date" name="start_date" required style="padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
        </div>
        <div>
            <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Bitiş</label>
            <input type="date" name="end_date" required style="padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
        </div>
        <div>
            <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Vekil Personel</label>
            <select name="deputy_user_id" style="padding:6px 10px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);min-width:180px;">
                <option value="">— Vekil seç (opsiyonel) —</option>
                @foreach($employees as $emp)
                <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Not</label>
            <input type="text" name="reason" placeholder="Opsiyonel açıklama" style="padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);width:200px;">
        </div>
        <button type="submit" class="btn ok" style="padding:7px 20px;font-size:12px;">Takvime Ekle</button>
    </form>
</section>

{{-- ─── BÖLÜM 4: Manager tarafından çalışan adına kayıt ───────────────────── --}}
<details>
    <summary style="font-size:13px;font-weight:600;color:var(--u-muted);cursor:pointer;padding:8px 0;list-style:none;display:flex;align-items:center;gap:6px;">
        <span>▶</span> Çalışan Adına İzin Kaydı Ekle
    </summary>
    <section class="panel" style="padding:14px 18px;margin-top:8px;">
        <form method="POST" action="/manager/hr/leaves" style="display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;">
            @csrf
            <div>
                <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Çalışan</label>
                <select name="user_id" required style="padding:6px 10px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);min-width:180px;">
                    <option value="">Seç…</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">İzin Türü</label>
                <select name="leave_type" required style="padding:6px 10px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
                    @foreach(\App\Models\Hr\HrLeaveRequest::$typeLabels as $v => $l)
                    <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Başlangıç</label>
                <input type="date" name="start_date" required style="padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
            </div>
            <div>
                <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Bitiş</label>
                <input type="date" name="end_date" required style="padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
            </div>
            <div>
                <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Durum</label>
                <select name="status" style="padding:6px 10px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
                    <option value="approved">Onaylı</option>
                    <option value="pending">Bekliyor</option>
                </select>
            </div>
            <div>
                <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Not</label>
                <input type="text" name="reason" style="padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);width:200px;">
            </div>
            <button type="submit" class="btn ok" style="padding:7px 18px;font-size:12px;">Kaydet</button>
        </form>
    </section>
</details>

@endsection

@push('scripts')
<script>
function showReject(id) {
    var row = document.getElementById('reject-row-' + id);
    if (row) row.style.display = (row.style.display === 'none' ? '' : 'none');
}
</script>
@endpush
