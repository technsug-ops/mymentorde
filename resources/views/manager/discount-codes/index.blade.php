@extends('manager.layouts.app')

@section('title', 'İndirim Kodları')
@section('page_title', 'İndirim Kodları')
@section('page_subtitle', 'Aday öğrenciler için kupon üret · % veya sabit EUR · son kullanma tarihi · kullanım kotası')

@push('head')
<style>
.dc-toolbar { display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-bottom: 14px; }
.dc-toolbar input, .dc-toolbar select { background: var(--u-bg); border: 1px solid var(--u-line);
    border-radius: 7px; padding: 6px 10px; font-size: 13px; color: var(--u-text); }
.dc-toolbar input:focus, .dc-toolbar select:focus { border-color: var(--u-brand); outline:none; }

.dc-table { width:100%; border-collapse: collapse; font-size: 13px; background: var(--u-card);
    border: 1px solid var(--u-line); border-radius: 10px; overflow: hidden; }
.dc-table th { padding: 10px 12px; text-align: left; font-size: 11px; font-weight: 700;
    color: var(--u-muted); text-transform: uppercase; letter-spacing:.4px;
    border-bottom: 2px solid var(--u-line); background: var(--u-bg); }
.dc-table td { padding: 11px 12px; border-bottom: 1px solid var(--u-line); color: var(--u-text); vertical-align: top; }
.dc-code { font-family: monospace; font-size: 13.5px; font-weight: 700; letter-spacing: .5px;
    background: var(--u-bg); padding: 3px 8px; border-radius: 5px; border: 1px solid var(--u-line); }
