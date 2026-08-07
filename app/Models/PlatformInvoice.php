<?php

namespace App\Models;

use App\Models\Concerns\PlatformOwnedData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformInvoice extends Model
{
    // Firmanin abonelik faturasi — platform sahibinin gelir kaydi.
    use PlatformOwnedData;

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_SENT      = 'sent';
    public const STATUS_PAID      = 'paid';
    public const STATUS_OVERDUE   = 'overdue';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SENT,
        self::STATUS_PAID,
        self::STATUS_OVERDUE,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'company_id',
        'invoice_number',
        'period_start',
        'period_end',
        'tier',
        'amount_eur',
        'tax_rate_pct',
        'tax_amount_eur',
        'total_eur',
        'status',
        'stripe_invoice_id',
        'sent_at',
        'paid_at',
        'pdf_path',
        'notes',
    ];

    protected $casts = [
        'period_start'   => 'date',
        'period_end'     => 'date',
        'amount_eur'     => 'decimal:2',
        'tax_rate_pct'   => 'decimal:2',
        'tax_amount_eur' => 'decimal:2',
        'total_eur'      => 'decimal:2',
        'sent_at'        => 'datetime',
        'paid_at'        => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Status -> CSS badge sinif eki (paid/overdue/sent/draft/cancelled). */
    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PAID      => 'plat-badge-paid',
            self::STATUS_OVERDUE   => 'plat-badge-overdue',
            self::STATUS_SENT      => 'plat-badge-sent',
            self::STATUS_DRAFT     => 'plat-badge-draft',
            self::STATUS_CANCELLED => 'plat-badge-cancelled',
            default                => 'plat-badge-draft',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PAID      => 'Ödendi',
            self::STATUS_OVERDUE   => 'Gecikti',
            self::STATUS_SENT      => 'Gönderildi',
            self::STATUS_DRAFT     => 'Taslak',
            self::STATUS_CANCELLED => 'İptal',
            default                => ucfirst($this->status),
        };
    }
}
