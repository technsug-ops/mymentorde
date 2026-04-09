@extends('manager.layouts.app')
@section('title', 'Marka Ayarları')
@section('page_title', 'Marka Ayarları')

@section('content')
<div style="max-width:600px;">

    @if(session('status'))
    <div class="card" style="border-left:4px solid var(--c-ok,#16a34a);margin-bottom:20px;padding:14px 18px;color:var(--c-ok,#16a34a);font-weight:600;">
        ✅ {{ session('status') }}
    </div>
    @endif

    <div class="card" style="padding:28px;">
        <h2 style="font-size:18px;font-weight:700;margin-bottom:6px;">Firma Marka Ayarları</h2>
        <p style="color:var(--muted);font-size:13px;margin-bottom:24px;">
            Burada girilen firma adı ve logo tüm portallarda (Manager, Senior, Marketing, Staff) anlık olarak güncellenir.
        </p>

        <form method="POST" action="/manager/brand">
            @csrf
            @method('PUT')

            {{-- Logo Alanı 300×300 --}}
            <div style="margin-bottom:24px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:10px;">Logo Önizleme (300×300)</label>
                <div id="prev-logo" style="width:300px;height:300px;border-radius:16px;background:linear-gradient(135deg,#0f172a,#1e40af);display:flex;align-items:center;justify-content:center;overflow:hidden;border:3px solid rgba(255,255,255,.12);box-shadow:0 8px 32px rgba(0,0,0,.18);">
                    @if($brandLogoUrl)
                        <img id="prev-img" src="{{ $brandLogoUrl }}" style="width:100%;height:100%;object-fit:contain;padding:16px;" onerror="this.style.display='none';document.getElementById('prev-initial').style.display='flex';">
                        <span id="prev-initial" style="display:none;font-size:100px;font-weight:900;color:#fff;">{{ strtoupper(substr($brandName,0,1)) }}</span>
                    @else
                        <span id="prev-initial" style="font-size:100px;font-weight:900;color:#fff;">{{ strtoupper(substr($brandName,0,1)) }}</span>
                        <img id="prev-img" src="" style="display:none;width:100%;height:100%;object-fit:contain;padding:16px;">
                    @endif
                </div>
                <div style="margin-top:8px;font-size:12px;color:var(--muted);">Logo URL girildiğinde burada görünür. Boş bırakılırsa firma adının ilk harfi gösterilir.</div>
            </div>

            {{-- Sidebar Mini Önizleme --}}
            <div style="background:linear-gradient(to right,#0f172a,#1e40af);border-radius:10px;padding:14px 18px;margin-bottom:28px;display:flex;align-items:center;gap:12px;">
                <div style="width:36px;height:36px;border-radius:8px;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:900;color:#fff;flex-shrink:0;overflow:hidden;">
                    <span id="sidebar-initial">{{ strtoupper(substr($brandName,0,1)) }}</span>
                </div>
                <div>
                    <div id="prev-name" style="font-size:15px;font-weight:800;color:#fff;">{{ $brandName }}</div>
                    <div style="font-size:11px;color:rgba(255,255,255,.50);">Sidebar görünümü</div>
                </div>
            </div>

            {{-- Firma Adı --}}
            <div style="margin-bottom:20px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Firma / Sistem Adı <span style="color:var(--c-danger)">*</span></label>
                <input type="text" name="brand_name" id="inp-name" value="{{ old('brand_name', $brandName) }}"
                       placeholder="Örn: {{ config('brand.name', 'MentorDE') }}, AcademiX, EduPro..."
                       style="width:100%;padding:10px 14px;border-radius:8px;font-size:15px;"
                       oninput="document.getElementById('prev-name').textContent=this.value||'{{ config('brand.name', 'MentorDE') }}';
                                var ini=this.value?this.value[0].toUpperCase():'M';
                                document.getElementById('prev-initial').textContent=ini;
                                document.getElementById('sidebar-initial').textContent=ini;">
                @error('brand_name')<div style="color:var(--c-danger);font-size:12px;margin-top:4px;">{{ $message }}</div>@enderror
            </div>

            {{-- Logo URL --}}
            <div style="margin-bottom:28px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Logo URL <span style="color:var(--muted);font-weight:400;">(opsiyonel — boş bırakılırsa harf gösterilir)</span></label>
                <input type="url" name="brand_logo_url" id="inp-logo" value="{{ old('brand_logo_url', $brandLogoUrl) }}"
                       placeholder="https://cdn.firmaniniz.com/logo.png"
                       style="width:100%;padding:10px 14px;border-radius:8px;font-size:14px;"
                       oninput="var url=this.value.trim();var img=document.getElementById('prev-img');var ini=document.getElementById('prev-initial');if(url){img.src=url;img.style.display='block';ini.style.display='none';}else{img.style.display='none';ini.style.display='flex';}">
                <div style="font-size:12px;color:var(--muted);margin-top:5px;">PNG, SVG veya JPG — minimum 64×64 px, şeffaf arka plan önerilir</div>
                @error('brand_logo_url')<div style="color:var(--c-danger);font-size:12px;margin-top:4px;">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="btn btn-primary" style="padding:11px 28px;font-size:15px;">
                💾 Kaydet & Yayınla
            </button>
        </form>
    </div>

    <div class="card" style="padding:20px 24px;margin-top:16px;background:var(--bg);">
        <div style="font-size:13px;font-weight:700;margin-bottom:10px;">ℹ️ Bu ayar neyi etkiler?</div>
        <ul style="font-size:13px;color:var(--muted);line-height:2;margin:0;padding-left:18px;">
            <li>Tüm portal sidebar'larındaki firma adı ve logo</li>
            <li>Manager, Senior, Marketing Admin, Staff Panel</li>
            <li>Tarayıcı sekme başlıkları (kısmen)</li>
            <li>Değişiklik anlık olarak tüm kullanıcılara yansır</li>
        </ul>
    </div>
</div>
@endsection
