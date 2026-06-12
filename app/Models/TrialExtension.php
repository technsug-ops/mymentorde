<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Platform Owner — Trial uzatma kaydi (audit trail).
 *
 * Platform Owner her manuel trial uzatmasi yaptiginda buraya bir satir dusulur:
 *   - kim uzatti (granted_by_user_id)
 *   - ne kadar (extension_days)
 *   - neden (reason)
 *   - eski / yeni trial_ends_at
 *
 * Bu sayede "x company'nin trial'i neden hala acik?" sorusu cevaplanabilir +
 * Platform Owner'lar arasi sorumluluk takibi yapilir.
 */
class TrialExtension extends Model
{
    protected $fillable = [
        'company_id',
        'extension_days',
        'reason',
        'granted_by_user_id',
        'previous_trial_ends_at',
        'new_trial_ends_at',
    ];

    protected $casts = [
        'extension_days'         => 'integer',
        'previous_trial_ends_at' => 'date',
        'new_trial_ends_at'      => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_user_id');
    }
}
