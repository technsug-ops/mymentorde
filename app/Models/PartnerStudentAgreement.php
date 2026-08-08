<?php

namespace App\Models;

use App\Models\Concerns\SharedBetweenTwoCompanies;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bir öğrenci için partnerin PORTALA ödeyeceği bedel.
 *
 * ⚠ Partnerin öğrenciden ne aldığı burada YOK — bilerek. Öğrenciyi portal
 * white-label takip ediyor; partnerin kendi fiyatı ve sözleşme metni bu
 * sistemin konusu değil. Buradaki rakam iki firma arasındaki alacak.
 *
 * ⚠ BelongsToCompany KULLANILMIYOR: kaydın iki sahibi var. Gerekçe ve
 * sözleşme için bkz. SharedBetweenTwoCompanies ve PartnerAgreement.
 *
 * ── NEDEN TEK ADIMDA KAPANABİLİYOR ──────────────────────────────────────
 * Çerçeve anlaşmada öğrenci başına standart bedel varsa o tutar zaten
 * karşılıklı imzalanmıştır; partnerin ayrıca teklif beklemesi akışı
 * durdururdu. Standart tutarda anlaşma tek adımda `accepted` olur.
 * FARKLI bir tutar isteniyorsa operasyon teklif eder, partner kabul eder.
 */
class PartnerStudentAgreement extends Model
{
    use SharedBetweenTwoCompanies;

    public const STATUS_PROPOSED  = 'proposed';
    public const STATUS_ACCEPTED  = 'accepted';
    public const STATUS_REJECTED  = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'company_id',
        'partner_company_id',
        'agreement_id',
        'guest_application_id',
        'student_id',
        'subject_name',
        'fee_eur',
        'currency',
        'status',
        'proposed_at',
        'proposed_by',
        'accepted_at',
        'accepted_by',
        'note',
    ];

    protected $casts = [
        'fee_eur'     => 'float',
        'proposed_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(PartnerAgreement::class, 'agreement_id');
    }

    public function guestApplication(): BelongsTo
    {
        return $this->belongsTo(GuestApplication::class, 'guest_application_id');
    }

    /** Bir firmanın görebileceği anlaşmalar: ya açan ya karşı taraf. */
    public function scopeVisibleTo(Builder $query, int $companyId): Builder
    {
        return $query->where(function (Builder $q) use ($companyId): void {
            $q->where('company_id', $companyId)
              ->orWhere('partner_company_id', $companyId);
        });
    }

    /**
     * Adayın YÜRÜRLÜKTEKİ anlaşması (kabul edilmiş).
     *
     * Dönüşüm kapısı bunu soruyor: para tarafı netleşmeden öğrenci açılmaz.
     */
    public function scopeAcceptedForGuest(Builder $query, int $guestApplicationId): Builder
    {
        return $query
            ->where('guest_application_id', $guestApplicationId)
            ->where('status', self::STATUS_ACCEPTED);
    }

    public function isAccepted(): bool
    {
        return (string) $this->status === self::STATUS_ACCEPTED;
    }

    /**
     * Bu aday için kabul edilmiş anlaşma var mı?
     *
     * ⚠ Kapsamsız çalışır (model global kapsam kullanmıyor); sınır çağıran
     * taraftaki firma kontrolüdür.
     */
    public static function isSettledForGuest(int $guestApplicationId): bool
    {
        return $guestApplicationId > 0
            && static::query()->acceptedForGuest($guestApplicationId)->exists();
    }
}
