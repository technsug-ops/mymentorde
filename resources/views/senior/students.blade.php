@extends('senior.layouts.app')
@section('title','Öğrencilerim')
@section('page_title','Öğrencilerim')

@section('content')
@php
    $assignments = $assignments ?? collect();
    $guestPool   = $guestPool   ?? collect();
    $active      = $assignments->where('is_archived', false);
    $archived    = $assignments->where('is_archived', true);

    $byRisk = [
        'critical' => $active->whereIn('risk_level', ['critical']),
        'high'     => $active->whereIn('risk_level', ['high']),
        'medium'   => $active->whereIn('risk_level', ['medium','normal']),
        'low'      => $active->filter(fn($r) => !in_array($r->risk_level ?? '', ['critical','high','medium','normal'])),
    ];

    $riskLabelMap = ['critical'=>'Kritik','high'=>'Yüksek','medium'=>'Orta','normal'=>'Orta','low'=>'Düşük'];
    $payLabelMap  = ['overdue'=>'Gecikmiş','pending'=>'Bekliyor','paid'=>'Ödeme Tamam','ok'=>'Ödeme Tamam'];
    $gStatusLabel = ['new'=>'Yeni','contacted'=>'İletişimde','qualified'=>'Nitelikli','lost'=>'Kayıp'];
    $appTypeLabel = ['bachelor'=>'Lisans','master'=>'Yüksek Lisans','phd'=>'Doktora','language'=>'Dil'];

    $critHigh = $byRisk['critical']->count() + $byRisk['high']->count();
@endphp

{{-- Gradient header --}}
<div style="background:linear-gradient(to right,#6d28d9,#7c3aed);border-radius:14px;padding:20px 24px;margin-bottom:16px;color:#fff;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
    <div>
        <div style="font-size:var(--tx-xl);font-weight:800;letter-spacing:-.3px;">🎓 Öğrenci Pipeline</div>
        <div style="font-size:var(--tx-sm);opacity:.8;margin-top:2px;">Aktif öğrenciler, risk takibi ve guest havuzu</div>
    </div>
    <div style="display:flex;gap:20px;text-align:center;">
        <div>
            <div style="font-size:var(--tx-2xl);font-weight:800;line-height:1;">{{ $active->count() }}</div>
            <div style="font-size:var(--tx-xs);opacity:.7;text-transform:uppercase;letter-spacing:.05em;">Aktif</div>
        </div>
        <div style="width:1px;background:rgba(255,255,255,.2);"></div>
        <div>
            <div style="font-size:var(--tx-2xl);font-weight:800;line-height:1;">{{ $guestPool->count() }}</div>
            <div style="font-size:var(--tx-xs);opacity:.7;text-transform:uppercase;letter-spacing:.05em;">Aday Öğrenci</div>
        </div>
    </div>
</div>

{{-- KPI strip --}}
<div class="kpi-row" style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:16px;">
    @foreach([
        ['label'=>'Aktif Öğrenci', 'val'=>$active->count(),    'color'=>'#7c3aed','icon'=>'🎓','sub'=>'toplam atanmış'],
        ['label'=>'Aday Öğrenci Havuzu',  'val'=>$guestPool->count(),  'color'=>'#7c3aed','icon'=>'👤','sub'=>'dönüşüm bekleniyor'],
        ['label'=>'Kritik / Yüksek','val'=>$critHigh,          'color'=>$critHigh > 0 ? '#dc2626' : '#16a34a','icon'=>$critHigh > 0 ? '🚨' : '✅','sub'=>$critHigh > 0 ? 'aksiyon gerekli' : 'her şey yolunda'],
        ['label'=>'Arşiv',         'val'=>$archived->count(),  'color'=>'#6b7280','icon'=>'📦','sub'=>'tamamlananlar'],
    ] as $k)
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:14px;text-align:center;">
        <div style="font-size:var(--tx-xl);line-height:1;">{{ $k['icon'] }}</div>
        <div style="font-size:var(--tx-2xl);font-weight:800;color:{{ $k['color'] }};margin:4px 0 2px;line-height:1;">{{ $k['val'] }}</div>
        <div style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;">{{ $k['label'] }}</div>
        <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:2px;">{{ $k['sub'] }}</div>
    </div>
    @endforeach
</div>

