@extends('platform.layouts.app')

@section('title', 'Form Şablonu')

@section('content')

<h1 class="plat-page-title">Form Şablonu</h1>
<p class="plat-page-sub">
    Kayıt formu tek merkezden yönetilir. Firmalar merkezî tanımı kullanır;
    kendi satırlarını edinen firma merkezden <strong>kopar</strong>.
</p>

@if(session('status'))
    <div class="plat-card" style="border-color:rgba(22,163,74,.4);background:rgba(22,163,74,.08);margin-bottom:16px;">
        {{ session('status') }}
    </div>
@endif

<div class="plat-card" style="margin-bottom:16px;">
    <h3 class="plat-card-title">Merkezî Tanım</h3>
    <div style="font-size:26px;font-weight:800;color:#4ade80;">{{ $centralCount }}</div>
    <div class="plat-card-sub">alan — tüm firmalar bunu kullanır</div>
</div>

@if($diverged->isEmpty())
    <div class="plat-card" style="border-color:rgba(22,163,74,.4);background:rgba(22,163,74,.06);">
        <strong style="color:#4ade80;">Sapma yok.</strong>
        Tüm firmalar merkezî şablonu kullanıyor — formu değiştirdiğinizde
        değişiklik hepsine <strong>anında</strong> yansır.
    </div>
@else
    <div class="plat-card" style="border-color:rgba(217,119,6,.45);background:rgba(217,119,6,.08);margin-bottom:16px;">
        <strong style="color:#fbbf24;">{{ $diverged->count() }} firma merkezden kopmuş.</strong>
        Bu firmalar kendi form kopyalarını kullanıyor; merkezde yaptığınız
        değişiklikler onlara <strong>ULAŞMAZ</strong>.
    </div>

    @foreach($diverged as $row)
        <div class="plat-card" style="margin-bottom:14px;">
            <h3 class="plat-card-title">
                {{ $row['company']->name ?? ('#' . $row['company_id']) }}
                <span style="font-weight:400;color:var(--plat-muted);font-size:12.5px;">
                    · {{ $row['total'] }} kendi alanı
                </span>
            </h3>

            {{-- Sapma İKİ YÖNLÜ: eksik alan firmanın yeni soruları hiç
                 sormaması, fazla alan ise merkezde karşılığı olmayan veri
                 toplanması demek. --}}
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px;margin-bottom:14px;">
                <div>
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#fca5a5;margin-bottom:6px;">
                        Merkezde var, firmada yok ({{ $row['missing']->count() }})
                    </div>
                    @if($row['missing']->isEmpty())
                        <div style="font-size:12px;color:var(--plat-muted);">—</div>
                    @else
                        <div style="font-size:12px;line-height:1.7;word-break:break-word;">
                            {{ $row['missing']->implode(', ') }}
                        </div>
                        <div style="font-size:11px;color:var(--plat-muted);margin-top:5px;">
                            Bu firma söz konusu alanları hiç sormuyor.
                        </div>
                    @endif
                </div>

                <div>
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#fbbf24;margin-bottom:6px;">
                        Firmada var, merkezde yok ({{ $row['extra']->count() }})
                    </div>
                    @if($row['extra']->isEmpty())
                        <div style="font-size:12px;color:var(--plat-muted);">—</div>
                    @else
                        <div style="font-size:12px;line-height:1.7;word-break:break-word;">
                            {{ $row['extra']->implode(', ') }}
                        </div>
                        <div style="font-size:11px;color:var(--plat-muted);margin-top:5px;">
                            Merkezde karşılığı olmayan veri toplanıyor.
                            <strong>Sıfırlarsanız bu alanlar kaybolur.</strong>
                        </div>
                    @endif
                </div>
            </div>

            <form method="POST" action="{{ route('platform.form-template.reset', $row['company_id']) }}"
                  onsubmit="return confirm('{{ $row['company']->name ?? 'Firma' }} merkezî şablona döndürülecek. Kendi alanları SİLİNECEK. Devam?');">
                @csrf
                <button type="submit" class="plat-btn plat-btn-ghost" style="color:#fca5a5;border-color:rgba(220,38,38,.4);">
                    Merkezî şablona döndür
                </button>
            </form>
        </div>
    @endforeach
@endif

@endsection
