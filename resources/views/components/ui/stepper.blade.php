@props([
    'steps'   => [],  // [['label'=>'...','status'=>'done|active|pending']]
    'current' => 0,
])
{{-- steps array kullanımı:
<x-ui.stepper :steps="[
    ['label'=>'Kayıt', 'status'=>'done'],
    ['label'=>'Paket', 'status'=>'active'],
    ['label'=>'Sözleşme', 'status'=>'pending'],
]" />

-- VEYA slot kullanımı --
<x-ui.stepper>
    <x-ui.stepper-step label="Kayıt" status="done" number="1" />
</x-ui.stepper>
--}}

<div class="ds-stepper">
    @if(!empty($steps))
        @foreach($steps as $i => $step)
        @php
            $status = $step['status'] ?? ($i < $current ? 'done' : ($i === $current ? 'active' : 'pending'));
        @endphp
        <div class="ds-step {{ $status }}">
            <div class="ds-step-circle">
                @if($status === 'done') ✓ @else {{ $i + 1 }} @endif
            </div>
            <div class="ds-step-label">{{ $step['label'] }}</div>
        </div>
        @endforeach
    @else
        {{ $slot }}
    @endif
</div>
