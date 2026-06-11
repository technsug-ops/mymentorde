@extends('manager.layouts.app')
@section('title','Yorum Moderasyonu')
@section('page_title','Yorum Moderasyonu')

@section('content')
<style nonce="{{ $cspNonce ?? '' }}">
.rv-wrap { max-width:1180px; margin:18px auto 40px; padding:0 18px; }

.rv-hero {
    background:linear-gradient(135deg,#7e58bf 0%,#5b3a99 100%);
    color:#fff; border-radius:16px; padding:22px 26px; margin-bottom:22px;
    display:flex; align-items:center; gap:18px;
}
.rv-hero-icon {
    width:54px; height:54px; border-radius:14px;
    background:rgba(255,255,255,.16); display:flex; align-items:center; justify-content:center;
    flex-shrink:0;
}
.rv-hero h1 { margin:0 0 4px; font-size:21px; font-weight:700; letter-spacing:-.01em; }
.rv-hero p  { margin:0; font-size:13px; opacity:.92; line-height:1.55; }

.rv-tabs {
    display:flex; gap:6px; margin-bottom:18px; flex-wrap:wrap;
}
.rv-tab {
    padding:9px 16px; border-radius:8px;
    background:#fff; border:1px solid #e5e7eb;
    color:#475569; text-decoration:none; font-size:13.5px; font-weight:600;
    transition:.15s; display:inline-flex; align-items:center; gap:7px;
}
.rv-tab:hover { color:#7e58bf; border-color:#7e58bf; text-decoration:none; }
.rv-tab.active { background:#7e58bf; color:#fff; border-color:#7e58bf; }
.rv-tab .rv-tab-badge {
    background:rgba(0,0,0,.12); padding:2px 8px; border-radius:10px; font-size:11px; font-weight:700;
}
.rv-tab.active .rv-tab-badge { background:rgba(255,255,255,.25); }

.rv-card {
    background:#fff; border:1px solid #e5e7eb; border-radius:14px;
    padding:0; overflow:hidden; box-shadow:0 1px 2px rgba(15,23,42,.04);
}
.rv-tbl { width:100%; border-collapse:collapse; font-size:13.5px; }
.rv-tbl th, .rv-tbl td { padding:12px 14px; text-align:left; vertical-align:top; }
.rv-tbl thead th {
    background:#fafbfc; font-size:12px; text-transform:uppercase;
    letter-spacing:.04em; color:#64748b; font-weight:600; border-bottom:1px solid #e5e7eb;
}
.rv-tbl tbody tr { border-bottom:1px solid #f1f5f9; }
.rv-tbl tbody tr:last-child { border-bottom:0; }

.rv-stars { color:#f5b400; display:inline-flex; gap:1px; }
.rv-body-cell { max-width:340px; }
.rv-title { font-weight:600; color:#0f172a; margin-bottom:3px; }
.rv-body  { color:#475569; font-size:13px; line-height:1.5; }
.rv-author { font-weight:600; color:#0f172a; }
.rv-author-meta { color:#64748b; font-size:12px; margin-top:2px; }

.rv-status {
    display:inline-flex; align-items:center; gap:5px;
    padding:4px 10px; border-radius:10px; font-size:11.5px; font-weight:600;
}
.rv-status-approved { background:#ecfdf5; color:#065f46; border:1px solid #6ee7b7; }
.rv-status-pending  { background:#fffbeb; color:#92400e; border:1px solid #fcd34d; }
.rv-status-rejected { background:#fef2f2; color:#991b1b; border:1px solid #fca5a5; }
.rv-public-on  { color:#065f46; font-size:12px; }
.rv-public-off { color:#991b1b; font-size:12px; }

.rv-actions { display:flex; flex-direction:column; gap:6px; min-width:130px; }
.rv-actions button, .rv-actions a {
    border:0; cursor:pointer; padding:6px 12px; border-radius:6px;
    font-size:12px; font-weight:600; text-decoration:none; text-align:center;
    display:inline-flex; align-items:center; justify-content:center; gap:5px;
}
.rv-act-approve { background:#10b981; color:#fff; }
.rv-act-approve:hover { background:#059669; }
.rv-act-reject  { background:#ef4444; color:#fff; }
.rv-act-reject:hover { background:#dc2626; }
.rv-act-toggle  { background:#f1f5f9; color:#0f172a; }
.rv-act-toggle:hover { background:#e2e8f0; }
.rv-act-delete  { background:transparent; color:#991b1b; }
.rv-act-delete:hover { background:#fee2e2; }

.rv-empty { text-align:center; padding:60px 20px; color:#64748b; }
.rv-empty-ic { width:60px; height:60px; margin:0 auto 14px; color:#cbd5e1; }
.rv-empty h3 { margin:0 0 6px; font-size:16px; color:#0f172a; }
.rv-empty p  { margin:0; font-size:13px; }

.rv-pagination { margin-top:18px; }
</style>

<div class="rv-wrap">

    <div class="rv-hero">
        <div class="rv-hero-icon">
            <x-icon name="star-filled" size="28" />
        </div>
        <div>
            <h1>Yorum Moderasyonu</h1>
            <p>Kullanıcı yorumlarını incele, onayla, gizle veya kaldır. Onaylanan yorumlar uzman profillerinde yayınlanır.</p>
        </div>
    </div>

    @if(session('success'))
        <div style="padding:12px 16px; background:#ecfdf5; border:1px solid #6ee7b7; color:#065f46; border-radius:10px; margin-bottom:14px; font-size:13.5px;">
            {{ session('success') }}
        </div>
    @endif

    {{-- Status filter tabs --}}
    <div class="rv-tabs">
        <a href="{{ route('manager.reviews.index') }}"
           class="rv-tab {{ !$status ? 'active' : '' }}">
            Hepsi <span class="rv-tab-badge">{{ array_sum($counts) }}</span>
        </a>
        <a href="{{ route('manager.reviews.index', ['status' => 'pending']) }}"
           class="rv-tab {{ $status === 'pending' ? 'active' : '' }}">
            Bekleyen <span class="rv-tab-badge">{{ $counts['pending'] }}</span>
        </a>
        <a href="{{ route('manager.reviews.index', ['status' => 'approved']) }}"
           class="rv-tab {{ $status === 'approved' ? 'active' : '' }}">
            Onaylı <span class="rv-tab-badge">{{ $counts['approved'] }}</span>
        </a>
        <a href="{{ route('manager.reviews.index', ['status' => 'rejected']) }}"
           class="rv-tab {{ $status === 'rejected' ? 'active' : '' }}">
            Reddedildi <span class="rv-tab-badge">{{ $counts['rejected'] }}</span>
        </a>
    </div>

    <div class="rv-card">
        @if($reviews->count() === 0)
            <div class="rv-empty">
                <div class="rv-empty-ic"><x-icon name="message-circle" size="48" /></div>
                <h3>Henüz yorum yok</h3>
                <p>Tamamlanan görüşmelerden sonra kullanıcılar yorum gönderebilir.</p>
            </div>
        @else
            <table class="rv-tbl">
                <thead>
                    <tr>
                        <th style="width:90px;">Puan</th>
                        <th>Yorum</th>
                        <th>Uzman</th>
                        <th>Yorumcu</th>
                        <th style="width:120px;">Durum</th>
                        <th style="width:140px;">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reviews as $r)
                        <tr>
                            <td>
                                <span class="rv-stars">
                                    @for($i=1; $i<=5; $i++)
                                        <x-icon :name="$i <= (int) $r->rating ? 'star-filled' : 'star'" size="13" />
                                    @endfor
                                </span>
                                <div style="font-size:11px; color:#64748b; margin-top:2px;">
                                    {{ optional($r->submitted_at ?: $r->created_at)->format('d.m.Y') }}
                                </div>
                            </td>
                            <td class="rv-body-cell">
                                @if($r->title)
                                    <div class="rv-title">{{ $r->title }}</div>
                                @endif
                                @if($r->body)
                                    <div class="rv-body">{{ \Illuminate\Support\Str::limit($r->body, 220) }}</div>
                                @else
                                    <div class="rv-body" style="font-style:italic;">— Sadece yıldız puanı —</div>
                                @endif
                            </td>
                            <td>
                                @php $senior = $seniors->get($r->senior_user_id); @endphp
                                <div class="rv-author">{{ $senior?->name ?? '—' }}</div>
                                <div class="rv-author-meta">#{{ $r->senior_user_id }}</div>
                            </td>
                            <td>
                                <div class="rv-author">{{ $r->reviewer_name }}</div>
                                <div class="rv-author-meta">{{ $r->reviewer_email }}</div>
                                @if($r->is_verified)
                                    <span style="display:inline-block; margin-top:3px; padding:1px 6px; border-radius:6px; background:#ecfdf5; color:#065f46; font-size:10.5px; font-weight:600;">
                                        ✓ Doğrulanmış
                                    </span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $sc = $r->moderation_status;
                                    $statusLabel = ['pending' => 'Bekliyor', 'approved' => 'Onaylı', 'rejected' => 'Reddedildi'][$sc] ?? $sc;
                                @endphp
                                <span class="rv-status rv-status-{{ $sc }}">{{ $statusLabel }}</span>
                                <div style="margin-top:4px;">
                                    @if($r->is_public)
                                        <span class="rv-public-on">● Yayında</span>
                                    @else
                                        <span class="rv-public-off">● Gizli</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="rv-actions">
                                    @if($r->moderation_status !== 'approved')
                                        <form method="POST" action="{{ route('manager.reviews.approve', $r) }}">
                                            @csrf
                                            <button type="submit" class="rv-act-approve">
                                                <x-icon name="check" size="11" /> Onayla
                                            </button>
                                        </form>
                                    @endif
                                    @if($r->moderation_status !== 'rejected')
                                        <form method="POST" action="{{ route('manager.reviews.reject', $r) }}">
                                            @csrf
                                            <button type="submit" class="rv-act-reject">
                                                <x-icon name="x" size="11" /> Reddet
                                            </button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('manager.reviews.toggle', $r) }}">
                                        @csrf
                                        <button type="submit" class="rv-act-toggle">
                                            {{ $r->is_public ? 'Gizle' : 'Yayınla' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('manager.reviews.destroy', $r) }}"
                                          data-confirm="Yorumu kalıcı olarak silmek istiyor musun?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="rv-act-delete">
                                            <x-icon name="trash" size="11" /> Sil
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    @if($reviews->hasPages())
        <div class="rv-pagination">{{ $reviews->onEachSide(1)->links() }}</div>
    @endif
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    document.querySelectorAll('form[data-confirm]').forEach(function(f){
        f.addEventListener('submit', function(ev){
            var msg = f.getAttribute('data-confirm');
            if(!confirm(msg)){ ev.preventDefault(); }
        });
    });
})();
</script>
@endsection
