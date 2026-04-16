@extends('marketing-admin.layouts.app')
@section('title', 'Email Drip Kampanyaları')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;">
    <h1>Email Drip Kampanyaları</h1>
    <button class="btn" onclick="document.getElementById('newForm').style.display=document.getElementById('newForm').style.display==='none'?'':'none'">+ Yeni Seri</button>
</div>

<div id="newForm" style="display:none;margin-bottom:16px;" class="card">
    <div class="card-title">Yeni Drip Serisi</div>
    <form method="POST" action="/mktg-admin/email/drip-sequences">
        @csrf
        <div class="grid2">
            <div class="field"><label>Seri Adı *</label><input name="name" type="text" required></div>
            <div class="field"><label>Tetikleyici *</label>
                <select name="trigger_event" required>
                    <option value="guest_registered">Aday Öğrenci Kayıt</option>
                    <option value="contract_signed">Sözleşme İmzalandı</option>
                    <option value="package_selected">Paket Seçildi</option>
                </select>
            </div>
            <div class="field" style="grid-column:1/-1"><label>Açıklama</label><textarea name="description" rows="2"></textarea></div>
        </div>
        <button type="submit" class="btn ok">Oluştur</button>
    </form>
</div>

<div class="list">
    @forelse($sequences as $seq)
    <div class="item">
        <div style="flex:1;">
            <a href="/mktg-admin/email/drip-sequences/{{ $seq->id }}" style="font-weight:500;">{{ $seq->name }}</a>
            <div class="u-muted" style="font-size:var(--tx-xs);">
                Tetikleyici: <strong>{{ $seq->trigger_event }}</strong> ·
                {{ $seq->steps_count ?? 0 }} adım ·
                {{ $seq->enrollments_count ?? 0 }} kayıt
            </div>
        </div>
        <span class="badge {{ $seq->is_active ? 'ok' : 'pending' }}">{{ $seq->is_active ? 'Aktif' : 'Pasif' }}</span>
        <form method="POST" action="/mktg-admin/email/drip-sequences/{{ $seq->id }}" style="margin-left:8px;" onsubmit="return confirm('Silmek istediğinize emin misiniz?')">
            @csrf @method('DELETE')
            <button class="btn warn" type="submit" style="padding:4px 8px;font-size:var(--tx-xs);">Sil</button>
        </form>
    </div>
    @empty
    <div class="item"><span class="u-muted">Henüz drip serisi yok.</span></div>
    @endforelse
</div>
{{ $sequences->links('partials.pagination') }}

<details class="card" style="margin-top:12px;">
    <summary class="det-sum">
        <h3>📖 Kullanım Kılavuzu — Drip E-posta Serileri</h3>
        <span class="det-chev">▼</span>
    </summary>
    <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;padding-top:12px;">
        <li>Drip serisi → belirli bir tetikleyici sonrası otomatik e-posta dizisi gönderir</li>
        <li>Tipik seri: Hoş geldin (0. gün) → Bilgi (3. gün) → CTA (7. gün) → Takip (14. gün)</li>
        <li>Her serinin adımlarını yönetmek için seriye tıkla → Adım Ekle</li>
        <li>Aktif seri duraklatılmadan değiştirilemez — önce pause et</li>
    </ul>
</details>
@endsection
