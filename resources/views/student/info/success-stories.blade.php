@extends('student.layouts.app')
@section('title', 'Başarı Hikayeleri')
@section('page_title', 'Öğrenci Başarı Hikayeleri')
@push('head')
<style>
.col3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; margin-bottom:20px; }
.col2i { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; margin-bottom:20px; }
@media(max-width:800px){ .col3,.col2i{ grid-template-columns:1fr 1fr; } }
@media(max-width:600px){ .col3,.col2i{ grid-template-columns:1fr; } }
</style>
@endpush
@section('content')

<div class="card" style="background:linear-gradient(to right,#0891b2,#16a34a);color:#fff;margin-bottom:20px;">
    <div class="card-body" style="padding:28px 28px 24px;">
        <div style="font-size:var(--tx-sm);opacity:.85;margin-bottom:6px;">MentorDE Ailesi</div>
        <div style="font-size:var(--tx-2xl);font-weight:800;margin-bottom:8px;">⭐ Başarı Hikayeleri</div>
        <div style="font-size:var(--tx-sm);opacity:.85;max-width:560px;line-height:1.6;">
            Türkiye'den Almanya'ya hayallerini gerçekleştiren öğrencilerimizin gerçek deneyimleri.
        </div>
    </div>
</div>

<div class="col3" style="margin-bottom:28px;">
    @foreach([['🎓','80+','Öğrenci Almanya\'da'],['🏛','50+','Farklı Üniversite'],['⭐','%95','Memnuniyet Oranı']] as [$icon,$val,$lbl])
    <div class="card" style="text-align:center;">
        <div class="card-body" style="padding:20px;">
            <div style="font-size:var(--tx-2xl);margin-bottom:8px;">{{ $icon }}</div>
            <div style="font-size:var(--tx-2xl);font-weight:800;color:var(--u-brand,#2563eb);line-height:1;">{{ $val }}</div>
            <div style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);margin-top:4px;">{{ $lbl }}</div>
        </div>
    </div>
    @endforeach
</div>

@php
$srcMeta = [
    'Google'     => ['color'=>'#4285F4','bg'=>'#e8f0fe'],
    'Trustpilot' => ['color'=>'#00b67a','bg'=>'#e5f7f1'],
    'MentorDE'   => ['color'=>'#e11d48','bg'=>'#fff1f2'],
];
$avatarGradients = [
    'linear-gradient(to right,#7c3aed,#2563eb)',
    'linear-gradient(to right,#0891b2,#16a34a)',
    'linear-gradient(to right,#dc2626,#d97706)',
    'linear-gradient(to right,#7c3aed,#2563eb)',
    'linear-gradient(to right,#0891b2,#16a34a)',
    'linear-gradient(to right,#dc2626,#d97706)',
];
$hasCms = isset($cmsStories) && $cmsStories->isNotEmpty();
$staticStories = [
    ['initials'=>'AK','name'=>'Ahmet K.','program'=>'TU München — Mak. Müh.','source'=>'Google',
     'quote'=>'"MentorDE olmadan bu süreci tek başıma yönetemezdim. Şu an TU München\'de 2. yılımdayım."'],
    ['initials'=>'ZY','name'=>'Zeynep Y.','program'=>'TU Berlin — Bilgisayar Müh.','source'=>'Trustpilot',
     'quote'=>'"Apostil ve yeminli tercüme için nereye gideceğimi bilmiyordum. Danışmanım adım adım rehberlik etti."'],
    ['initials'=>'MS','name'=>'Murat S.','program'=>'HAW Hamburg — İşletme','source'=>'MentorDE',
     'quote'=>'"MentorDE\'nin sistematik takibi sayesinde hiçbir belge eksik kalmadı."'],
    ['initials'=>'EA','name'=>'Elif A.','program'=>'Uni Stuttgart — Elektrik Müh.','source'=>'Google',
     'quote'=>'"Danışmanım doğrudan üniversiteye geçiş için alternatif bir yol gösterdi. Harika!"'],
    ['initials'=>'KD','name'=>'Kemal D.','program'=>'Goethe Uni — Finans','source'=>'Trustpilot',
     'quote'=>'"Goethe Uni\'ye kabul aldığımda inanamadım. Motivasyon mektubum için danışmanım çok yardımcı oldu."'],
    ['initials'=>'NT','name'=>'Neslihan T.','program'=>'TH Köln — Medya Tasarımı','source'=>'MentorDE',
     'quote'=>'"TH Köln tasarım programına kabul — çok mutluyum! Almanya\'da yaşam beklediğimden güzel."'],
];
@endphp

