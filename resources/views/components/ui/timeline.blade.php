@props([])
{{-- Kullanım:
<x-ui.timeline>
    <x-ui.timeline-item title="Başvuru Yapıldı" meta="10 Mart 2026" status="success">
        Form tamamlandı ve sisteme iletildi.
    </x-ui.timeline-item>
</x-ui.timeline>
--}}
<div class="ds-timeline">
    {{ $slot }}
</div>
