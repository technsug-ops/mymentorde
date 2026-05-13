<?php

namespace App\Http\Resources\Api\Partner;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Liste görünümünde kısa program özeti.
 * Internal field'lar (quality_score, is_manually_curated, metadata) gizli.
 */
class ProgramSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'university_id'            => $this->university_id,
            'university_name'          => $this->university_name_cached,
            'course_name'              => $this->course_name,
            'degree_type'              => $this->degree_type,
            'degree_specification'     => $this->degree_specification,
            'language'                 => $this->language,
            'location'                 => $this->location,
            'duration_semesters'       => $this->duration_semesters,
            'tuition_eur_per_semester' => $this->tuition_eur_per_semester,
            'is_tuition_free'          => $this->tuition_eur_per_semester === null
                || (int) $this->tuition_eur_per_semester === 0,
            'study_fields'             => $this->study_fields ?: [],
            'referral_url'             => $this->buildReferralUrl($request),
        ];
    }

    /**
     * Lead-gen tracking link. Partner sitesindeki "Detaylı öneri al"
     * butonu bu URL'e gönderir → UniMatch funnel'a kayıt olur.
     */
    private function buildReferralUrl(Request $request): string
    {
        /** @var \App\Models\ApiPartner|null $partner */
        $partner = $request->attributes->get('api_partner');
        $slug = $partner?->slug ?? 'unknown';

        return url("/uni-match?utm_source=partner&utm_medium=api&utm_campaign={$slug}&pid={$this->id}");
    }
}
