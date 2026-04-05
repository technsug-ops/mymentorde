@extends('marketing-admin.layouts.app')

@section('topbar-actions')
<a class="btn alt" href="/mktg-admin/workflows" style="font-size:var(--tx-xs);padding:6px 12px;">← Listele</a>
<a class="btn alt" href="/mktg-admin/workflows/{{ $workflow->id }}/builder" style="font-size:var(--tx-xs);padding:6px 12px;">Builder</a>
<a class="btn" href="/mktg-admin/workflows/{{ $workflow->id }}/enrollments" style="font-size:var(--tx-xs);padding:6px 12px;">Enrollments</a>
<a class="btn alt" href="/mktg-admin/workflows/{{ $workflow->id }}/analytics" style="font-size:var(--tx-xs);padding:6px 12px;">Analytics</a>
@endsection

@section('title', 'Enrollments — ' . $workflow->name)
@section('page_subtitle', $workflow->name . ' — aday kayıt ve ilerleme takibi')

@section('content')
@php
$total     = $enrollments->total();
$cActive   = $enrollments->getCollection()->whereIn('status', ['active','waiting'])->count();
$cDone     = $enrollments->getCollection()->where('status','completed')->count();
$cExited   = $enrollments->getCollection()->where('status','exited')->count();
$cErrored  = $enrollments->getCollection()->where('status','errored')->count();

$statusLabels = [
    'active'    => 'Aktif',
    'waiting'   => 'Bekliyor',
    'completed' => 'Tamamlandı',
    'exited'    => 'Çıkıldı',
    'errored'   => 'Hata',
];
$statusBadge = [
    'active'    => 'info',
    'waiting'   => 'pending',
    'completed' => 'ok',
    'exited'    => '',
    'errored'   => 'danger',
];
@endphp