<div style="font-weight:700;font-size:var(--tx-base);margin-bottom:16px;">Öğrenci Deneyimleri</div>

<div class="col3">
@if($hasCms)
    @foreach($cmsStories as $i => $story)
    @php
        $tags=$story->tags??[];$source=$tags[0]??'MentorDE';
        $sm=$srcMeta[$source]??['color'=>'#6366f1','bg'=>'#eef2ff'];
        $initials=$story->cover_image_alt?:strtoupper(substr($story->title_tr??'M',0,2));
        $gradient=$avatarGradients[$i%count($avatarGradients)];
        $extraTags=array_slice(is_array($tags)?$tags:[],1);
    @endphp
    <div style="background:var(--u-card,#fff);border:1.5px solid #f43f5e;border-radius:12px;display:flex;flex-direction:column;">
        <div style="padding:18px 18px 14px;display:flex;align-items:center;gap:14px;">
            <div style="width:52px;height:52px;border-radius:50%;background:{{ $gradient }};display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:var(--tx-base);flex-shrink:0;box-shadow:0 2px 8px rgba(0,0,0,.12);">{{ $initials }}</div>
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:var(--tx-sm);color:var(--u-text);margin-bottom:3px;">{{ $story->title_tr }}</div>
                <div style="color:#f59e0b;font-size:var(--tx-sm);letter-spacing:1px;margin-bottom:4px;">★★★★★</div>
                <span style="display:inline-flex;padding:2px 8px;border-radius:20px;background:{{ $sm['bg'] }};color:{{ $sm['color'] }};font-size:var(--tx-xs);font-weight:700;">{{ $source }}</span>
            </div>
        </div>
        <div style="border-top:1px solid #fecdd3;margin:0 18px;"></div>
        <div style="padding:14px 18px 18px;flex:1;">
            <div style="font-size:var(--tx-sm);color:var(--u-text,#1e293b);line-height:1.7;">{{ $story->content_tr }}</div>
            @if($story->summary_tr)<div style="margin-top:10px;font-size:var(--tx-xs);color:var(--u-muted,#94a3b8);">{{ $story->summary_tr }}</div>@endif
        </div>
    </div>
    @endforeach
@else
    @foreach($staticStories as $i => $s)
    @php $sm=$srcMeta[$s['source']]??['color'=>'#6366f1','bg'=>'#eef2ff'];$gradient=$avatarGradients[$i%count($avatarGradients)]; @endphp
    <div style="background:var(--u-card,#fff);border:1.5px solid #f43f5e;border-radius:12px;display:flex;flex-direction:column;">
        <div style="padding:18px 18px 14px;display:flex;align-items:center;gap:14px;">
            <div style="width:52px;height:52px;border-radius:50%;background:{{ $gradient }};display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:var(--tx-base);flex-shrink:0;box-shadow:0 2px 8px rgba(0,0,0,.12);">{{ $s['initials'] }}</div>
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:var(--tx-sm);color:var(--u-text);margin-bottom:3px;">{{ $s['name'] }}</div>
                <div style="color:#f59e0b;font-size:var(--tx-sm);letter-spacing:1px;margin-bottom:4px;">★★★★★</div>
                <span style="display:inline-flex;padding:2px 8px;border-radius:20px;background:{{ $sm['bg'] }};color:{{ $sm['color'] }};font-size:var(--tx-xs);font-weight:700;">{{ $s['source'] }}</span>
            </div>
        </div>
        <div style="border-top:1px solid #fecdd3;margin:0 18px;"></div>
        <div style="padding:14px 18px 18px;flex:1;">
            <div style="font-size:var(--tx-sm);color:var(--u-text,#1e293b);line-height:1.7;">{{ $s['quote'] }}</div>
            <div style="margin-top:10px;font-size:var(--tx-xs);color:var(--u-muted,#94a3b8);">{{ $s['program'] }}</div>
        </div>
    </div>
    @endforeach
@endif
</div>

<div style="text-align:center;padding:8px 0;">
    <a href="{{ '/student/dashboard' }}" style="color:var(--u-brand,#2563eb);font-size:var(--tx-sm);font-weight:600;text-decoration:none;">← Dashboard'a Dön</a>
</div>

@endsection
