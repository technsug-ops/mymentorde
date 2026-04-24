@extends('manager.layouts.app')
@section('title', 'Dealer Başvurusu #' . $app->id)
@section('page_title', '🤝 Başvuru ' . $app->reference_code . ' — ' . $app->full_name)

@section('content')
<style>
.das-wrap { max-width:900px; margin:20px auto; padding:0 16px; }
.das-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:24px; margin-bottom:16px; }
.das-card h3 { margin:0 0 12px; font-size:14px; color:#5b2e91; text-transform:uppercase; letter-spacing:.08em; }
.das-row { display:flex; gap:12px; padding:8px 0; border-bottom:1px solid #f1f5f9; font-size:13px; }
.das-row:last-child { border-bottom:0; }
.das-row .key { min-width:160px; color:#64748b; font-weight:600; }
.das-row .val { color:#1e293b; font-weight:600; }
.das-back { display:inline-block; color:#5b2e91; font-size:13px; margin-bottom:16px; text-decoration:none; font-weight:600; }

.das-status-bar {
    display:flex; justify-content:space-between; align-items:center;
    padding:16px 20px; background:#f8fafc; border-radius:10px; margin-bottom:16px;
}
.das-badge { display:inline-block; padding:4px 12px; border-radius:12px; font-size:12px; font-weight:700; }
.das-badge.pending { background:#fef3c7; color:#92400e; }
.das-badge.in_review { background:#dbeafe; color:#1e40af; }
.das-badge.approved { background:#dcfce7; color:#166534; }
.das-badge.rejected { background:#fee2e2; color:#991b1b; }
.das-badge.waitlist { background:#f3e8ff; color:#6b21a8; }

.das-action-form { display:grid; gap:10px; }
.das-action-form textarea, .das-action-form input, .das-action-form select {
    padding:10px 12px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:13px; font-family:inherit;
}
.das-action-form textarea { min-height:90px; }
.das-btn {
    padding:10px 18px; border:none; border-radius:8px; cursor:pointer;
    font-size:13px; font-weight:700; color:#fff;
}
.das-btn.approve { background:#16a34a; }
.das-btn.reject { background:#dc2626; }
.das-btn.review { background:#2563eb; }
.das-btn.waitlist { background:#9333ea; }

.das-success {
    background:#dcfce7; color:#166534; padding:12px 16px; border-radius:8px; margin-bottom:14px; font-size:13px;
    border-left:3px solid #16a34a;
}
</style>

<div class="das-wrap">
    <a href="{{ route('manager.dealer-applications', ['status' => $app->status]) }}" class="das-back">
        ← Tüm başvurulara dön
    </a>

    @if (session('success'))
        <div class="das-success">✅ {{ session('success') }}</div>
    @endif

    <div class="das-status-bar">
        <div>
            <strong style="font-size:16px;">{{ $app->full_name }}</strong>
            <div style="font-size:12px; color:#64748b;">{{ $app->email }} · {{ $app->phone }}</div>
        </div>
        <span class="das-badge {{ $app->status }}">
            @switch($app->status)
                @case('pending')⏳ Bekliyor@break
                @case('in_review')🔍 İncelemede@break
                @case('approved')✅ Onaylı@break
                @case('rejected')❌ Red@break
                @case('waitlist')📋 Waitlist@break
            @endswitch
        </span>
    </div>

    <div class="das-card">
        <h3>👤 Kişisel Bilgi</h3>
        <div class="das-row"><span class="key">Ad Soyad</span><span class="val">{{ $app->full_name }}</span></div>
        <div class="das-row"><span class="key">Email</span><span class="val"><a href="mailto:{{ $app->email }}">{{ $app->email }}</a></span></div>
        <div class="das-row"><span class="key">Telefon</span><span class="val">
            <a href="tel:{{ $app->phone }}">{{ $app->phone }}</a> ·
            @php $phClean = preg_replace('/[^0-9+]/', '', $app->phone); @endphp
            <a href="https://wa.me/{{ ltrim($phClean, '+') }}" target="_blank" rel="noopener" style="margin-left:8px;">💬 WhatsApp</a>
        </span></div>
        <div class="das-row"><span class="key">Şehir / Ülke</span><span class="val">{{ $app->city ?: '—' }} {{ $app->country ? '/ ' . $app->country : '' }}</span></div>
    </div>

    <div class="das-card">
        <h3>🏢 Çalışma Tercihi</h3>
        <div class="das-row"><span class="key">Plan</span><span class="val">
            @switch($app->preferred_plan)
                @case('lead_generation')🤝 Lead Generation (€200-400/kayıt)@break
                @case('freelance')🎯 Freelance Danışmanlık (€500-750/kayıt)@break
                @case('unsure')💡 Kararsız@break
            @endswitch
        </span></div>
        <div class="das-row"><span class="key">Başvuru Tipi</span><span class="val">{{ ucfirst($app->business_type) }}</span></div>
        @if ($app->company_name)
            <div class="das-row"><span class="key">Firma</span><span class="val">{{ $app->company_name }}</span></div>
        @endif
        @if ($app->tax_number)
            <div class="das-row"><span class="key">Vergi No</span><span class="val">{{ $app->tax_number }}</span></div>
        @endif
        <div class="das-row"><span class="key">Aylık Hedef</span><span class="val">{{ $app->expected_monthly_volume ?? '—' }} aday</span></div>
        <div class="das-row"><span class="key">Deneyim</span><span class="val">{{ $app->education_experience ? '✅ Var' : '❌ Yok' }}</span></div>
        @if ($app->experience_details)
            <div class="das-row"><span class="key">Deneyim Detay</span><span class="val" style="white-space:pre-wrap;">{{ $app->experience_details }}</span></div>
        @endif
    </div>

    <div class="das-card">
        <h3>📍 Kaynak & Motivasyon</h3>
        <div class="das-row"><span class="key">Nereden Duydu</span><span class="val">{{ ucfirst(str_replace('_', ' ', (string) $app->heard_from)) ?: '—' }}</span></div>
        @if ($app->referrer_email)
            <div class="das-row"><span class="key">Yönlendiren</span><span class="val">{{ $app->referrer_email }}</span></div>
        @endif
        @if ($app->motivation)
            <div class="das-row"><span class="key">Motivasyon</span><span class="val" style="white-space:pre-wrap;">{{ $app->motivation }}</span></div>
        @endif
        <div class="das-row"><span class="key">UTM Source</span><span class="val">{{ $app->utm_source ?: 'direct' }}</span></div>
        <div class="das-row"><span class="key">UTM Campaign</span><span class="val">{{ $app->utm_campaign ?: '—' }}</span></div>
        <div class="das-row"><span class="key">IP</span><span class="val" style="font-family:monospace;">{{ $app->ip_address ?: '—' }}</span></div>
        <div class="das-row"><span class="key">Başvuru Zamanı</span><span class="val">{{ $app->created_at->format('d.m.Y H:i') }} ({{ $app->created_at->diffForHumans() }})</span></div>
    </div>

    @if ($app->reviewed_at)
        <div class="das-card" style="background:#f8fafc;">
            <h3>🔍 İnceleme Geçmişi</h3>
            <div class="das-row"><span class="key">Gözden geçiren</span><span class="val">{{ optional($app->reviewer)->name ?? 'Bilinmiyor' }}</span></div>
            <div class="das-row"><span class="key">Zaman</span><span class="val">{{ $app->reviewed_at->format('d.m.Y H:i') }}</span></div>
            @if ($app->review_note)
                <div class="das-row"><span class="key">Not</span><span class="val" style="white-space:pre-wrap;">{{ $app->review_note }}</span></div>
            @endif
            @if ($app->rejected_reason)
                <div class="das-row"><span class="key">Red Sebebi</span><span class="val" style="color:#991b1b; white-space:pre-wrap;">{{ $app->rejected_reason }}</span></div>
            @endif
        </div>
    @endif

    @if (!in_array($app->status, ['approved', 'rejected']))
    <div class="das-card" style="border-left:4px solid #5b2e91;">
        <h3>⚡ Aksiyon Al</h3>
        <form method="POST" action="{{ route('manager.dealer-applications.status', $app->id) }}" class="das-action-form">
            @csrf
            <label>Yeni Durum</label>
            <select name="status" id="action-status" required>
                <option value="in_review">🔍 İncelemede (daha fazla bilgi gerekli)</option>
                <option value="approved">✅ Onayla (dealer erişimi aç)</option>
                <option value="rejected">❌ Reddet</option>
                <option value="waitlist">📋 Waitlist (şu an yer yok, ileride)</option>
            </select>

            <label>İç Not (gözlem, konuşma sonucu)</label>
            <textarea name="note" placeholder="Örn: 2 arama sonrası görüştüm, geçmiş deneyimi doğruladım, plan Freelance olarak güncellenecek..."></textarea>

            <label>Red Sebebi (sadece red için)</label>
            <textarea name="rejected_reason" placeholder="Opsiyonel — başvurana otomatik email ile iletilebilir"></textarea>

            <div style="display:flex; gap:8px; margin-top:8px;">
                <button type="submit" class="das-btn approve" onclick="document.getElementById('action-status').value='approved'; return confirm('Onaylamak istediğinize emin misiniz?');">✅ Onayla</button>
                <button type="submit" class="das-btn reject" onclick="document.getElementById('action-status').value='rejected'; return confirm('Reddetmek istediğinize emin misiniz?');">❌ Reddet</button>
                <button type="submit" class="das-btn review" onclick="document.getElementById('action-status').value='in_review';">🔍 İnceleme</button>
                <button type="submit" class="das-btn waitlist" onclick="document.getElementById('action-status').value='waitlist';">📋 Waitlist</button>
            </div>
        </form>
    </div>
    @endif
</div>
@endsection
