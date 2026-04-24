@extends('manager.layouts.app')
@section('title', 'Dealer Başvuruları')
@section('page_title', '🤝 Satış Ortağı Başvuruları')

@section('content')
<style>
.da-idx-wrap { max-width:1200px; margin:20px auto; padding:0 16px; }
.da-tabs { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:18px; }
.da-tab { padding:8px 16px; border:1px solid #e2e8f0; border-radius:8px; background:#fff; font-size:13px; font-weight:600; color:#64748b; text-decoration:none; }
.da-tab:hover { background:#f8fafc; }
.da-tab.active { background:#5b2e91; color:#fff; border-color:#5b2e91; }
.da-tab .badge { margin-left:6px; background:rgba(0,0,0,.1); padding:1px 8px; border-radius:10px; font-size:10px; }
.da-tab.active .badge { background:rgba(255,255,255,.25); }

.da-table { width:100%; border-collapse:collapse; background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; font-size:13px; }
.da-table th { background:#f8fafc; padding:10px 12px; text-align:left; font-size:10px; text-transform:uppercase; color:#64748b; letter-spacing:.06em; }
.da-table td { padding:12px; border-top:1px solid #e2e8f0; }
.da-table tr:hover { background:#f8fafc; }
.da-badge { display:inline-block; padding:3px 10px; border-radius:10px; font-size:10px; font-weight:700; }
.da-badge.pending { background:#fef3c7; color:#92400e; }
.da-badge.in_review { background:#dbeafe; color:#1e40af; }
.da-badge.approved { background:#dcfce7; color:#166534; }
.da-badge.rejected { background:#fee2e2; color:#991b1b; }
.da-badge.waitlist { background:#f3e8ff; color:#6b21a8; }
</style>

<div class="da-idx-wrap">
    <div class="da-tabs">
        <a href="?status=pending" class="da-tab {{ $currentStatus === 'pending' ? 'active' : '' }}">
            ⏳ Bekleyen <span class="badge">{{ $counts['pending'] }}</span>
        </a>
        <a href="?status=in_review" class="da-tab {{ $currentStatus === 'in_review' ? 'active' : '' }}">
            🔍 İncelemede <span class="badge">{{ $counts['in_review'] }}</span>
        </a>
        <a href="?status=approved" class="da-tab {{ $currentStatus === 'approved' ? 'active' : '' }}">
            ✅ Onaylı <span class="badge">{{ $counts['approved'] }}</span>
        </a>
        <a href="?status=rejected" class="da-tab {{ $currentStatus === 'rejected' ? 'active' : '' }}">
            ❌ Red <span class="badge">{{ $counts['rejected'] }}</span>
        </a>
        <a href="?status=waitlist" class="da-tab {{ $currentStatus === 'waitlist' ? 'active' : '' }}">
            📋 Waitlist <span class="badge">{{ $counts['waitlist'] }}</span>
        </a>
        <a href="?status=all" class="da-tab {{ $currentStatus === 'all' ? 'active' : '' }}">
            📦 Tümü <span class="badge">{{ $counts['all'] }}</span>
        </a>
    </div>

    @if ($applications->isEmpty())
        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:60px 20px; text-align:center; color:#94a3b8;">
            Bu durumda başvuru yok.
        </div>
    @else
        <table class="da-table">
            <thead>
                <tr>
                    <th style="width:80px;">#</th>
                    <th>Başvuran</th>
                    <th style="width:130px;">Plan</th>
                    <th style="width:100px;">Aylık Hedef</th>
                    <th>UTM Source</th>
                    <th style="width:110px;">Durum</th>
                    <th style="width:110px;">Tarih</th>
                    <th style="width:90px;">Aksiyon</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($applications as $app)
                <tr>
                    <td style="font-family:monospace; color:#64748b;">#{{ str_pad((string) $app->id, 6, '0', STR_PAD_LEFT) }}</td>
                    <td>
                        <div style="font-weight:700;">{{ $app->full_name }}</div>
                        <div style="font-size:11px; color:#64748b;">
                            {{ $app->email }} · {{ $app->phone }}
                            @if ($app->city) · {{ $app->city }}@endif
                        </div>
                    </td>
                    <td>
                        @if ($app->preferred_plan === 'lead_generation')
                            <span class="da-badge" style="background:#dbeafe; color:#1e40af;">🤝 Lead</span>
                        @elseif ($app->preferred_plan === 'freelance')
                            <span class="da-badge" style="background:#fef3c7; color:#92400e;">🎯 Freelance</span>
                        @else
                            <span class="da-badge" style="background:#f1f5f9; color:#64748b;">💡 Kararsız</span>
                        @endif
                    </td>
                    <td>
                        @if ($app->expected_monthly_volume)
                            <strong>{{ $app->expected_monthly_volume }}</strong> aday
                        @else
                            <span style="color:#cbd5e1;">—</span>
                        @endif
                    </td>
                    <td style="font-size:11px; color:#64748b;">
                        @if ($app->utm_source)
                            {{ $app->utm_source }}
                            @if ($app->utm_campaign)<br><span style="color:#94a3b8;">{{ $app->utm_campaign }}</span>@endif
                        @else
                            <span style="color:#cbd5e1;">direct</span>
                        @endif
                    </td>
                    <td>
                        <span class="da-badge {{ $app->status }}">
                            @switch($app->status)
                                @case('pending')⏳ Bekliyor@break
                                @case('in_review')🔍 İncelemede@break
                                @case('approved')✅ Onaylı@break
                                @case('rejected')❌ Red@break
                                @case('waitlist')📋 Waitlist@break
                            @endswitch
                        </span>
                    </td>
                    <td style="font-size:11px; color:#64748b;">
                        {{ $app->created_at->diffForHumans() }}
                    </td>
                    <td>
                        <a href="{{ route('manager.dealer-applications.show', $app->id) }}"
                           style="color:#5b2e91; font-weight:700; text-decoration:none; font-size:12px;">
                            İncele →
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div style="margin-top:20px;">
            {{ $applications->links() }}
        </div>
    @endif
</div>
@endsection
