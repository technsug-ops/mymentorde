{{-- Şirket kota tablosu — leads ve students sayfaları aynı veriyi kullanır.
     Kişisel veri ve satış hunisi bilerek yok; burada olan tek şey KAPASİTE. --}}

@php
    /** @var \Illuminate\Support\Collection $companies */
    $barColor = function (?int $pct): string {
        if ($pct === null) return 'var(--plat-muted)';
        if ($pct >= 100)   return '#dc2626';
        if ($pct >= 80)    return '#f59e0b';
        return 'var(--plat-accent)';
    };
@endphp

<div style="overflow-x:auto;">
    <table class="plat-table" style="width:100%;min-width:720px;">
        <thead>
            <tr>
                <th>Şirket</th>
                <th>Paket</th>
                <th style="min-width:170px;">Aday</th>
                <th style="min-width:170px;">Öğrenci</th>
                <th>Durum</th>
            </tr>
        </thead>
        <tbody>
            @foreach($companies as $c)
                <tr @if($c['over']) style="background:rgba(220,38,38,.07);" @elseif($c['near']) style="background:rgba(245,158,11,.07);" @endif>
                    <td>
                        <span style="font-weight:600;">{{ $c['name'] }}</span>
                        <span style="display:block;font-size:11px;color:var(--plat-muted);">
                            #{{ $c['id'] }} · {{ $c['code'] }}@if($c['parent']) · üst firma #{{ $c['parent'] }}@endif
                        </span>
                    </td>

                    <td>
                        <span class="plat-badge plat-badge-{{ $c['tier'] }}">{{ $c['tierLabel'] }}</span>
                    </td>

                    @foreach([['leads', 'leadMax', 'leadPct'], ['students', 'studentMax', 'studentPct']] as [$useKey, $maxKey, $pctKey])
                        <td>
                            <div style="display:flex;align-items:baseline;gap:6px;font-variant-numeric:tabular-nums;">
                                <strong style="font-size:15px;">{{ number_format($c[$useKey], 0, ',', '.') }}</strong>
                                <span style="font-size:11px;color:var(--plat-muted);">
                                    @if($c[$maxKey] === null)
                                        / sınırsız
                                    @else
                                        / {{ number_format($c[$maxKey], 0, ',', '.') }}
                                    @endif
                                </span>
                            </div>

                            @if($c[$pctKey] !== null)
                                <div style="height:5px;background:var(--plat-panel-2);border-radius:3px;overflow:hidden;margin-top:5px;max-width:130px;">
                                    <div style="height:100%;width:{{ min(100, $c[$pctKey]) }}%;background:{{ $barColor($c[$pctKey]) }};"></div>
                                </div>
                                <span style="font-size:10.5px;color:var(--plat-muted);">%{{ $c[$pctKey] }}</span>
                            @endif
                        </td>
                    @endforeach

                    <td>
                        @if(!$c['active'])
                            <span class="plat-badge plat-badge-inactive">Pasif</span>
                        @elseif($c['over'])
                            <span style="font-size:12px;font-weight:700;color:#dc2626;">Limit doldu</span>
                            <span style="display:block;font-size:11px;color:var(--plat-muted);">üst pakete geçmeli</span>
                        @elseif($c['near'])
                            <span style="font-size:12px;font-weight:700;color:#f59e0b;">Sınıra yakın</span>
                            <span style="display:block;font-size:11px;color:var(--plat-muted);">üst paket adayı</span>
                        @elseif($c['unlimited'])
                            <span style="font-size:12px;color:var(--plat-muted);">Sınırsız paket</span>
                        @else
                            <span class="plat-badge plat-badge-active">Rahat</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
