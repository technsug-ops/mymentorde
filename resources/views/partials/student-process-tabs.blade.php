{{--
  Süreç Takibi alt sekmeleri: Genel / Vize / Konut
  Kullanım:  @include('partials.student-process-tabs', ['active' => 'general|visa|housing'])
--}}
@php $pt_active = $active ?? 'general'; @endphp
<style>
.pt-tabs { display:flex; gap:4px; border-bottom:1px solid var(--u-line,#e5e7eb); margin-bottom:16px; overflow-x:auto; -webkit-overflow-scrolling:touch; scrollbar-width:thin; }
.pt-tabs::-webkit-scrollbar { height:3px; }
.pt-tab {
    display:inline-flex; align-items:center; gap:7px; padding:10px 16px;
    font-size:13px; font-weight:600; color:var(--u-muted,#64748b);
    text-decoration:none; white-space:nowrap; border-bottom:2px solid transparent;
    transition:color .15s, border-color .15s;
}
.pt-tab span { font-size:15px; line-height:1; }
.pt-tab:hover { color:var(--u-text,#0f172a); text-decoration:none; }
.pt-tab--active {
    color:var(--u-brand,#7c3aed); border-bottom-color:var(--u-brand,#7c3aed);
}
@media(max-width:640px){ .pt-tab { padding:9px 12px; font-size:12px; } }
</style>
<div class="pt-tabs">
    <a href="/student/process-tracking" class="pt-tab {{ $pt_active === 'general' ? 'pt-tab--active' : '' }}">
        <span>🎯</span> Genel Süreç
    </a>
    <a href="/student/visa" class="pt-tab {{ $pt_active === 'visa' ? 'pt-tab--active' : '' }}">
        <span>🛂</span> Vize Takibi
    </a>
    <a href="/student/housing" class="pt-tab {{ $pt_active === 'housing' ? 'pt-tab--active' : '' }}">
        <span>🏠</span> Konut & Barınma
    </a>
</div>
