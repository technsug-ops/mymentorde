@extends(($portalRole ?? 'guest') === 'student' ? 'student.layouts.app' : 'guest.layouts.app')

@section('title', 'Uzman Havuzu — Randevu Al')

@push('content')
@endpush

@section('content')
<div class="pbd-wrap" style="max-width:1180px;margin:24px auto;padding:0 18px;">

    {{-- Header --}}
    <div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:18px;margin-bottom:18px;">
        <div>
            <h1 style="margin:0 0 6px;font-size:24px;font-weight:800;letter-spacing:-.01em;color:var(--c-text,#0f172a);display:flex;align-items:center;gap:10px;">
                <x-icon name="users" size="26" aria-label="Uzman Havuzu" />
                Uzman Havuzu
            </h1>
            <p style="margin:0;color:var(--c-muted,#64748b);font-size:14px;max-width:640px;line-height:1.55;">
                @if($isContracted)
                    Sözleşmen kapsamında istediğin uzmandan <strong>ücretsiz</strong> randevu alabilirsin.
                @else
                    Sana en uygun uzmanı bul, müsait saatleri gör. Ücretli randevu sistemi yakında devreye alınacak.
                @endif
            </p>
        </div>

        @if($isContracted)
            <span style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:999px;background:#dcfce7;color:#166534;font-size:13px;font-weight:700;border:1px solid #86efac;">
                <x-icon name="check-circle" size="14" aria-label="Ücretsiz" />
                Tüm randevular ücretsiz
            </span>
        @endif
    </div>

    {{-- Filter bar --}}
    <form method="GET" action="{{ route($routeName) }}"
          style="display:grid;grid-template-columns:1fr 1fr auto;gap:10px;background:var(--c-surface,#fff);border:1px solid var(--c-border,#e2e8f0);border-radius:14px;padding:14px;margin-bottom:18px;box-shadow:0 1px 2px rgba(0,0,0,.03);">
        <div>
            <label style="display:block;font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--c-muted,#64748b);margin-bottom:4px;font-weight:600;">Konu</label>
            <input type="text" name="topic" value="{{ $filterTopic }}"
                   placeholder="örn. vize, sperrkonto, anmeldung"
                   style="width:100%;padding:9px 12px;border:1px solid var(--c-border,#e2e8f0);border-radius:8px;font-size:13.5px;background:var(--c-surface-2,#fff);color:var(--c-text,#0f172a);">
        </div>
        <div>
            <label style="display:block;font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--c-muted,#64748b);margin-bottom:4px;font-weight:600;">Dil</label>
            <select name="lang"
                    style="width:100%;padding:9px 12px;border:1px solid var(--c-border,#e2e8f0);border-radius:8px;font-size:13.5px;background:var(--c-surface-2,#fff);color:var(--c-text,#0f172a);">
                <option value="">Hepsi</option>
                <option value="Türkçe"   @selected($filterLang==='Türkçe')>Türkçe</option>
                <option value="İngilizce" @selected($filterLang==='İngilizce')>İngilizce</option>
                <option value="Almanca"  @selected($filterLang==='Almanca')>Almanca</option>
            </select>
        </div>
        <div style="display:flex;align-items:flex-end;gap:8px;">
            <button type="submit"
                    style="padding:10px 16px;background:var(--c-accent,#1e40af);color:#fff;border:none;border-radius:8px;font-weight:700;font-size:13px;cursor:pointer;white-space:nowrap;">
                <x-icon name="search" size="14" aria-label="Filtre Uygula" /> Filtrele
            </button>
            @if($filterTopic !== '' || $filterLang !== '')
                <a href="{{ route($routeName) }}"
                   style="padding:10px 14px;background:#f1f5f9;color:#334155;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;">
                    Temizle
                </a>
            @endif
        </div>
    </form>

    {{-- Count --}}
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
        <div style="font-size:13px;color:var(--c-muted,#64748b);">
            <strong style="color:var(--c-text,#0f172a);">{{ $cards->count() }}</strong> uzman gösteriliyor
            @if($cards->count() !== $totalCount)
                <span style="opacity:.7;">(toplam {{ $totalCount }} arasından)</span>
            @endif
        </div>
    </div>

    {{-- Cards grid --}}
    @if($cards->isEmpty())
        <div style="background:var(--c-surface,#fff);border:1px dashed var(--c-border,#e2e8f0);border-radius:14px;padding:48px 24px;text-align:center;color:var(--c-muted,#64748b);">
            <div style="margin-bottom:14px;display:flex;justify-content:center;opacity:.55;">
                <x-icon name="users" size="48" aria-label="Boş" />
            </div>
            <h3 style="margin:0 0 8px;font-size:17px;color:var(--c-text,#0f172a);">Şu an aktif uzman yok</h3>
            <p style="margin:0;font-size:13.5px;max-width:420px;margin:0 auto;line-height:1.55;">
                Filtrelerini değiştirmeyi dene ya da kısa süre sonra tekrar gel — yeni uzmanlar düzenli olarak eklenir.
            </p>
        </div>
    @else
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(310px,1fr));gap:18px;">
            @foreach($cards as $c)
                <article style="background:var(--c-surface,#fff);border:1px solid var(--c-border,#e2e8f0);border-radius:16px;padding:18px;display:flex;flex-direction:column;gap:12px;box-shadow:0 1px 2px rgba(0,0,0,.03);transition:transform .15s,box-shadow .15s;">

                    <div style="display:flex;align-items:flex-start;gap:14px;">
                        @if($c['photo_url'])
                            <img src="{{ $c['photo_url'] }}" alt="{{ $c['name'] }}"
                                 style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid var(--c-border,#e2e8f0);flex-shrink:0;">
                        @else
                            <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#7e58bf,#1e40af);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:22px;flex-shrink:0;">
                                {{ strtoupper(mb_substr($c['name'] ?? $c['display_name'] ?? 'M', 0, 1)) }}
                            </div>
                        @endif
                        <div style="flex:1;min-width:0;">
                            <h3 style="margin:0 0 3px;font-size:16px;font-weight:800;color:var(--c-text,#0f172a);line-height:1.25;">
                                {{ $c['name'] ?? $c['display_name'] }}
                            </h3>
                            <div style="font-size:12px;color:var(--c-muted,#64748b);">
                                {{ $c['slot_duration'] }} dk görüşme
                            </div>
                        </div>
                    </div>

                    @if($c['bio'])
                        <p style="margin:0;font-size:13px;color:var(--c-text-soft,#475569);line-height:1.55;">
                            {{ $c['bio'] }}
                        </p>
                    @endif

                    @if(!empty($c['topics']))
                        <div style="display:flex;flex-wrap:wrap;gap:6px;">
                            @foreach(array_slice($c['topics'], 0, 4) as $topic)
                                <span style="font-size:11.5px;padding:3px 9px;background:var(--accent-soft,#eef2ff);color:var(--c-accent,#1e40af);border-radius:999px;font-weight:600;">
                                    {{ $topic }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    @if(!empty($c['languages']))
                        <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--c-muted,#64748b);">
                            <x-icon name="globe" size="13" aria-label="Diller" />
                            {{ implode(' · ', $c['languages']) }}
                        </div>
                    @endif

                    <div style="margin-top:auto;display:flex;align-items:center;justify-content:space-between;gap:10px;padding-top:6px;">
                        <div>
                            @if($c['is_free'])
                                <span style="display:inline-flex;align-items:center;gap:4px;font-size:12px;padding:4px 10px;background:#dcfce7;color:#166534;border-radius:999px;font-weight:700;border:1px solid #86efac;">
                                    <x-icon name="check-circle" size="12" aria-label="Ücretsiz" />
                                    Ücretsiz
                                </span>
                            @else
                                <span style="display:inline-flex;align-items:center;gap:4px;font-size:12px;padding:4px 10px;background:#fff7ed;color:#9a3412;border-radius:999px;font-weight:700;border:1px solid #fed7aa;">
                                    <x-icon name="clock" size="12" aria-label="Yakında" />
                                    Yakında ücretli
                                </span>
                            @endif
                        </div>
                        <a href="{{ route($routeName . '.show', ['slug' => $c['slug']]) }}"
                           style="display:inline-flex;align-items:center;gap:6px;padding:9px 14px;background:var(--c-accent,#1e40af);color:#fff;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none;white-space:nowrap;">
                            Randevu Al
                            <x-icon name="chevron-right" size="14" aria-label="Devam" />
                        </a>
                    </div>

                </article>
            @endforeach
        </div>
    @endif

</div>
@endsection
