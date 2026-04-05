@props(['status', 'label' => null])
@php
$m=['approved'=>'ok','done'=>'ok','active'=>'ok','sent'=>'ok','confirmed'=>'ok','paid'=>'ok','converted'=>'ok','healthy'=>'ok','low'=>'ok','rejected'=>'danger','failed'=>'danger','blocked'=>'danger','cancelled'=>'danger','critical'=>'danger','overdue'=>'danger','pending'=>'warn','in_progress'=>'warn','in_review'=>'warn','warning'=>'warn','high'=>'warn','signed_uploaded'=>'warn','requested'=>'warn','scheduled'=>'warn','uploaded'=>'info','new'=>'info','open'=>'info','todo'=>'info','draft'=>'pending','not_requested'=>'pending','medium'=>'warn'];
$type = $m[$status] ?? 'info';
$colors = [
    'ok'      => 'background:#dcfce7;color:#166534;border:1px solid #bbf7d0;',
    'danger'  => 'background:#fef2f2;color:#991b1b;border:1px solid #fecaca;',
    'warn'    => 'background:#fffbeb;color:#92400e;border:1px solid #fde68a;',
    'info'    => 'background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe;',
    'pending' => 'background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb;',
];
$style = $colors[$type] ?? $colors['info'];
$text = $label ?? str_replace('_',' ',ucfirst($status ?? 'unknown'));
@endphp
<span style="{{ $style }}display:inline-flex;align-items:center;justify-content:center;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:600;line-height:1.4;white-space:nowrap;">{{ $text }}</span>