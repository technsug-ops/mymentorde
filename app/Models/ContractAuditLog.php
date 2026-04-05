<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'guest_application_id',
        'old_status',
        'new_status',
        'changed_by',
        'note',
        'ip',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function guestApplication(): BelongsTo
    {
        return $this->belongsTo(GuestApplication::class);
    }

    /** Belirli bir başvurunun audit trail'ini yeni → eski sırasıyla döndür */
    public static function forApplication(int $guestApplicationId)
    {
        return static::where('guest_application_id', $guestApplicationId)
            ->orderByDesc('created_at');
    }

    /** Yeni kayıt oluşturmak için helper */
    public static function log(
        int    $guestApplicationId,
        ?string $oldStatus,
        string  $newStatus,
        ?string $changedBy = null,
        ?string $note = null,
        ?string $ip = null
    ): static {
        return static::create([
            'guest_application_id' => $guestApplicationId,
            'old_status'           => $oldStatus,
            'new_status'           => $newStatus,
            'changed_by'           => $changedBy,
            'note'                 => $note,
            'ip'                   => $ip,
            'created_at'           => now(),
        ]);
    }
}
