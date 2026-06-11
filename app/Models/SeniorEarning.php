<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Senior'in marketplace üzerinden kazandığı net tutarı (cent) izleyen tablo.
 *
 * Status akışı (Marketplace Phase 5):
 *   recorded   → booking ödendi, henüz iptal/refund penceresinde
 *   available  → 24 saat geçti, payout edilebilir hale geldi (settle command)
 *   paid_out   → SeniorPayout altında ödendi (Phase 6)
 *   refunded   → müşteri iadesi sonrası earning ters cevrildi
 *   voided     → manuel iptal / hata (ileride manager iptali için)
 *
 * NOT: Tüm tutarlar `_cents` integer cinsindendir — float yuvarlama hatasını
 * önlemek için her zaman cents üzerinden işle, gösterimde 100'e böl.
 */
class SeniorEarning extends Model
{
    use BelongsToCompany;

    public const STATUS_RECORDED = 'recorded';
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_PAID_OUT = 'paid_out';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_VOIDED = 'voided';

    protected $table = 'senior_earnings';

    protected $fillable = [
        'company_id',
        'senior_user_id',
        'public_booking_id',
        'student_appointment_id',
        'amount_net_cents',
        'tax_rate_pct_applied',
        'tax_amount_cents',
        'amount_gross_cents',
        'commission_pct_applied',
        'commission_cents',
        'senior_payout_cents',
        'currency',
        'status',
        'payout_id',
        'recorded_at',
        'available_at',
        'reversed_at',
        'stripe_charge_id',
    ];

    protected $casts = [
        'amount_net_cents'       => 'integer',
        'tax_rate_pct_applied'   => 'decimal:2',
        'tax_amount_cents'       => 'integer',
        'amount_gross_cents'     => 'integer',
        'commission_pct_applied' => 'decimal:2',
        'commission_cents'       => 'integer',
        'senior_payout_cents'    => 'integer',
        'recorded_at'            => 'datetime',
        'available_at'           => 'datetime',
        'reversed_at'            => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────

    public function senior(): BelongsTo
    {
        return $this->belongsTo(User::class, 'senior_user_id');
    }

    public function publicBooking(): BelongsTo
    {
        return $this->belongsTo(PublicBooking::class, 'public_booking_id');
    }

    public function studentAppointment(): BelongsTo
    {
        return $this->belongsTo(StudentAppointment::class, 'student_appointment_id');
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(SeniorPayout::class, 'payout_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    /**
     * Henüz settle penceresinde bekleyen kazançlar (booking_paid → +24h).
     * Phase 5: pending semantik, status=recorded.
     */
    public function scopePending(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_RECORDED);
    }

    /**
     * Available kazançlar — senior'a payout için hazır.
     */
    public function scopeAvailable(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_AVAILABLE);
    }

    /**
     * Ödenmiş kazançlar — Phase 6 payout ile bağlanır.
     */
    public function scopePaid(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_PAID_OUT);
    }

    public function scopeRefunded(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_REFUNDED);
    }

    // ── Transitions ───────────────────────────────────────────────────────

    /**
     * 24 saatlik iptal penceresi kapanınca settle command bunu çağırır.
     * Idempotent — aynı kayıt birden fazla kez işlense de status'u yalnız bir kez değişir.
     */
    public function markAvailable(?\DateTimeInterface $at = null): bool
    {
        if ($this->status !== self::STATUS_RECORDED) {
            return false;
        }
        return (bool) $this->update([
            'status'       => self::STATUS_AVAILABLE,
            'available_at' => $at ?? now(),
        ]);
    }

    /**
     * Refund tetiklendiğinde earning ters çevrilir — Phase 6 payout dahil edilmez.
     */
    public function markRefunded(?\DateTimeInterface $at = null): bool
    {
        if (in_array($this->status, [self::STATUS_REFUNDED, self::STATUS_PAID_OUT], true)) {
            // Zaten refunded veya ödenmiş — ödenmiş ise manuel takip gerekir,
            // burada otomatik dönüş yok (Phase 6 payout reversal).
            return false;
        }
        return (bool) $this->update([
            'status'      => self::STATUS_REFUNDED,
            'reversed_at' => $at ?? now(),
        ]);
    }

    /** EUR cinsinden senior payout (UI gösterimi). */
    public function payoutEur(): float
    {
        return round(((int) $this->senior_payout_cents) / 100, 2);
    }
}