{{-- Filter bar --}}
<form method="GET" style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:12px 14px;margin-bottom:14px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="🔍  Öğrenci ID / branch / dealer / guest"
        style="flex:1;min-width:200px;border:1px solid var(--u-line);border-radius:7px;padding:8px 12px;font-size:var(--tx-sm);color:var(--u-text);background:var(--u-bg);">
    <select name="archived" style="border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);color:var(--u-text);background:var(--u-bg);">
        <option value="all" @selected(($filters['archived']??'all')==='all')>Tüm Durumlar</option>
        <option value="no"  @selected(($filters['archived']??'')==='no')>Sadece Aktif</option>
        <option value="yes" @selected(($filters['archived']??'')==='yes')>Sadece Arşiv</option>
    </select>
    <select name="risk" style="border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);color:var(--u-text);background:var(--u-bg);">
        <option value="all"      @selected(($filters['risk']??'all')==='all')>Tüm Riskler</option>
        <option value="critical" @selected(($filters['risk']??'')==='critical')>🔴 Kritik</option>
        <option value="high"     @selected(($filters['risk']??'')==='high')>🟠 Yüksek</option>
        <option value="medium"   @selected(($filters['risk']??'')==='medium')>🟡 Orta</option>
        <option value="low"      @selected(($filters['risk']??'')==='low')>🟢 Düşük</option>
    </select>
    <button type="submit" style="background:#7c3aed;color:#fff;border:none;border-radius:7px;padding:8px 18px;font-size:var(--tx-sm);font-weight:600;cursor:pointer;">Filtrele</button>
    <a href="{{ url('/senior/students') }}" style="color:var(--u-muted);font-size:var(--tx-sm);text-decoration:none;padding:8px 10px;border:1px solid var(--u-line);border-radius:7px;background:var(--u-bg);">Temizle</a>
    <a href="/senior/students/export-csv?{{ http_build_query(['q'=>($filters['q']??''),'archived'=>($filters['archived']??'all'),'risk'=>($filters['risk']??'all')]) }}"
       style="margin-left:auto;font-size:var(--tx-xs);padding:8px 13px;border:1px solid var(--u-line);border-radius:7px;background:var(--u-bg);color:var(--u-text);text-decoration:none;font-weight:600;">⬇ CSV</a>
</form>

{{-- Risk Pipeline --}}
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:16px;margin-bottom:14px;">
    <div style="font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--u-muted);margin-bottom:12px;">Risk Pipeline</div>
    <div class="kpi-row-compact" style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;">
        @foreach([
            ['key'=>'critical','label'=>'Kritik', 'color'=>'#dc2626','bg'=>'#fff5f5','border'=>'#f2b8b8','icon'=>'🔴'],
            ['key'=>'high',    'label'=>'Yüksek', 'color'=>'#b91c1c','bg'=>'#fff8f0','border'=>'#f2d4c7','icon'=>'🟠'],
            ['key'=>'medium',  'label'=>'Orta',   'color'=>'#92400e','bg'=>'#fffbeb','border'=>'#fde68a','icon'=>'🟡'],
            ['key'=>'low',     'label'=>'Düşük',  'color'=>'#166534','bg'=>'#f0fdf4','border'=>'#bbf7d0','icon'=>'🟢'],
        ] as $rg)
        @php $cnt = $byRisk[$rg['key']]->count(); @endphp
        <a href="?risk={{ $rg['key'] }}" style="background:{{ $cnt > 0 ? $rg['bg'] : 'var(--u-bg)' }};border:1px solid {{ $cnt > 0 ? $rg['border'] : 'var(--u-line)' }};border-radius:8px;padding:8px 4px;text-align:center;text-decoration:none;display:block;transition:all .15s;{{ $cnt === 0 ? 'opacity:.5;' : '' }}"
           onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
            <div style="font-size:var(--tx-lg);">{{ $rg['icon'] }}</div>
            <div style="font-size:var(--tx-2xl);font-weight:800;color:{{ $rg['color'] }};margin:4px 0 2px;line-height:1;">{{ $cnt }}</div>
            <div style="font-size:var(--tx-xs);font-weight:700;color:{{ $cnt > 0 ? $rg['color'] : 'var(--u-muted)' }};text-transform:uppercase;letter-spacing:.04em;">{{ $rg['label'] }}</div>
            @if($cnt > 0)<div style="font-size:var(--tx-xs);color:{{ $rg['color'] }};margin-top:2px;">filtrele →</div>@endif
        </a>
        @endforeach
    </div>
</div>

