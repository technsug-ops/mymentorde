@props([
    'headers'  => [],     // ['Alan Adı', ...] veya [['label'=>'...','width'=>'...','align'=>'...']]
    'striped'  => false,
    'hoverable'=> true,
    'empty'    => 'Kayıt bulunamadı.',
    'emptyIcon'=> '📋',
])
<div class="ds-table-wrap">
    <table class="ds-table{{ $striped ? ' striped' : '' }}">
        @if(!empty($headers))
        <thead>
            <tr>
                @foreach($headers as $h)
                    @if(is_array($h))
                        <th style="{{ isset($h['width']) ? 'width:'.$h['width'].';' : '' }}{{ isset($h['align']) ? 'text-align:'.$h['align'].';' : '' }}">
                            {{ $h['label'] }}
                        </th>
                    @else
                        <th>{{ $h }}</th>
                    @endif
                @endforeach
            </tr>
        </thead>
        @endif

        <tbody>
            @if($slot->isNotEmpty())
                {{ $slot }}
            @else
                <tr>
                    <td colspan="{{ max(1, count($headers)) }}" style="text-align:center;padding:40px 20px;color:var(--color-muted)">
                        <div style="font-size:32px;margin-bottom:8px">{{ $emptyIcon }}</div>
                        <div style="font-size:var(--font-sm)">{{ $empty }}</div>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