.dc-meta { color: var(--u-muted); font-size: 11.5px; margin-top: 2px; }
.dc-badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight:700; }
.dc-active   { background: rgba(22,163,74,.12); color: #15803d; }
.dc-inactive { background: rgba(100,116,139,.15); color: #475569; }
.dc-expired  { background: rgba(220,38,38,.12); color: rgb(185,28,28); }

.dc-btn { display: inline-block; padding: 5px 10px; font-size: 11.5px; font-weight: 600;
    border-radius: 6px; border: 1px solid var(--u-line); background: var(--u-bg);
    color: var(--u-text); cursor: pointer; text-decoration: none; }
.dc-btn:hover { background: var(--u-card); border-color: var(--u-brand); }
.dc-btn.primary { background: var(--u-brand,#2563eb); color:white; border-color: var(--u-brand); }
.dc-btn.warn { background: rgba(217,119,6,.1); color: rgb(180,83,9); border-color: rgba(217,119,6,.3); }
.dc-actions { display:flex; gap:6px; flex-wrap:wrap; }
.dc-actions form { margin: 0; display: inline; }
.dc-empty { padding: 30px 20px; text-align:center; color: var(--u-muted); }
</style>
@endpush

@section('content')
<div class="container-fluid">

    @if(session('success'))<div style="background:rgba(22,163,74,.08);color:#15803d;border:1px solid rgba(22,163,74,.3);padding:10px 14px;border-radius:10px;margin-bottom:14px;">✅ {{ session('success') }}</div>@endif
    @if($errors->any())
        <div style="background:rgba(220,38,38,.08);color:rgb(185,28,28);border:1px solid rgba(220,38,38,.3);padding:10px 14px;border-radius:10px;margin-bottom:14px;">
            @foreach($errors->all() as $e) ⚠ {{ $e }}<br> @endforeach
        </div>
    @endif

    <form method="GET" class="dc-toolbar">
        <input type="text" name="q" value="{{ $q }}" placeholder="Kod veya açıklama ara…">
        <select name="status">
            <option value="all"      {{ $status==='all'?'selected':'' }}>Tüm</option>
            <option value="active"   {{ $status==='active'?'selected':'' }}>Aktif</option>
            <option value="inactive" {{ $status==='inactive'?'selected':'' }}>Pasif</option>
        </select>
        <button class="dc-btn" type="submit">Filtrele</button>
        <a class="dc-btn primary" href="{{ route('manager.discount-codes.create') }}" style="margin-left:auto;">+ Yeni Kod</a>
        <a class="dc-btn" href="{{ route('manager.discount-codes.redemptions') }}">📊 Kullanımlar</a>
    </form>

    @if($codes->isEmpty())
        <div class="dc-empty">Hiç indirim kodu yok. Yukarıdaki "+ Yeni Kod" butonu ile başla.</div>
    @else
        <div style="overflow-x:auto;">
            <table class="dc-table">
                <thead>
                    <tr>
                        <th>Kod</th>
                        <th>İndirim</th>
                        <th>Geçerlilik</th>
                        <th>Kullanım</th>
                        <th>Durum</th>
                        <th>Aksiyonlar</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($codes as $c)
                    @php
                        $remaining = $c->max_redemptions !== null ? max(0, $c->max_redemptions - $c->redemption_count) : null;
                        $isExpired = $c->valid_until && $c->valid_until->isPast();
                    @endphp
                    <tr>
                        <td>
                            <span class="dc-code">{{ $c->code }}</span>
                            @if($c->description)
                                <div class="dc-meta">{{ $c->description }}</div>
                            @endif
                        </td>
                        <td>
                            @if($c->discount_type === 'percent')
                                <strong>%{{ rtrim(rtrim(number_format((float) $c->discount_value, 2, '.', ''), '0'), '.') }}</strong>
                            @else
                                <strong>{{ number_format((float) $c->discount_value, 0, ',', '.') }} EUR</strong>
                            @endif
                        </td>
                        <td>
                            @if($c->valid_from)<div class="dc-meta">↳ {{ $c->valid_from->format('d.m.Y') }} itibariyle</div>@endif
                            @if($c->valid_until)
                                <div>{{ $c->valid_until->format('d.m.Y') }} bitiş</div>
                            @else
                                <div class="dc-meta">— sınırsız tarih</div>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $c->redemption_count }}</strong>
                            @if($c->max_redemptions !== null)
                                / {{ $c->max_redemptions }}
                                <div class="dc-meta">{{ $remaining }} kalan</div>
                            @else
                                <div class="dc-meta">sınırsız</div>
                            @endif
                            <div class="dc-meta">kişi başı: {{ $c->max_per_user }}</div>
                        </td>
                        <td>
                            @if($isExpired)
                                <span class="dc-badge dc-expired">Süresi doldu</span>
                            @elseif($c->is_active)
                                <span class="dc-badge dc-active">Aktif</span>
                            @else
                                <span class="dc-badge dc-inactive">Pasif</span>
                            @endif
                        </td>
                        <td>
                            <div class="dc-actions">
                                <a class="dc-btn" href="{{ route('manager.discount-codes.edit', $c) }}">Düzenle</a>
                                <a class="dc-btn" href="{{ route('promo.show', $c->code) }}" target="_blank">👁 Önizle</a>
                                <button class="dc-btn dc-share-btn" type="button"
                                        data-url="{{ url(route('promo.show', $c->code, false)) }}"
                                        title="Paylaşım linkini panoya kopyala">🔗 Linki Kopyala</button>
                                <form method="POST" action="{{ route('manager.discount-codes.toggle', $c) }}">
                                    @csrf
                                    <button class="dc-btn warn" type="submit">{{ $c->is_active ? 'Pasif yap' : 'Aktif yap' }}</button>
                                </form>
                                <a class="dc-btn" href="{{ route('manager.discount-codes.redemptions', ['code_id' => $c->id]) }}">Kullanımlar</a>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top:12px;">{{ $codes->links() }}</div>
    @endif
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
document.querySelectorAll('.dc-share-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
        var url = this.getAttribute('data-url');
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(() => {
                var orig = btn.textContent; btn.textContent = '✓ Kopyalandı!';
                setTimeout(()=>{ btn.textContent = orig; }, 1500);
            });
        } else {
            // fallback
            var ta = document.createElement('textarea'); ta.value = url; document.body.appendChild(ta);
            ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
            var orig = btn.textContent; btn.textContent = '✓ Kopyalandı!';
            setTimeout(()=>{ btn.textContent = orig; }, 1500);
        }
    });
});
</script>
@endpush
@endsection
