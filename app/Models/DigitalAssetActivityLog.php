<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalAssetActivityLog extends Model
{
    protected $table = 'digital_asset_activity_log';

    public const UPDATED_AT = null; // sadece created_at kullanılıyor

    protected $fillable = [
        'company_id',
        'user_id',
        'user_name',
        'action',
        'target_type',
        'target_id',
        'target_name',
        'meta',
        'ip_address',
    ];

    protected $casts = [
        'meta'       => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper — controller'dan kolay çağrım.
     */
    public static function record(
        string $action,
        string $targetType,
        ?int $targetId,
        ?string $targetName,
        ?User $user,
        array $meta = [],
        ?string $ipAddress = null
    ): void {
        self::create([
            'company_id'  => $user?->company_id,
            'user_id'     => $user?->id,
            'user_name'   => $user?->name,
            'action'      => $action,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'target_name' => $targetName,
            'meta'        => $meta ?: null,
            'ip_address'  => $ipAddress,
        ]);
    }
}
