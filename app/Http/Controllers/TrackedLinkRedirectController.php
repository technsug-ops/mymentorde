<?php

namespace App\Http\Controllers;

use App\Models\MarketingTrackingClick;
use App\Models\MarketingTrackingLink;
use Illuminate\Http\Request;

class TrackedLinkRedirectController extends Controller
{
    public function __invoke(Request $request, string $code)
    {
        $cleanCode = trim((string) $code);
        abort_if($cleanCode === '', 404);

        $link = MarketingTrackingLink::query()
            ->where('code', $cleanCode)
            ->where('status', 'active')
            ->firstOrFail();

        $link->increment('click_count');
        $link->forceFill(['last_clicked_at' => now()])->save();

        MarketingTrackingClick::query()->create([
            'tracking_link_id' => $link->id,
            'tracking_code' => $link->code,
            'ip_address' => $request->ip(),
            'user_agent' => $this->limit((string) $request->userAgent(), 500),
            'referrer_url' => $this->limit((string) $request->headers->get('referer', ''), 2000),
            'landing_url' => $this->limit($request->fullUrl(), 2000),
            'query_params' => $request->query(),
        ]);

        $target = $this->buildTargetUrl($request, $link);

        return redirect()->away($target);
    }

    private function buildTargetUrl(Request $request, MarketingTrackingLink $link): string
    {
        $raw = trim((string) ($link->destination_path ?: '/apply'));

        // Open redirect koruması: harici domain'leri yok say, sadece path + query kullan.
        // Tracking linkler her zaman kendi domain'imize yönlendirmelidir.
        $parsed      = parse_url($raw);
        $path        = '/'.ltrim((string) ($parsed['path'] ?? '/apply'), '/');
        $rawQuery    = isset($parsed['query']) && $parsed['query'] !== '' ? '?'.$parsed['query'] : '';
        $destination = url($path.$rawQuery);

        $params = [];
        $this->setParam($params, 'utm_source', $link->utm_source, $request->query('utm_source'));
        $this->setParam($params, 'utm_medium', $link->utm_medium, $request->query('utm_medium'));
        $this->setParam($params, 'utm_campaign', $link->utm_campaign, $request->query('utm_campaign'));
        $this->setParam($params, 'utm_term', $link->utm_term, $request->query('utm_term'));
        $this->setParam($params, 'utm_content', $link->utm_content, $request->query('utm_content'));
        $this->setParam($params, 'campaign_code', $link->campaign_code ?: $link->utm_campaign, $request->query('campaign_code'));
        $this->setParam($params, 'dealer_code', $link->dealer_code, $request->query('dealer_code'));
        $this->setParam($params, 'lead_source', $link->source_code, $request->query('lead_source'));

        $params['trk'] = $link->code;

        foreach (['gclid', 'fbclid', 'ttclid'] as $clickKey) {
            $value = trim((string) $request->query($clickKey, ''));
            if ($value !== '') {
                $params[$clickKey] = $value;
            }
        }

        return $this->mergeQuery($destination, $params);
    }

    private function setParam(array &$bag, string $key, mixed $preferred, mixed $fallback): void
    {
        $preferredValue = trim((string) ($preferred ?? ''));
        if ($preferredValue !== '') {
            $bag[$key] = $preferredValue;
            return;
        }

        $fallbackValue = trim((string) ($fallback ?? ''));
        if ($fallbackValue !== '') {
            $bag[$key] = $fallbackValue;
        }
    }

    private function mergeQuery(string $url, array $params): string
    {
        $fragment = '';
        $hashPos = strpos($url, '#');
        if ($hashPos !== false) {
            $fragment = substr($url, $hashPos);
            $url = substr($url, 0, $hashPos);
        }

        [$base, $queryPart] = array_pad(explode('?', $url, 2), 2, '');
        $existing = [];
        if ($queryPart !== '') {
            parse_str($queryPart, $existing);
        }

        $merged = array_merge($existing, array_filter($params, fn ($v) => trim((string) $v) !== ''));
        $qs = http_build_query($merged);

        return $base.($qs !== '' ? '?'.$qs : '').$fragment;
    }

    private function limit(string $value, int $max): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if (mb_strlen($trimmed) <= $max) {
            return $trimmed;
        }

        return mb_substr($trimmed, 0, $max);
    }
}

