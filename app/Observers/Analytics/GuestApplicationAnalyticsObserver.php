<?php

namespace App\Observers\Analytics;

use App\Models\GuestApplication;
use App\Services\Analytics\AnalyticsService;

/**
 * GuestApplication (= lead) lifecycle events → PostHog.
 *
 * Yakalanan event'ler:
 *   - lead_created
 *   - lead_score_changed (tier veya score değişimi)
 *   - lead_converted (converted_to_student → true)
 */
class GuestApplicationAnalyticsObserver
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    public function created(GuestApplication $lead): void
    {
        $this->analytics->capture('lead_created', [
            'lead_id'     => $lead->id,
            'source'      => $lead->source ?? null,
            'utm_source'  => $lead->utm_source ?? null,
            'utm_medium'  => $lead->utm_medium ?? null,
            'utm_campaign'=> $lead->utm_campaign ?? null,
            'utm_term'    => $lead->utm_term ?? null,
            'utm_content' => $lead->utm_content ?? null,
            'dealer_id'   => $lead->dealer_id ?? null,
            'company_id'  => $lead->company_id ?? null,
            'initial_score' => $lead->lead_score ?? $lead->score ?? 0,
        ], $this->distinctIdFor($lead));
    }

    public function updated(GuestApplication $lead): void
    {
        // lead_score_changed
        $scoreField = $lead->isDirty('lead_score') ? 'lead_score' : ($lead->isDirty('score') ? 'score' : null);
        if ($scoreField) {
            $old = (int) ($lead->getOriginal($scoreField) ?? 0);
            $new = (int) ($lead->{$scoreField} ?? 0);
            if ($old !== $new) {
                $this->analytics->capture('lead_score_changed', [
                    'lead_id'   => $lead->id,
                    'old_score' => $old,
                    'new_score' => $new,
                    'delta'     => $new - $old,
                    'company_id'=> $lead->company_id ?? null,
                ], $this->distinctIdFor($lead));
            }
        }

        // lead_converted (ilk kez student oldu)
        if ($lead->wasChanged('converted_to_student') && $lead->converted_to_student === true) {
            $this->analytics->capture('lead_converted', [
                'lead_id'           => $lead->id,
                'student_id'        => $lead->converted_student_id ?? null,
                'source'            => $lead->source ?? null,
                'utm_source'        => $lead->utm_source ?? null,
                'utm_campaign'      => $lead->utm_campaign ?? null,
                'days_to_convert'   => $lead->created_at ? (int) $lead->created_at->diffInDays(now()) : null,
                'contract_amount'   => (float) ($lead->contract_amount_eur ?? 0),
                'company_id'        => $lead->company_id ?? null,
            ], $this->distinctIdFor($lead));
        }
    }

    /**
     * Lead için distinct_id — converted_student_id varsa user ID, yoksa lead_{id}.
     * Bu sayede anonymous → lead → user zinciri tek kişide birleşir.
     */
    private function distinctIdFor(GuestApplication $lead): string
    {
        if (!empty($lead->converted_student_id)) {
            return (string) $lead->converted_student_id;
        }
        return 'lead_' . $lead->id;
    }
}
