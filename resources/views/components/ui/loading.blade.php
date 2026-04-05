@props(['text' => 'Yükleniyor...'])
<div style="display:flex;align-items:center;justify-content:center;gap:8px;padding:24px;color:var(--u-muted);">
    <svg width="20" height="20" viewBox="0 0 24 24" style="animation:spin 1s linear infinite"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="31 31"/></svg>
    <span style="font-size:13px;">{{ $text }}</span>
</div>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>
