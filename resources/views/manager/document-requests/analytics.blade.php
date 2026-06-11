@extends('manager.layouts.app')
@section('title', 'Belge Talep Analytics')
@section('page_title', 'Belge Talep Analytics')

@section('content')
@php
    /** @var array $kpi */
    /** @var array $funnel */
    /** @var \Illuminate\Support\Collection $byCategory */
    /** @var \Illuminate\Support\Collection $monthlyTrend */
    /** @var array $reminderImpact */
    /** @var \Illuminate\Support\Collection $recent */
    /** @var \Illuminate\Support\Collection $categories */
    /** @var array $targetTypes */
    /** @var array $statusList */
    /** @var array $filters */
    $kpiCards = [
        [
            'label' => 'Toplam token',
            'value' => number_format((int) $kpi['total_tokens'], 0, ',', '.'),
            'sub'   => 'Seçilen aralıkta oluşturulan',
            'icon'  => 'file-text',
            'tone'  => 'indigo',
        ],
        [
            'label' => 'Yüklenen belge',
            'value' => number_format((int) $kpi['uploaded_tokens'], 0, ',', '.'),
            'sub'   => 'Aday/öğrenci tarafından yüklendi',
            'icon'  => 'check',
            'tone'  => 'emerald',
        ],
        [
            'label' => 'Dönüşüm oranı',
            'value' => '%' . number_format((float) $kpi['conversion_rate'], 1, ',', '.'),
            'sub'   => 'Yüklenen / toplam',
            'icon'  => 'trending-up',
            'tone'  => 'sky',
        ],
        [
            'label' => 'Ortalama yükleme süresi',
            'value' => $kpi['avg_upload_minutes'] > 0 ? number_format((float) $kpi['avg_upload_minutes'], 1, ',', '.') . ' dk' : '—',
            'sub'   => 'Link açıldığından yüklemeye',
            'icon'  => 'clock',
            'tone'  => 'amber',
        ],
        [
            'label' => 'Hatırlatma dönüşümü',
            'value' => '%' . number_format((float) $kpi['reminder_conversion'], 1, ',', '.'),
            'sub'   => $kpi['reminders_sent'] . ' hatırlatma gönderildi',
            'icon'  => 'mail',
            'tone'  => 'rose',
        ],
        [
            'label' => 'Süresi dolan',
            'value' => number_format((int) $kpi['expired_tokens'], 0, ',', '.'),
            'sub'   => 'Yüklenmedi + süre doldu',
            'icon'  => 'x',
            'tone'  => 'slate',
        ],
    ];

    $toneStyles = [
        'indigo'  => ['#eef2ff', '#4f46e5'],
        'emerald' => ['#ecfdf5', '#059669'],
        'sky'     => ['#f0f9ff', '#0284c7'],
        'amber'   => ['#fffbeb', '#d97706'],
        'rose'    => ['#fff1f2', '#e11d48'],
        'slate'   => ['#f1f5f9', '#475569'],
    ];

    $statusLabels = [
        'uploaded' => ['Yüklendi', '#059669', '#ecfdf5'],
        'pending'  => ['Bekliyor', '#d97706', '#fffbeb'],
        'expired'  => ['Süresi doldu', '#e11d48', '#fff1f2'],
    ];
@endphp