{{-- Lists: Aktif + Aday Öğrenci --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">

    {{-- Aktif Öğrenciler --}}
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;overflow:hidden;">
        <div style="padding:14px 16px;border-bottom:1px solid var(--u-line);display:flex;align-items:center;justify-content:space-between;cursor:pointer;user-select:none;" onclick="toggleAcc('acc-active',this)">
            <div style="font-weight:700;font-size:var(--tx-base);">Aktif Öğrenciler</div>
            <div style="display:flex;align-items:center;gap:8px;">
                <span class="badge info">{{ $active->count() }}</span>
                <span id="acc-active-caret" style="font-size:var(--tx-xs);color:var(--u-muted);transition:transform .2s;">▼</span>
            </div>
        </div>
        <div id="acc-active">
            @forelse($active as $row)
            @php
                $rl      = (string) ($row->risk_level ?? '');
                $riskCls = match($rl) { 'critical','high' => 'danger', 'medium','normal' => 'warn', default => 'ok' };
                $pl      = (string) ($row->payment_status ?? '');
                $payCls  = match($pl) { 'overdue' => 'danger', 'pending' => 'warn', 'paid','ok' => 'ok', default => '' };
                $initials = strtoupper(substr($row->student_id ?? 'S', 0, 2));
            @endphp
            @if($loop->index === 2)<div id="acc-active-more" style="display:none;">@endif
            <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);display:flex;align-items:center;gap:10px;transition:background .12s;"
                 onmouseover="this.style.background='var(--u-bg)'" onmouseout="this.style.background=''">
                <div style="width:34px;height:34px;border-radius:50%;background:var(--u-brand);color:#fff;display:flex;align-items:center;justify-content:center;font-size:var(--tx-xs);font-weight:700;flex-shrink:0;">{{ $initials }}</div>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:700;font-size:var(--tx-sm);color:var(--u-text);">{{ $row->student_id }}</div>
                    <div style="display:flex;gap:4px;flex-wrap:wrap;margin-top:3px;">
                        <span class="badge {{ $riskCls }}" style="font-size:var(--tx-xs);">{{ $riskLabelMap[$rl] ?? 'Düşük' }}</span>
                        @if($pl)<span class="badge {{ $payCls }}" style="font-size:var(--tx-xs);">{{ $payLabelMap[$pl] ?? $pl }}</span>@endif
                        @if($row->branch)<span class="badge" style="font-size:var(--tx-xs);">{{ $row->branch }}</span>@endif
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;gap:4px;flex-shrink:0;">
                    <a href="{{ url('/senior/students/' . $row->student_id) }}"
                       style="font-size:var(--tx-xs);padding:4px 10px;border:1px solid var(--u-line);border-radius:6px;background:var(--u-bg);color:var(--u-text);text-decoration:none;font-weight:600;text-align:center;">Detay</a>
                    <a href="/im"
                       style="font-size:var(--tx-xs);padding:4px 10px;border:1px solid #7c3aed33;border-radius:6px;background:#7c3aed08;color:#7c3aed;text-decoration:none;font-weight:600;text-align:center;">Mesaj</a>
                </div>
            </div>
            @if($loop->last && $loop->count > 2)</div>@endif
            @empty
            <div style="padding:32px 16px;text-align:center;color:var(--u-muted);">
                <div style="font-size:30px;margin-bottom:6px;">🎓</div>
                <div style="font-size:var(--tx-sm);">Aktif öğrenci bulunamadı.</div>
            </div>
            @endforelse
            @if($active->count() > 2)
            <div style="padding:10px 16px;text-align:center;border-top:1px solid var(--u-line);">
                <button onclick="toggleMore('acc-active-more','acc-active-morebtn')" id="acc-active-morebtn"
                        style="background:none;border:1px solid var(--u-line);border-radius:7px;padding:6px 16px;font-size:var(--tx-xs);font-weight:600;color:#7c3aed;cursor:pointer;">
                    + {{ $active->count() - 2 }} daha göster
                </button>
            </div>
            @endif
        </div>

        @if($archived->count() > 0)
        <details style="border-top:1px solid var(--u-line);">
            <summary style="padding:10px 16px;cursor:pointer;font-size:var(--tx-xs);color:var(--u-muted);list-style:none;display:flex;align-items:center;gap:6px;">
                <span>📦 Arşivlenenler</span>
                <span class="badge" style="font-size:var(--tx-xs);">{{ $archived->count() }}</span>
            </summary>
            <div style="opacity:.65;">
                @foreach($archived as $row)
                <div style="padding:10px 16px;border-top:1px solid var(--u-line);display:flex;align-items:center;gap:10px;">
                    <div style="width:30px;height:30px;border-radius:50%;background:#9db4cc;color:#fff;display:flex;align-items:center;justify-content:center;font-size:var(--tx-xs);font-weight:700;">{{ strtoupper(substr($row->student_id ?? 'S',0,2)) }}</div>
                    <div style="flex:1;">
                        <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-text);">{{ $row->student_id }}</div>
                        <div style="display:flex;gap:4px;margin-top:2px;">
                            <span class="badge" style="font-size:var(--tx-xs);">arşiv</span>
                            @if($row->branch)<span class="badge" style="font-size:var(--tx-xs);">{{ $row->branch }}</span>@endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </details>
        @endif
    </div>

    {{-- Aday Öğrenci Havuzu --}}
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;overflow:hidden;">
        <div style="padding:14px 16px;border-bottom:1px solid var(--u-line);display:flex;align-items:center;justify-content:space-between;cursor:pointer;user-select:none;" onclick="toggleAcc('acc-guest',this)">
            <div style="font-weight:700;font-size:var(--tx-base);">Aday Öğrenci Havuzu</div>
            <div style="display:flex;align-items:center;gap:8px;">
                <span class="badge info">{{ $guestPool->count() }}</span>
                <span id="acc-guest-caret" style="font-size:var(--tx-xs);color:var(--u-muted);transition:transform .2s;">▼</span>
            </div>
        </div>
        <div id="acc-guest">
            @forelse($guestPool as $guest)
            @php
                $gStatus = (string) ($guest->lead_status ?? $guest->status ?? 'new');
                $gStCls  = match($gStatus) { 'qualified' => 'ok', 'contacted' => 'info', 'new' => 'pending', 'lost' => 'danger', default => '' };
                $fullName    = trim(($guest->first_name ?? '') . ' ' . ($guest->last_name ?? ''));
                $displayName = $fullName ?: $guest->email;
                $initials    = $fullName
                    ? strtoupper(substr($guest->first_name ?? 'G', 0, 1) . substr($guest->last_name ?? '', 0, 1))
                    : 'GS';
                $appType = (string) ($guest->application_type ?? '');
            @endphp
            @if($loop->index === 2)<div id="acc-guest-more" style="display:none;">@endif
            <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);display:flex;align-items:center;gap:10px;transition:background .12s;"
                 onmouseover="this.style.background='var(--u-bg)'" onmouseout="this.style.background=''">
                <div style="width:34px;height:34px;border-radius:50%;background:#6c8cbf;color:#fff;display:flex;align-items:center;justify-content:center;font-size:var(--tx-xs);font-weight:700;flex-shrink:0;">{{ $initials }}</div>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:700;font-size:var(--tx-sm);color:var(--u-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $displayName }}</div>
                    @if($fullName)<div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:1px;">{{ $guest->email }}</div>@endif
                    <div style="display:flex;gap:4px;flex-wrap:wrap;margin-top:3px;">
                        <span class="badge {{ $gStCls }}" style="font-size:var(--tx-xs);">{{ $gStatusLabel[$gStatus] ?? $gStatus }}</span>
                        @if($appType)<span class="badge" style="font-size:var(--tx-xs);">{{ $appTypeLabel[$appType] ?? $appType }}</span>@endif
                    </div>
                </div>
                <a href="/senior/guests/{{ $guest->id }}"
                   style="font-size:var(--tx-xs);padding:4px 10px;border:1px solid var(--u-line);border-radius:6px;background:var(--u-bg);color:var(--u-text);text-decoration:none;font-weight:600;flex-shrink:0;">Detay</a>
            </div>
            @if($loop->last && $loop->count > 2)</div>@endif
            @empty
            <div style="padding:32px 16px;text-align:center;color:var(--u-muted);">
                <div style="font-size:30px;margin-bottom:6px;">👤</div>
                <div style="font-size:var(--tx-sm);">Aday Öğrenci havuzu boş.</div>
            </div>
            @endforelse
            @if($guestPool->count() > 2)
            <div style="padding:10px 16px;text-align:center;border-top:1px solid var(--u-line);">
                <button onclick="toggleMore('acc-guest-more','acc-guest-morebtn')" id="acc-guest-morebtn"
                        style="background:none;border:1px solid var(--u-line);border-radius:7px;padding:6px 16px;font-size:var(--tx-xs);font-weight:600;color:#7c3aed;cursor:pointer;">
                    + {{ $guestPool->count() - 2 }} daha göster
                </button>
            </div>
            @endif
        </div>
    </div>

</div>

<script>
function toggleAcc(id, header) {
    const body  = document.getElementById(id);
    const caret = document.getElementById(id + '-caret');
    const open  = body.style.display !== 'none';
    body.style.display = open ? 'none' : '';
    if (caret) caret.style.transform = open ? 'rotate(-90deg)' : '';
}
function toggleMore(moreId, btnId) {
    const more = document.getElementById(moreId);
    const btn  = document.getElementById(btnId);
    if (!more) return;
    const open = more.style.display !== 'none';
    more.style.display = open ? 'none' : '';
    if (btn) {
        const total = more.querySelectorAll('[style*="padding:12px"]').length;
        btn.textContent = open ? '+ ' + total + ' daha göster' : '▲ Gizle';
    }
}
</script>

@endsection
