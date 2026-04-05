<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentShipment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'student_id',
        'direction',
        'carrier',
        'tracking_number',
        'content_description',
        'sent_at',
        'estimated_delivery',
        'delivered_at',
        'status',
        'notes',
        'is_visible_to_student',
        'added_by',
    ];

    protected $casts = [
        'is_visible_to_student' => 'boolean',
        'sent_at'               => 'date',
        'estimated_delivery'    => 'date',
        'delivered_at'          => 'date',
    ];

    public const STATUS_LABELS = [
        'preparing'  => 'Hazırlanıyor',
        'shipped'    => 'Gönderildi',
        'in_transit' => 'Transfer',
        'delivered'  => 'Teslim Edildi',
        'returned'   => 'İade',
        'lost'       => 'Kayıp',
    ];

    public const STATUS_BADGE = [
        'preparing'  => 'pending',
        'shipped'    => 'info',
        'in_transit' => 'info',
        'delivered'  => 'ok',
        'returned'   => 'warn',
        'lost'       => 'danger',
    ];

    public const CARRIER_LABELS = [
        'PTT'      => 'PTT',
        'DHL'      => 'DHL',
        'UPS'      => 'UPS',
        'Yurtiçi'  => 'Yurtiçi Kargo',
        'FedEx'    => 'FedEx',
        'other'    => 'Diğer',
    ];

    public function scopeVisibleToStudent($query)
    {
        return $query->where('is_visible_to_student', true);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
