@extends('manager.layouts.app')
@section('title', 'AI Intelligence — ' . trim(($lead->first_name ?? '') . ' ' . ($lead->last_name ?? '')))
@section('page_title', '🤖 AI Intelligence — ' . trim(($lead->first_name ?? '') . ' ' . ($lead->last_name ?? '')))

@section('content')
<style>
.li-wrap { max-width:1200px; margin:20px auto; padding:0 16px; }
.li-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:22px; margin-bottom:18px; }
.li-card h2 { margin:0 0 6px; font-size:16px; color:#0f172a; display:flex; align-items:center; gap:8px; }

.li-header { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; }
@media(max-width:900px){ .li-header { grid-template-columns:1fr; } }
.li-meta-row { display:flex; gap:8px; font-size:12px; margin:6px 0; }
.li-meta-row .key { color:#64748b; min-width:100px; }
.li-meta-row .val { color:#1e293b; font-weight:600; }

.li-timeline { display:flex; flex-direction:column; gap:12px; max-height:700px; overflow-y:auto; padding-right:6px; }
.li-bubble { background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px 14px; }
.li-bubble .q { font-weight:600; color:#1e293b; margin-bottom:6px; }
.li-bubble .a { color:#475569; font-size:13px; line-height:1.55; }
.li-bubble .meta { font-size:10px; color:#94a3b8; margin-top:6px; }
.li-bubble.good { border-left:4px solid #16a34a; }
.li-bubble.bad  { border-left:4px solid #dc2626; background:#fef2f2; }

.li-topic-tag { display:inline-block; background:#dbeafe; color:#1e40af; padding:3px 10px; border-radius:12px; font-size:11px; font-weight:600; margin:2px 4px 2px 0; }

.li-back { display:inline-flex; align-items:center; gap:6px; color:#5b2e91; font-size:13px; margin-bottom:16px; text-decoration:none; }
.li-back:hover { text-decoration:underline; }
</style>

<div class="li-wrap">
    <a href="{{ route('manager.ai-labs.analytics') }}" class="li-back">← AI Labs Analytics'e dön</a>

    {{-- Header — Lead bilgileri --}}
    <div class="li-card">
        <h2>👤 {{ trim(($lead->first_name ?? '') . ' ' . ($lead->last_name ?? '')) ?: 'İsimsiz aday' }}
            @if ($lead->converted_to_student)
                <span class="ala-badge green" style="margin-left:8px;">✅ Müşteri</span>
            @endif
        </h2>
        <div class="li-header">
            <div>
                <div class="li-meta-row"><span class="key">Email</span><span class="val">{{ $lead->email }}</span></div>
                <div class="li-meta-row"><span class="key">Telefon</span><span class="val">{{ $lead->phone ?: '—' }}</span></div>
                <div class="li-meta-row"><span class="key">Oluşturuldu</span><span class="val">{{ $lead->created_at->format('d.m.Y H:i') }}</span></div>
            </div>
            <div>
                <div class="li-meta-row">
                    <span class="key">Lead Skoru</span>
                    <span class="val" style="color:{{ ($lead->lead_score ?? 0) >= 75 ? '#dc2626' : (($lead->lead_score ?? 0) >= 50 ? '#f59e0b' : '#64748b') }}">
                        {{ $lead->lead_score ?? 0 }} / 100
                    </span>
                </div>
                <div class="li-meta-row"><span class="key">Tier</span><span class="val">{{ $lead->lead_score_tier ?? '—' }}</span></div>
                <div class="li-meta-row">
                    <span class="key">Atanan Senior</span>
                    <span class="val">{{ $lead->assigned_senior_email ?: '⚠️ Atanmamış' }}</span>
                </div>
            </div>
            <div>
                <div class="li-meta-row"><span class="key">UTM Source</span><span class="val">{{ $lead->utm_source ?: '—' }}</span></div>
                <div class="li-meta-row"><span class="key">UTM Campaign</span><span class="val">{{ $lead->utm_campaign ?: '—' }}</span></div>
                <div class="li-meta-row"><span class="key">Source</span><span class="val">{{ $lead->source ?? '—' }}</span></div>
            </div>
        </div>
    </div>

    {{-- Konu breakdown --}}
    @if (!empty($topics))
    <div class="li-card">
        <h2>🎯 Bu adayın ilgilendiği konular</h2>
        <p class="hint" style="color:#64748b; font-size:12px; margin:0 0 12px;">
            AI'ya sorduğu {{ $conversations->count() }} sorudan kategorize edildi.
            Toplam {{ number_format($total_tokens / 1000, 1) }}K token harcandı.
        </p>
        <div>
            @foreach ($topics as $cat => $count)
                <span class="li-topic-tag">{{ ucfirst($cat) }} × {{ $count }}</span>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Insight (basit heuristic) --}}
    @php
        $topCats = array_keys(array_slice($topics, 0, 3, true));
        $insightText = '';
        if (in_array('vize', $topCats) && in_array('barınma', $topCats)) {
            $insightText = 'Vize + barınma kombinasyonu → başvuru sonrası aşamada, yakın zamanda karar verecek.';
        } elseif (in_array('dil', $topCats)) {
            $insightText = 'Dil öğrenimi konusu ağır → erken aşamada, uzun vadeli nurturing gerek.';
        } elseif (in_array('maliyet', $topCats) || in_array('blokhesap', $topCats)) {
            $insightText = 'Finansal sorular → bütçe planlaması yapıyor, fiyat hassasiyeti var.';
        } elseif (in_array('üniversite', $topCats)) {
            $insightText = 'Üniversite seçimi yapıyor → program tavsiyesi + referans vaka gösterimi etkili olur.';
        }
    @endphp
    @if ($insightText)
    <div class="li-card" style="background:#fef3c7; border-color:#fcd34d;">
        <h2 style="color:#92400e;">💡 AI Insight</h2>
        <p style="color:#92400e; margin:0; font-size:13px; line-height:1.6;">{{ $insightText }}</p>
    </div>
    @endif

    {{-- Timeline --}}
    <div class="li-card">
        <h2>💬 AI Asistan Konuşma Geçmişi ({{ $conversations->count() }} soru)</h2>
        @if ($conversations->isEmpty())
            <div style="color:#64748b; font-size:13px; padding:20px; text-align:center;">Bu aday AI asistanı henüz kullanmamış.</div>
        @else
            <div class="li-timeline">
                @foreach ($conversations as $c)
                    @php
                        $bubbleClass = match($c->feedback_rating) {
                            'good' => 'good',
                            'bad'  => 'bad',
                            default => '',
                        };
                    @endphp
                    <div class="li-bubble {{ $bubbleClass }}">
                        <div class="q">❓ {{ $c->question }}</div>
                        <div class="a">🤖 {{ \Illuminate\Support\Str::limit($c->answer, 600) }}</div>
                        <div class="meta">
                            {{ $c->created_at->format('d.m.Y H:i') }}
                            · {{ $c->response_mode ?: 'external' }}
                            · {{ ($c->tokens_input ?? 0) + ($c->tokens_output ?? 0) }} token
                            @if ($c->feedback_rating === 'good')
                                · 👍 beğendi
                            @elseif ($c->feedback_rating === 'bad')
                                · 👎 beğenmedi {{ $c->feedback_reason ? "({$c->feedback_reason})" : '' }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
