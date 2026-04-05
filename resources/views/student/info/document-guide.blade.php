@extends('student.layouts.app')
@section('title', 'Belge Hazırlık Kılavuzu')
@section('page_title', 'Belge Hazırlık Kılavuzu')
@push('head')
<style>
.col2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px; }
@media(max-width:700px){ .col2{ grid-template-columns:1fr; } }
</style>
@endpush
@section('content')

<div class="card" style="background:linear-gradient(to right,#7c3aed,#2563eb);color:#fff;margin-bottom:20px;">
    <div class="card-body" style="padding:28px 28px 24px;">
        <div style="font-size:var(--tx-sm);opacity:.85;margin-bottom:6px;">Adım Adım Hazırlık</div>
        <div style="font-size:var(--tx-2xl);font-weight:800;margin-bottom:8px;">📋 Belge Hazırlık Kılavuzu</div>
        <div style="font-size:var(--tx-sm);opacity:.85;max-width:560px;line-height:1.6;">
            Almanya üniversite başvurusu için hangi belgelerin gerektiğini ve nasıl temin edileceğini öğrenin.
        </div>
    </div>
</div>

<div class="card" style="margin-bottom:24px;">
    <div class="card-body" style="padding:20px;">
        <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:4px;">⏱ Ne Zaman Başlamalısınız?</div>
        <div style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);margin-bottom:16px;">Kış dönemi (Ekim) başlangıcı için tavsiye edilen zaman çizelgesi:</div>
        <div style="display:flex;gap:0;overflow-x:auto;padding-bottom:8px;">
            @foreach([
                ['12 ay önce','Ocak','Lisans notlarını topla, Almanca kursuna başla','ok'],
                ['9 ay önce','Nisan','Apostil işlemleri, yeminli tercüme','info'],
                ['6 ay önce','Temmuz','uni-assist başvurusu, motivasyon mektubu','info'],
                ['4 ay önce','Eylül','Kabul mektuplarını bekle, Sperrkonto aç','warn'],
                ['2 ay önce','Kasım','Vize başvurusu (Almanya Büyükelçiliği)','warn'],
                ['0','Ocak','Konut ara, uçak bileti al','ok'],
            ] as [$when,$mon,$desc,$badge])
            <div style="flex:1;min-width:120px;padding:0 8px;text-align:center;">
                <div style="width:32px;height:32px;border-radius:50%;background:var(--u-brand,#2563eb);color:#fff;font-weight:700;font-size:var(--tx-xs);display:flex;align-items:center;justify-content:center;margin:0 auto 8px;">{{ $loop->iteration }}</div>
                <div style="font-weight:700;font-size:var(--tx-xs);margin-bottom:2px;">{{ $mon }}</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.4;">{{ $desc }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">Belge Kategorileri</div>
