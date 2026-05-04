@php
    $pageTitle = $title ?? ($meta['label_' . ($locale ?? 'tr')] ?? 'Yasal');
@endphp
@extends('legal.layout', ['pageTitle' => $pageTitle])

@section('content')
<style>
.legal-meta {
    display: flex;
    gap: 14px;
    align-items: center;
    flex-wrap: wrap;
    padding-bottom: 18px;
    margin-bottom: 24px;
    border-bottom: 1px solid var(--line);
}
.legal-meta h1 {
    margin: 0;
    flex: 1;
    min-width: 200px;
}
.lang-switcher { display: flex; gap: 6px; }
.lang-switcher a {
    padding: 6px 14px;
    font-size: 12px;
    font-weight: 700;
    border: 1.5px solid var(--line);
    border-radius: 8px;
    text-decoration: none;
    color: var(--muted);
    background: var(--surface);
    transition: all .15s;
}
.lang-switcher a:hover { border-color: var(--primary); color: var(--primary); }
.lang-switcher a.active {
    background: var(--primary);
    color: #fff;
    border-color: var(--primary);
}
.legal-updated {
    font-size: 12px;
    color: var(--muted);
    width: 100%;
    margin-top: 4px;
}
.legal-empty {
    padding: 56px 24px;
    text-align: center;
    color: var(--muted);
    background: linear-gradient(135deg, var(--primary-soft) 0%, #f4f2ee 100%);
    border: 2px dashed var(--line);
    border-radius: 14px;
}
.legal-empty .emoji {
    font-size: 48px;
    margin-bottom: 14px;
    opacity: 0.6;
}
.legal-empty .empty-title {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 6px;
    color: var(--text);
}
.legal-empty .empty-desc {
    font-size: 13.5px;
    line-height: 1.6;
    max-width: 420px;
    margin: 0 auto;
}
.legal-fallback-warn {
    margin-top: 18px;
    text-align: center;
    font-size: 12px;
    color: var(--muted);
    padding: 10px 14px;
    background: var(--primary-soft);
    border-radius: 8px;
}
</style>

<div class="legal-meta">
    <h1>{{ $meta['emoji'] }} {{ $meta['label_' . ($requestedLocale ?? 'tr')] ?? $title }}</h1>
    <div class="lang-switcher">
        <a href="?lang=tr" class="{{ $locale === 'tr' ? 'active' : '' }}">TR</a>
        <a href="?lang=de" class="{{ $locale === 'de' ? 'active' : '' }}">DE</a>
        <a href="?lang=en" class="{{ $locale === 'en' ? 'active' : '' }}">EN</a>
    </div>
    @if($updatedAt)
        <div class="legal-updated">Son güncelleme: {{ $updatedAt->format('d.m.Y') }}</div>
    @endif
</div>

@if(trim((string) $body) === '')
    <div class="legal-empty">
        <div class="emoji">📄</div>
        <div class="empty-title">Henüz içerik girilmemiş</div>
        <div class="empty-desc">Bu sayfanın içeriği yönetici tarafından henüz girilmedi. Lütfen daha sonra tekrar deneyin.</div>
    </div>
@else
    {!! $body !!}
@endif

@if($locale !== ($requestedLocale ?? $locale))
    <div class="legal-fallback-warn">
        ⚠ {{ strtoupper($requestedLocale) }} için içerik bulunamadı, {{ strtoupper($locale) }} dilinde gösteriliyor.
    </div>
@endif
@endsection
