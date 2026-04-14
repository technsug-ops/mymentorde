@extends('manager.layouts.app')

@section('title', 'Kişi Kartı — ' . ($user->name ?: $user->email))
@section('page_title', 'Kişi Kartı')

@push('head')
<style>
.hr-tab-btn { padding:8px 16px;font-size:12px;font-weight:700;border:none;border-bottom:2.5px solid transparent;background:none;cursor:pointer;color:var(--u-muted);transition:all .12s; }
.hr-tab-btn.active { color:#1e40af;border-bottom-color:#1e40af; }
.hr-tab-pane { display:none; }
.hr-tab-pane.active { display:block; }
</style>
@endpush

@section('content')

<div style="margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;">
    <a href="/manager/staff" style="font-size:var(--tx-sm);color:#7c3aed;font-weight:700;text-decoration:none;">← Personel Listesi</a>
</div>

@if(session('status'))
<div style="margin-bottom:12px;padding:10px 16px;border-radius:8px;background:#dcfce7;color:#166534;font-weight:600;font-size:13px;border:1px solid #bbf7d0;">{{ session('status') }}</div>
@endif

{{-- Başlık --}}
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:14px 18px;margin-bottom:14px;display:flex;align-items:center;gap:14px;">
    <div style="width:52px;height:52px;border-radius:50%;background:#1e40af;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:20px;flex-shrink:0;">
        {{ strtoupper(substr($user->name ?: $user->email, 0, 1)) }}
    </div>
    <div style="flex:1;">
        <div style="font-size:var(--tx-base);font-weight:800;color:var(--u-text);">{{ $user->name ?: '—' }}</div>
        <div style="font-size:11px;color:var(--u-muted);">{{ $user->email }}</div>
        <div style="margin-top:4px;display:flex;gap:6px;flex-wrap:wrap;">
            <span class="badge info" style="font-size:10px;">{{ $roleLabel }}</span>
            <span class="badge {{ $user->is_active ? 'ok' : 'danger' }}" style="font-size:10px;">{{ $user->is_active ? 'Aktif' : 'Pasif' }}</span>
            @if($profile->position_title)<span style="font-size:11px;color:var(--u-muted);">{{ $profile->position_title }}</span>@endif
        </div>
    </div>
</div>

{{-- Sekmeler --}}
<div style="border-bottom:1px solid var(--u-line);margin-bottom:14px;display:flex;gap:0;">
    <button class="hr-tab-btn {{ $activeTab==='profile' ? 'active' : '' }}" data-tab="profile">👤 Profil</button>
    <button class="hr-tab-btn {{ $activeTab==='leaves' ? 'active' : '' }}" data-tab="leaves">🌴 İzinler</button>
    <button class="hr-tab-btn {{ $activeTab==='certs' ? 'active' : '' }}" data-tab="certs">🎓 Sertifikalar</button>
    @if($isStaff)
    <button class="hr-tab-btn {{ $activeTab==='kpi' ? 'active' : '' }}" data-tab="kpi">📊 KPI</button>
    @endif
    @if($showContracts)
    <button class="hr-tab-btn {{ $activeTab==='contracts' ? 'active' : '' }}" data-tab="contracts">📄 Sözleşmeler</button>
    @endif
    <button class="hr-tab-btn {{ $activeTab==='roles' ? 'active' : '' }}" data-tab="roles">🛡 Roller</button>
    <button class="hr-tab-btn {{ $activeTab==='account' ? 'active' : '' }}" data-tab="account">🔑 Hesap</button>
</div>

{{-- Profil Sekmesi --}}
<div id="tab-profile" class="hr-tab-pane {{ $activeTab==='profile' ? 'active' : '' }}">
<div class="grid2" style="gap:12px;">

    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:14px 18px;">
        <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:12px;">Temel Bilgiler</div>
        <table style="width:100%;border-collapse:collapse;font-size:12px;">
            <tr><td style="padding:5px 0;color:var(--u-muted);width:140px;">Ad Soyad</td><td><strong>{{ $user->name ?: '—' }}</strong></td></tr>
            <tr><td style="padding:5px 0;color:var(--u-muted);">E-posta</td><td>{{ $user->email }}</td></tr>
            <tr><td style="padding:5px 0;color:var(--u-muted);">Rol</td><td><span class="badge info" style="font-size:10px;">{{ $roleLabel }}</span></td></tr>
            <tr><td style="padding:5px 0;color:var(--u-muted);">Durum</td><td><span class="badge {{ $user->is_active ? 'ok' : 'danger' }}" style="font-size:10px;">{{ $user->is_active ? 'Aktif' : 'Pasif' }}</span></td></tr>
            <tr><td style="padding:5px 0;color:var(--u-muted);">Kayıt</td><td>{{ optional($user->created_at)->format('d.m.Y') }}</td></tr>
        </table>
    </div>

    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:14px 18px;">
        <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:12px;">HR Profili</div>
        <form method="POST" action="/manager/hr/persons/{{ $user->id }}/profile">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px;">
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">İşe Giriş Tarihi</label>
                    <input type="date" name="hire_date" value="{{ optional($profile->hire_date)->format('Y-m-d') }}"
                           style="width:100%;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
                </div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Yıllık İzin Kotası (gün)</label>
                    <input type="number" name="annual_leave_quota" value="{{ $quota }}" min="0" max="60"
                           style="width:100%;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
                </div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Unvan / Pozisyon</label>
                    <input type="text" name="position_title" value="{{ $profile->position_title }}"
                           style="width:100%;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
                </div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Telefon</label>
                    <input type="text" name="phone" value="{{ $profile->phone }}"
                           style="width:100%;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
                </div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Acil Kişi Adı</label>
                    <input type="text" name="emergency_contact_name" value="{{ $profile->emergency_contact_name }}"
                           style="width:100%;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
                </div>
                <div>
                    <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Acil Kişi Telefonu</label>
                    <input type="text" name="emergency_contact_phone" value="{{ $profile->emergency_contact_phone }}"
                           style="width:100%;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
                </div>
            </div>
            <div style="margin-bottom:8px;">
                <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Notlar</label>
                <textarea name="notes" rows="2" style="width:100%;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);resize:vertical;">{{ $profile->notes }}</textarea>
            </div>
            <button type="submit" class="btn ok" style="font-size:12px;padding:7px 18px;">Kaydet</button>
        </form>
    </div>

</div>
</div>

{{-- İzinler Sekmesi --}}
<div id="tab-leaves" class="hr-tab-pane {{ $activeTab==='leaves' ? 'active' : '' }}">

    {{-- Bakiye --}}
    @php
        $pct = $quota > 0 ? min(100, round($usedLeave / $quota * 100)) : 0;
        $barColor = $pct >= 90 ? '#dc2626' : ($pct >= 70 ? '#d97706' : '#16a34a');
    @endphp
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:14px 18px;margin-bottom:12px;">
        <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:10px;">{{ $year }} Yılı Yıllık İzin Bakiyesi</div>
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <div style="flex:1;min-width:150px;">
                <div style="height:8px;background:var(--u-line);border-radius:999px;overflow:hidden;">
                    <div style="height:100%;width:{{ $pct }}%;background:{{ $barColor }};border-radius:999px;transition:width .3s;"></div>
                </div>
            </div>
            <div style="font-size:13px;font-weight:700;color:{{ $barColor }};white-space:nowrap;">
                {{ $usedLeave }} / {{ $quota }} gün kullanıldı
            </div>
            <div style="font-size:13px;font-weight:800;color:var(--u-text);white-space:nowrap;">
                {{ $remaining }} gün kalan
            </div>
        </div>
    </div>

    {{-- İzin Listesi --}}
    <section class="panel" style="padding:0;overflow:hidden;">
        <div style="padding:10px 14px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;">
            <div style="font-weight:700;font-size:var(--tx-sm);">İzin Geçmişi</div>
            <a href="/manager/hr/leaves?user_id={{ $user->id }}" style="font-size:11px;color:#1e40af;font-weight:700;text-decoration:none;">Tüm izinler →</a>
        </div>
        @if($leaves->isEmpty())
        <div style="padding:30px;text-align:center;color:var(--u-muted);font-size:13px;">İzin kaydı bulunamadı.</div>
        @else
        <table style="width:100%;border-collapse:collapse;font-size:12px;">
            <thead><tr style="background:var(--u-bg);">
                <th style="padding:7px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Tür</th>
                <th style="padding:7px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Tarih</th>
                <th style="padding:7px 12px;text-align:center;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Gün</th>
                <th style="padding:7px 12px;text-align:center;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Durum</th>
                <th style="padding:7px 12px;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Sebep</th>
            </tr></thead>
            <tbody>
            @foreach($leaves as $lv)
            <tr style="border-bottom:1px solid var(--u-line);">
                <td style="padding:8px 12px;">{{ \App\Models\Hr\HrLeaveRequest::$typeLabels[$lv->leave_type] ?? $lv->leave_type }}</td>
                <td style="padding:8px 12px;white-space:nowrap;">{{ $lv->start_date->format('d.m.Y') }} – {{ $lv->end_date->format('d.m.Y') }}</td>
                <td style="padding:8px 12px;text-align:center;font-weight:700;">{{ $lv->days_count }}</td>
                <td style="padding:8px 12px;text-align:center;">
                    <span class="badge {{ \App\Models\Hr\HrLeaveRequest::$statusBadge[$lv->status] ?? '' }}" style="font-size:10px;">
                        {{ \App\Models\Hr\HrLeaveRequest::$statusLabels[$lv->status] ?? $lv->status }}
                    </span>
                </td>
                <td style="padding:8px 12px;color:var(--u-muted);font-size:11px;">{{ Str::limit($lv->reason ?? '—', 40) }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </section>
</div>

{{-- Sertifikalar Sekmesi --}}
<div id="tab-certs" class="hr-tab-pane {{ $activeTab==='certs' ? 'active' : '' }}">
    <section class="panel" style="padding:0;overflow:hidden;">
        <div style="padding:10px 14px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;">
            <div style="font-weight:700;font-size:var(--tx-sm);">Sertifikalar</div>
            <a href="/manager/hr/certifications?user_id={{ $user->id }}" style="font-size:11px;color:#1e40af;font-weight:700;text-decoration:none;">Tümü / Ekle →</a>
        </div>
        @if($certs->isEmpty())
        <div style="padding:30px;text-align:center;color:var(--u-muted);font-size:13px;">Sertifika kaydı bulunamadı.</div>
        @else
        <table style="width:100%;border-collapse:collapse;font-size:12px;">
            <thead><tr style="background:var(--u-bg);">
                <th style="padding:7px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Sertifika</th>
                <th style="padding:7px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Veren Kurum</th>
                <th style="padding:7px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Alım Tarihi</th>
                <th style="padding:7px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Son Geçerlilik</th>
                <th style="padding:7px 12px;text-align:center;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Durum</th>
            </tr></thead>
            <tbody>
            @foreach($certs as $c)
            <tr style="border-bottom:1px solid var(--u-line);">
                <td style="padding:8px 12px;font-weight:600;color:var(--u-text);">{{ $c->cert_name }}</td>
                <td style="padding:8px 12px;color:var(--u-muted);">{{ $c->issuer ?: '—' }}</td>
                <td style="padding:8px 12px;white-space:nowrap;">{{ $c->issue_date->format('d.m.Y') }}</td>
                <td style="padding:8px 12px;white-space:nowrap;">{{ $c->expiry_date ? $c->expiry_date->format('d.m.Y') : '—' }}</td>
                <td style="padding:8px 12px;text-align:center;">
                    <span class="badge {{ $c->statusBadge() }}" style="font-size:10px;">{{ $c->statusLabel() }}</span>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </section>
</div>

{{-- KPI Sekmesi (sadece staff) --}}
@if($isStaff)
<div id="tab-kpi" class="hr-tab-pane {{ $activeTab==='kpi' ? 'active' : '' }}">
@php
    $monthNames = ['01'=>'Ocak','02'=>'Şubat','03'=>'Mart','04'=>'Nisan','05'=>'Mayıs','06'=>'Haziran',
                   '07'=>'Temmuz','08'=>'Ağustos','09'=>'Eylül','10'=>'Ekim','11'=>'Kasım','12'=>'Aralık'];
    $pMonth = substr($kpiPeriod, 5, 2);
    $pYear  = substr($kpiPeriod, 0, 4);
    $periodLabel = ($monthNames[$pMonth] ?? $pMonth) . ' ' . $pYear;

    $tTasks   = $kpiTargets?->target_tasks_done ?? 0;
    $tTickets = $kpiTargets?->target_tickets_resolved ?? 0;
    $tHours   = $kpiTargets?->target_hours_logged ?? 0;
    $aTasks   = $kpiActuals['tasks_done'];
    $aTickets = $kpiActuals['tickets_resolved'];
    $aHours   = $kpiActuals['hours_logged'];

    $pctTasks   = $tTasks   > 0 ? min(100, round($aTasks   / $tTasks   * 100)) : null;
    $pctTickets = $tTickets > 0 ? min(100, round($aTickets / $tTickets * 100)) : null;
    $pctHours   = $tHours   > 0 ? min(100, round($aHours   / $tHours   * 100)) : null;
    $barColor   = fn($pct) => $pct === null ? '#94a3b8' : ($pct >= 100 ? '#16a34a' : ($pct >= 60 ? '#3b82f6' : '#f59e0b'));
@endphp

<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:14px 18px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px;">
        <div style="font-weight:700;font-size:var(--tx-sm);">📊 KPI — {{ $periodLabel }}</div>
        <form method="GET" action="/manager/hr/persons/{{ $user->id }}" style="display:flex;gap:6px;align-items:center;">
            <input type="hidden" name="tab" value="kpi">
            <input type="month" name="period" value="{{ $kpiPeriod }}"
                   style="padding:5px 10px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
            <button type="submit" class="btn alt" style="font-size:11px;padding:5px 12px;">Göster</button>
        </form>
    </div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px;">
        @foreach([['Tamamlanan Görev',$aTasks,$tTasks,$pctTasks,''],['Çözülen Ticket',$aTickets,$tTickets,$pctTickets,''],['Harcanan Saat',$aHours,$tHours,$pctHours,'h']] as [$lbl,$a,$t,$pct,$sfx])
        <div style="background:var(--u-bg);border:1px solid var(--u-line);border-radius:9px;padding:12px 14px;">
            <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">{{ $lbl }}</div>
            <div style="font-size:20px;font-weight:900;color:var(--u-text);line-height:1;margin-bottom:2px;">{{ $a }}{{ $sfx }}</div>
            <div style="font-size:11px;color:var(--u-muted);margin-bottom:8px;">/ {{ $t > 0 ? $t.$sfx.' hedef' : 'hedef yok' }}</div>
            <div style="height:6px;background:var(--u-line);border-radius:999px;overflow:hidden;">
                <div style="height:100%;width:{{ $pct ?? min(100,(int)($a*5)) }}%;background:{{ $barColor($pct) }};border-radius:999px;"></div>
            </div>
            @if($pct !== null)<div style="font-size:11px;font-weight:700;color:{{ $barColor($pct) }};margin-top:4px;">%{{ $pct }}</div>@endif
        </div>
        @endforeach
    </div>
    {{-- KPI Trend (son 6 ay) --}}
    @if($kpiTrend && $kpiTrend->isNotEmpty())
    <div style="margin-bottom:14px;">
        <div style="font-size:11px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">Son 6 Ay Trend</div>
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:11px;">
            <thead>
                <tr style="background:var(--u-bg);">
                    <th style="padding:5px 8px;text-align:left;font-weight:700;color:var(--u-muted);">Dönem</th>
                    <th style="padding:5px 8px;text-align:center;font-weight:700;color:var(--u-muted);">Görev</th>
                    <th style="padding:5px 8px;text-align:center;font-weight:700;color:var(--u-muted);">Ticket</th>
                    <th style="padding:5px 8px;text-align:center;font-weight:700;color:var(--u-muted);">Saat</th>
                    <th style="padding:5px 8px;text-align:center;font-weight:700;color:var(--u-muted);">Skor</th>
                </tr>
            </thead>
            <tbody>
            @foreach($kpiTrend as $tr)
            @php
                $sc = $tr['score'];
                $scColor = $sc >= 80 ? '#16a34a' : ($sc >= 50 ? '#2563eb' : ($sc > 0 ? '#f59e0b' : '#94a3b8'));
            @endphp
            <tr style="border-bottom:1px solid var(--u-line);">
                <td style="padding:5px 8px;font-family:monospace;font-weight:600;">{{ $tr['period'] }}</td>
                <td style="padding:5px 8px;text-align:center;">{{ $tr['act']['tasks_done'] ?? 0 }}</td>
                <td style="padding:5px 8px;text-align:center;">{{ $tr['act']['tickets_resolved'] ?? 0 }}</td>
                <td style="padding:5px 8px;text-align:center;">{{ $tr['act']['hours_logged'] ?? 0 }}</td>
                <td style="padding:5px 8px;text-align:center;">
                    <span style="font-size:12px;font-weight:800;color:{{ $scColor }};">{{ $sc }}</span>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>
    </div>
    @endif

    <details style="border:1px dashed var(--u-line);border-radius:8px;">
        <summary style="cursor:pointer;font-size:12px;font-weight:600;color:#7c3aed;padding:8px 14px;list-style:none;user-select:none;">⚙ Hedef Güncelle</summary>
        <form method="POST" action="/manager/staff/{{ $user->id }}/kpi-targets"
              style="padding:12px 14px;display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;border-top:1px dashed var(--u-line);">
            @csrf
            <input type="hidden" name="period" value="{{ $kpiPeriod }}">
            @foreach([['target_tasks_done','Görev Hedefi',$tTasks],['target_tickets_resolved','Ticket Hedefi',$tTickets]] as [$n,$l,$v])
            <div>
                <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">{{ $l }}</label>
                <input type="number" name="{{ $n }}" value="{{ $v }}" min="0" style="width:90px;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
            </div>
            @endforeach
            <div>
                <label style="display:block;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Saat Hedefi</label>
                <input type="number" name="target_hours_logged" value="{{ $tHours }}" min="0" step="0.5" style="width:90px;padding:6px 9px;border:1.5px solid var(--u-line);border-radius:7px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
            </div>
            <button type="submit" class="btn ok" style="padding:7px 16px;font-size:12px;">Kaydet</button>
        </form>
    </details>
</div>
</div>
@endif

{{-- Sözleşmeler Sekmesi --}}
@if($showContracts)
<div id="tab-contracts" class="hr-tab-pane {{ $activeTab==='contracts' ? 'active' : '' }}">
    <section class="panel" style="padding:0;overflow:hidden;">
        <div style="padding:10px 14px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;">
            <div style="font-weight:700;font-size:var(--tx-sm);">İş Sözleşmeleri</div>
            <a href="/manager/business-contracts/create?type=staff&user_id={{ $user->id }}" class="btn ok" style="font-size:11px;padding:4px 12px;">+ Yeni Sözleşme</a>
        </div>
        @if($contracts->isEmpty())
        <div style="padding:30px;text-align:center;color:var(--u-muted);font-size:13px;">Sözleşme bulunamadı.</div>
        @else
        @foreach($contracts as $c)
        @php
            [$cBadge, $cLabel] = match($c->status ?? '') {
                'draft'           => ['', 'Taslak'],
                'issued'          => ['info', 'Gönderildi'],
                'signed_uploaded' => ['warn', 'İmzalandı'],
                'approved'        => ['ok', 'Onaylandı'],
                'cancelled'       => ['danger', 'İptal'],
                default           => ['', $c->status ?? '—'],
            };
        @endphp
        <div style="padding:10px 16px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;">
            <div>
                <div style="font-size:var(--tx-sm);font-weight:600;">{{ $c->title ?: $c->contract_no }}</div>
                <div style="font-size:11px;color:var(--u-muted);">{{ optional($c->created_at)->format('d.m.Y') }}</div>
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
                <span class="badge {{ $cBadge }}" style="font-size:10px;">{{ $cLabel }}</span>
                <a href="/manager/business-contracts/{{ $c->id }}" style="font-size:11px;color:#1e40af;font-weight:600;text-decoration:none;">Detay →</a>
            </div>
        </div>
        @endforeach
        @endif
    </section>
</div>
@endif

{{-- Hesap Sekmesi --}}
{{-- Roller Sekmesi --}}
<div id="tab-roles" class="hr-tab-pane {{ $activeTab==='roles' ? 'active' : '' }}">
<div class="grid2" style="gap:12px;align-items:start;">

    {{-- Aktif Şablonlar --}}
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:14px 18px;">
        <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:12px;">🛡 Aktif Şablonlar</div>
        @php $activeAssignments = $user->roleAssignments()->where('is_active',true)->with('template.permissions')->get(); @endphp
        @if($activeAssignments->isEmpty())
        <div style="color:var(--u-muted);font-size:12px;padding:10px 0;">Atanmış şablon yok — rol tabanlı varsayılan izinler aktif.</div>
        @else
        <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:14px;">
        @foreach($activeAssignments as $asgn)
        <div style="padding:10px 12px;border:1px solid var(--u-line);border-radius:8px;background:var(--u-bg);">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                <div>
                    <div style="font-size:13px;font-weight:700;color:var(--u-text);">{{ $asgn->template?->name ?? '?' }}</div>
                    <div style="font-size:10px;color:var(--u-muted);margin-top:2px;">
                        {{ $asgn->template?->permissions->count() ?? 0 }} izin
                        · Atandı: {{ optional($asgn->assigned_at)->format('d.m.Y') ?? '—' }}
                    </div>
                </div>
                <form method="POST" action="/manager/hr/persons/{{ $user->id }}/templates/{{ $asgn->id }}/revoke" style="display:inline;">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn warn" style="font-size:10px;padding:3px 10px;">✕ Kaldır</button>
                </form>
            </div>
            @if($asgn->template?->permissions->isNotEmpty())
            <div style="display:flex;flex-wrap:wrap;gap:3px;margin-top:8px;">
                @foreach($asgn->template->permissions as $p)
                <span style="font-size:10px;padding:1px 7px;border-radius:3px;background:#eff6ff;color:#1e40af;font-family:monospace;font-weight:600;">{{ $p->code }}</span>
                @endforeach
            </div>
            @endif
        </div>
        @endforeach
        </div>
        @endif

        {{-- Şablon Ekle --}}
        <div style="border-top:1px solid var(--u-line);padding-top:12px;">
            <div style="font-size:11px;font-weight:700;color:var(--u-muted);margin-bottom:8px;">➕ Şablon Ekle</div>
            <form method="POST" action="/manager/hr/persons/{{ $user->id }}/templates/add">
                @csrf
                <select name="role_template_id" required
                    style="width:100%;padding:7px 10px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);margin-bottom:8px;">
                    <option value="">— Şablon seçin —</option>
                    @foreach($roleTemplates as $tpl)
                    @if(!$activeAssignments->pluck('role_template_id')->contains($tpl->id))
                    <option value="{{ $tpl->id }}">{{ $tpl->name }} ({{ str_replace('_',' ',$tpl->parent_role) }})</option>
                    @endif
                    @endforeach
                </select>
                <button type="submit" class="btn ok" style="width:100%;font-size:12px;">Şablon Ata</button>
            </form>
        </div>
    </div>

    {{-- Efektif İzinler --}}
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:14px 18px;">
        <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:12px;">🔑 Efektif İzinler</div>
        @php $effectivePerms = $user->effectivePermissionCodes(); sort($effectivePerms); @endphp
        @if(empty($effectivePerms))
        <div style="color:var(--u-muted);font-size:12px;">İzin bulunamadı.</div>
        @else
        <div style="display:flex;flex-wrap:wrap;gap:4px;">
            @foreach($effectivePerms as $code)
            <span style="font-size:11px;padding:2px 8px;border-radius:4px;background:#eff6ff;color:#1e40af;font-family:monospace;font-weight:600;">{{ $code }}</span>
            @endforeach
        </div>
        <div style="margin-top:10px;font-size:10px;color:var(--u-muted);">Toplam {{ count($effectivePerms) }} izin (tüm şablonlardan birleşik)</div>
        @endif
    </div>

</div>
</div>

<div id="tab-account" class="hr-tab-pane {{ $activeTab==='account' ? 'active' : '' }}">
<div class="grid2" style="gap:12px;">

    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:14px 18px;">
        <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:12px;">Hesap Bilgileri</div>
        <table style="width:100%;border-collapse:collapse;font-size:12px;">
            <tr><td style="padding:5px 0;color:var(--u-muted);width:130px;">Ad Soyad</td><td><strong>{{ $user->name ?: '—' }}</strong></td></tr>
            <tr><td style="padding:5px 0;color:var(--u-muted);">E-posta</td><td>{{ $user->email }}</td></tr>
            <tr><td style="padding:5px 0;color:var(--u-muted);">Departman / Tür</td><td><span class="badge info" style="font-size:10px;">{{ $roleLabel }}</span></td></tr>
            <tr><td style="padding:5px 0;color:var(--u-muted);">Rol (Sistem)</td><td style="font-size:11px;color:var(--u-muted);font-family:monospace;">{{ $user->role }}</td></tr>
            <tr><td style="padding:5px 0;color:var(--u-muted);">Durum</td>
                <td>
                    @if($user->is_active)
                        <span class="badge ok" style="font-size:10px;">Aktif</span>
                    @else
                        <span class="badge danger" style="font-size:10px;">Pasif</span>
                    @endif
                </td>
            </tr>
            <tr><td style="padding:5px 0;color:var(--u-muted);">Kayıt Tarihi</td><td>{{ optional($user->created_at)->format('d.m.Y H:i') }}</td></tr>
        </table>
        <div style="margin-top:14px;padding-top:12px;border-top:1px solid var(--u-line);display:flex;gap:8px;flex-wrap:wrap;">
            @if(in_array($user->role, ['system_admin','system_staff','operations_admin','operations_staff','finance_admin','finance_staff','marketing_admin','marketing_staff','sales_admin','sales_staff']))
            <a href="/manager/staff/{{ $user->id }}/edit" class="btn alt" style="font-size:11px;padding:5px 14px;">Düzenle</a>
            @elseif($user->role === 'senior')
            <a href="/manager/seniors/{{ $user->id }}" class="btn alt" style="font-size:11px;padding:5px 14px;">Eğitim Danışmanı Detayı</a>
            @endif
            <form method="POST" action="/manager/hr/persons/{{ $user->id }}/toggle" style="display:inline;">
                @csrf
                <button type="submit" class="btn {{ $user->is_active ? 'warn' : 'ok' }}" style="font-size:11px;padding:5px 14px;">
                    {{ $user->is_active ? 'Pasif Yap' : 'Aktif Et' }}
                </button>
            </form>
        </div>

        {{-- Şifre Sıfırlama --}}
        <div style="margin-top:14px;padding-top:12px;border-top:1px solid var(--u-line);">
            <div style="font-size:11px;font-weight:700;color:var(--u-muted);margin-bottom:8px;">🔒 Şifre Sıfırla</div>
            <form method="POST" action="/manager/hr/persons/{{ $user->id }}/reset-password">
                @csrf
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
                    <div style="flex:1;min-width:140px;">
                        <label style="font-size:10px;color:var(--u-muted);display:block;margin-bottom:3px;">Yeni Şifre</label>
                        <input type="password" name="new_password" required minlength="8"
                               style="width:100%;padding:6px 10px;border:1.5px solid var(--u-line);border-radius:6px;font-size:12px;background:var(--u-bg);color:var(--u-text);box-sizing:border-box;">
                    </div>
                    <div style="flex:1;min-width:140px;">
                        <label style="font-size:10px;color:var(--u-muted);display:block;margin-bottom:3px;">Tekrar</label>
                        <input type="password" name="new_password_confirmation" required minlength="8"
                               style="width:100%;padding:6px 10px;border:1.5px solid var(--u-line);border-radius:6px;font-size:12px;background:var(--u-bg);color:var(--u-text);box-sizing:border-box;">
                    </div>
                    <button type="submit" id="pwResetBtn" class="btn warn" style="font-size:11px;padding:6px 14px;">Sıfırla</button>
                </div>
            </form>
        </div>
    </div>

    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:14px 18px;">
        <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:12px;">Hızlı Bağlantılar</div>
        <div style="display:flex;flex-direction:column;gap:8px;">
            <a href="/manager/hr/persons/{{ $user->id }}?tab=kpi" style="padding:8px 14px;font-size:12px;font-weight:600;border:1.5px solid var(--u-line);border-radius:8px;text-decoration:none;color:var(--u-text);background:var(--u-bg);">📊 KPI Performansı</a>
            <a href="/manager/hr/persons/{{ $user->id }}?tab=contracts" style="padding:8px 14px;font-size:12px;font-weight:600;border:1.5px solid var(--u-line);border-radius:8px;text-decoration:none;color:var(--u-text);background:var(--u-bg);">📄 Sözleşmeler</a>
            <a href="/manager/hr/persons/{{ $user->id }}?tab=leaves" style="padding:8px 14px;font-size:12px;font-weight:600;border:1.5px solid var(--u-line);border-radius:8px;text-decoration:none;color:var(--u-text);background:var(--u-bg);">🌴 İzinler</a>
            <a href="/manager/hr/persons/{{ $user->id }}?tab=certs" style="padding:8px 14px;font-size:12px;font-weight:600;border:1.5px solid var(--u-line);border-radius:8px;text-decoration:none;color:var(--u-text);background:var(--u-bg);">🎓 Sertifikalar</a>
        </div>
    </div>

</div>

{{-- Rol Değişiklik Geçmişi --}}
@if($roleAudits->isNotEmpty())
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:14px 18px;margin-top:12px;">
    <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:10px;">📋 Rol Değişiklik Geçmişi</div>
    <div style="display:flex;flex-direction:column;gap:6px;">
    @foreach($roleAudits as $audit)
    @php
        $payload = $audit->payload ? json_decode($audit->payload, true) : [];
        $badgeClass = match(true) {
            str_contains($audit->action, 'assign') => 'ok',
            str_contains($audit->action, 'revoke') => 'warn',
            default => 'info',
        };
    @endphp
    <div style="display:flex;align-items:flex-start;gap:10px;padding:8px 10px;background:var(--u-bg);border:1px solid var(--u-line);border-radius:7px;">
        <span class="badge {{ $badgeClass }}" style="font-size:9px;flex-shrink:0;margin-top:1px;">{{ str_replace('_', ' ', $audit->action) }}</span>
        <div style="flex:1;min-width:0;">
            @if(!empty($payload))
            <div style="font-size:11px;color:var(--u-muted);">
                @foreach($payload as $k => $v)
                <span style="font-family:monospace;font-size:10px;">{{ $k }}: {{ is_array($v) ? implode(', ', $v) : $v }}</span>
                @endforeach
            </div>
            @endif
        </div>
        <div style="font-size:10px;color:var(--u-muted);white-space:nowrap;flex-shrink:0;">{{ \Carbon\Carbon::parse($audit->created_at)->format('d.m.Y H:i') }}</div>
    </div>
    @endforeach
    </div>
</div>
@endif

</div>

@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    document.querySelectorAll('.hr-tab-btn[data-tab]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var name = this.dataset.tab;
            document.querySelectorAll('.hr-tab-pane').forEach(function(p){ p.classList.remove('active'); });
            document.querySelectorAll('.hr-tab-btn').forEach(function(b){ b.classList.remove('active'); });
            var pane = document.getElementById('tab-' + name);
            if (pane) pane.classList.add('active');
            this.classList.add('active');
        });
    });

    document.getElementById('pwResetBtn')?.closest('form')?.addEventListener('submit', function(e) {
        if (!confirm('Şifreyi sıfırlamak istediğinize emin misiniz?')) e.preventDefault();
    });
}());
</script>
@endpush
