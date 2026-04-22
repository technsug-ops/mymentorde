@extends('senior.layouts.app')
@section('title','Randevularım')
@section('page_title','Randevularım')

@section('content')
@php
    $appts       = $appointments ?? collect();
    $totalCnt    = $appts->count();
    $scheduledCnt= $appts->whereIn('status',['scheduled','confirmed','requested','pending'])->count();
    $completedCnt= $appts->whereIn('status',['done','completed'])->count();
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

{{-- Gradient Header — kompakt --}}
<div style="background:linear-gradient(to right,#6d28d9,#7c3aed);border-radius:14px;padding:14px 16px;margin-bottom:14px;color:#fff;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
        <div style="font-size:16px;font-weight:800;">📅 Randevularım</div>
    </div>
    <div style="display:flex;gap:6px;">
        @foreach([
            ['label'=>'Toplam',     'count'=>$totalCnt,     'status'=>'all'],
            ['label'=>'Yaklaşan',   'count'=>$scheduledCnt, 'status'=>'scheduled'],
            ['label'=>'Tamamlanan', 'count'=>$completedCnt, 'status'=>'done'],
            ['label'=>'İptal',      'count'=>$cancelledCnt, 'status'=>'cancelled'],
        ] as $chip)
        @php
            $active = $filterStatus === $chip['status'];
            $href   = url('/senior/appointments').'?status='.$chip['status'].($filterQ ? '&q='.urlencode($filterQ) : '');
        @endphp
        <a href="{{ $href }}" style="flex:1;text-align:center;padding:6px 4px;border-radius:8px;font-size:10px;font-weight:700;text-decoration:none;
            background:{{ $active ? 'rgba(255,255,255,.25)' : 'rgba(255,255,255,.1)' }};
            color:#fff;border:1px solid {{ $active ? 'rgba(255,255,255,.6)' : 'transparent' }};">
            <div style="font-size:18px;font-weight:800;line-height:1;">{{ $chip['count'] }}</div>
            {{ $chip['label'] }}
        </a>
        @endforeach
    </div>
</div>

{{-- Tab Navigation (booking modülü açıkken) --}}
@if($bookingModuleEnabled ?? false)
    @php
        $tabBase = url('/senior/appointments');
        $tabs = [
            ['key'=>'appointments', 'label'=>'📅 Randevular',   'badge'=>$totalCnt],
            ['key'=>'availability', 'label'=>'🗓️ Müsaitlik',    'badge'=>null],
            ['key'=>'settings',     'label'=>'⚙️ Ayarlar',      'badge'=>null],
        ];
    @endphp
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:4px;margin-bottom:14px;display:flex;gap:3px;overflow-x:auto;">
        @foreach($tabs as $t)
            @php $isActive = ($activeTab ?? 'appointments') === $t['key']; @endphp
            <a href="{{ $tabBase }}?tab={{ $t['key'] }}"
               style="flex:1;min-width:120px;text-align:center;padding:9px 12px;border-radius:8px;font-size:var(--tx-sm);font-weight:700;text-decoration:none;white-space:nowrap;
                      background:{{ $isActive ? '#7c3aed' : 'transparent' }};
                      color:{{ $isActive ? '#fff' : 'var(--u-text)' }};
                      transition:all .15s;">
                {{ $t['label'] }}
                @if(!is_null($t['badge']))
                    <span style="margin-left:4px;font-size:11px;opacity:.8;">({{ $t['badge'] }})</span>
                @endif
            </a>
        @endforeach
    </div>
@endif

{{-- ══════════════════ TAB 1: RANDEVULAR (default) ══════════════════ --}}
@if(($activeTab ?? 'appointments') === 'appointments')

