<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataProcessingAgreement extends Model
{
    use BelongsToCompany, SoftDeletes;

    public const STATUS_LABELS = [
        'active'     => 'Aktif',
        'pending'    => 'Bekliyor',
        'expired'    => 'Süresi Dolmuş',
        'terminated' => 'Sonlandırıldı',
    ];

    protected $fillable = [
        'company_id',
        'provider_name',
        'provider_url',
        'contact_email',
        'avv_pdf_path',
        'signed_date',
        'expires_date',
        'country',
        'eu_based',
        'processed_categories',
        'purpose_summary',
        'status',
        'notes',
        'updated_by_user_id',
    ];

    protected $casts = [
        'signed_date'          => 'date',
        'expires_date'         => 'date',
        'eu_based'             => 'boolean',
        'processed_categories' => 'array',
    ];

    /**
     * 60 günden az kaldıysa expiry uyarısı.
     */
    public function isExpiringSoon(): bool
    {
        if (!$this->expires_date) return false;
        return $this->expires_date->isBetween(now(), now()->addDays(60));
    }

    public function isExpired(): bool
    {
        if (!$this->expires_date) return false;
        return $this->expires_date->isPast();
    }
}
