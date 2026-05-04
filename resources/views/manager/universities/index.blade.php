@extends('manager.layouts.app')

@section('title', 'Üniversiteler — Görsel Yönetimi')
@section('page_title', '🏛️ Üniversite Görselleri')
@section('page_subtitle', 'UniMatch program detay filigran + sonuç sayfası logosu için her üniversitenin gerçek görselini yükle')

@push('head')
<style>
.uni-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 22px; }
.uni-stat { background: #fff; border: 1px solid #e5e5e5; border-radius: 12px; padding: 18px 20px; }
.uni-stat-num { font-size: 28px; font-weight: 800; color: #7e58bf; line-height: 1; letter-spacing: -.5px; }
.uni-stat-label { font-size: 12px; color: #6b5894; font-weight: 600; margin-top: 4px; text-transform: uppercase; letter-spacing: .4px; }

.uni-toolbar { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 16px; }
.uni-toolbar input[type="text"], .uni-toolbar select {
    font: inherit; font-size: 13.5px; padding: 9px 14px; border: 1px solid #d4c5e8;
    border-radius: 8px; background: #fff; color: #1a1a1a;
}
.uni-toolbar input[type="text"] { min-width: 280px; }
.uni-toolbar button[type="submit"] { background: #7e58bf; color: #fff; border: 0; padding: 9px 18px; border-radius: 8px; font: inherit; font-weight: 700; font-size: 13.5px; cursor: pointer; }
.uni-toolbar a.clear { font-size: 12.5px; color: #6b5894; text-decoration: none; padding: 6px 10px; }
.uni-toolbar a.clear:hover { text-decoration: underline; }

.uni-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 14px;
}
.uni-card {
    background: #fff; border: 1px solid #e5e5e5; border-radius: 12px;
    overflow: hidden; display: flex; flex-direction: column;
    transition: border-color .2s, box-shadow .2s;
}
.uni-card:hover { border-color: #b79ae9; box-shadow: 0 6px 20px rgba(126,88,191,.10); }
.uni-card-img {
    height: 130px; background: linear-gradient(135deg, #ede9fe, #d4c5e8);
    display: flex; align-items: center; justify-content: center;
    color: #6c47a8; font-size: 36px; font-weight: 800; letter-spacing: -1px;
    background-size: cover; background-position: center;
}
.uni-card-img.has-image::before {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(180deg, transparent 60%, rgba(0,0,0,.2));
}
.uni-card-body { padding: 14px 16px; flex: 1; display: flex; flex-direction: column; }
.uni-card-name { font-size: 14.5px; font-weight: 700; color: #1a1a1a; line-height: 1.3; margin-bottom: 4px; }
.uni-card-meta { font-size: 12px; color: #6b5894; margin-bottom: 12px; }
.uni-card-meta-tag { display: inline-block; background: #f4f2ee; color: #6b5894; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; margin-right: 4px; }

.uni-card-actions { margin-top: auto; display: flex; gap: 8px; flex-wrap: wrap; }
.uni-card-actions form { flex: 1; min-width: 0; }
.uni-card-actions input[type="file"] {
    width: 100%; font-size: 11.5px; padding: 5px;
    border: 1px dashed #d4c5e8; border-radius: 6px; background: #faf7fd;
    cursor: pointer;
}
.uni-card-actions button.upload {
    width: 100%; background: #7e58bf; color: #fff; border: 0;
    padding: 7px 10px; border-radius: 6px;
    font: inherit; font-size: 11.5px; font-weight: 700; cursor: pointer;
    margin-top: 5px;
}
.uni-card-actions button.upload:hover { background: #6c47a8; }
.uni-card-actions button.delete {
    background: transparent; color: #dc2626; border: 1px solid #fecaca;
    padding: 7px 12px; border-radius: 6px;
    font: inherit; font-size: 11.5px; font-weight: 600; cursor: pointer;
    align-self: stretch;
}
.uni-card-actions button.delete:hover { background: #fef2f2; }

.uni-pagination { margin-top: 22px; display: flex; justify-content: center; }
</style>
@endpush

@section('content')
<div class="uni-stats">
    <div class="uni-stat">
        <div class="uni-stat-num">{{ number_format($stats['total']) }}</div>
        <div class="uni-stat-label">Toplam üniversite</div>
    </div>
    <div class="uni-stat">
        <div class="uni-stat-num" style="color:#15803d;">{{ number_format($stats['with_img']) }}</div>
        <div class="uni-stat-label">Görseli olan</div>
    </div>
    <div class="uni-stat">
        <div class="uni-stat-num" style="color:#dc2626;">{{ number_format($stats['without_img']) }}</div>
        <div class="uni-stat-label">Görseli olmayan</div>
    </div>
</div>

<form method="GET" class="uni-toolbar">
    <input type="text" name="q" placeholder="🔍 İsim veya şehir ara..." value="{{ $q }}">
    <select name="img">
        <option value="">Tümü</option>
        <option value="no" @selected($hasImg === 'no')>Görseli olmayanlar</option>
        <option value="yes" @selected($hasImg === 'yes')>Görseli olanlar</option>
    </select>
    <button type="submit">Filtrele</button>
    @if($q || $hasImg)
        <a href="{{ route('manager.universities.index') }}" class="clear">× Temizle</a>
    @endif
</form>

@if(session('success'))
<div style="padding: 12px 16px; background: #d1fae5; border-left: 4px solid #059669; border-radius: 8px; color: #064e3b; margin-bottom: 14px; font-size: 13.5px;">
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="padding: 12px 16px; background: #fee2e2; border-left: 4px solid #dc2626; border-radius: 8px; color: #7f1d1d; margin-bottom: 14px; font-size: 13.5px;">
    {{ session('error') }}
</div>
@endif
@if(session('info'))
<div style="padding: 12px 16px; background: #dbeafe; border-left: 4px solid #2563eb; border-radius: 8px; color: #1e3a8a; margin-bottom: 14px; font-size: 13.5px;">
    {{ session('info') }}
</div>
@endif
@if(isset($errors) && $errors->any())
<div style="padding: 12px 16px; background: #fee2e2; border-left: 4px solid #dc2626; border-radius: 8px; color: #7f1d1d; margin-bottom: 14px; font-size: 13.5px;">
    @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
</div>
@endif

@if($universities->isEmpty())
<div style="text-align: center; padding: 60px 20px; color: #8a7baf; background: #fff; border-radius: 12px; border: 1px dashed #d4c5e8;">
    Eşleşen üniversite bulunamadı.
</div>
@else
<div class="uni-grid">
    @foreach($universities as $uni)
        @php
            $hasImage = ! empty($uni->image_path);
            $imgUrl = $hasImage
                ? (str_starts_with($uni->image_path, 'http') ? $uni->image_path : asset(ltrim($uni->image_path, '/')))
                : null;
            $initial = mb_strtoupper(mb_substr($uni->name ?? '?', 0, 2));
        @endphp
        <div class="uni-card">
            <div class="uni-card-img {{ $hasImage ? 'has-image' : '' }}"
                 @if($imgUrl) style="background-image: url('{{ $imgUrl }}'); color: transparent;" @endif>
                @if(! $hasImage){{ $initial }}@endif
            </div>
            <div class="uni-card-body">
                <div class="uni-card-name">{{ $uni->name }}</div>
                <div class="uni-card-meta">
                    @if($uni->city)<span class="uni-card-meta-tag">📍 {{ $uni->city }}</span>@endif
                    @if($uni->type)<span class="uni-card-meta-tag">{{ $uni->type }}</span>@endif
                    @if($uni->is_uni_assist_member)<span class="uni-card-meta-tag" style="background:rgba(217,119,6,.12);color:#92400e;">📨 uni-assist</span>@endif
                </div>
                <div class="uni-card-actions">
                    <form action="{{ route('manager.universities.image.upload', $uni) }}"
                          method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="file" name="image" accept="image/jpeg,image/png,image/webp" required>
                        <button type="submit" class="upload">{{ $hasImage ? '🔁 Değiştir' : '📤 Yükle' }}</button>
                    </form>
                    @if($hasImage)
                    <form action="{{ route('manager.universities.image.delete', $uni) }}"
                          method="POST" onsubmit="return confirm('Görseli kaldırmak istediğinizden emin misiniz?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="delete" title="Kaldır">×</button>
                    </form>
                    @endif
                </div>

                {{-- Video URL formu — YouTube/Vimeo --}}
                <details style="margin-top:10px;border-top:1px dashed #e5e5e5;padding-top:10px;">
                    <summary style="cursor:pointer;font-size:12px;color:#7e58bf;font-weight:600;">
                        🎬 Tanıtım Videosu @if(!empty($uni->video_url))<span style="color:#059669;">(✓ var)</span>@else<span style="color:#9ca3af;">(yok)</span>@endif
                    </summary>
                    <form action="{{ route('manager.universities.video.update', $uni) }}" method="POST" style="margin-top:8px;display:flex;flex-direction:column;gap:6px;">
                        @csrf
                        <input type="url" name="video_url" placeholder="https://youtu.be/... veya https://vimeo.com/..."
                               value="{{ $uni->video_url }}"
                               style="width:100%;padding:7px 10px;border:1px solid #d4c5e8;border-radius:6px;font-size:12px;">
                        <input type="text" name="video_caption" placeholder="Kısa altyazı (opsiyonel)"
                               value="{{ $uni->video_caption }}" maxlength="200"
                               style="width:100%;padding:7px 10px;border:1px solid #d4c5e8;border-radius:6px;font-size:12px;">
                        <div style="display:flex;gap:6px;">
                            <button type="submit" class="upload" style="flex:1;">{{ !empty($uni->video_url) ? '🔁 Güncelle' : '💾 Kaydet' }}</button>
                            @if(!empty($uni->video_url))
                            <button type="submit" formaction="{{ route('manager.universities.video.delete', $uni) }}"
                                    formmethod="POST" class="delete" title="Videoyu kaldır"
                                    onclick="return confirm('Videoyu kaldırmak istediğinizden emin misiniz?');">
                                ×
                            </button>
                            @endif
                        </div>
                    </form>
                    @if(!empty($uni->video_url) && $uni->video_embed_url)
                    <div style="margin-top:8px;font-size:11px;color:#6b7280;">
                        Önizleme: <a href="{{ $uni->video_url }}" target="_blank" style="color:#7e58bf;">{{ Str::limit($uni->video_url, 50) }}</a>
                    </div>
                    @endif
                </details>
            </div>
        </div>
    @endforeach
</div>

<div class="uni-pagination">
    {{ $universities->links() }}
</div>
@endif
@endsection