{{-- Filtre bar --}}
<form method="GET" class="card" style="margin-bottom:18px;padding:14px;display:flex;gap:10px;flex-wrap:wrap;align-items:end;">
    <div>
        <label class="u-muted" style="font-size:11px;letter-spacing:.05em;display:block;margin-bottom:4px;">ARALIK</label>
        <select name="range" style="padding:7px 9px;border:1px solid var(--u-line);border-radius:6px;min-width:130px;">
            @foreach(['7d'=>'Son 7 gün','30d'=>'Son 30 gün','90d'=>'Son 90 gün','180d'=>'Son 6 ay','365d'=>'Son 12 ay'] as $k => $label)
                <option value="{{ $k }}" @selected(($filters['range'] ?? '30d') === $k)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="u-muted" style="font-size:11px;letter-spacing:.05em;display:block;margin-bottom:4px;">BAŞLANGIÇ</label>
        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" style="padding:7px 9px;border:1px solid var(--u-line);border-radius:6px;">
    </div>
    <div>
        <label class="u-muted" style="font-size:11px;letter-spacing:.05em;display:block;margin-bottom:4px;">BİTİŞ</label>
        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" style="padding:7px 9px;border:1px solid var(--u-line);border-radius:6px;">
    </div>
    <div>
        <label class="u-muted" style="font-size:11px;letter-spacing:.05em;display:block;margin-bottom:4px;">KATEGORİ</label>
        <select name="category" style="padding:7px 9px;border:1px solid var(--u-line);border-radius:6px;min-width:180px;">
            <option value="">Tüm kategoriler</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->code }}" @selected(($filters['category'] ?? '') === $cat->code)>{{ $cat->name_tr ?? $cat->code }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="u-muted" style="font-size:11px;letter-spacing:.05em;display:block;margin-bottom:4px;">DURUM</label>
        <select name="status" style="padding:7px 9px;border:1px solid var(--u-line);border-radius:6px;min-width:140px;">
            <option value="">Tüm durumlar</option>
            @foreach($statusList as $k => $label)
                <option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="u-muted" style="font-size:11px;letter-spacing:.05em;display:block;margin-bottom:4px;">HEDEF TİP</label>
        <select name="target_type" style="padding:7px 9px;border:1px solid var(--u-line);border-radius:6px;min-width:160px;">
            <option value="">Tüm tipler</option>
            @foreach($targetTypes as $k => $label)
                <option value="{{ $k }}" @selected(($filters['target_type'] ?? '') === $k)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <button class="btn" type="submit" style="padding:8px 16px;display:inline-flex;align-items:center;gap:6px;">
        <x-icon name="bar-chart-3" size="16" /> Uygula
    </button>
    <a href="{{ url('/manager/document-requests/analytics') }}" class="u-muted" style="font-size:12px;text-decoration:none;align-self:center;">Sıfırla</a>
    <a href="{{ url('/manager/document-requests/analytics/export?' . http_build_query($filters)) }}"
       class="btn" style="padding:8px 16px;background:#0f172a;color:#fff;display:inline-flex;align-items:center;gap:6px;margin-left:auto;">
        <x-icon name="file-text" size="16" /> CSV indir
    </a>
</form>

