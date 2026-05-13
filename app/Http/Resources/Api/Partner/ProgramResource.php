<?php

namespace App\Http\Resources\Api\Partner;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Detay görünümü — programa ait tüm public alanlar.
 * Internal field'lar (quality_score, is_manually_curated, metadata, raw source links) gizli.
 */
class ProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                            => $this->id,
            'university' => [
                'id'   => $this->university_id,
                'name' => $this->university_name_cached,
            ],
            'course_name'                   => $this->course_name,
            'degree_type'                   => $this->degree_type,
            'degree_specification'          => $this->degree_specification,
            'language'                      => $this->language,
            'languages_raw'                 => $this->languages_raw ?: [],
            'location'                      => $this->location,
            'duration_semesters'            => $this->duration_semesters,
            'tuition' => [
                'eur_per_semester'          => $this->tuition_eur_per_semester,
                'application_fee_eur'       => $this->application_fee_eur,
                'cost_per_semester_eur'     => $this->cost_per_semester_eur,
                'is_free'                   => $this->tuition_eur_per_semester === null
                    || (int) $this->tuition_eur_per_semester === 0,
            ],
            'deadlines' => [
                'summer'                    => optional($this->application_deadline_summer)->toDateString(),
                'winter'                    => optional($this->application_deadline_winter)->toDateString(),
            ],
            'admission' => [
                'type'                      => $this->admission_type,
                'nc_value'                  => $this->nc_value,
            ],
            'study_fields'                  => $this->study_fields ?: [],
            'subjects'                      => $this->subjects ?: [],
            'description'                   => $this->description_tr ?: $this->description,
            'qualification_requirements'    => $this->qualification_requirements_tr ?: $this->qualification_requirements,
            'language_requirements'         => $this->language_requirements_tr ?: $this->language_requirements,
            'required_documents'            => $this->required_documents_tr ?: $this->required_documents,
            'referral_url'                  => $this->buildReferralUrl($request),
        ];
    }

    private function buildReferralUrl(Request $request): string
    {
        /** @var \App\Models\ApiPartner|null $partner */
        $partner = $request->attributes->get('api_partner');
        $slug = $partner?->slug ?? 'unknown';

        return url("/uni-match?utm_source=partner&utm_medium=api&utm_campaign={$slug}&pid={$this->id}");
    }
}
