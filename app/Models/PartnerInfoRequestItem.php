<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Talebin tek bir kalemi: bir belge ya da bir bilgi sorusu.
 *
 * Kalem bazlı tutulmasının sebebi, talebin kısmen karşılanabilmesi. Beş
 * belge istenip üçü geldiğinde "talep açık" demek yetmez; hangi ikisinin
 * beklendiği görünmeli.
 */
class PartnerInfoRequestItem extends Model
{
    public const KIND_DOCUMENT = 'document';
    public const KIND_INFO     = 'info';

    public const STATUS_PENDING  = 'pending';
    public const STATUS_PROVIDED = 'provided';

    protected $fillable = [
        'request_id',
        'kind',
        'category_code',
        'label',
        'status',
        'document_id',
        'response_text',
        'forwarded_token_id',
        'forwarded_at',
        'provided_by',
        'provided_at',
    ];

    protected $casts = [
        'forwarded_at' => 'datetime',
        'provided_at'  => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(PartnerInfoRequest::class, 'request_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /** Partner kalemi kendi öğrencisine iletmiş mi? */
    public function isForwarded(): bool
    {
        return $this->forwarded_at !== null;
    }
}