{{-- KPI strip --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(190px, 1fr));gap:12px;margin-bottom:20px;">
    @foreach($kpiCards as $card)
        @php [$bg, $fg] = $toneStyles[$card['tone']] ?? ['#f1f5f9', '#0f172a']; @endphp
        <div class="card" style="padding:16px;display:flex;gap:12px;align-items:flex-start;">
            <div style="width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:{{ $bg }};color:{{ $fg }};flex-shrink:0;">
                <x-icon name="{{ $card['icon'] }}" size="20" />
            </div>
            <div style="min-width:0;">
                <div class="u-muted" style="font-size:11px;letter-spacing:.05em;text-transform:uppercase;">{{ $card['label'] }}</div>
                <div style="font-size:22px;font-weight:800;margin-top:2px;line-height:1.1;">{{ $card['value'] }}</div>
                <div class="u-muted" style="font-size:11px;margin-top:2px;">{{ $card['sub'] }}</div>
            </div>
        </div>
    @endforeach
</div>

{{-- Funnel + Reminder Impact (2 column) --}}
<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:16px;margin-bottom:20px;">
    {{-- Funnel --}}
    <div class="card" style="padding:18px;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
            <x-icon name="bar-chart-3" size="18" />
            <div style="font-weight:700;font-size:15px;">Dönüşüm hunisi</div>
        </div>
        @php $maxCount = max(1, collect($funnel)->max('count')); @endphp
        @foreach($funnel as $step)
        @php
            $widthPct = $maxCount > 0 ? ($step['count'] / $maxCount) * 100 : 0;
            $stepIcons = ['created'=>'send','sent'=>'mail','reminded'=>'clock','uploaded'=>'check','approved'=>'shield-check'];
            $stepColors = ['#4f46e5','#0284c7','#d97706','#059669','#7c3aed'];
            $colorIdx = $loop->index;
        @endphp
        <div style="margin-bottom:12px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px;">
                <div style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;">
                    <x-icon name="{{ $stepIcons[$step['key']] ?? 'file-text' }}" size="14" />
                    {{ $step['label'] }}
                </div>
                <div style="font-size:13px;">
                    <strong>{{ number_format($step['count'], 0, ',', '.') }}</strong>
                    <span class="u-muted" style="margin-left:8px;">%{{ number_format($step['pct'], 1, ',', '.') }}</span>
                </div>
            </div>
            <div style="height:12px;background:#f1f5f9;border-radius:999px;overflow:hidden;">
                <div style="height:100%;width:{{ max(2, $widthPct) }}%;background:{{ $stepColors[$colorIdx] ?? '#4f46e5' }};border-radius:999px;transition:width .3s;"></div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Reminder Impact --}}
    <div class="card" style="padding:18px;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
            <x-icon name="clock" size="18" />
            <div style="font-weight:700;font-size:15px;">Hatırlatma etkisi</div>
        </div>
        @foreach($reminderImpact as $key => $imp)
            @php
                $accent = $key === 'none' ? '#94a3b8' : ($key === 'first' ? '#0284c7' : '#7c3aed');
            @endphp
            <div style="border:1px solid var(--u-line);border-radius:10px;padding:12px;margin-bottom:10px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                    <div style="font-size:13px;font-weight:600;">{{ $imp['label'] }}</div>
                    <div style="font-size:18px;font-weight:800;color:{{ $accent }};">%{{ number_format($imp['pct'], 1, ',', '.') }}</div>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:11px;color:#64748b;">
                    <span>{{ $imp['uploaded'] }} / {{ $imp['total'] }} yüklendi</span>
                    <span>dönüşüm</span>
                </div>
                <div style="height:6px;background:#f1f5f9;border-radius:999px;margin-top:6px;overflow:hidden;">
                    <div style="height:100%;width:{{ max(2, $imp['pct']) }}%;background:{{ $accent }};border-radius:999px;"></div>
                </div>
            </div>
        @endforeach
    </div>
</div>

{{-- Aylık trend (inline SVG bar chart) --}}
<div class="card" style="padding:18px;margin-bottom:20px;">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
        <x-icon name="calendar" size="18" />
        <div style="font-weight:700;font-size:15px;">Aylık trend (son 12 ay)</div>
        <div class="u-muted" style="font-size:11px;margin-left:auto;">
            <span style="display:inline-block;width:10px;height:10px;background:#cbd5e1;border-radius:2px;vertical-align:middle;margin-right:4px;"></span>Toplam token
            <span style="display:inline-block;width:10px;height:10px;background:#059669;border-radius:2px;vertical-align:middle;margin:0 4px 0 10px;"></span>Yüklenen
        </div>
    </div>
    @php $tMax = max(1, (int) $trendMax); @endphp
    <div style="display:grid;grid-template-columns:repeat({{ $monthlyTrend->count() }}, 1fr);gap:6px;align-items:end;height:170px;padding:0 4px;">
        @foreach($monthlyTrend as $m)
            @php
                $totalH    = $tMax > 0 ? ($m['total'] / $tMax) * 100 : 0;
                $uploadedH = $tMax > 0 ? ($m['uploaded'] / $tMax) * 100 : 0;
            @endphp
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%;position:relative;" title="{{ $m['label'] }}: {{ $m['uploaded'] }}/{{ $m['total'] }} (%{{ $m['pct'] }})">
                <div style="position:relative;width:100%;max-width:32px;height:100%;display:flex;align-items:flex-end;justify-content:center;">
                    <div style="position:absolute;bottom:0;width:100%;height:{{ max(1, $totalH) }}%;background:#cbd5e1;border-radius:4px 4px 0 0;"></div>
                    <div style="position:absolute;bottom:0;width:60%;height:{{ max(1, $uploadedH) }}%;background:#059669;border-radius:4px 4px 0 0;"></div>
                </div>
            </div>
        @endforeach
    </div>
    <div style="display:grid;grid-template-columns:repeat({{ $monthlyTrend->count() }}, 1fr);gap:6px;margin-top:6px;">
        @foreach($monthlyTrend as $m)
            <div style="text-align:center;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;">{{ $m['label'] }}</div>
        @endforeach
    </div>
