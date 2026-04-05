@extends('senior.layouts.app')
@section('title','Randevularım')
@section('page_title','Randevularım')

@section('content')
@php
    $appts       = $appointments ?? collect();
    $totalCnt    = $appts->count();
    $scheduledCnt= $appts->whereIn('status',['scheduled','confirmed','requested'])->count();
    $completedCnt= $appts->where('status','completed')->count();
    $cancelledCnt= $appts->where('status','cancelled')->count();
    $dayLabels   = [
        'monday'    => 'Pazartesi',
        'tuesday'   => 'Salı',
        'wednesday' => 'Çarşamba',
        'thursday'  => 'Perşembe',
        'friday'    => 'Cuma',
        'saturday'  => 'Cumartesi',
        'sunday'    => 'Pazar',
    ];
    $filterQ      = $filters['q']      ?? '';
    $filterStatus = $filters['status'] ?? 'all';
@endphp

@if(session('status'))
<div style="padding:10px 16px;border-radius:8px;background:#16a34a;color:#fff;margin-bottom:14px;font-weight:600;font-size:var(--tx-sm);">✓ {{ session('status') }}</div>
@endif

{{-- Gradient Header --}}
<div style="background:linear-gradient(to right,#6d28d9,#7c3aed);border-radius:14px;padding:20px 24px;margin-bottom:16px;color:#fff;">
    <div style="font-size:var(--tx-xl);font-weight:800;letter-spacing:-.3px;margin-bottom:4px;">📅 Randevularım</div>
    <div style="font-size:var(--tx-sm);opacity:.8;margin-bottom:16px;">Öğrenci görüşmeleri ve takip randevuları</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        @foreach([
            ['label'=>'Toplam',     'count'=>$totalCnt,     'status'=>'all'],
            ['label'=>'Yaklaşan',   'count'=>$scheduledCnt, 'status'=>'scheduled'],
            ['label'=>'Tamamlanan', 'count'=>$completedCnt, 'status'=>'completed'],
            ['label'=>'İptal',      'count'=>$cancelledCnt, 'status'=>'cancelled'],
        ] as $chip)
        @php
            $active = $filterStatus === $chip['status'];
            $href   = url('/senior/appointments').'?status='.$chip['status'].($filterQ ? '&q='.urlencode($filterQ) : '');
        @endphp
        <a href="{{ $href }}" style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:999px;font-size:var(--tx-xs);font-weight:700;text-decoration:none;transition:all .15s;
            background:{{ $active ? 'rgba(255,255,255,.3)' : 'rgba(255,255,255,.12)' }};
            color:#fff;
            border:1.5px solid {{ $active ? 'rgba(255,255,255,.7)' : 'rgba(255,255,255,.2)' }};">
            {{ $chip['label'] }}
            <span style="background:rgba(255,255,255,.22);border-radius:999px;padding:1px 8px;font-size:var(--tx-xs);">{{ $chip['count'] }}</span>
        </a>
        @endforeach
    </div>
</div>

