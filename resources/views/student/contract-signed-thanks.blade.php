@extends('student.layouts.app')

@section('title', 'Sözleşme İmzalandı')
@section('page_title', 'Sözleşme İmzalandı')

@section('content')
<div style="max-width:560px;margin:60px auto;text-align:center;">
    <div style="font-size:72px;margin-bottom:24px;">🎉</div>
    <h2 style="font-size:var(--tx-xl);font-weight:700;color:var(--u-text);margin-bottom:12px;">Sözleşmeniz İmzalandı!</h2>
    <p style="font-size:var(--tx-base);color:var(--u-muted);line-height:1.7;margin-bottom:28px;">
        Sözleşmenizi başarıyla imzaladınız. Danışmanınız en kısa sürede onaylayacak ve süreciniz devam edecek.
    </p>
    <div class="card" style="text-align:left;padding:20px 24px;margin-bottom:24px;">
        <div style="font-weight:600;color:var(--u-text);margin-bottom:10px;">📋 Sonraki Adımlar</div>
        <ul style="color:var(--u-muted);font-size:var(--tx-sm);line-height:2;padding-left:18px;">
            <li>Danışmanınız sözleşmenizi inceleyecek</li>
            <li>Onaylandığında bildirim alacaksınız</li>
            <li>Süreç takibi sayfasından güncellemeleri izleyebilirsiniz</li>
        </ul>
    </div>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
        <a href="/student/dashboard" class="btn">Ana Sayfaya Dön</a>
        <a href="/student/process-tracking" class="btn alt">Süreç Takibi</a>
    </div>
</div>
@endsection
