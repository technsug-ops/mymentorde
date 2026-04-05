<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class LeadSourceDatum extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'guest_id',
        'initial_source',
        'source_detail',
        'initial_source_detail',
        'initial_source_platform',
        'verified_source',
        'verified_source_detail',
        'source_match',
        'campaign_id',
        'dealer_id',
        'referral_link_id',
        'event_id',
        'cms_content_id',
        'utm_params',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'funnel_registered',
        'funnel_registered_at',
        'funnel_form_completed',
        'funnel_form_completed_at',
        'funnel_documents_uploaded',
        'funnel_documents_uploaded_at',
        'funnel_package_selected',
        'funnel_package_selected_at',
        'funnel_contract_signed',
        'funnel_contract_signed_at',
        'funnel_converted',
        'funnel_converted_at',
        'funnel_dropped_at_stage',
        'content_interactions',
    ];

    protected $casts = [
        'utm_params' => 'array',
        'content_interactions' => 'array',
        'source_match' => 'boolean',
        'funnel_registered' => 'boolean',
        'funnel_form_completed' => 'boolean',
        'funnel_documents_uploaded' => 'boolean',
        'funnel_package_selected' => 'boolean',
        'funnel_contract_signed' => 'boolean',
        'funnel_converted' => 'boolean',
        'funnel_registered_at' => 'datetime',
        'funnel_form_completed_at' => 'datetime',
        'funnel_documents_uploaded_at' => 'datetime',
        'funnel_package_selected_at' => 'datetime',
        'funnel_contract_signed_at' => 'datetime',
        'funnel_converted_at' => 'datetime',
    ];
}
