@extends('student.layouts.app')
@section('title','Gelen Belgeler')
@section('page_title','Kurumdan Gelen Belgeler')

@section('content')

<section class="panel" style="margin-bottom:10px;">
    <div class="muted">Danışmanınız tarafından sizinle paylaşılan kurumsal belgeler aşağıda listelenmiştir.</div>
</section>

@if($grouped->isEmpty())
    <section class="panel">
        <div class="muted">Henüz sizinle paylaşılan belge bulunmuyor.</div>
    </section>
@else
    @foreach($grouped as $catKey => $docs)
        @php
            $catInfo  = $catalog[$catKey] ?? [];
            $catLabel = $catInfo['label_tr'] ?? $catKey;
            $catIcon  = $catInfo['icon'] ?? '';
        @endphp
        <section class="panel" style="margin-bottom:10px;">
            <h3 style="margin:0 0 8px;">{{ $catIcon }} {{ $catLabel }}</h3>
            <div class="list">
                @foreach($docs as $doc)
                    @php
                        $badgeClass = match($doc->status) {
                            'received','completed' => 'ok',
                            'action_required'      => 'warn',
                            'expected'             => 'info',
                            default                => 'pending',
                        };
                        $statusLabel = [
                            'expected'        => 'Bekleniyor',
                            'received'        => 'Alındı',
                            'action_required' => 'Aksiyon Gerekli',
                            'completed'       => 'Tamamlandı',
                            'archived'        => 'Arşivlendi',
                        ][$doc->status] ?? $doc->status;
                    @endphp
                    <div class="item">
                        <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:flex-start;width:100%;">
                            <div style="flex:1;">
                                <strong>{{ $doc->document_type_label }}</strong>
                                @if($doc->institution_name)
                                    <div class="muted" style="font-size:var(--tx-xs);">{{ $doc->institution_name }}</div>
                                @endif
                                @if($doc->received_date)
                                    <div class="muted" style="font-size:var(--tx-xs);">Tarih: {{ $doc->received_date->format('d.m.Y') }}</div>
                                @endif
                                @if($doc->notes)
                                    <div class="muted" style="font-size:var(--tx-xs);margin-top:3px;">{{ $doc->notes }}</div>
                                @endif
                            </div>
                            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;">
                                <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                                @if($doc->file_id && $doc->file)
                                    <a class="btn" href="{{ Storage::url($doc->file->storage_path ?? '') }}"
                                       target="_blank" style="font-size:var(--tx-xs);padding:4px 10px;">⬇ İndir / Aç</a>
                                @endif
                                <span class="muted" style="font-size:var(--tx-xs);">{{ $doc->created_at->format('d.m.Y') }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
@endif

@endsection