<div class="col2">
    <div class="card">
        <div class="card-body" style="padding:20px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                <span style="font-size:var(--tx-xl);">🎓</span>
                <span style="font-weight:700;font-size:var(--tx-base);">Akademik Belgeler</span>
                <span class="badge danger" style="font-size:var(--tx-xs);margin-left:auto;">Zorunlu</span>
            </div>
            @foreach([
                ['Lise Diploması (Apostil)','Müdürlük → Noterde onay → Apostil → Yeminli Tercüme','60-90 gün'],
                ['Transkript (Notlar)','Resmi transkript → Apostil + Yeminli Tercüme','30-60 gün'],
                ['Almanca Dil Sertifikası','B2 minimum. TestDaF, DSH veya Goethe B2/C1.','3-6 ay hazırlık'],
                ['Pasaport','10 yıllık önerilir. Biyometrik fotoğraf gerekli.','1-2 hafta'],
            ] as [$doc,$how,$time])
            <div style="padding:10px 0;border-bottom:1px solid var(--u-line,#e2e8f0);">
                <div style="font-weight:600;font-size:var(--tx-sm);margin-bottom:4px;">{{ $doc }}</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.5;margin-bottom:4px;">{{ $how }}</div>
                <span class="badge warn" style="font-size:var(--tx-xs);">⏱ {{ $time }}</span>
            </div>
            @endforeach
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding:20px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                <span style="font-size:var(--tx-xl);">💶</span>
                <span style="font-weight:700;font-size:var(--tx-base);">Finansal & Vize Belgeleri</span>
                <span class="badge danger" style="font-size:var(--tx-xs);margin-left:auto;">Zorunlu</span>
            </div>
            @foreach([
                ['Sperrkonto (Bloke Hesap)','Deutsche Bank, Fintiba veya Coracle. €11.208 yatırılmalı.','5-10 iş günü'],
                ['Sağlık Sigortası Belgesi','TK, AOK veya Barmer\'den başlatılabilir.','1-2 hafta'],
                ['Motivasyon Mektubu','Almanca veya İngilizce. AI Asistanımız yardımcı olabilir!','3-5 gün'],
                ['Vize Başvuru Formu','Randevu 2-3 ay önceden alın!','Randevu bekleme!'],
            ] as [$doc,$how,$time])
            <div style="padding:10px 0;border-bottom:1px solid var(--u-line,#e2e8f0);">
                <div style="font-weight:600;font-size:var(--tx-sm);margin-bottom:4px;">{{ $doc }}</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.5;margin-bottom:4px;">{{ $how }}</div>
                <span class="badge warn" style="font-size:var(--tx-xs);">⏱ {{ $time }}</span>
            </div>
            @endforeach
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding:20px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                <span style="font-size:var(--tx-xl);">⚠️</span>
                <span style="font-weight:700;font-size:var(--tx-base);">Sık Yapılan Hatalar</span>
                <span class="badge danger" style="font-size:var(--tx-xs);margin-left:auto;">Dikkat!</span>
            </div>
            @foreach([
                ['Apostil geç alındı','Apostil ve yeminli tercüme 2-3 ay sürebilir. Erken başlayın.'],
                ['Dil sınavı son güne bırakıldı','Sınav tarihleri sınırlı. En az 6 ay önceden kayıt yaptırın.'],
                ['Vize randevusu geç alındı','Büyükelçilik randevuları 2-3 ay dolu olabilir.'],
                ['Belge formatı yanlış','Bazı üniversiteler sadece dijital PDF ister.'],
            ] as [$hata,$acik])
            <div style="padding:10px 0;border-bottom:1px solid var(--u-line,#e2e8f0);">
                <div style="font-weight:600;font-size:var(--tx-sm);color:var(--u-danger,#dc2626);margin-bottom:4px;">❌ {{ $hata }}</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.5;">{{ $acik }}</div>
            </div>
            @endforeach
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding:20px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                <span style="font-size:var(--tx-xl);">📎</span>
                <span style="font-weight:700;font-size:var(--tx-base);">Güçlendirici Belgeler</span>
                <span class="badge info" style="font-size:var(--tx-xs);margin-left:auto;">Opsiyonel</span>
            </div>
            @foreach([
                ['Referans Mektubu','Öğretmen veya akademisyenden.'],
                ['CV (Özgeçmiş)','Europass formatı önerilir. Almanca yazılmalı.'],
                ['İngilizce Sertifika','İngilizce eğitim programları için IELTS/TOEFL.'],
                ['Staj / İş Deneyimi','Teknik programlar için önemli.'],
            ] as [$doc,$how])
            <div style="padding:10px 0;border-bottom:1px solid var(--u-line,#e2e8f0);">
                <div style="font-weight:600;font-size:var(--tx-sm);margin-bottom:4px;">{{ $doc }}</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.5;">{{ $how }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="card" style="border:1.5px solid var(--u-brand,#2563eb);margin-bottom:20px;">
    <div class="card-body" style="padding:20px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
        <div style="font-size:32px;">📂</div>
        <div style="flex:1;min-width:200px;">
            <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:4px;">Belgelerinizi Sisteme Yükleyin</div>
            <div style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);">Danışmanınız yüklediğiniz belgeleri inceleyerek geri bildirim verecektir.</div>
        </div>
        <a href="{{ route('student.registration.documents') }}" class="btn ok">Belge Yükle →</a>
        <a href="{{ '/student/dashboard' }}" class="btn alt">← Dashboard</a>
    </div>
</div>

@endsection
