@extends('manager.layouts.app')
@section('title', ($aiLabsName ?? 'AI Labs') . ' — İçerik Üretici')
@section('page_title','✨ ' . ($aiLabsName ?? 'AI Labs') . ' — İçerik Üretici')

@section('content')
<style>
.alc-wrap { max-width:1200px; margin:20px auto; padding:0 16px; }
.alc-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:22px; margin-bottom:18px; }
.alc-card h2 { margin:0 0 6px; font-size:17px; color:#0f172a; }
.alc-card p.hint { margin:0 0 16px; font-size:12px; color:#64748b; }
.alc-msg-ok { background:#dcfce7; border:1px solid #86efac; color:#166534; padding:10px 14px; border-radius:8px; font-size:13px; margin-bottom:12px; }
.alc-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:14px; }
@media(max-width:900px){ .alc-grid { grid-template-columns:1fr; } }
.alc-template-card {
    background:#fff; border:2px solid #e2e8f0; border-radius:12px; padding:16px;
    cursor:pointer; transition:all .15s; text-decoration:none; color:inherit; display:block;
}
.alc-template-card:hover { border-color:#5b2e91; box-shadow:0 4px 12px rgba(91,46,145,.1); }
.alc-template-icon { font-size:34px; margin-bottom:8px; display:block; }
.alc-template-name { font-weight:700; color:#0f172a; font-size:14px; margin-bottom:6px; }
.alc-template-desc { font-size:11px; color:#64748b; line-height:1.5; }
.alc-drafts { width:100%; border-collapse:collapse; font-size:13px; }
.alc-drafts th { text-align:left; padding:10px 12px; background:#f8fafc; color:#64748b; font-weight:600; font-size:11px; text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid #e2e8f0; }
.alc-drafts td { padding:12px; border-bottom:1px solid #f1f5f9; }
.alc-status { display:inline-block; padding:3px 8px; border-radius:10px; font-size:11px; font-weight:700; }
.alc-status-draft { background:#fef3c7; color:#92400e; }
.alc-status-published { background:#dcfce7; color:#166534; }
.alc-status-archived { background:#f1f5f9; color:#64748b; }
.alc-btn { padding:5px 10px; border:none; border-radius:6px; font-size:11px; font-weight:700; cursor:pointer; text-decoration:none; display:inline-block; }
.alc-btn-ghost { background:#f1f5f9; color:#0f172a; border:1px solid #e2e8f0; }
.alc-btn-danger { background:#dc2626; color:#fff; }
.alc-empty { text-align:center; padding:40px 20px; color:#94a3b8; font-size:14px; }
</style>

<div class="alc-wrap">

    @if (session('status'))
        <div class="alc-msg-ok">{{ session('status') }}</div>
    @endif

    {{-- Template seçimi --}}
    <div class="alc-card">
        <h2>Yeni İçerik Oluştur</h2>
        <p class="hint">Bir template seç → form doldur → AI draft üretir → düzenle → PDF/DOCX/Markdown indir.</p>

        <div class="alc-grid">
            @foreach ($templates as $code => $tpl)
                <a href="{{ url('/manager/ai-labs/content/new/' . $code) }}" class="alc-template-card">
                    <span class="alc-template-icon">{{ $tpl['icon'] }}</span>
                    <div class="alc-template-name">{{ $tpl['name'] }}</div>
                    <div class="alc-template-desc">{{ $tpl['description'] }}</div>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Mevcut draftlar --}}
    <div class="alc-card">
        <h2>📚 Geçmiş İçerikler ({{ $drafts->count() }})</h2>

        @if ($drafts->isEmpty())
            <div class="alc-empty">Henüz içerik üretilmemiş. Yukarıdan bir template seçerek başla.</div>
        @else
            <table class="alc-drafts">
                <thead>
                    <tr>
                        <th>Başlık</th>
                        <th>Template</th>
                        <th>Durum</th>
                        <th>Tokens</th>
                        <th>Tarih</th>
                        <th style="text-align:right;">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($drafts as $d)
                    @php
                        $tpl = $templates[$d->template_code] ?? null;
                        $icon = $tpl['icon'] ?? '📄';
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ url('/manager/ai-labs/content/' . $d->id . '/edit') }}" style="color:#0f172a; font-weight:600; text-decoration:none;">
                                {{ $d->title }}
                            </a>
                        </td>
                        <td style="font-size:12px;">{{ $icon }} {{ $tpl['name'] ?? $d->template_code }}</td>
                        <td>
                            @if ($d->status === 'published')
                                <span class="alc-status alc-status-published">🟢 Yayında</span>
                            @elseif ($d->status === 'archived')
                                <span class="alc-status alc-status-archived">Arşiv</span>
                            @else
                                <span class="alc-status alc-status-draft">📝 Taslak</span>
                            @endif
                        </td>
                        <td style="font-size:11px; color:#64748b;">
                            {{ $d->tokens_input }} / {{ $d->tokens_output }}
                        </td>
                        <td style="font-size:11px; color:#64748b;">{{ $d->updated_at?->diffForHumans() }}</td>
                        <td style="text-align:right; white-space:nowrap;">
                            <a href="{{ url('/manager/ai-labs/content/' . $d->id . '/edit') }}" class="alc-btn alc-btn-ghost">Aç</a>
                            <form method="POST" action="{{ url('/manager/ai-labs/content/' . $d->id) }}" style="display:inline;" onsubmit="return confirm('Silinecek — emin misin?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="alc-btn alc-btn-danger">Sil</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
