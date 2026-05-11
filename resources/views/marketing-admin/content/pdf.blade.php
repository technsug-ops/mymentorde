<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>MentorDE İçerik Export — {{ now()->format('d.m.Y H:i') }}</title>
    <style>
        @page { margin: 1.4cm 1.4cm 1.8cm 1.4cm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; line-height: 1.55; color: #1e293b; margin: 0; }

        .doc-header { border-bottom: 2px solid #7e58bf; padding-bottom: 10px; margin-bottom: 18px; }
        .doc-header h1 { margin: 0; font-size: 18pt; color: #7e58bf; }
        .doc-header .doc-meta { font-size: 9pt; color: #64748b; margin-top: 4px; }

        .article { page-break-after: always; }
        .article:last-child { page-break-after: auto; }

        .a-head { background: #f8fafc; border-left: 4px solid #7e58bf; padding: 10px 14px; margin-bottom: 14px; }
        .a-code { font-family: monospace; font-size: 9pt; background: rgba(126,88,191,.12); color: #7e58bf; padding: 2px 7px; border-radius: 4px; display: inline-block; font-weight: bold; }
        .a-title { font-size: 16pt; font-weight: bold; margin: 6px 0 4px; color: #0f172a; line-height: 1.3; }
        .a-meta { font-size: 9pt; color: #64748b; margin-top: 5px; }
        .a-meta span { margin-right: 12px; }

        .a-cover { width: 100%; max-height: 220px; object-fit: cover; border-radius: 6px; margin-bottom: 12px; display: block; }
        .a-cover-cap { font-size: 8pt; color: #94a3b8; font-style: italic; margin-top: -8px; margin-bottom: 12px; }

        .a-author { background: rgba(126,88,191,.06); padding: 8px 12px; border-radius: 5px; margin-bottom: 14px; font-size: 10pt; }
        .a-author strong { color: #7e58bf; }

        .a-summary { background: #f8fafc; border-left: 3px solid #94a3b8; padding: 10px 14px; margin-bottom: 14px; font-size: 11pt; font-style: italic; color: #475569; }

        .a-content { font-size: 11pt; line-height: 1.7; }
        .a-content h2 { font-size: 14pt; color: #7e58bf; margin: 16px 0 6px; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px; }
        .a-content h3 { font-size: 12pt; color: #0f172a; margin: 12px 0 4px; }
        .a-content p { margin: 0 0 8px; text-align: justify; }
        .a-content ul, .a-content ol { margin: 6px 0 10px 18px; }
        .a-content li { margin-bottom: 3px; }
        .a-content strong { color: #1e293b; }
        .a-content a { color: #2563eb; text-decoration: underline; }

        .a-footer { margin-top: 16px; padding-top: 8px; border-top: 1px dashed #cbd5e1; font-size: 8pt; color: #94a3b8; }
        .a-tags { margin-top: 6px; }
        .a-tag { display: inline-block; background: rgba(99,102,241,.1); color: #4338ca; padding: 2px 7px; border-radius: 999px; font-size: 8pt; margin-right: 4px; }
    </style>
</head>
<body>
    <div class="doc-header">
        <h1>MentorDE İçerik Arşivi</h1>
        <div class="doc-meta">
            {{ $rows->count() }} içerik · {{ now()->format('d.m.Y H:i') }} ·
            {{ url('/') }}
        </div>
    </div>

    @foreach($rows as $row)
        <div class="article">
            <div class="a-head">
                @if($row->content_code)<span class="a-code">{{ $row->content_code }}</span>@endif
                <div class="a-title">{{ $row->title_tr }}</div>
                <div class="a-meta">
                    @if($row->category)<span><strong>Kategori:</strong> {{ $row->category }}</span>@endif
                    @if($row->type)<span><strong>Tip:</strong> {{ $row->type }}</span>@endif
                    @if($row->status)<span><strong>Durum:</strong> {{ $row->status }}</span>@endif
                    @if($row->published_at)<span><strong>Yayın:</strong> {{ \Illuminate\Support\Carbon::parse($row->published_at)->format('d.m.Y') }}</span>@endif
                    @if($row->metric_total_views)<span><strong>Görüntülenme:</strong> {{ number_format($row->metric_total_views) }}</span>@endif
                </div>
            </div>

            @if($row->cover_image_url && $embedImages && !empty($localCoverPaths[$row->id]))
                <img class="a-cover" src="{{ $localCoverPaths[$row->id] }}" alt="">
                @if($row->cover_image_alt)<div class="a-cover-cap">{{ $row->cover_image_alt }}</div>@endif
            @endif

            @if($row->author_name)
                <div class="a-author">
                    ✍️ <strong>{{ $row->author_name }}</strong>
                    @if($row->author_role) <span style="color:#64748b;">— {{ $row->author_role }}</span>@endif
                </div>
            @endif

            @if($row->summary_tr)
                <div class="a-summary">{{ $row->summary_tr }}</div>
            @endif

            <div class="a-content">
                {!! $row->content_tr ?: '<em style="color:#94a3b8;">İçerik boş.</em>' !!}
            </div>

            <div class="a-footer">
                @if(!empty($row->tags) && is_array($row->tags))
                    <div class="a-tags">
                        @foreach($row->tags as $tag)
                            <span class="a-tag">#{{ $tag }}</span>
                        @endforeach
                    </div>
                @endif
                <div style="margin-top:6px;">
                    Slug: {{ $row->slug }} · ID: #{{ $row->id }} · Oluşturulma: {{ $row->created_at?->format('d.m.Y') ?? '-' }}
                </div>
            </div>
        </div>
    @endforeach
</body>
</html>