{{-- Not: Eski "Çalışma Saatleri" + "Randevu Ayarları" özet kartları kaldırıldı.
     Aynı bilgi artık Müsaitlik + Ayarlar tab'larından yönetiliyor. --}}

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
                <option value="done"      @selected($filterStatus === 'done')>Tamamlandı</option>
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
            'completed', 'done' => 'ok',
            'cancelled'  => 'danger',
            'pending'    => 'warn',
            'requested'  => 'pending',
            default      => '',
        };
        $apptLabel = match($row->status ?? '') {
            'scheduled'  => 'Planlandı',
            'confirmed'  => 'Onaylandı',
            'completed', 'done' => 'Tamamlandı',
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
                    @if($row->publicBooking)
                        <span class="badge" style="font-size:var(--tx-xs);background:#dbeafe;color:#1e40af;border:1px solid #93c5fd;">🌐 Public Booking</span>
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
                @if($row->publicBooking)
                    @php $pb = $row->publicBooking; @endphp
                    <div style="margin-top:6px;padding:8px 10px;background:#eff6ff;border-left:3px solid #3b82f6;border-radius:4px;font-size:var(--tx-xs);color:#1e3a8a;">
                        <div><strong>Invitee:</strong> {{ $pb->invitee_name }} · <a href="mailto:{{ $pb->invitee_email }}" style="color:#1e40af;">{{ $pb->invitee_email }}</a>
                            @if($pb->invitee_phone) · 📞 {{ $pb->invitee_phone }}@endif
                        </div>
                        @if($pb->notes)
                            <div style="margin-top:3px;"><strong>Not:</strong> {{ $pb->notes }}</div>
                        @endif
                    </div>
                @endif
                @if(!empty($row->notes))
                    <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:4px;font-style:italic;">{{ $row->notes }}</div>
                @endif
            </div>
        </div>

        {{-- Edit + Cancel actions for scheduled/confirmed --}}
        @if(in_array($row->status ?? '', ['scheduled', 'confirmed', 'pending'], true))
        <div style="margin-top:10px;display:flex;gap:6px;flex-wrap:wrap;">
            <button onclick="toggleSec('appt-edit-{{ $row->id }}')" type="button"
                    style="background:#6d28d9;color:#fff;border:none;border-radius:7px;padding:7px 14px;font-size:var(--tx-xs);font-weight:700;cursor:pointer;">
                ✏️ Düzenle
            </button>
            <button onclick="toggleSec('appt-cancel-{{ $row->id }}')" type="button"
                    style="background:#fee2e2;color:#b91c1c;border:1px solid #fecaca;border-radius:7px;padding:7px 14px;font-size:var(--tx-xs);font-weight:700;cursor:pointer;">
                ✕ İptal Et
            </button>
            @if($row->google_event_id)
                <span style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;border-radius:14px;background:rgba(22,163,74,.1);color:#16a34a;font-size:10.5px;font-weight:700;">
                    📅 Google'da senkronize
                </span>
            @endif
        </div>

        {{-- Edit form --}}
        <div id="appt-edit-{{ $row->id }}" style="display:none;margin-top:12px;background:#faf5ff;border:1px solid #d8b4fe;border-radius:8px;padding:14px;">
            <form method="POST" action="{{ route('senior.appointments.update', $row->id) }}">
                @csrf
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                    <div style="grid-column:1/-1;">
                        <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Başlık *</div>
                        <input type="text" name="title" required value="{{ $row->title }}" maxlength="190"
                               style="width:100%;border:1px solid #d8b4fe;border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:#fff;color:var(--u-text);">
                    </div>
                    <div>
                        <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Tarih & Saat *</div>
                        <input type="datetime-local" name="scheduled_at" required
                               data-collision-check="{{ $row->id }}"
                               value="{{ optional($row->scheduled_at)->format('Y-m-d\TH:i') }}"
                               style="width:100%;border:1px solid #d8b4fe;border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:#fff;color:var(--u-text);">
                    </div>
                    <div>
                        <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Süre (dk)</div>
                        <input type="number" name="duration_minutes" min="15" max="180"
                               data-collision-check="{{ $row->id }}"
                               value="{{ $row->duration_minutes ?? 60 }}"
                               style="width:100%;border:1px solid #d8b4fe;border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:#fff;color:var(--u-text);">
                    </div>
                    <div id="collision-warn-{{ $row->id }}" style="grid-column:1/-1;display:none;background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;padding:10px 12px;font-size:var(--tx-xs);color:#92400e;">
                        <div style="font-weight:700;margin-bottom:4px;">⚠️ Çakışma Uyarısı</div>
                        <div id="collision-detail-{{ $row->id }}"></div>
                        <label style="display:flex;align-items:center;gap:6px;margin-top:8px;cursor:pointer;">
                            <input type="checkbox" name="override_collision" value="1">
                            <span>Bilinçli olarak üst üste bindir (önerilmez)</span>
                        </label>
                    </div>
                    <div>
                        <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Kanal</div>
                        <select name="channel"
                                style="width:100%;border:1px solid #d8b4fe;border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:#fff;color:var(--u-text);">
                            <option value="online"    @selected(($row->channel ?? '')==='online')>Online (Google Meet)</option>
                            <option value="phone"     @selected(($row->channel ?? '')==='phone')>Telefon</option>
                            <option value="in_person" @selected(($row->channel ?? '')==='in_person')>Ofiste</option>
                        </select>
                    </div>
                    <div>
                        <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Toplantı URL (opsiyonel)</div>
                        <input type="url" name="meeting_url" value="{{ $row->meeting_url }}" placeholder="https://meet.google.com/..."
                               style="width:100%;border:1px solid #d8b4fe;border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:#fff;color:var(--u-text);">
                    </div>
                    <div style="grid-column:1/-1;">
                        <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Not</div>
                        <textarea name="note" rows="2" maxlength="1000"
                                  style="width:100%;border:1px solid #d8b4fe;border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:#fff;color:var(--u-text);resize:vertical;">{{ $row->note }}</textarea>
                    </div>
                </div>
                <div style="display:flex;gap:6px;">
                    <button type="submit" style="background:#6d28d9;color:#fff;border:none;border-radius:7px;padding:8px 18px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;">💾 Kaydet</button>
                    <button type="button" onclick="toggleSec('appt-edit-{{ $row->id }}')" style="background:#fff;color:var(--u-text);border:1px solid #d8b4fe;border-radius:7px;padding:8px 14px;font-size:var(--tx-sm);cursor:pointer;">Vazgeç</button>
                </div>
                <div style="margin-top:8px;font-size:var(--tx-xs);color:var(--u-muted);">
                    💡 Değişiklikler Google Takvim'e otomatik yansır.
                </div>
            </form>
        </div>

        {{-- Cancel form --}}
        <div id="appt-cancel-{{ $row->id }}" style="display:none;margin-top:12px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:14px;">
            <form method="POST" action="{{ route('senior.appointments.cancel', $row->id) }}" onsubmit="return apptCancelValidate({{ $row->id }});">
                @csrf
                <div style="font-size:var(--tx-sm);color:#991b1b;font-weight:600;margin-bottom:8px;">Randevuyu İptal Et</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:8px;">Analiz için iptal nedenini seç — sonra bu verilerle hangi sebepler en sık iptali tetikliyor görebileceğiz.</div>
                <select name="cancel_category" id="cancel-cat-{{ $row->id }}" required onchange="apptCancelToggleOther({{ $row->id }})"
                        style="width:100%;border:1px solid #fecaca;border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:#fff;color:var(--u-text);margin-bottom:8px;">
                    <option value="">— Neden seç —</option>
                    <option value="student_no_show">Öğrenci gelmedi / cevap vermedi</option>
                    <option value="student_request">Öğrenci iptal istedi</option>
                    <option value="reschedule">Yeni tarihe ertelendi</option>
                    <option value="senior_unavailable">Danışman müsait değil</option>
                    <option value="duplicate">Yanlışlıkla açıldı / mükerrer</option>
                    <option value="not_needed">Artık gerek kalmadı</option>
                    <option value="technical">Teknik sorun (görüntülü bağlantı vb.)</option>
                    <option value="other">Diğer</option>
                </select>
                <textarea name="cancel_reason" id="cancel-reason-{{ $row->id }}" rows="2" maxlength="500"
                          placeholder="Açıklama (Diğer seçersen zorunlu, diğerlerinde opsiyonel)"
                          style="width:100%;border:1px solid #fecaca;border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:#fff;color:var(--u-text);margin-bottom:10px;resize:vertical;"></textarea>
                <div style="display:flex;gap:6px;">
                    <button type="submit" style="background:#dc2626;color:#fff;border:none;border-radius:7px;padding:8px 18px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;">✕ İptal Et</button>
                    <button type="button" onclick="toggleSec('appt-cancel-{{ $row->id }}')" style="background:#fff;color:var(--u-text);border:1px solid #fecaca;border-radius:7px;padding:8px 14px;font-size:var(--tx-sm);cursor:pointer;">Vazgeç</button>
                </div>
            </form>
        </div>
        @endif

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

@endif
{{-- ══════════════════ /TAB 1 ══════════════════ --}}


{{-- ══════════════════ TAB 2: MÜSAİTLİK (haftalık + istisnalar) ══════════════════ --}}
@if(($activeTab ?? '') === 'availability' && ($bookingModuleEnabled ?? false))

<style>
.bk-card { background:var(--u-card); border:1px solid var(--u-line); border-radius:10px; padding:18px; margin-bottom:14px; }
.bk-card h3 { margin:0 0 4px; font-size:15px; color:var(--u-text); }
.bk-card .hint { margin:0 0 14px; font-size:12px; color:var(--u-muted); line-height:1.6; }
.bk-row { display:flex; gap:10px; align-items:center; padding:10px 12px; border-bottom:1px solid var(--u-line); font-size:13px; }
.bk-row:last-child { border-bottom:0; }
.bk-row .day { font-weight:700; color:var(--u-text); min-width:110px; }
.bk-row .time { color:var(--u-muted); font-family:monospace; }
.bk-inline { display:flex; gap:8px; align-items:flex-end; flex-wrap:wrap; }
.bk-inline > * { flex-shrink:0; }
.bk-field label { display:block; font-size:11px; font-weight:600; color:var(--u-muted); margin-bottom:3px; }
.bk-field input, .bk-field select {
    padding:7px 10px; border:1px solid var(--u-line); border-radius:7px;
    font-size:13px; background:var(--u-bg); color:var(--u-text); box-sizing:border-box;
}
.bk-btn { padding:8px 16px; border:none; border-radius:7px; font-size:12px; font-weight:700; cursor:pointer; }
.bk-btn-primary { background:#7c3aed; color:#fff; }
.bk-btn-danger { background:#dc2626; color:#fff; }
.bk-badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:700; }
.bk-badge.green { background:#dcfce7; color:#166534; }
.bk-badge.red { background:#fee2e2; color:#991b1b; }
.bk-public-url { background:#eef2ff; border:1px solid #c7d2fe; padding:10px 14px; border-radius:8px; font-family:monospace; font-size:12px; color:#3730a3; display:flex; justify-content:space-between; align-items:center; gap:10px; word-break:break-all; margin-bottom:14px; }
</style>

{{-- Public URL (varsa) --}}
@if($bookingPublicUrl)
    <div class="bk-public-url">
        <span>🔗 {{ $bookingPublicUrl }}</span>
        <button type="button" class="bk-btn" data-copy-url="{{ $bookingPublicUrl }}" style="background:#eef2ff;color:#3730a3;border:1px solid #c7d2fe;padding:5px 10px;font-size:11px;">📋 Kopyala</button>
    </div>
@endif

{{-- ══════ AYLIK TAKVIM ══════ --}}
<div class="bk-card">
    <style>
    .bk-cal-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; gap:10px; flex-wrap:wrap; }
    .bk-cal-title { font-size:18px; font-weight:700; color:var(--u-text); text-transform:capitalize; }
    .bk-cal-nav { display:flex; gap:6px; }
    .bk-cal-nav a { padding:6px 12px; background:var(--u-bg); border:1px solid var(--u-line); border-radius:7px; font-size:12px; font-weight:700; color:var(--u-text); text-decoration:none; transition:all .15s; }
    .bk-cal-nav a:hover { border-color:#7c3aed; color:#7c3aed; }
    .bk-cal-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:4px; }
    .bk-cal-wd { text-align:center; font-size:11px; font-weight:700; color:var(--u-muted); text-transform:uppercase; letter-spacing:.04em; padding:6px 0; }
    .bk-cal-day {
        position:relative; background:var(--u-bg); border:1px solid var(--u-line);
        border-radius:8px; padding:6px 4px; min-height:60px;
        cursor:pointer; transition:all .12s;
        display:flex; flex-direction:column; align-items:center; justify-content:flex-start; gap:3px;
    }
    .bk-cal-day:hover { border-color:#7c3aed; transform:translateY(-1px); }
    .bk-cal-day .d-num { font-size:13px; font-weight:700; color:var(--u-text); }
    .bk-cal-day.out-of-month { opacity:.35; }
    .bk-cal-day.is-past { opacity:.6; cursor:not-allowed; }
    .bk-cal-day.is-past:hover { border-color:var(--u-line); transform:none; }
    .bk-cal-day.is-today { border-color:#7c3aed; border-width:2px; }
    .bk-cal-day.has-pattern { background:#dcfce7; border-color:#86efac; }
    .bk-cal-day.blocked { background:#fee2e2; border-color:#fca5a5; }
    .bk-cal-day.override { background:#fef3c7; border-color:#fcd34d; }
    .bk-cal-day .d-dots { display:flex; gap:2px; }
    .bk-cal-day .d-dot { width:5px; height:5px; border-radius:50%; }
    .bk-cal-day .d-dot.gr { background:#16a34a; }
    .bk-cal-day .d-dot.rd { background:#dc2626; }
    .bk-cal-day .d-dot.yl { background:#d97706; }
    .bk-cal-day .d-appts { font-size:9px; color:#7c3aed; font-weight:700; margin-top:auto; padding-top:2px; }
    .bk-cal-legend { display:flex; gap:14px; flex-wrap:wrap; font-size:11px; color:var(--u-muted); margin-top:10px; padding-top:10px; border-top:1px solid var(--u-line); }
    .bk-cal-legend-item { display:flex; align-items:center; gap:6px; }
    .bk-cal-legend-item span.sw { width:12px; height:12px; border-radius:3px; display:inline-block; }
    </style>

    <div class="bk-cal-head">
        <div class="bk-cal-title">📅 {{ $calendarTitle ?? '' }}</div>
        <div class="bk-cal-nav">
            <a href="{{ $calendarPrevUrl ?? '#' }}">‹ Önceki</a>
            <a href="{{ url('/senior/appointments?tab=availability') }}">Bu Ay</a>
            <a href="{{ $calendarNextUrl ?? '#' }}">Sonraki ›</a>
        </div>
    </div>

    <p class="hint">
        <strong>Günlere tıklayarak özel istisna ekleyebilirsin</strong> — tatil, izin veya o güne özel saat aralığı.
        Yeşil: haftalık müsaitlik geçerli · Kırmızı: kapalı · Sarı: özel saat · Sağ altındaki mor sayı: o günkü randevu adedi.
    </p>

    <div class="bk-cal-grid">
        @foreach(['Pzt','Sal','Çar','Per','Cum','Cmt','Paz'] as $wdLabel)
            <div class="bk-cal-wd">{{ $wdLabel }}</div>
        @endforeach

        @foreach(($calendarGrid ?? []) as $day)
            @php
                $classes = ['bk-cal-day'];
                if (!$day['is_current_month']) $classes[] = 'out-of-month';
                if ($day['is_past'])            $classes[] = 'is-past';
                if ($day['is_today'])           $classes[] = 'is-today';
                $hasException = $day['exception'] !== null;
                if ($hasException && $day['exception']->is_blocked) {
                    $classes[] = 'blocked';
                } elseif ($hasException && !$day['exception']->is_blocked) {
                    $classes[] = 'override';
                } elseif ($day['has_pattern']) {
                    $classes[] = 'has-pattern';
                }
                $title = $day['date'];
                if ($hasException && $day['exception']->is_blocked) {
                    $title .= ' — Kapalı' . ($day['exception']->reason ? ' (' . $day['exception']->reason . ')' : '');
                } elseif ($hasException) {
                    $title .= ' — Özel: ' . \Carbon\Carbon::parse($day['exception']->override_start_time)->format('H:i') . '-' . \Carbon\Carbon::parse($day['exception']->override_end_time)->format('H:i');
                } elseif ($day['has_pattern']) {
                    $title .= ' — Haftalık müsait';
                } else {
                    $title .= ' — Müsait değil';
                }
                if ($day['appointment_count'] > 0) {
                    $title .= ' · ' . $day['appointment_count'] . ' randevu';
                }
            @endphp
            <div class="{{ implode(' ', $classes) }}"
                 title="{{ $title }}"
                 data-day="{{ $day['date'] }}"
                 data-has-exception="{{ $hasException ? '1' : '0' }}">
                <div class="d-num">{{ $day['day'] }}</div>
                <div class="d-dots">
                    @if($day['has_pattern'] && !$hasException)
                        <span class="d-dot gr"></span>
                    @endif
                    @if($hasException && $day['exception']->is_blocked)
                        <span class="d-dot rd"></span>
                    @endif
                    @if($hasException && !$day['exception']->is_blocked)
                        <span class="d-dot yl"></span>
                    @endif
                </div>
                @if($day['appointment_count'] > 0)
                    <div class="d-appts">{{ $day['appointment_count'] }} rndv</div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="bk-cal-legend">
        <div class="bk-cal-legend-item"><span class="sw" style="background:#dcfce7;border:1px solid #86efac;"></span> Haftalık müsait</div>
        <div class="bk-cal-legend-item"><span class="sw" style="background:#fee2e2;border:1px solid #fca5a5;"></span> Kapalı (tatil/izin)</div>
        <div class="bk-cal-legend-item"><span class="sw" style="background:#fef3c7;border:1px solid #fcd34d;"></span> Özel saat</div>
        <div class="bk-cal-legend-item"><span class="sw" style="background:var(--u-bg);border:1px solid var(--u-line);"></span> Müsait değil</div>
    </div>
</div>

<div class="bk-card">
    <h3>🗓️ Haftalık Müsaitlik</h3>
    <p class="hint">Haftanın hangi günleri ve saatleri müsaitsin? Birden fazla dilim ekleyebilirsin (örn. Salı 09-12 + Salı 14-17).</p>

    <form method="POST" action="{{ route('senior.booking-settings.patterns.store') }}" class="bk-inline">
        @csrf
        <input type="hidden" name="_redirect_tab" value="availability">
        <div class="bk-field" style="flex:1;min-width:140px;">
            <label>Gün</label>
            <select name="weekday">
                @foreach(($weekdayLabels ?? []) as $idx => $lbl)
                    <option value="{{ $idx }}">{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div class="bk-field" style="min-width:110px;">
            <label>Başlangıç</label>
            <input type="time" name="start_time" required value="09:00">
        </div>
        <div class="bk-field" style="min-width:110px;">
            <label>Bitiş</label>
            <input type="time" name="end_time" required value="17:00">
        </div>
        <button type="submit" class="bk-btn bk-btn-primary">➕ Ekle</button>
    </form>

    <div style="margin-top:14px;">
        @forelse(($availabilityPatterns ?? collect()) as $p)
            <div class="bk-row">
                <span class="day">{{ $weekdayLabels[$p->weekday] ?? ('Gün ' . $p->weekday) }}</span>
                <span class="time">{{ \Carbon\Carbon::parse($p->start_time)->format('H:i') }} – {{ \Carbon\Carbon::parse($p->end_time)->format('H:i') }}</span>
                <span style="margin-left:auto;">
                    <form method="POST" action="{{ route('senior.booking-settings.patterns.destroy', $p) }}" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bk-btn bk-btn-danger" style="padding:4px 10px;font-size:11px;">Sil</button>
                    </form>
                </span>
            </div>
        @empty
            <div style="padding:20px;text-align:center;color:var(--u-muted);font-size:13px;">Henüz müsaitlik tanımı yok. Yukarıdan ekle.</div>
        @endforelse
    </div>
</div>

<div class="bk-card">
    <h3>🚫 İstisnalar / İzin Günleri</h3>
    <p class="hint">Belirli bir günü tamamen kapatabilir veya özel saat aralığı tanımlayabilirsin (tatil, izin, ek slot vb.).</p>

    <form method="POST" action="{{ route('senior.booking-settings.exceptions.store') }}" class="bk-inline">
        @csrf
        <div class="bk-field" style="min-width:140px;">
            <label>Tarih</label>
            <input type="date" name="date" required min="{{ now()->toDateString() }}">
        </div>
        <div class="bk-field" style="min-width:140px;">
            <label>Tür</label>
            <select name="is_blocked" id="bk-exc-type">
                <option value="1">Tamamen kapalı</option>
                <option value="0">Özel saat</option>
            </select>
        </div>
        <div class="bk-field bk-exc-times" style="min-width:100px;display:none;">
            <label>Başlangıç</label>
            <input type="time" name="override_start_time">
        </div>
        <div class="bk-field bk-exc-times" style="min-width:100px;display:none;">
            <label>Bitiş</label>
            <input type="time" name="override_end_time">
        </div>
        <div class="bk-field" style="min-width:180px;flex:1;">
            <label>Açıklama (opsiyonel)</label>
            <input type="text" name="reason" maxlength="255" placeholder="Örn: Resmi tatil">
        </div>
        <button type="submit" class="bk-btn bk-btn-primary">➕ Ekle</button>
    </form>

    <div style="margin-top:14px;">
        @forelse(($availabilityExceptions ?? collect()) as $ex)
            <div class="bk-row">
                <span class="day">{{ $ex->date->format('d.m.Y') }}</span>
                @if($ex->is_blocked)
                    <span class="bk-badge red">Kapalı</span>
                @else
                    <span class="bk-badge green">Özel saat</span>
                    <span class="time">{{ \Carbon\Carbon::parse($ex->override_start_time)->format('H:i') }} – {{ \Carbon\Carbon::parse($ex->override_end_time)->format('H:i') }}</span>
                @endif
                @if($ex->reason)
                    <span style="color:var(--u-muted);font-size:12px;">— {{ $ex->reason }}</span>
                @endif
                <span style="margin-left:auto;">
                    <form method="POST" action="{{ route('senior.booking-settings.exceptions.destroy', $ex) }}" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bk-btn bk-btn-danger" style="padding:4px 10px;font-size:11px;">Sil</button>
                    </form>
                </span>
            </div>
        @empty
            <div style="padding:20px;text-align:center;color:var(--u-muted);font-size:13px;">İstisna tanımı yok.</div>
        @endforelse
    </div>
</div>

<script>
(function(){
    var sel = document.getElementById('bk-exc-type');
    var times = document.querySelectorAll('.bk-exc-times');
    if (sel) {
        var toggle = function() {
            times.forEach(function(t){ t.style.display = sel.value === '0' ? '' : 'none'; });
        };
        sel.addEventListener('change', toggle);
        toggle();
    }
    document.querySelectorAll('[data-copy-url]').forEach(function(btn){
        btn.addEventListener('click', function(){
            var url = btn.getAttribute('data-copy-url');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function(){
                    var prev = btn.textContent;
                    btn.textContent = '✅ Kopyalandı';
                    setTimeout(function(){ btn.textContent = prev; }, 1500);
                });
            }
        });
    });

    // Calendar gün tıklama → istisna formunun date alanını prefill + scroll
    document.querySelectorAll('.bk-cal-day').forEach(function(cell){
        if (cell.classList.contains('is-past')) return;
        cell.addEventListener('click', function(){
            var date = cell.getAttribute('data-day');
            if (!date) return;
            // İstisna formundaki tarih input'unu bul
            var excForm = document.querySelector('form[action*="exceptions"][action*="senior.booking-settings.exceptions"]')
                || document.querySelector('form[action*="/senior/booking-settings/exceptions"]');
            // Fallback: input[name=date] + datetime içindeki
            var dateInput = excForm
                ? excForm.querySelector('input[name="date"]')
                : document.querySelector('input[name="date"][type="date"]');
            if (!dateInput) return;
            dateInput.value = date;
            dateInput.focus();
            // Görünür forma scroll
            var target = excForm || dateInput;
            target.scrollIntoView({behavior:'smooth', block:'center'});
            // Flash highlight
            dateInput.style.transition = 'box-shadow .3s';
            dateInput.style.boxShadow = '0 0 0 3px rgba(124,58,237,.4)';
            setTimeout(function(){ dateInput.style.boxShadow = ''; }, 1500);
        });
    });
})();
</script>

@endif
{{-- ══════════════════ /TAB 2 ══════════════════ --}}


{{-- ══════════════════ TAB 3: AYARLAR ══════════════════ --}}
@if(($activeTab ?? '') === 'settings' && ($bookingModuleEnabled ?? false) && $bookingSettings)

<div class="bk-card" style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:18px;margin-bottom:14px;">
    <h3 style="margin:0 0 4px;font-size:15px;color:var(--u-text);">⚙️ Genel Ayarlar</h3>
    <p style="margin:0 0 14px;font-size:12px;color:var(--u-muted);line-height:1.6;">Slot süresi, buffer, minimum bildirim ve public erişim ayarları. Sözleşmeli öğrenciler her zaman ücretsiz randevu alır.</p>

    @if($bookingPublicUrl)
        <div class="bk-public-url" style="background:#eef2ff;border:1px solid #c7d2fe;padding:10px 14px;border-radius:8px;font-family:monospace;font-size:12px;color:#3730a3;display:flex;justify-content:space-between;align-items:center;gap:10px;word-break:break-all;margin-bottom:14px;">
            <span>🔗 {{ $bookingPublicUrl }}</span>
            <button type="button" data-copy-url="{{ $bookingPublicUrl }}" style="background:#eef2ff;color:#3730a3;border:1px solid #c7d2fe;padding:5px 10px;font-size:11px;border-radius:7px;cursor:pointer;">📋 Kopyala</button>
        </div>
    @endif

    <form method="POST" action="{{ route('senior.booking-settings.update') }}">
        @csrf
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:12px;">
            <div class="bk-field">
                <label>Slot süresi (dk)</label>
                <select name="slot_duration" style="width:100%;">
                    @foreach ([15, 20, 30, 45, 60, 90, 120] as $m)
                        <option value="{{ $m }}" @selected((int)$bookingSettings->slot_duration === $m)>{{ $m }} dakika</option>
                    @endforeach
                </select>
            </div>
            <div class="bk-field">
                <label>Buffer (randevular arası)</label>
                <select name="buffer_minutes" style="width:100%;">
                    @foreach ([0, 5, 10, 15, 30] as $m)
                        <option value="{{ $m }}" @selected((int)$bookingSettings->buffer_minutes === $m)>{{ $m }} dakika</option>
                    @endforeach
                </select>
            </div>
            <div class="bk-field">
                <label>En az ne kadar önceden</label>
                <select name="min_notice_hours" style="width:100%;">
                    @foreach ([0, 2, 4, 6, 12, 24, 48, 72] as $h)
                        <option value="{{ $h }}" @selected((int)$bookingSettings->min_notice_hours === $h)>{{ $h }} saat</option>
                    @endforeach
                </select>
            </div>
            <div class="bk-field">
                <label>Max. ne kadar ileri</label>
                <select name="max_future_days" style="width:100%;">
                    @foreach ([14, 30, 60, 90, 180, 365] as $d)
                        <option value="{{ $d }}" @selected((int)$bookingSettings->max_future_days === $d)>{{ $d }} gün</option>
                    @endforeach
                </select>
            </div>
            <div class="bk-field">
                <label>Zaman dilimi</label>
                <select name="timezone" style="width:100%;">
                    @foreach (($supportedTimezones ?? []) as $tz)
                        <option value="{{ $tz }}" @selected($bookingSettings->timezone === $tz)>{{ $tz }}</option>
                    @endforeach
                </select>
            </div>
            <div class="bk-field">
                <label>Görünen ad (başlık)</label>
                <input type="text" name="display_name" value="{{ old('display_name', $bookingSettings->display_name) }}" placeholder="Örn: Danışmanlık Görüşmesi" maxlength="120" style="width:100%;">
            </div>
        </div>

        <div class="bk-field" style="margin-top:12px;">
            <label>Karşılama mesajı (public sayfa üstü)</label>
            <textarea name="welcome_message" rows="2" maxlength="2000" placeholder="Opsiyonel. Örn: 'Danışmanlık için aşağıdan size uygun saati seçiniz.'"
                      style="width:100%;padding:8px 10px;border:1px solid var(--u-line);border-radius:7px;font-size:13px;font-family:inherit;box-sizing:border-box;">{{ old('welcome_message', $bookingSettings->welcome_message) }}</textarea>
        </div>

        <div style="display:flex;gap:16px;margin-top:14px;flex-wrap:wrap;padding:10px 14px;background:var(--u-bg);border-radius:8px;border:1px solid var(--u-line);">
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                <input type="checkbox" name="is_active" value="1" @checked($bookingSettings->is_active)>
                <span>Randevu sistemim <strong>aktif</strong></span>
            </label>
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                <input type="checkbox" name="is_public" value="1" @checked($bookingSettings->is_public)>
                <span>🌐 <strong>Public link açık</strong> (herkes booking yapabilir)</span>
            </label>
        </div>

        <div style="margin-top:14px;">
            <button type="submit" class="bk-btn bk-btn-primary" style="background:#7c3aed;color:#fff;border:none;border-radius:7px;padding:10px 22px;font-size:13px;font-weight:700;cursor:pointer;">💾 Ayarları Kaydet</button>
        </div>
    </form>
</div>

<script>
document.querySelectorAll('[data-copy-url]').forEach(function(btn){
    btn.addEventListener('click', function(){
        var url = btn.getAttribute('data-copy-url');
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function(){
                var prev = btn.textContent;
                btn.textContent = '✅ Kopyalandı';
                setTimeout(function(){ btn.textContent = prev; }, 1500);
            });
        }
    });
});
</script>

@endif
{{-- ══════════════════ /TAB 3 ══════════════════ --}}


<script>
function toggleSec(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
function apptCancelToggleOther(id) {
    const cat = document.getElementById('cancel-cat-' + id);
    const ta  = document.getElementById('cancel-reason-' + id);
    if (!cat || !ta) return;
    if (cat.value === 'other') {
        ta.required = true;
        ta.placeholder = 'Açıklama (zorunlu) — Diğer seçildi';
        ta.focus();
    } else {
        ta.required = false;
        ta.placeholder = 'Açıklama (opsiyonel)';
    }
}
function apptCancelValidate(id) {
    const cat = document.getElementById('cancel-cat-' + id);
    const ta  = document.getElementById('cancel-reason-' + id);
    if (!cat || !cat.value) {
        alert('Lütfen bir iptal nedeni seç.');
        return false;
    }
    if (cat.value === 'other' && (!ta.value || ta.value.trim().length < 3)) {
        alert('Diğer için açıklama yazmalısın.');
        ta.focus();
        return false;
    }
    return confirm('Bu randevuyu iptal etmek istediğinden emin misin? Google Takvim\'den de silinecek.');
}

// Collision check — scheduled_at veya duration değişince senior'un başka
// aktif randevusu ile çakışıyor mu kontrol et, uyarı kutusu göster.
(function(){
    var checkUrl = @json(route('senior.appointments.check-collision'));
    var csrf     = @json(csrf_token());
    var timers   = {};

    function runCheck(apptId){
        var editBox = document.getElementById('appt-edit-' + apptId);
        if (!editBox) return;
        var dt  = editBox.querySelector('input[name="scheduled_at"]');
        var dur = editBox.querySelector('input[name="duration_minutes"]');
        if (!dt || !dt.value) return;

        var fd = new FormData();
        fd.append('_token', csrf);
        fd.append('scheduled_at', dt.value);
        fd.append('duration_minutes', (dur && dur.value) ? dur.value : 30);
        fd.append('except_id', apptId);

        fetch(checkUrl, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
        .then(function(r){ return r.json(); })
        .then(function(j){
            var warnBox = document.getElementById('collision-warn-' + apptId);
            var detail  = document.getElementById('collision-detail-' + apptId);
            if (!warnBox || !detail) return;
            if (j.ok && j.collision && j.conflicts && j.conflicts.length) {
                var html = 'Bu saatte çakışan randevu(lar) var:<ul style="margin:6px 0 0 18px;padding:0;">';
                j.conflicts.forEach(function(c){
                    html += '<li><strong>' + c.scheduled_at + '</strong> · ' + c.title + ' · ' + c.student_id + ' (' + c.duration + ' dk)</li>';
                });
                html += '</ul>';
                detail.innerHTML = html;
                warnBox.style.display = '';
            } else {
                warnBox.style.display = 'none';
                var cb = warnBox.querySelector('input[name="override_collision"]');
                if (cb) cb.checked = false;
            }
        })
        .catch(function(){ /* sessizce yut */ });
    }

    document.addEventListener('input', function(e){
        var el = e.target;
        if (!el || !el.matches) return;
        if (!el.matches('[data-collision-check]')) return;
        var apptId = el.getAttribute('data-collision-check');
        clearTimeout(timers[apptId]);
        timers[apptId] = setTimeout(function(){ runCheck(apptId); }, 400);
    });
})();
</script>

@endsection
