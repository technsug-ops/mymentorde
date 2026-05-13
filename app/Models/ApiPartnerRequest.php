<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Partner API request audit log entry.
 *
 * Per-request kayıt: endpoint, IP, query params, response code, latency.
 * 90 günden eski kayıtlar scheduled job ile silinir (ileride eklenecek).
 */
class ApiPartnerRequest extends Model
{
    protected $table = 'api_partner_requests';

    public $timestamps = false;  // sadece created_at, useCurrent ile DB'de

    protected $fillable = [
        'api_partner_id',
        'endpoint', 'method', 'ip',
        'query_params',
        'response_code', 'response_time_ms', 'result_count',
        'user_agent',
    ];

    protected $casts = [
        'query_params'     => 'array',
        'response_code'    => 'integer',
        'response_time_ms' => 'integer',
        'result_count'     => 'integer',
        'created_at'       => 'datetime',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(ApiPartner::class, 'api_partner_id');
    }
}
