<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name',
        'code',
        'is_active',
        'enabled_modules',
        'doc_request_monthly_limit',
    ];

    protected $casts = [
        'is_active'                 => 'boolean',
        'silence_checkin_overrides' => 'array',
        'enabled_modules'           => 'array',
        'doc_request_monthly_limit' => 'integer',
    ];

    /**
     * D11: Bu ay icin uretilen doc_request token sayisi (quota gating icin).
     */
    public function docRequestMonthlyUsage(?\Carbon\CarbonInterface $now = null): int
    {
        $now = $now ?? \Carbon\CarbonImmutable::now();
        return (int) \App\Models\DocumentUploadToken::query()
            ->where('company_id', $this->id)
            ->whereBetween('created_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
            ->count();
    }

    /**
     * Bu company doc_request quota'sina takildi mi?
     * NULL limit (sinirsiz) ise her zaman false.
     */
    public function isDocRequestQuotaExhausted(?\Carbon\CarbonInterface $now = null): bool
    {
        $limit = $this->doc_request_monthly_limit;
        if ($limit === null || $limit <= 0) return false;
        return $this->docRequestMonthlyUsage($now) >= $limit;
    }
}

