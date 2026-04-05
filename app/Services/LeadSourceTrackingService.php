<?php

namespace App\Services;

use App\Models\GuestApplication;
use App\Models\LeadSourceDatum;
use App\Models\MarketingCampaign;
use App\Models\MarketingTrackingLink;

class LeadSourceTrackingService
{
    public function captureFromGuestApplication(GuestApplication $guestApplication): LeadSourceDatum
    {
        $guestKey = trim((string) $guestApplication->id);
        if ($guestKey === '') {
            abort(422, 'Guest application kimligi bulunamadi.');
        }

        $now = now();
        $initialSource = $this->normalize($guestApplication->lead_source) ?: 'organic';
        $utmSource = $this->normalize($guestApplication->utm_source);
        $utmMedium = $this->normalize($guestApplication->utm_medium);
        $utmCampaign = $this->normalize($guestApplication->utm_campaign);
        $utmTerm = $this->normalize($guestApplication->utm_term);
        $utmContent = $this->normalize($guestApplication->utm_content);
        $trackingLinkCode = $this->normalize($guestApplication->tracking_link_code);
        $trackingLink = $trackingLinkCode
            ? MarketingTrackingLink::query()->where('code', $trackingLinkCode)->first()
            : null;

        if ($trackingLink) {
            $trackedSource = $this->normalize($trackingLink->source_code);
            if ($trackedSource) {
                $initialSource = $trackedSource;
            }
            $utmSource = $utmSource ?: $this->normalize($trackingLink->utm_source);
            $utmMedium = $utmMedium ?: $this->normalize($trackingLink->utm_medium);
            $utmCampaign = $utmCampaign ?: $this->normalize($trackingLink->utm_campaign);
            $utmTerm = $utmTerm ?: $this->normalize($trackingLink->utm_term);
            $utmContent = $utmContent ?: $this->normalize($trackingLink->utm_content);
        }

        $campaignHint = $this->normalize($guestApplication->campaign_code) ?: $utmCampaign;
        if ($campaignHint === '' || $campaignHint === null) {
            $campaignHint = $this->normalize($trackingLink?->campaign_code);
        }
        $campaignId = (int) ($trackingLink?->campaign_id ?? 0);
        if ($campaignId <= 0) {
            $campaignId = $this->resolveCampaignId($campaignHint);
        }
        $dealerCode = $this->normalize($guestApplication->dealer_code) ?: $this->normalize($trackingLink?->dealer_code);

        $utmParams = array_filter([
            'utm_source' => $utmSource,
            'utm_medium' => $utmMedium,
            'utm_campaign' => $utmCampaign,
            'utm_term' => $utmTerm,
            'utm_content' => $utmContent,
            'campaign_code' => $campaignHint,
        ], fn ($v) => $v !== null && $v !== '');

        $row = LeadSourceDatum::query()->firstOrNew([
            'guest_id' => $guestKey,
        ]);

        $row->initial_source = $initialSource;
        if (!$row->initial_source_detail) {
            $row->initial_source_detail = $campaignHint;
        }
        if (!$row->initial_source_platform) {
            $row->initial_source_platform = $utmSource;
        }
        if ($campaignId !== null) {
            $row->campaign_id = $campaignId;
        }
        if (!$row->dealer_id) {
            $row->dealer_id = $dealerCode;
        }
        if ($trackingLinkCode) {
            $row->referral_link_id = $trackingLinkCode;
        }
        $row->utm_source = $utmSource;
        $row->utm_medium = $utmMedium;
        $row->utm_campaign = $utmCampaign;
        $row->utm_term = $utmTerm;
        $row->utm_content = $utmContent;
        $row->utm_params = $utmParams !== [] ? $utmParams : null;

        if (!$row->funnel_registered) {
            $row->funnel_registered = true;
        }
        if (!$row->funnel_registered_at) {
            $row->funnel_registered_at = $guestApplication->created_at ?: $now;
        }
        if (!$row->funnel_form_completed) {
            $row->funnel_form_completed = true;
        }
        if (!$row->funnel_form_completed_at) {
            $row->funnel_form_completed_at = $guestApplication->created_at ?: $now;
        }

        $row->content_interactions = $this->mergeContentInteractions($row, [
            'click_id' => $this->normalize($guestApplication->click_id),
            'landing_url' => $this->normalize($guestApplication->landing_url),
            'referrer_url' => $this->normalize($guestApplication->referrer_url),
            'lead_source' => $initialSource,
            'application_type' => $this->normalize($guestApplication->application_type),
            'branch' => $this->normalize($guestApplication->branch),
            'tracking_link_code' => $trackingLinkCode,
            'captured_at' => $now->toDateTimeString(),
        ]);

        $row->save();

        return $row;
    }

