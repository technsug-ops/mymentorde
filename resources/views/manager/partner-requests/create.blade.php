@extends('manager.layouts.app')

@section('title', 'Yeni Talep')
@section('page_title', 'Partnerden Bilgi / Belge İste')

@push('head')
    @include('manager.partner-requests._styles')
@endpush

@section('content')

<a href="/manager/partner-requests" class="pr-muted" style="font-size:12px;text-decoration:none;">← Talep listesi</a>

@if($errors->any())
    <div class="pr-note" style="border-left-color:#dc2626;background:rgba(220,38,38,.06);border-color:rgba(220,38,38,.25);margin-top:10px;">
        {{ $errors->first() }}
    </div>
@endif

{{-- 1. ADIM: firma seçimi.
     Kişi listesi firmaya bağlı olduğu için önce firma belirlenmeli. JS'siz
     iki adım: seçim sayfayı kendi üstüne yeniden yükler. --}}
<section class="panel" style="margin-top:12px;margin-bottom:12px;">
    <form method="GET" action="/manager/partner-requests/create" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;">
        <div style="display:flex;flex-direction:column;">
            <label class="pr-label">Partner Firma</label>
            <select name="partner_company_id" style="min-width:260px;">
                <option value="">– Seçin –</option>
                @foreach($partners as $id => $name)
                    <option value="{{ $id }}" @selected($partnerId === (int) $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn" style="font-size:12px;">Devam</button>
    </form>
</section>

@if($partnerId > 0 && $people->isEmpty())
    <div class="pr-note">Bu firmanın henüz aday veya öğrencisi yok.</div>
@endif

@if($people->isNotEmpty())
{{-- 2. ADIM: kişi + istenecek kalemler --}}
<form method="POST" action="/manager/partner-requests">
    @csrf
    <input type="hidden" name="partner_company_id" value="{{ $partnerId }}">

    <section class="panel" style="margin-bottom:12px;">
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <div style="display:flex;flex-direction:column;">
                <label class="pr-label">Kişi</label>
                <select name="subject" style="min-width:300px;" required>
                    @foreach($people as $key => $label)
                        <option value="{{ $key }}" @selected(old('subject') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;flex-direction:column;">
                <label class="pr-label">Son Tarih</label>
                <input type="date" name="due_at" value="{{ old('due_at') }}">
            </div>
        </div>
    </section>

    {{-- İstenebilecek belgelerin tamamı — katalog ortak, firmaya bağlı değil. --}}
    <section class="panel" style="margin-bottom:12px;">
        <h2 style="font-size:14px;margin-bottom:4px;">Belgeler</h2>
        <div class="pr-muted" style="font-size:12px;margin-bottom:10px;">
            İstediğiniz belgeleri işaretleyin. Partner bunları kendi öğrencisinden isteyecek.
        </div>

        @foreach($categories as $group => $items)
            <div class="pr-group">
                <div class="pr-group-title">{{ $group }}</div>
                <div class="pr-checks">
                    @foreach($items as $cat)
                        <label class="pr-check">
                            <input type="checkbox" name="category_codes[]" value="{{ $cat->code }}"
                                   @checked(in_array($cat->code, old('category_codes', []), true))>
                            <span>{{ $cat->name_tr ?: $cat->code }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </section>

    <section class="panel" style="margin-bottom:12px;">
        <h2 style="font-size:14px;margin-bottom:4px;">Bilgi Soruları</h2>
        <div class="pr-muted" style="font-size:12px;margin-bottom:8px;">
            Belge değil de bilgi istiyorsanız her satıra bir soru yazın.
        </div>
        <textarea name="info_items" rows="4" style="width:100%;"
                  placeholder="Lise diploma notu&#10;Almanya'da kalacağı adres&#10;Pasaport geçerlilik tarihi">{{ old('info_items') }}</textarea>
    </section>

    <section class="panel" style="margin-bottom:12px;">
        <label class="pr-label">Not (opsiyonel)</label>
        <textarea name="note" rows="2" style="width:100%;" placeholder="Partner firmaya iletilecek açıklama">{{ old('note') }}</textarea>
    </section>

    <button type="submit" class="btn btn-primary">Talebi Gönder</button>
</form>
@endif

@endsection
