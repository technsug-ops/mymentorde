@extends('manager.layouts.app')
@section('title', $tpl ? 'Şablon Düzenle' : 'Yeni Şablon')
@section('page_title', $tpl ? 'Şablon Düzenle' : 'Yeni Şablon')

@section('content')
<div style="max-width:780px;">

@if($errors->any())
<div style="background:#fee2e2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;color:#991b1b;margin-bottom:16px;font-size:13px;">
    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
</div>
@endif

<section class="panel" style="padding:24px 28px;">
    <form method="POST" action="{{ $tpl ? '/manager/doc-templates/'.$tpl->id : '/manager/doc-templates' }}">
        @csrf
        @if($tpl) @method('PUT') @endif

        <div style="display:flex;flex-direction:column;gap:18px;">

            <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:12px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Şablon Adı *</label>
                    <input type="text" name="name" value="{{ old('name', $tpl?->name) }}" required maxlength="150"
                           style="width:100%;box-sizing:border-box;padding:9px 12px;border:1.5px solid var(--u-line);border-radius:8px;font-size:14px;background:var(--u-card);color:var(--u-text);"
                           placeholder="örn. Standart Motivasyon Şablonu">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Belge Tipi *</label>
                    <select name="doc_type" required
                            style="width:100%;padding:9px 10px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-card);color:var(--u-text);">
                        @foreach(\App\Models\DocumentBuilderTemplate::$docTypeLabels as $val => $lbl)
                        <option value="{{ $val }}" @selected(old('doc_type', $tpl?->doc_type) === $val)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Dil *</label>
                    <select name="language" required
                            style="width:100%;padding:9px 10px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-card);color:var(--u-text);">
                        <option value="de" @selected(old('language', $tpl?->language ?? 'de') === 'de')>🇩🇪 Almanca</option>
                        <option value="tr" @selected(old('language', $tpl?->language) === 'tr')>🇹🇷 Türkçe</option>
                        <option value="en" @selected(old('language', $tpl?->language) === 'en')>🇬🇧 İngilizce</option>
                    </select>
                </div>
            </div>

            {{-- section_order --}}
            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">
                    Bölüm Sırası (JSON array) *
                </label>
                <textarea name="section_order" required rows="3"
                          style="width:100%;box-sizing:border-box;padding:9px 12px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-card);color:var(--u-text);font-family:monospace;resize:vertical;">{{ old('section_order', $tpl ? json_encode($tpl->section_order, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) : '["greeting","intro","body","closing"]') }}</textarea>
                <div style="font-size:11px;color:var(--u-muted);margin-top:3px;">Bölüm adlarını sırayla listeleyin. Örn: ["greeting","intro","body","closing"]</div>
            </div>

            {{-- section_templates --}}
            @php
                // Blade parser iç içe {{ }} ile çakışmasın diye default değer
                // PHP bloğunda hazırlanıp sadece değişken echo'lanıyor.
                $_defaultSecTpl = "{\n  \"greeting\": \"Sehr geehrte Damen und Herren,\",\n  \"intro\": \"ich möchte mich um einen Studienplatz im Fach {%target_program%} bewerben.\",\n  \"body\": \"{%motivation_text%}\",\n  \"closing\": \"Mit freundlichen Grüßen,\\n{%full_name%}\"\n}";
                $_defaultSecTpl = str_replace(['{%','%}'], ['{{','}}'], $_defaultSecTpl);
                $_secTplValue = old('section_templates', $tpl ? json_encode($tpl->section_templates, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) : $_defaultSecTpl);
                $_openBraces = '{' . '{';
                $_closeBraces = '}' . '}';
            @endphp
            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">
                    Bölüm İçerikleri (JSON object) *
                </label>
                <textarea name="section_templates" required rows="12"
                          style="width:100%;box-sizing:border-box;padding:9px 12px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-card);color:var(--u-text);font-family:monospace;resize:vertical;">{{ $_secTplValue }}</textarea>
                <div style="font-size:11px;color:var(--u-muted);margin-top:3px;">
                    Desteklenen değişkenler: <code>{{ $_openBraces }}full_name{{ $_closeBraces }}</code>, <code>{{ $_openBraces }}target_program{{ $_closeBraces }}</code>, <code>{{ $_openBraces }}motivation_text{{ $_closeBraces }}</code>, <code>{{ $_openBraces }}birth_date{{ $_closeBraces }}</code>, <code>{{ $_openBraces }}phone{{ $_closeBraces }}</code>, <code>{{ $_openBraces }}email{{ $_closeBraces }}</code>
                </div>
            </div>

            {{-- variables --}}
            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">
                    Değişkenler (JSON, opsiyonel)
                </label>
                <textarea name="variables" rows="3"
                          style="width:100%;box-sizing:border-box;padding:9px 12px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-card);color:var(--u-text);font-family:monospace;resize:vertical;">{{ old('variables', $tpl && $tpl->variables ? json_encode($tpl->variables, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) : '') }}</textarea>
            </div>

            <div style="display:flex;gap:24px;flex-wrap:wrap;">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600;color:var(--u-text);">
                    <input type="checkbox" name="is_active" value="1"
                           @checked(old('is_active', $tpl ? $tpl->is_active : true))
                           style="width:16px;height:16px;">
                    ✓ Aktif (öğrenciye görünür)
                </label>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600;color:var(--u-text);">
                    <input type="checkbox" name="is_default" value="1"
                           @checked(old('is_default', $tpl?->is_default))
                           style="width:16px;height:16px;">
                    ⭐ Bu tip/dil için varsayılan şablon
                </label>
            </div>

        </div>

        <div style="display:flex;gap:8px;margin-top:22px;padding-top:16px;border-top:1px solid var(--u-line);">
            <button type="submit" class="btn ok">{{ $tpl ? 'Güncelle' : 'Kaydet' }}</button>
            <a href="/manager/doc-templates" class="btn alt">İptal</a>
        </div>
    </form>
</section>

</div>

<script>
// JSON alanları validate et
document.querySelector('form').addEventListener('submit', function(e) {
    ['section_order','section_templates','variables'].forEach(function(name) {
        var el = document.querySelector('[name="'+name+'"]');
        if (!el || el.value.trim() === '') return;
        try { JSON.parse(el.value); }
        catch(err) {
            e.preventDefault();
            alert('"'+name+'" geçersiz JSON: ' + err.message);
        }
    });
});
</script>
@endsection
