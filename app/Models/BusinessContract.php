<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessContract extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'contract_type',
        'dealer_id',
        'user_id',
        'template_id',
        'contract_no',
        'title',
        'body_text',
        'meta',
        'status',
        'issued_at',
        'signed_at',
        'approved_at',
        'signed_file_path',
        'issued_by',
        'approved_by',
        'notes',
    ];

    protected $casts = [
        'meta'        => 'array',
        'issued_at'   => 'datetime',
        'signed_at'   => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function staffUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function issuedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft'          => 'Taslak',
            'issued'         => 'Gönderildi',
            'signed_uploaded'=> 'İmzalı Yüklendi',
            'approved'       => 'Onaylandı',
            'cancelled'      => 'İptal',
            default          => $this->status,
        };
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'draft'          => 'pending',
            'issued'         => 'info',
            'signed_uploaded'=> 'warn',
            'approved'       => 'ok',
            'cancelled'      => 'danger',
            default          => 'pending',
        };
    }
}
