@extends('guest.layouts.app')

@section('title', 'Favorilerim')
@section('page_title', 'Favorilerim')

@push('head')
<style>
.sv-hero{background:linear-gradient(to right,var(--theme-hero-from-guest),var(--theme-hero-to-guest));border-radius:14px;padding:28px 28px;color:#fff;margin-bottom:24px;}
.sv-hero h1{font-size:1.4rem;font-weight:700;margin:0 0 6px;}
.sv-hero p{font-size:.9rem;opacity:.85;margin:0;}
.sv-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;}
@media(max-width:900px){.sv-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:600px){.sv-grid{grid-template-columns:1fr;}}
.sv-card{background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e7eb);border-radius:12px;overflow:hidden;display:flex;flex-direction:column;text-decoration:none;color:inherit;transition:transform .15s,box-shadow .15s;}
.sv-card:hover{transform:translateY(-2px);box-shadow:0 6px 18px rgba(0,0,0,.09);}
.sv-card-img{height:120px;display:flex;align-items:center;justify-content:center;color:#fff;}
.sv-card-img svg{width:36px;height:36px;opacity:.92;}
.sv-card-body{padding:12px 14px;flex:1;display:flex;flex-direction:column;gap:5px;}
.sv-card-title{font-size:.9rem;font-weight:600;color:var(--u-text,#1a1a1a);line-height:1.35;}
.sv-card-sum{font-size:.8rem;color:var(--u-muted,#888);overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;}
</style>
@endpush

@section('content')
@php
$gradients = [
    'student-life'   => 'linear-gradient(135deg,#1f6fd9,#0d2748)',
    'culture-fun'    => 'linear-gradient(135deg,#6b3fa0,#2e1660)',
    'careers'        => 'linear-gradient(135deg,#166534,#0a2e18)',
    'tips-tricks'    => 'linear-gradient(135deg,#1e607a,#0a2e3e)',
    'city-content'   => 'linear-gradient(135deg,#0e6fa0,#072840)',
    'uni-content'    => 'linear-gradient(135deg,#2a3fa8,#0f1d5a)',
    'success-stories'=> 'linear-gradient(135deg,#1a3a8a,#0d1e52)',
];
$typeIcons = ['blog'=>'pencil','video_feature'=>'play-circle','podcast'=>'headphones','presentation'=>'presentation','experience'=>'message-circle','career_guide'=>'map','tip'=>'lightbulb'];
$typeLabels = ['blog'=>'Blog','video_feature'=>'Video','podcast'=>'Podcast','presentation'=>'Sunum','experience'=>'Deneyim','career_guide'=>'Rehber','tip'=>'İpucu'];
@endphp

<div style="font-size:.82rem;color:var(--u-muted,#888);margin-bottom:12px;display:flex;align-items:center;gap:6px;">
    <a href="{{ route('guest.dashboard') }}" style="color:var(--u-brand,#2563eb);text-decoration:none;">Ana Sayfa</a>
    <span>›</span>
    @pageVisible('discover')
    <a href="{{ route('guest.discover') }}" style="color:var(--u-brand,#2563eb);text-decoration:none;display:inline-flex;align-items:center;gap:4px;"><x-icon name="compass" size="14" aria-label="Keşfet" /> Keşfet</a>
    <span>›</span>
    @endpageVisible
    <span style="color:var(--u-text,#333);font-weight:600;display:inline-flex;align-items:center;gap:4px;"><x-icon name="bookmark-filled" size="14" aria-label="Favoriler" /> Favorilerim</span>
</div>

<div class="sv-hero">
    <h1 style="display:flex;align-items:center;gap:8px;"><x-icon name="bookmark-filled" size="22" aria-label="Favoriler" /> Favorilerim</h1>
    <p>Kaydettiğiniz içerikler. İstediğiniz zaman tekrar okuyun.</p>
</div>

@if($items->isEmpty())
<div style="text-align:center;padding:60px 24px;background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e7eb);border-radius:12px;">
    <div style="display:flex;justify-content:center;margin-bottom:12px;color:var(--u-muted,#9ca3af);"><x-icon name="bookmark" size="44" aria-label="Favoriler boş" /></div>
    <div style="font-size:1rem;font-weight:600;margin-bottom:8px;">Henüz favorin yok</div>
    <div style="font-size:.88rem;color:var(--u-muted,#888);margin-bottom:8px;line-height:1.6;">
        Favoriye eklemek için içeriği aç → sayfanın altındaki <strong>"Favoriye Ekle"</strong> butonuna bas.
    </div>
    <div style="font-size:.78rem;margin-bottom:20px;background:#fef3c7;border:1px solid #fde68a;color:#92400e;padding:10px 14px;border-radius:8px;display:inline-flex;align-items:center;gap:6px;">
        <x-icon name="circle-alert" size="14" aria-label="Dikkat" /> Dikkat: <strong>"Beğen"</strong> butonu farklıdır — sadece istatistik. Favorilere düşürmek için <strong>"Favoriye Ekle"</strong> kullan.
    </div>
    <br>
    @pageVisible('discover')
    <a href="{{ route('guest.discover') }}" class="btn" style="display:inline-flex;align-items:center;gap:6px;"><x-icon name="compass" size="14" aria-label="Keşfet" /> İçeriklere Göz At</a>
    @endpageVisible
</div>
@else
<div class="sv-grid">
    @foreach($items as $item)
    <a href="{{ route('guest.content-detail', $item->slug) }}" class="sv-card">
        <div class="sv-card-img" style="background:{{ $gradients[$item->category] ?? 'linear-gradient(135deg,#1f6fd9,#0d2748)' }}">
            <x-icon name="{{ $typeIcons[$item->type] ?? 'file-text' }}" size="32" aria-label="İçerik türü ikonu" />
        </div>
        <div class="sv-card-body">
            <div style="display:flex;gap:5px;flex-wrap:wrap;">
                <span class="badge badge-{{ $item->type }}" style="font-size:.72rem;">{{ $typeLabels[$item->type] ?? $item->type }}</span>
            </div>
            <div class="sv-card-title">{{ $item->title_tr }}</div>
            <div class="sv-card-sum">{{ $item->summary_tr }}</div>
        </div>
    </a>
    @endforeach
</div>

@if($items->hasPages())
<div style="text-align:center;margin-top:20px;">
    {{ $items->links('partials.pagination') }}
</div>
@endif
@endif

@pageVisible('discover')
<div style="margin-top:20px;">
    <a href="{{ route('guest.discover') }}" class="btn alt" style="display:inline-flex;align-items:center;gap:6px;"><x-icon name="arrow-left" size="14" aria-label="Geri" /> Keşfet'e Dön</a>
</div>
@endpageVisible
@endsection
