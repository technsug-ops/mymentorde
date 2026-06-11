@extends('manager.layouts.app')

@section('title', 'Landing Sayfaları Envanteri')
@section('page_title', 'Public Landing Envanter')
@section('page_subtitle', 'Sisteme bağlı tüm public sayfalar — link unutulmasın · içerik kim sorumlu · nasıl düzenlenir')

@push('head')
<style>
.li-stats { display:grid; grid-template-columns:repeat(4, 1fr); gap:12px; margin-bottom:18px; }
@media(max-width:900px){ .li-stats{ grid-template-columns:repeat(2,1fr); } }
.li-stat { background:var(--u-card); border:1px solid var(--u-line); border-radius:10px; padding:14px 16px; }
.li-stat-num { font-size:28px; font-weight:800; color:var(--u-text); letter-spacing:-1px; line-height:1; }
.li-stat-lbl { font-size:11px; color:var(--u-muted); text-transform:uppercase; letter-spacing:.5px; margin-top:6px; }
.li-stat.warn { border-color:rgba(217,119,6,.4); background:rgba(217,119,6,.04); }
.li-stat.warn .li-stat-num { color:rgb(180,83,9); }
.li-stat.danger { border-color:rgba(220,38,38,.4); background:rgba(220,38,38,.04); }
.li-stat.danger .li-stat-num { color:rgb(185,28,28); }
.li-stat.ok { border-color:rgba(22,163,74,.3); }
.li-stat.ok .li-stat-num { color:#15803d; }

.li-alert { padding:14px 18px; border-radius:10px; margin-bottom:14px; font-size:13px; line-height:1.55; }
.li-alert.warn { background:rgba(217,119,6,.08); border:1px solid rgba(217,119,6,.3); color:rgb(120,53,15); }
.li-alert.danger { background:rgba(220,38,38,.06); border:1px solid rgba(220,38,38,.3); color:rgb(127,29,29); }
.li-alert code { background:rgba(0,0,0,.06); padding:1px 6px; border-radius:4px; font-size:12px; }
.li-alert ul { margin:8px 0 0 18px; padding:0; }
.li-alert li { margin-bottom:4px; }

.li-section { background:var(--u-card); border:1px solid var(--u-line); border-radius:12px; padding:18px 20px; margin-bottom:16px; }
.li-section h3 { margin:0 0 12px; font-size:14px; color:var(--u-text); text-transform:uppercase; letter-spacing:.5px; display:flex; align-items:center; gap:8px; }
.li-section .count-pill { background:var(--u-bg); padding:2px 10px; border-radius:999px; font-size:12px; font-weight:700; color:var(--u-muted); border:1px solid var(--u-line); }

.li-table { width:100%; border-collapse:collapse; font-size:13px; }
.li-table th { padding:10px 12px; text-align:left; font-size:11px; font-weight:700; color:var(--u-muted); text-transform:uppercase; letter-spacing:.4px; border-bottom:2px solid var(--u-line); background:var(--u-bg); }
.li-table td { padding:11px 12px; border-bottom:1px solid var(--u-line); color:var(--u-text); vertical-align:top; }
.li-table tr:last-child td { border-bottom:0; }
.li-table tr:hover td { background:var(--u-bg); }

.li-name { font-weight:700; }
.li-path { font-family:monospace; font-size:12px; background:var(--u-bg); padding:2px 6px; border-radius:5px; display:inline-block; color:var(--u-text); }
.li-meta { font-size:11.5px; color:var(--u-muted); margin-top:3px; line-height:1.5; }
.li-tag { display:inline-block; padding:2px 8px; border-radius:999px; font-size:10.5px; font-weight:700; margin:2px 2px 0 0; }
.li-tag.tier-critical { background:rgba(220,38,38,.1); color:rgb(185,28,28); }
.li-tag.tier-important { background:rgba(217,119,6,.1); color:rgb(180,83,9); }
.li-tag.tier-nice { background:rgba(100,116,139,.12); color:#475569; }
.li-tag.owner { background:rgba(126,88,191,.1); color:#5a3a8d; }

.li-actions { display:flex; gap:6px; flex-wrap:wrap; }
.li-btn { padding:5px 10px; font-size:11.5px; font-weight:600; border-radius:6px; border:1px solid var(--u-line); background:var(--u-bg); color:var(--u-text); cursor:pointer; text-decoration:none; }
.li-btn:hover { border-color:var(--u-brand); }
.li-btn.primary { background:var(--u-brand,#2563eb); color:white; border-color:var(--u-brand); }

.li-status-active { color:#15803d; font-weight:700; }
.li-status-inactive { color:#94a3b8; }

.li-type-emoji { font-size:18px; margin-right:6px; display:inline-flex; align-items:center; }
.li-stat-lbl-row { display:inline-flex; align-items:center; gap:6px; }
</style>
@endpush

@section('content')
<div class="container-fluid">

    <div class="li-stats">
        <div class="li-stat ok">
            <div class="li-stat-num">{{ $totals['matched'] }}</div>
            <div class="li-stat-lbl li-stat-lbl-row"><x-icon name="circle-check" size="14" aria-label="sağlıklı" /> Sağlıklı (Eşleşen)</div>
        </div>
        <div class="li-stat">
            <div class="li-stat-num">{{ $totals['all'] }}</div>
            <div class="li-stat-lbl">Toplam Registry</div>
        </div>
        <div class="li-stat warn">
            <div class="li-stat-num">{{ $totals['missing'] }}</div>
            <div class="li-stat-lbl li-stat-lbl-row"><x-icon name="alert-triangle" size="14" aria-label="eksik" /> Eksik (Route var, registry yok)</div>
        </div>
        <div class="li-stat danger">
            <div class="li-stat-num">{{ $totals['dead'] }}</div>
            <div class="li-stat-lbl li-stat-lbl-row"><x-icon name="x-circle" size="14" aria-label="ölü" /> Ölü (Registry var, route yok)</div>
        </div>
    </div>

    @if(count($missing) > 0)
        <div class="li-alert warn">
            <strong style="display:inline-flex;align-items:center;gap:6px;"><x-icon name="alert-triangle" size="16" aria-label="uyarı" /> {{ count($missing) }} eksik registry kaydı</strong> — sistemde public route var ama <code>config/public_landings.php</code>'a kaydedilmemiş. Yeni eklediğin sayfalar burada listelenmeli ki diğer manager'lar da bilsin:
            <ul>
                @foreach(array_slice($missing, 0, 15) as $path)
                    <li><code>{{ $path }}</code> &mdash; <a href="{{ url($path) }}" target="_blank">aç</a></li>
                @endforeach
                @if(count($missing) > 15)
                    <li>... ve {{ count($missing) - 15 }} tane daha</li>
                @endif
            </ul>
        </div>
    @endif

    @if(count($dead) > 0)
        <div class="li-alert danger">
            <strong style="display:inline-flex;align-items:center;gap:6px;"><x-icon name="x-circle" size="16" aria-label="ölü kayıt" /> {{ count($dead) }} ölü registry kaydı</strong> — config'te tanımlı ama route artık yok (silinmiş?). Temizle veya tekrar route ekle:
            <ul>
                @foreach($dead as $entry)
                    <li><code>{{ $entry['path'] }}</code> — {{ $entry['name'] ?? '' }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $typeMeta = [
            'marketing' => ['icon' => 'megaphone',      'label' => 'Marketing / Tanıtım'],
            'form'      => ['icon' => 'clipboard-list', 'label' => 'Form / Conversion'],
            'widget'    => ['icon' => 'package',        'label' => 'Public Widget'],
            'legal'     => ['icon' => 'scale',          'label' => 'Yasal'],
            'utility'   => ['icon' => 'wrench',         'label' => 'Utility'],
        ];
    @endphp

    @foreach(['marketing', 'form', 'widget', 'legal', 'utility'] as $type)
        @if(! empty($grouped[$type]))
            <div class="li-section">
                <h3>
                    <span class="li-type-emoji"><x-icon name="{{ $typeMeta[$type]['icon'] }}" size="18" aria-label="{{ $typeMeta[$type]['label'] }}" /></span>
                    {{ $typeMeta[$type]['label'] }}
                    <span class="count-pill">{{ count($grouped[$type]) }}</span>
                </h3>
                <table class="li-table">
                    <thead>
                        <tr>
                            <th style="width:24%">Sayfa</th>
                            <th>URL</th>
                            <th>Açıklama / Düzenleme</th>
                            <th>Etiket</th>
                            <th>Durum</th>
                            <th>Aksiyon</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($grouped[$type] as $entry)
                        <tr>
                            <td>
                                <div class="li-name">{{ $entry['name'] }}</div>
                                <div class="li-meta">
                                    <span class="li-tag tier-{{ $entry['tier'] ?? 'nice' }}">{{ ucfirst($entry['tier'] ?? 'nice') }}</span>
                                    <span class="li-tag owner">{{ $entry['owner'] ?? '—' }}</span>
                                </div>
                            </td>
                            <td><span class="li-path">{{ $entry['path'] }}</span></td>
                            <td style="max-width:340px;">
                                <div>{{ $entry['description'] ?? '' }}</div>
                                @if(! empty($entry['edit_notes']))
                                    <div class="li-meta"><strong>Düzenle:</strong> {{ $entry['edit_notes'] }}</div>
                                @endif
                            </td>
                            <td>
                                @foreach((array) ($entry['tags'] ?? []) as $tag)
                                    <span class="li-tag" style="background:var(--u-bg); color:var(--u-muted);">{{ $tag }}</span>
                                @endforeach
                            </td>
                            <td>
                                @if($entry['is_active'] ?? true)
                                    <span class="li-status-active">● Aktif</span>
                                @else
                                    <span class="li-status-inactive">○ Pasif</span>
                                @endif
                            </td>
                            <td>
                                <div class="li-actions">
                                    @if(! str_contains((string) $entry['path'], '{'))
                                        <a class="li-btn" href="{{ url($entry['path']) }}" target="_blank" style="display:inline-flex;align-items:center;gap:5px;"><x-icon name="external-link" size="13" aria-label="aç" /> Aç</a>
                                    @else
                                        <span class="li-btn" style="opacity:.5;cursor:default;">{slug}</span>
                                    @endif
                                    @if(! empty($entry['edit_route']))
                                        @php
                                            try { $editUrl = route($entry['edit_route']); } catch (\Throwable $e) { $editUrl = null; }
                                        @endphp
                                        @if($editUrl)
                                            <a class="li-btn primary" href="{{ $editUrl }}" style="display:inline-flex;align-items:center;gap:5px;"><x-icon name="settings" size="13" aria-label="düzenle" /> Düzenle</a>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endforeach

    <div class="li-alert" style="background:var(--u-bg); border:1px solid var(--u-line); color:var(--u-muted);">
        <strong style="display:inline-flex;align-items:center;gap:6px;"><x-icon name="pencil" size="14" aria-label="not" /> Yeni public landing eklediğinde:</strong>
        <ul>
            <li><code>config/public_landings.php</code>'a entry ekle (path, name, type, description, edit_notes, tier, owner)</li>
            <li>Bu sayfayı yenile — eksik uyarısı kaybolacak</li>
            <li>Tasarım için <strong>brandbook</strong> (#7e58bf mor + Space Grotesk + cream) uygula</li>
        </ul>
    </div>
</div>
@endsection
