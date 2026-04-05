@extends('manager.layouts.app')
@section('title', $lang === 'en' ? 'System Handbook' : 'Sistem El Kitabı')

@push('styles')
    @include('handbook._style')
@endpush

@section('content')
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 class="page-title">📖 {{ $lang === 'en' ? 'System Handbook' : 'Sistem El Kitabı' }}</h1>
        <p class="page-subtitle" style="margin:0;">{{ $lang === 'en' ? 'Full reference for all portals, roles, and modules.' : 'Tüm portaller, roller ve modüller için tam referans.' }}</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
        <div class="handbook-lang">
            <a href="?lang=tr" class="{{ $lang === 'tr' ? 'active' : '' }}">TR</a>
            <a href="?lang=en" class="{{ $lang === 'en' ? 'active' : '' }}">EN</a>
        </div>
        <a href="{{ route('manager.handbook.download') }}?lang={{ $lang }}" class="btn alt" style="padding:7px 16px;font-size:.85rem;">
            ⬇ HTML {{ $lang === 'en' ? 'Download' : 'İndir' }}
        </a>
    </div>
</div>

<div class="card handbook-wrap" style="max-width:960px;">
    <div class="handbook-body">
        {!! $html !!}
    </div>
</div>
@endsection
