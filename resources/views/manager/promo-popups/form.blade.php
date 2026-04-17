@extends('manager.layouts.app')
@section('title', $popup ? 'Popup Düzenle' : 'Yeni Popup')
@section('page_title', $popup ? 'Popup Düzenle' : 'Yeni Popup Oluştur')

@section('content')
<div class="page-header">
    <h1>{{ $popup ? '✏️ Popup Düzenle: ' . $popup->title : '📺 Yeni Tanıtım Popup' }}</h1>
</div>

@if($errors->any())
<div style="margin-bottom:14px;padding:10px 16px;border-radius:8px;background:#fee2e2;color:#991b1b;font-weight:600;font-size:13px;border:1px solid #fecaca;">
    @foreach($errors->all() as $err)<div>⚠ {{ $err }}</div>@endforeach
</div>
@endif

<form method="POST" action="{{ $popup ? route('manager.promo-popups.update', $popup) : route('manager.promo-popups.store') }}">
    @csrf
    @if($popup) @method('PUT') @endif

    <div class="card" style="padding:20px;margin-bottom:16px;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;margin-bottom:6px;">Popup Başlığı (dahili) *</label>
                <input type="text" name="title" value="{{ old('title', $popup?->title) }}" required maxlength="150"
                       style="width:100%;padding:9px 12px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;margin-bottom:6px;">Video Tipi *</label>
                <select name="video_type" style="width:100%;padding:9px 12px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;">
                    <option value="youtube" @selected(old('video_type', $popup?->video_type) === 'youtube')>YouTube</option>
                    <option value="vimeo" @selected(old('video_type', $popup?->video_type) === 'vimeo')>Vimeo</option>
                    <option value="custom" @selected(old('video_type', $popup?->video_type) === 'custom')>Özel URL (mp4)</option>
                </select>
            </div>
        </div>

        <div style="margin-top:14px;">
            <label style="display:block;font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;margin-bottom:6px;">Video URL</label>
            <input type="url" name="video_url" value="{{ old('video_url', $popup?->video_url) }}" maxlength="500"
                   placeholder="https://www.youtube.com/watch?v=... veya https://youtu.be/..."
                   style="width:100%;padding:9px 12px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;">
            <div class="u-muted" style="font-size:11px;margin-top:4px;">YouTube: watch URL veya embed URL. Vimeo: video URL. Custom: direkt .mp4 URL.</div>
        </div>

        <div style="margin-top:14px;">
            <label style="display:block;font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;margin-bottom:6px;">Açıklama Metni (opsiyonel)</label>
            <textarea name="description" rows="3" maxlength="2000"
                      style="width:100%;padding:9px 12px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;resize:vertical;">{{ old('description', $popup?->description) }}</textarea>
        </div>
    </div>

    <div class="card" style="padding:20px;margin-bottom:16px;">
        <div style="font-weight:700;margin-bottom:12px;">🎯 Hedef Ayarları</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;margin-bottom:8px;">Gösterilecek Sayfalar *</label>
                @php $selectedPages = old('target_pages', $popup?->target_pages ?? []); @endphp
                @foreach(\App\Models\PromoPopup::PAGE_OPTIONS as $val => $lbl)
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;margin-bottom:6px;cursor:pointer;">
                    <input type="checkbox" name="target_pages[]" value="{{ $val }}" @checked(in_array($val, $selectedPages))>
                    {{ $lbl }}
                </label>
                @endforeach
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;margin-bottom:8px;">Hedef Roller *</label>
                @php $selectedRoles = old('target_roles', $popup?->target_roles ?? []); @endphp
                @foreach(\App\Models\PromoPopup::ROLE_OPTIONS as $val => $lbl)
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;margin-bottom:6px;cursor:pointer;">
                    <input type="checkbox" name="target_roles[]" value="{{ $val }}" @checked(in_array($val, $selectedRoles))>
                    {{ $lbl }}
                </label>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card" style="padding:20px;margin-bottom:16px;">
        <div style="font-weight:700;margin-bottom:12px;">⏱ Zamanlama</div>
        <div style="display:grid;grid-template-columns:repeat(4, 1fr);gap:16px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;margin-bottom:6px;">Gecikme (saniye) *</label>
                <input type="number" name="delay_seconds" value="{{ old('delay_seconds', $popup?->delay_seconds ?? 3) }}" min="0" max="120" required
                       style="width:100%;padding:9px 12px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;margin-bottom:6px;">Gösterim Sıklığı *</label>
                <select name="frequency" style="width:100%;padding:9px 12px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;">
                    @foreach(\App\Models\PromoPopup::FREQUENCY_OPTIONS as $val => $lbl)
                    <option value="{{ $val }}" @selected(old('frequency', $popup?->frequency ?? 'first_login') === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;margin-bottom:6px;">Başlangıç (opsiyonel)</label>
                <input type="date" name="starts_at" value="{{ old('starts_at', $popup?->starts_at?->format('Y-m-d')) }}"
                       style="width:100%;padding:9px 12px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;margin-bottom:6px;">Bitiş (opsiyonel)</label>
                <input type="date" name="ends_at" value="{{ old('ends_at', $popup?->ends_at?->format('Y-m-d')) }}"
                       style="width:100%;padding:9px 12px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;">
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:14px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;margin-bottom:6px;">Öncelik (1=en yüksek)</label>
                <input type="number" name="priority" value="{{ old('priority', $popup?->priority ?? 10) }}" min="1" max="100"
                       style="width:100%;padding:9px 12px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;">
            </div>
            <div style="display:flex;align-items:end;padding-bottom:4px;">
                <label style="display:flex;align-items:center;gap:8px;font-size:14px;cursor:pointer;">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $popup?->is_active ?? true))>
                    <span style="font-weight:700;">Aktif</span>
                </label>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:10px;justify-content:flex-end;">
        <a href="{{ route('manager.promo-popups.index') }}" class="btn alt" style="padding:10px 20px;border-radius:8px;text-decoration:none;">İptal</a>
        <button type="submit" class="btn" style="background:#1e40af;color:#fff;padding:10px 24px;border-radius:8px;font-weight:700;">
            {{ $popup ? '💾 Güncelle' : '✓ Oluştur' }}
        </button>
    </div>
</form>
@endsection
