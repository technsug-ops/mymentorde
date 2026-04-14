@props([
    'cols'  => 4,   // 2 | 3 | 4
    'stats' => [],  // [['value'=>'42','label'=>'...','icon'=>'🎓','trend'=>'+5%','trendUp'=>true]]
    'gap'   => 4,
])
{{-- stats array ile kullanım:
<x-data.stat-grid :stats="[
    ['icon'=>'👥','value'=>'142','label'=>'Toplam Aday Öğrenci'],
    ['icon'=>'✅','value'=>'38','label'=>'Dönüşüm','trend'=>'+12%','trendUp'=>true],
]" />

-- VEYA slot ile --
<x-data.stat-grid cols="3">
    <x-data.stat icon="🎓" value="142" label="Toplam" />
</x-data.stat-grid>
--}}

<div class="ds-stat-grid cols-{{ $cols }}">
    @foreach($stats as $s)
    <div class="ds-stat">
        @if(!empty($s['icon']))
            <div class="ds-stat-icon">{{ $s['icon'] }}</div>
        @endif
        <div class="ds-stat-value">{{ $s['value'] ?? '—' }}</div>
        <div class="ds-stat-label">{{ $s['label'] ?? '' }}</div>
        @if(!empty($s['trend']))
        <div class="ds-stat-trend {{ isset($s['trendUp']) ? ($s['trendUp'] ? 'up' : 'down') : '' }}">
            @if(isset($s['trendUp'])) {{ $s['trendUp'] ? '↑' : '↓' }} @endif
            {{ $s['trend'] }}
        </div>
        @endif
    </div>
    @endforeach

    {{ $slot }}
</div>
