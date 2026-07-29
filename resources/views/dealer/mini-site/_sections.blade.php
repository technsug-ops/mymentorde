{{--
    Sayfa kurgusu: bölüm sırası + aç/kapa.
    Kayıt: dealers.site_sections → [{key, on}] (sıra = dizideki sıra).
    Çözümleme/normalleştirme: App\Support\PartnerSiteSections::resolve()
--}}
@php
    $secRows  = \App\Support\PartnerSiteSections::resolve(old('site_sections', $d?->site_sections));
    $btn2     = 'border:1px solid var(--border,#cbd5e1);background:var(--surface,#fff);color:var(--muted,#64748b);'
              . 'border-radius:8px;width:28px;height:28px;line-height:1;cursor:pointer;font-size:13px;padding:0;';
    // Seçili şablon bu bölümleri basıyor mu / sıra onda geçerli mi?
    $tplKey   = $currentTpl ?? \App\Support\PartnerTemplates::DEFAULT;
    $tplName  = \App\Support\PartnerTemplates::all()[$tplKey]['name'] ?? $tplKey;
    $tplMod   = \App\Support\PartnerTemplates::isModular($tplKey);
    $tplSecs  = \App\Support\PartnerTemplates::sectionsOf($tplKey);
@endphp

<div style="{{ $sectionBox }}" data-repeat="site_sections" data-max="{{ count(\App\Support\PartnerSiteSections::SECTIONS) }}" data-fixed>
    <div style="font-weight:600;font-size:14px;margin-bottom:4px;">Sayfa Kurgusu (bölüm sırası)</div>
    <small style="color:var(--muted,#64748b);font-size:12px;">
        Bölümleri ↑ ↓ ile sıralayın, istemediğinizi kapatın. Kapalı bölüm sitede hiç basılmaz.
        Zaten içeriği boş olan bölüm (örn. paket girmediyseniz) açık olsa da görünmez.
        Üst menü, hero, başvuru kutusu ve alt bilgi sabittir.
    </small>

    @unless($tplMod)
        <div style="margin-top:10px;background:#fef9c3;border:1px solid #fde68a;color:#854d0e;border-radius:10px;padding:10px 12px;font-size:12.5px;">
            Seçili şablon (<b>{{ $tplName }}</b>) sabit kurgulu — bölüm sırası ve aç/kapa
            <b>bu şablonda uygulanmaz</b>. Buradaki seçim kaydedilir ve sıralanabilir bir şablona
            (örn. Lavanta) geçtiğinizde devreye girer.
        </div>
    @endunless

    <div data-rows style="margin-top:12px;">
        @foreach($secRows as $i => $s)
            <div data-row style="display:flex;align-items:center;gap:10px;border:1px solid var(--border,#e2e8f0);border-radius:10px;padding:10px 12px;margin-top:8px;background:var(--bg,#f8fafc);">
                <span style="font-size:12px;font-weight:700;color:var(--muted,#64748b);min-width:18px;" data-num>{{ $i + 1 }}</span>
                <input type="hidden" name="site_sections[{{ $i }}][key]" value="{{ $s['key'] }}">
                <input type="hidden" name="site_sections[{{ $i }}][on]" value="0">
                <label style="display:flex;align-items:center;gap:9px;cursor:pointer;flex:1;min-width:0;">
                    <input type="checkbox" name="site_sections[{{ $i }}][on]" value="1" @checked($s['on']) style="width:17px;height:17px;cursor:pointer;flex-shrink:0;">
                    <span style="min-width:0;">
                        <span style="font-size:13.5px;font-weight:600;">{{ $s['label'] }}</span>
                        <span style="font-size:12px;color:var(--muted,#64748b);"> — {{ $s['hint'] }}</span>
                        @unless(in_array($s['key'], $tplSecs, true))
                            <span style="font-size:11px;font-weight:600;color:#854d0e;background:#fef9c3;border-radius:999px;padding:2px 8px;margin-left:6px;white-space:nowrap;">{{ $tplName }} şablonunda yok</span>
                        @endunless
                    </span>
                </label>
                <span style="display:flex;gap:6px;flex-shrink:0;">
                    <button type="button" data-act="up"   title="Yukarı taşı" style="{{ $btn2 }}">↑</button>
                    <button type="button" data-act="down" title="Aşağı taşı" style="{{ $btn2 }}">↓</button>
                </span>
            </div>
        @endforeach
    </div>
</div>
