<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentAccommodation extends Model
{
    use SoftDeletes;

    protected $table = 'student_accommodations';

    protected $fillable = [
        'company_id',
        'student_id',
        'type',
        'booking_status',
        'address',
        'city',
        'postal_code',
        'monthly_cost_eur',
        'utilities_included',
        'move_in_date',
        'contract_end_date',
        'landlord_name',
        'landlord_phone',
        'landlord_email',
        'notes',
        'is_visible_to_student',
        'added_by',
    ];

    protected $casts = [
        'move_in_date'          => 'date',
        'contract_end_date'     => 'date',
        'monthly_cost_eur'      => 'decimal:2',
        'utilities_included'    => 'boolean',
        'is_visible_to_student' => 'boolean',
    ];

    public const TYPE_LABELS = [
        'on_campus'  => 'Yurt (Kampüste)',
        'off_campus' => 'Kiralık Daire',
        'host_family'=> 'Aile Yanı',
        'other'      => 'Diğer',
    ];

    public const STATUS_LABELS = [
        'searching'  => 'Aranıyor',
        'applied'    => 'Başvuruldu',
        'booked'     => 'Rezerve Edildi',
        'confirmed'  => 'Onaylandı',
        'cancelled'  => 'İptal Edildi',
    ];

    public const STATUS_BADGE = [
        'searching'  => 'warn',
        'applied'    => 'info',
        'booked'     => 'info',
        'confirmed'  => 'ok',
        'cancelled'  => 'danger',
    ];

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->booking_status] ?? $this->booking_status;
    }

    public function statusBadge(): string
    {
        return self::STATUS_BADGE[$this->booking_status] ?? '';
    }

    public function isConfirmed(): bool
    {
        return $this->booking_status === 'confirmed';
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