</div>

{{-- Kategori bazlı tablo + Son aktivite (2 column) --}}
<div style="display:grid;grid-template-columns:1.2fr 1fr;gap:16px;margin-bottom:20px;">
    <div class="card" style="padding:18px;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
            <x-icon name="file-text" size="18" />
            <div style="font-weight:700;font-size:15px;">Kategori bazlı performans</div>
        </div>
        @if($byCategory->isEmpty())
            <div class="u-muted" style="font-size:13px;padding:20px;text-align:center;">Bu aralıkta token yok.</div>
        @else
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid var(--u-line);">
                    <th style="text-align:left;padding:8px 10px;font-weight:600;">Kategori</th>
                    <th style="text-align:right;padding:8px 10px;font-weight:600;">Toplam</th>
                    <th style="text-align:right;padding:8px 10px;font-weight:600;">Yüklendi</th>
                    <th style="text-align:right;padding:8px 10px;font-weight:600;">Bekliyor</th>
                    <th style="text-align:right;padding:8px 10px;font-weight:600;">Süre doldu</th>
                    <th style="text-align:right;padding:8px 10px;font-weight:600;">Dönüşüm</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byCategory as $row)
                <tr style="border-bottom:1px solid var(--u-line);">
                    <td style="padding:8px 10px;font-weight:500;">{{ $row->category_name ?: $row->category_code }}</td>
                    <td style="padding:8px 10px;text-align:right;">{{ $row->total }}</td>
                    <td style="padding:8px 10px;text-align:right;color:#059669;">{{ $row->uploaded }}</td>
                    <td style="padding:8px 10px;text-align:right;color:#d97706;">{{ $row->pending }}</td>
                    <td style="padding:8px 10px;text-align:right;color:#e11d48;">{{ $row->expired }}</td>
                    <td style="padding:8px 10px;text-align:right;font-weight:700;color:{{ $row->conversion_rate >= 60 ? '#059669' : ($row->conversion_rate >= 30 ? '#d97706' : '#e11d48') }};">
                        %{{ number_format($row->conversion_rate, 1, ',', '.') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>

    <div class="card" style="padding:18px;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
            <x-icon name="users" size="18" />
            <div style="font-weight:700;font-size:15px;">Son 20 aktivite</div>
        </div>
        @if($recent->isEmpty())
            <div class="u-muted" style="font-size:13px;padding:20px;text-align:center;">Henüz aktivite yok.</div>
        @else
        <div style="max-height:420px;overflow-y:auto;">
            @foreach($recent as $r)
                @php [$stLabel, $stFg, $stBg] = $statusLabels[$r['status']] ?? ['—', '#475569', '#f1f5f9']; @endphp
                <div style="display:flex;gap:10px;padding:10px 0;border-bottom:1px solid var(--u-line);">
                    <div style="width:8px;height:8px;border-radius:999px;margin-top:8px;background:{{ $stFg }};flex-shrink:0;"></div>
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;justify-content:space-between;gap:6px;font-size:13px;font-weight:600;">
                            <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $r['target_name'] }}</div>
                            <span style="font-size:10px;padding:2px 7px;border-radius:999px;background:{{ $stBg }};color:{{ $stFg }};font-weight:600;flex-shrink:0;">{{ $stLabel }}</span>
                        </div>
                        <div class="u-muted" style="font-size:11px;display:flex;gap:8px;flex-wrap:wrap;margin-top:2px;">
                            <span>{{ $r['target_label'] }}</span>
                            <span>·</span>
                            <span>{{ $r['category_name'] }}</span>
                            @if($r['reminded'])
                                <span>·</span>
                                <span style="display:inline-flex;align-items:center;gap:2px;color:#0284c7;"><x-icon name="clock" size="11" /> Hatırlatıldı</span>
                            @endif
                        </div>
                        <div class="u-muted" style="font-size:11px;margin-top:2px;">
                            {{ optional($r['created_at'])->diffForHumans() }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection
