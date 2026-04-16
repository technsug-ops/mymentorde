@extends('manager.layouts.app')
@section('title', 'Belge Şablonları')
@section('page_title', 'Belge Şablonları')

@section('topbar-actions')
<a href="/manager/doc-templates/create" class="btn ok" style="font-size:12px;padding:6px 16px;">+ Yeni Şablon</a>
@endsection

@section('content')

@if(session('status'))
<div style="margin-bottom:12px;padding:10px 16px;border-radius:8px;background:#dcfce7;color:#166534;font-weight:600;font-size:13px;border:1px solid #bbf7d0;">{{ session('status') }}</div>
@endif

{{-- Filtre --}}
<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px;">
    @php $types = ['' => 'Tümü'] + \App\Models\DocumentBuilderTemplate::$docTypeLabels; @endphp
    @foreach($types as $val => $lbl)
    <a href="?doc_type={{ $val }}"
       style="padding:5px 14px;border-radius:999px;font-size:12px;font-weight:600;text-decoration:none;
              {{ $docType === $val ? 'background:#1e40af;color:#fff;' : 'background:var(--u-bg);color:var(--u-muted);border:1px solid var(--u-line);' }}">
        {{ $lbl }}
    </a>
    @endforeach
</div>

<section class="panel" style="padding:0;overflow:hidden;">
    @if($templates->isEmpty())
    <div style="padding:40px;text-align:center;color:var(--u-muted);font-size:13px;">Henüz şablon yok. <a href="/manager/doc-templates/create" style="color:var(--u-brand);">İlk şablonu oluştur →</a></div>
    @else
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead><tr style="background:var(--u-bg);">
                <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Şablon Adı</th>
                <th style="padding:10px 14px;text-align:center;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;width:70px;">Dil</th>
                <th style="padding:10px 14px;text-align:center;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;width:85px;">Durum</th>
                <th style="padding:10px 14px;text-align:center;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;width:50px;">v</th>
                <th style="padding:10px 14px;text-align:center;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;width:48px;">⭐</th>
                <th style="padding:10px 14px;text-align:center;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;width:80px;">Önizleme</th>
                <th style="padding:10px 14px;text-align:center;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;width:68px;">İndir</th>
                <th style="padding:10px 14px;text-align:center;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;width:82px;">Düzenle</th>
                <th style="padding:10px 14px;text-align:center;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;width:60px;">Sil</th>
            </tr></thead>
            <tbody>
            @php $prevDocType = null; @endphp
            @foreach($templates as $t)
                @if($prevDocType !== $t->doc_type)
                <tr style="background:linear-gradient(to right, #eff6ff, transparent);">
                    <td colspan="9" style="padding:10px 14px;font-size:11px;font-weight:800;color:#1e40af;text-transform:uppercase;letter-spacing:.05em;border-top:2px solid #bfdbfe;">
                        {{ \App\Models\DocumentBuilderTemplate::$docTypeLabels[$t->doc_type] ?? $t->doc_type }}
                    </td>
                </tr>
                @php $prevDocType = $t->doc_type; @endphp
                @endif
                <tr style="border-bottom:1px solid var(--u-line);{{ !$t->is_active ? 'opacity:.5;' : '' }}">
                    <td style="padding:10px 14px 10px 24px;font-weight:600;color:var(--u-text);">
                        {{ $t->name }}
                        @if($t->is_default) <span style="font-size:10px;background:#fef9c3;color:#854d0e;border-radius:4px;padding:1px 6px;margin-left:4px;font-weight:700;">⭐ VARSAYILAN</span> @endif
                    </td>
                    <td style="padding:10px 14px;text-align:center;font-size:12px;font-weight:700;">{{ strtoupper($t->language) }}</td>
                    <td style="padding:10px 14px;text-align:center;">
                        <span style="font-size:11px;font-weight:600;color:{{ $t->is_active ? 'var(--u-ok)' : 'var(--u-muted)' }}">
                            {{ $t->is_active ? '✓ Aktif' : 'Pasif' }}
                        </span>
                    </td>
                    <td style="padding:10px 14px;text-align:center;font-size:12px;color:var(--u-muted);">v{{ $t->version }}</td>
                    <td style="padding:10px 14px;text-align:center;">
                        @if($t->is_default)
                            <span title="Varsayılan şablon" style="font-size:14px;">⭐</span>
                        @else
                            <form method="POST" action="/manager/doc-templates/{{ $t->id }}/set-default" style="display:inline;margin:0;">
                                @csrf
                                <button type="submit" class="btn alt" style="font-size:11px;padding:3px 8px;opacity:.4;" title="Varsayılan yap">☆</button>
                            </form>
                        @endif
                    </td>
                    <td style="padding:10px 14px;text-align:center;">
                        <a href="/manager/doc-templates/{{ $t->id }}/preview" target="_blank" class="btn alt" style="font-size:11px;padding:4px 10px;text-decoration:none;" title="Önizleme">👁 Gör</a>
                    </td>
                    <td style="padding:10px 14px;text-align:center;">
                        <a href="/manager/doc-templates/{{ $t->id }}/download" class="btn alt" style="font-size:11px;padding:4px 10px;text-decoration:none;background:#16a34a;color:#fff;border-color:#15803d;" title="PDF indir">⬇ PDF</a>
                    </td>
                    <td style="padding:10px 14px;text-align:center;">
                        <a href="/manager/doc-templates/{{ $t->id }}/edit" class="btn alt" style="font-size:11px;padding:4px 10px;text-decoration:none;">✏️ Düzenle</a>
                    </td>
                    <td style="padding:10px 14px;text-align:center;">
                        <form method="POST" action="/manager/doc-templates/{{ $t->id }}" onsubmit="return confirm('Sil?')" style="display:inline;margin:0;">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn warn" style="font-size:11px;padding:4px 10px;">🗑 Sil</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
</section>

{{-- Bilgi Kartı --}}
<section class="panel" style="padding:16px 20px;margin-top:16px;background:#eff6ff;border:1px solid #bfdbfe;">
    <div style="font-size:13px;font-weight:700;color:#1e40af;margin-bottom:6px;">📋 Şablon Sistemi Hakkında</div>
    <div style="font-size:12px;color:#1e40af;line-height:1.7;">
        Şablonlar öğrencilerin belge oluştururken kullanabileceği bölüm yapıları ve metin iskeletleri içerir.
        Her belge tipi için birden fazla şablon oluşturabilirsiniz. ⭐ işaretli şablon öğrenciye varsayılan olarak sunulur.
        Şablonlar <code>section_order</code> (bölüm sırası) ve <code>section_templates</code> (bölüm içerikleri) JSON yapılarından oluşur.
    </div>
</section>
@endsection