<style>
.pl-stats { display:flex; gap:0; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; overflow:hidden; background:var(--u-card,#fff); }
.pl-stat  { flex:1; padding:12px 16px; border-right:1px solid var(--u-line,#e2e8f0); min-width:0; }
.pl-stat:last-child { border-right:none; }
.pl-val   { font-size:22px; font-weight:700; color:var(--u-brand,#1e40af); line-height:1.1; }
.pl-lbl   { font-size:11px; color:var(--u-muted,#64748b); margin-top:2px; }

.tl-wrap { overflow-x:auto; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; }
.tl-tbl  { width:100%; border-collapse:collapse; min-width:820px; }
.tl-tbl th { text-align:left; padding:9px 12px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--u-muted,#64748b); background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,var(--u-card,#fff)); border-bottom:1px solid var(--u-line,#e2e8f0); white-space:nowrap; }
.tl-tbl td { padding:9px 12px; font-size:13px; border-bottom:1px solid var(--u-line,#e2e8f0); vertical-align:middle; }
.tl-tbl tr:last-child td { border-bottom:none; }
.tl-tbl tbody tr:hover { background:color-mix(in srgb,var(--u-brand,#1e40af) 3%,var(--u-card,#fff)); }

.meta-pill { display:inline-block; background:color-mix(in srgb,var(--u-brand,#1e40af) 8%,var(--u-card,#fff)); color:var(--u-brand,#1e40af); border-radius:6px; padding:2px 7px; font-size:11px; margin-right:3px; margin-bottom:2px; }

.enr-expand { display:none; }
.enr-expand.open { display:table-row; }

details summary::-webkit-details-marker { display:none; }
details summary { outline:none; list-style:none; }
.det-sum { display:flex; justify-content:space-between; align-items:center; cursor:pointer; }
.det-sum h3 { margin:0; font-size:14px; font-weight:700; }
.det-chev { font-size:11px; color:var(--u-muted,#64748b); transition:transform .2s; }
details[open] .det-chev { transform:rotate(180deg); }
details[open] .det-sum { margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--u-line,#e2e8f0); }
</style>

<div style="display:grid;gap:12px;">

    {{-- KPI Bar --}}
    <div class="pl-stats">
        <div class="pl-stat">
            <div class="pl-val">{{ number_format($total) }}</div>
            <div class="pl-lbl">Toplam Kayıt</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val" style="color:var(--u-brand,#1e40af);">{{ $cActive }}</div>
            <div class="pl-lbl">Aktif / Bekliyor</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val" style="color:var(--u-ok,#16a34a);">{{ $cDone }}</div>
            <div class="pl-lbl">Tamamlandı</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val" style="color:var(--u-muted,#64748b);">{{ $cExited }}</div>
            <div class="pl-lbl">Çıkıldı</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val" style="color:var(--u-danger,#dc2626);">{{ $cErrored }}</div>
            <div class="pl-lbl">Hata</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val" style="font-size:var(--tx-lg);color:{{ $total > 0 && ($cDone/$total*100) >= 50 ? 'var(--u-ok,#16a34a)' : 'var(--u-warn,#d97706)' }};">
                {{ $total > 0 ? round($cDone / $total * 100, 1) : 0 }}%
            </div>
            <div class="pl-lbl">Tamamlanma Oranı</div>
        </div>
    </div>

    {{-- Tablo --}}
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);flex-wrap:wrap;gap:8px;">
            <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);">
                Enrollment Listesi
                <span style="font-weight:400;font-size:var(--tx-xs);margin-left:8px;color:var(--u-muted,#64748b);">{{ $workflow->name }}</span>
            </div>
            <form method="GET" action="/mktg-admin/workflows/{{ $workflow->id }}/enrollments"
                  style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                <select name="status" style="height:34px;padding:0 10px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;font-size:var(--tx-xs);background:var(--u-card,#fff);color:var(--u-text,#0f172a);outline:none;appearance:auto;">
                    <option value="">Tüm durumlar</option>
                    @foreach($statusLabels as $val => $lbl)
                    <option value="{{ $val }}" @selected(request('status') === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn" style="height:34px;font-size:var(--tx-xs);padding:0 14px;">Filtrele</button>
                <a href="/mktg-admin/workflows/{{ $workflow->id }}/enrollments" class="btn alt" style="height:34px;font-size:var(--tx-xs);padding:0 12px;display:flex;align-items:center;">Temizle</a>
            </form>
        </div>

        <div class="tl-wrap">
            <table class="tl-tbl" id="enrTable">
                <thead><tr>
                    <th style="width:46px;">ID</th>
                    <th>Aday</th>
                    <th style="width:80px;">Durum</th>
                    <th style="width:120px;">Aktif Node</th>
                    <th style="width:130px;">Kayıt Tarihi</th>
                    <th style="width:130px;">Sonraki Kontrol</th>
                    <th style="width:130px;">Tamamlandı</th>
                    <th style="width:44px;"></th>
                </tr></thead>
                <tbody>
                @forelse($enrollments as $enr)
                @php
                    $guest      = $enr->guestApplication;
                    $fullName   = $guest ? trim($guest->first_name . ' ' . $guest->last_name) : '—';
                    $badgeClass = $statusBadge[$enr->status] ?? '';
                    $meta       = is_array($enr->metadata) ? $enr->metadata : [];
                    $hasMeta    = !empty($meta);
                @endphp
                <tr style="cursor:{{ $hasMeta ? 'pointer' : 'default' }};"
                    onclick="{{ $hasMeta ? 'enrToggle('.$enr->id.',this)' : '' }}">
                    <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);font-family:ui-monospace,monospace;">#{{ $enr->id }}</td>
                    <td>
                        <strong style="font-size:var(--tx-sm);">{{ $fullName }}</strong>
                        @if($guest)
                        <br><span style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
                            {{ $guest->email ?? '' }}
                            @if($guest->phone) · {{ $guest->phone }} @endif
                        </span>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $badgeClass }}" style="font-size:var(--tx-xs);">
                            {{ $statusLabels[$enr->status] ?? $enr->status }}
                        </span>
                        @if($enr->exit_reason)
                        <br><span style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $enr->exit_reason }}</span>
                        @endif
                    </td>
                    <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
                        @if($enr->current_node_id)
                        <span class="meta-pill">#{{ $enr->current_node_id }}</span>
                        @else
                        <span style="color:var(--u-muted,#64748b);">—</span>
                        @endif
                    </td>
                    <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
                        {{ $enr->enrolled_at ? \Illuminate\Support\Carbon::parse($enr->enrolled_at)->format('d.m.Y H:i') : '—' }}
                    </td>
                    <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
                        {{ $enr->next_check_at ? \Illuminate\Support\Carbon::parse($enr->next_check_at)->format('d.m.Y H:i') : '—' }}
                    </td>
                    <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
                        @if($enr->completed_at)
                        <span style="color:var(--u-ok,#16a34a);">{{ \Illuminate\Support\Carbon::parse($enr->completed_at)->format('d.m.Y H:i') }}</span>
                        @else
                        —
                        @endif
                    </td>
                    <td style="text-align:center;">
                        @if($hasMeta)
                        <span class="det-chev" id="chev-{{ $enr->id }}" style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);transition:transform .2s;">▼</span>
                        @endif
                    </td>
                </tr>
                @if($hasMeta)
                <tr class="enr-expand" id="enrExp-{{ $enr->id }}">
                    <td colspan="8" style="background:color-mix(in srgb,var(--u-brand,#1e40af) 3%,var(--u-card,#fff));padding:12px 16px;">
                        <div style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted,#64748b);margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em;">Metadata</div>
                        <div style="display:flex;flex-wrap:wrap;gap:8px;">
                        @foreach($meta as $k => $v)
                            <div style="background:var(--u-card,#fff);border:1px solid var(--u-line,#e2e8f0);border-radius:8px;padding:6px 10px;min-width:140px;">
                                <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);text-transform:uppercase;letter-spacing:.04em;">{{ $k }}</div>
                                <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-text,#0f172a);">{{ is_array($v) ? json_encode($v) : $v }}</div>
                            </div>
                        @endforeach
                        </div>
                    </td>
                </tr>
                @endif
                @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:40px;color:var(--u-muted,#64748b);">
                        <div style="font-size:var(--tx-2xl);margin-bottom:8px;">📭</div>
                        Bu workflow için henüz enrollment yok.
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:12px;">{{ $enrollments->links() }}</div>
    </div>

    {{-- Rehber --}}
    <details class="card">
        <summary class="det-sum">
            <h3>Kullanım Rehberi</h3>
            <span class="det-chev">▼</span>
        </summary>
        <ol style="margin:0;padding-left:18px;font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.7;">
            <li>Her satır, bir adayın bu workflow'a kaydını temsil eder.</li>
            <li><strong>Aktif Node</strong>, adayın şu an hangi adımda beklediğini gösterir.</li>
            <li>Satıra tıkla → metadata detaylarını (e-posta açılması, tıklama bilgisi vb.) gör.</li>
            <li>Durum filtresi ile hatalı veya takılı kayıtları izole et.</li>
            <li><strong>Tamamlanma Oranı</strong> = Completed / Total — %50 altı sarı, %50 üstü yeşil.</li>
        </ol>
    </details>

</div>

<script>
function enrToggle(id, row) {
    var exp  = document.getElementById('enrExp-' + id);
    var chev = document.getElementById('chev-' + id);
    if (!exp) return;
    var isOpen = exp.classList.contains('open');
    exp.classList.toggle('open', !isOpen);
    if (chev) chev.style.transform = isOpen ? '' : 'rotate(180deg)';
}
</script>

<details class="card" style="margin-top:0;">
    <summary class="det-sum">
        <h3>📖 Kullanım Kılavuzu — Workflow Kayıtları</h3>
        <span class="det-chev">▼</span>
    </summary>
    <div style="padding-top:12px;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div>
            <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">📋 Durum Açıklamaları</strong>
            <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                <li><strong>active/waiting:</strong> Workflow ilerliyor — bir sonraki node bekleniyor</li>
                <li><strong>completed:</strong> Tüm node'lar başarıyla tamamlandı</li>
                <li><strong>exited:</strong> Koşul sağlanmadı veya manual olarak çıkarıldı</li>
                <li><strong>errored:</strong> Teknik hata — log detayını incele ve re-enroll et</li>
            </ul>
        </div>
        <div>
            <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">🔍 İzleme</strong>
            <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                <li>Satıra tıkla → execution log ve node tarihçesi açılır</li>
                <li>Durum filtresini kullan → sadece "errored" kayıtları görüntüle</li>
                <li>Yüksek exit oranı → workflow koşulları çok kısıtlayıcı olabilir</li>
            </ul>
        </div>
    </div>
</details>
@endsection