{{-- Working Hours + Settings (2 col) --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">

    {{-- Çalışma Saatleri --}}
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;overflow:hidden;">
        <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);">
            <span style="font-weight:700;font-size:var(--tx-sm);">🕐 Çalışma Saatleri</span>
        </div>
        <div style="padding:4px 0;">
            @foreach(($weeklySchedule ?? []) as $day => $row)
            @php
                $enabled = $row['enabled'] ?? false;
            @endphp
            <div style="display:grid;grid-template-columns:100px 60px 1fr;gap:8px;align-items:center;padding:8px 16px;border-bottom:1px solid var(--u-line);font-size:var(--tx-xs);">
                <div style="font-weight:700;color:var(--u-text);">{{ $dayLabels[$day] ?? $day }}</div>
                <div>
                    @if($enabled)
                        <span class="badge ok" style="font-size:var(--tx-xs);">Açık</span>
                    @else
                        <span class="badge" style="font-size:var(--tx-xs);color:var(--u-muted);">Kapalı</span>
                    @endif
                </div>
                <div style="color:var(--u-muted);">
                    @if($enabled)
                        {{ $row['start'] ?? '—' }} – {{ $row['end'] ?? '—' }}
                        @if($row['note'] ?? '')&nbsp;·&nbsp;<em>{{ $row['note'] }}</em>@endif
                    @else
                        —
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Randevu Ayarları --}}
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;overflow:hidden;">
        <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);">
            <span style="font-weight:700;font-size:var(--tx-sm);">⚙ Randevu Ayarları</span>
        </div>
        <div style="padding:16px;">
            @php
                $autoConfirm = data_get($portalPrefs ?? [], 'settings.appointment_auto_confirm', false);
                $slotMin     = (int) data_get($portalPrefs ?? [], 'settings.appointment_slot_minutes', 30);
                $bufferMin   = (int) data_get($portalPrefs ?? [], 'settings.appointment_buffer_minutes', 15);
                $apptNote    = data_get($portalPrefs ?? [], 'profile.appointment_note', '');
            @endphp
            <div style="display:grid;gap:12px;">
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:var(--u-bg);border-radius:8px;border:1px solid var(--u-line);">
                    <span style="font-size:var(--tx-sm);font-weight:600;color:var(--u-text);">Otomatik Onay</span>
                    <span class="badge {{ $autoConfirm ? 'ok' : 'warn' }}" style="font-size:var(--tx-xs);">{{ $autoConfirm ? 'Açık' : 'Kapalı' }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:var(--u-bg);border-radius:8px;border:1px solid var(--u-line);">
                    <span style="font-size:var(--tx-sm);font-weight:600;color:var(--u-text);">Slot Süresi</span>
                    <span style="font-size:var(--tx-sm);font-weight:700;color:var(--u-brand);">{{ $slotMin }} dk</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:var(--u-bg);border-radius:8px;border:1px solid var(--u-line);">
                    <span style="font-size:var(--tx-sm);font-weight:600;color:var(--u-text);">Buffer</span>
                    <span style="font-size:var(--tx-sm);font-weight:700;color:var(--u-brand);">{{ $bufferMin }} dk</span>
                </div>
                @if($apptNote)
                <div style="padding:10px 14px;background:var(--u-bg);border-radius:8px;border:1px solid var(--u-line);">
                    <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Randevu Notu</div>
                    <div style="font-size:var(--tx-sm);color:var(--u-text);">{{ $apptNote }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:14px 18px;margin-bottom:14px;">
    <form method="GET" action="{{ url('/senior/appointments') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
        <div style="flex:2;min-width:200px;">
            <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Ara (öğrenci / başlık / kanal)</div>
            <input type="text" name="q" value="{{ $filterQ }}" placeholder="Ara…"
                   style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
        </div>
        <div style="flex:1;min-width:150px;">
            <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Durum</div>
            <select name="status" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
                <option value="all"       @selected($filterStatus === 'all')>Tüm durumlar</option>
                <option value="requested" @selected($filterStatus === 'requested')>Talep Edildi</option>
                <option value="scheduled" @selected($filterStatus === 'scheduled')>Planlandı</option>
                <option value="confirmed" @selected($filterStatus === 'confirmed')>Onaylandı</option>
                <option value="completed" @selected($filterStatus === 'completed')>Tamamlandı</option>
                <option value="cancelled" @selected($filterStatus === 'cancelled')>İptal</option>
            </select>
        </div>
        <div style="display:flex;gap:6px;align-items:flex-end;">
            <button type="submit" style="background:#7c3aed;color:#fff;border:none;border-radius:7px;padding:9px 18px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;">Filtrele</button>
            <a href="{{ url('/senior/appointments') }}" style="background:var(--u-bg);color:var(--u-text);border:1px solid var(--u-line);border-radius:7px;padding:9px 14px;font-size:var(--tx-sm);font-weight:600;text-decoration:none;">Temizle</a>
        </div>
    </form>
</div>

{{-- Appointment List --}}
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;overflow:hidden;">
    <div style="padding:14px 18px;border-bottom:1px solid var(--u-line);display:flex;align-items:center;gap:8px;">
        <span style="font-weight:700;font-size:var(--tx-base);">Randevu Listesi</span>
        <span class="badge info">{{ $appts->count() }} kayıt</span>
    </div>

    @forelse($appts as $row)
    @php
        $apptBadge = match($row->status ?? '') {
            'scheduled'  => 'info',
            'confirmed'  => 'ok',
            'completed'  => 'ok',
            'cancelled'  => 'danger',
            'pending'    => 'warn',
            'requested'  => 'pending',
            default      => '',
        };
        $apptLabel = match($row->status ?? '') {
            'scheduled'  => 'Planlandı',
            'confirmed'  => 'Onaylandı',
            'completed'  => 'Tamamlandı',
            'cancelled'  => 'İptal',
            'pending'    => 'Bekliyor',
            'requested'  => 'Talep Edildi',
            default      => $row->status ?? '—',
        };
        $calLabel = match($row->calendar_provider ?? '') {
            'google_calendar' => '📅 Google Cal',
            'cal_com'         => '📅 Cal.com',
            'calendly'        => '📅 Calendly',
            default           => ($row->calendar_provider ? '📅 '.$row->calendar_provider : ''),
        };
    @endphp
    <div style="padding:14px 18px;border-bottom:1px solid var(--u-line);transition:background .12s;" onmouseover="this.style.background='var(--u-bg)'" onmouseout="this.style.background=''">

        {{-- Row 1: title + badges --}}
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:6px;">
            <div>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
                    <span style="font-weight:800;font-size:var(--tx-sm);color:var(--u-text);">{{ $row->title ?? 'Randevu' }}</span>
                    <span class="badge {{ $apptBadge }}" style="font-size:var(--tx-xs);">{{ $apptLabel }}</span>
                    @if($calLabel)
                        <span class="badge info" style="font-size:var(--tx-xs);">{{ $calLabel }}</span>
                    @endif
                </div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);display:flex;gap:12px;flex-wrap:wrap;">
                    <span>👤 {{ $row->student_id ?? '—' }}</span>
                    <span>🗓 {{ optional($row->scheduled_at)->format('d.m.Y H:i') ?: '—' }}</span>
                    <span>⏱ {{ (int)($row->duration_minutes ?? 0) }} dk</span>
                    @if($row->channel)<span>📡 {{ $row->channel }}</span>@endif
                    @if($row->meeting_url)
                        <a href="{{ $row->meeting_url }}" target="_blank" style="color:var(--u-brand);font-weight:600;text-decoration:none;">🔗 Toplantı Bağlantısı</a>
                    @endif
                </div>
                @if(!empty($row->notes))
                    <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:4px;font-style:italic;">{{ $row->notes }}</div>
                @endif
            </div>
        </div>

        {{-- Confirm form for 'requested' --}}
        @if(($row->status ?? '') === 'requested')
        <div style="margin-top:10px;">
            <button onclick="toggleSec('appt-confirm-{{ $row->id }}')" type="button"
                    style="background:#16a34a;color:#fff;border:none;border-radius:7px;padding:7px 14px;font-size:var(--tx-xs);font-weight:700;cursor:pointer;">
                ✓ Randevuyu Onayla & Takvime Ekle
            </button>
            <div id="appt-confirm-{{ $row->id }}" style="display:none;margin-top:12px;background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:14px;">
                <form method="POST" action="{{ route('senior.appointments.confirm', $row->id) }}">
                    @csrf
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:10px;">
                        <div>
                            <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Onaylanan Tarih & Saat *</div>
                            <input type="datetime-local" name="scheduled_at" required
                                   value="{{ optional($row->scheduled_at)->format('Y-m-d\TH:i') }}"
                                   style="width:100%;border:1px solid #86efac;border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:#fff;color:var(--u-text);">
                        </div>
                        <div>
                            <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Süre (dk)</div>
                            <input type="number" name="duration_minutes" min="15" max="180"
                                   value="{{ $row->duration_minutes ?? 60 }}"
                                   style="width:100%;border:1px solid #86efac;border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:#fff;color:var(--u-text);">
                        </div>
                        <div>
                            <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Toplantı URL (opsiyonel)</div>
                            <input type="url" name="meeting_url" placeholder="https://meet.google.com/..."
                                   value="{{ $row->meeting_url }}"
                                   style="width:100%;border:1px solid #86efac;border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:#fff;color:var(--u-text);">
                        </div>
                    </div>
                    <div style="display:flex;gap:6px;">
                        <button type="submit" style="background:#16a34a;color:#fff;border:none;border-radius:7px;padding:8px 18px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;">Onayla</button>
                        <button type="button" onclick="toggleSec('appt-confirm-{{ $row->id }}')" style="background:#fff;color:var(--u-text);border:1px solid #86efac;border-radius:7px;padding:8px 14px;font-size:var(--tx-sm);cursor:pointer;">İptal</button>
                    </div>
                </form>
            </div>
        </div>
        @endif

    </div>
    @empty
    <div style="padding:48px;text-align:center;color:var(--u-muted);font-size:var(--tx-sm);">Randevu bulunamadı.</div>
    @endforelse
</div>

<script>
function toggleSec(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>

@endsection