    public function markConverted(GuestApplication $guestApplication): LeadSourceDatum
    {
        $row = $this->captureFromGuestApplication($guestApplication);
        $now = now();

        $verifiedSource = $this->normalize($guestApplication->lead_source) ?: $this->normalize($row->initial_source) ?: 'organic';
        $row->verified_source = $verifiedSource;
        if (!$row->verified_source_detail) {
            $row->verified_source_detail = 'guest_to_student_conversion';
        }

        $initial = mb_strtolower((string) $this->normalize($row->initial_source));
        $verified = mb_strtolower((string) $this->normalize($row->verified_source));
        $row->source_match = ($initial !== '' && $verified !== '') ? ($initial === $verified) : null;

        if (!$row->funnel_package_selected) {
            $row->funnel_package_selected = true;
        }
        if (!$row->funnel_package_selected_at) {
            $row->funnel_package_selected_at = $now;
        }
        if (!$row->funnel_contract_signed) {
            $row->funnel_contract_signed = true;
        }
        if (!$row->funnel_contract_signed_at) {
            $row->funnel_contract_signed_at = $now;
        }
        if (!$row->funnel_converted) {
            $row->funnel_converted = true;
        }
        if (!$row->funnel_converted_at) {
            $row->funnel_converted_at = $now;
        }
        $row->funnel_dropped_at_stage = null;

        $row->content_interactions = $this->mergeContentInteractions($row, [
            'converted_student_id' => $this->normalize($guestApplication->converted_student_id),
            'converted_at' => $now->toDateTimeString(),
        ]);

        $row->save();

        return $row;
    }

    private function resolveCampaignId(?string $campaignHint): ?int
    {
        $hint = $this->normalize($campaignHint);
        if (!$hint) {
            return null;
        }

        $needle = mb_strtolower($hint);
        $campaigns = MarketingCampaign::query()
            ->orderByDesc('updated_at')
            ->limit(500)
            ->get(['id', 'name', 'utm_params']);

        foreach ($campaigns as $campaign) {
            $names = [
                $this->normalize($campaign->name),
            ];
            $utm = is_array($campaign->utm_params) ? $campaign->utm_params : [];
            $names[] = $this->normalize($utm['campaign_code'] ?? null);
            $names[] = $this->normalize($utm['utm_campaign'] ?? null);
            $names[] = $this->normalize($utm['code'] ?? null);

            foreach ($names as $candidate) {
                if ($candidate && mb_strtolower($candidate) === $needle) {
                    return (int) $campaign->id;
                }
            }
        }

        return null;
    }

    private function mergeContentInteractions(LeadSourceDatum $row, array $incoming): array
    {
        $base = is_array($row->content_interactions) ? $row->content_interactions : [];
        $cleanIncoming = array_filter($incoming, fn ($v) => $v !== null && $v !== '');
        if ($cleanIncoming === []) {
            return $base;
        }

        return array_merge($base, $cleanIncoming);
    }

    private function normalize(mixed $value): ?string
    {
        $v = trim((string) ($value ?? ''));
        return $v !== '' ? $v : null;
    }
}
