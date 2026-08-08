<?php

namespace App\Models;

use App\Models\Concerns\SharedBetweenTwoCompanies;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Portal ↔ partner firma ÇERÇEVE anlaşması.
 *
 * ⚠ BelongsToCompany KULLANILMIYOR — bilerek. Kaydın iki sahibi var:
 * anlaşmayı açan portal firması (`company_id`) ve partner
 * (`partner_company_id`). Tek firmalı global kapsam taraflardan birini her
 * zaman kör ederdi — partner kendi anlaşmasını göremezdi. Görünürlük
 * sorgularda açıkça kuruluyor (bkz. scopeVisibleTo).
 *
 * Bu, öğrenciyle yapılan sözleşme değil; partner firmayla yapılan iş
 * anlaşması. Öğrenci başına standart bedeli taşır ve öğrenci bazlı
 * anlaşmalar bu tutarı varsayılan alır.
 */
class PartnerAgreement extends Model
{
    use SharedBetweenTwoCompanies;

    public const STATUS_DRAFT      = 'draft';
    public const STATUS_SENT       = 'sent';
    public const STATUS_SIGNED     = 'signed';
    public const STATUS_TERMINATED = 'terminated';

    protected $fillable = [
        'company_id',
        'partner_company_id',
        'title',
        'body_text',
        'standard_student_fee_eur',
        'currency',
        'status',
        'valid_from',
        'valid_until',
        'sent_at',
        'signed_at',
        'signed_by_email',
        'terminated_at',
        'termination_reason',
        'signed_file_path',
        'created_by',
    ];

    protected $casts = [
        'standard_student_fee_eur' => 'float',
        'valid_from'               => 'date',
        'valid_until'              => 'date',
        'sent_at'                  => 'datetime',
        'signed_at'                => 'datetime',
        'terminated_at'            => 'datetime',
    ];

    public function studentAgreements(): HasMany
    {
        return $this->hasMany(PartnerStudentAgreement::class, 'agreement_id');
    }

    public function partnerCompany()
    {
        return $this->belongsTo(Company::class, 'partner_company_id');
    }

    /** Bir firmanın görebileceği anlaşmalar: ya açan ya karşı taraf. */
    public function scopeVisibleTo(Builder $query, int $companyId): Builder
    {
        return $query->where(function (Builder $q) use ($companyId): void {
            $q->where('company_id', $companyId)
              ->orWhere('partner_company_id', $companyId);
        });
    }

    /** Partnerin imzalaması gereken / imzaladığı anlaşmalar. */
    public function scopeForPartner(Builder $query, int $partnerCompanyId): Builder
    {
        return $query->where('partner_company_id', $partnerCompanyId);
    }

    /**
     * Yürürlükteki anlaşma: imzalanmış, feshedilmemiş, tarihi geçmemiş.
     *
     * Öğrenci bazlı anlaşmanın varsayılan tutarı buradan geliyor; süresi
     * dolmuş bir çerçevenin fiyatını uygulamak sessiz bir hata olurdu.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_SIGNED)
            ->whereNull('terminated_at')
            ->where(fn (Builder $q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', now()->toDateString()))
            ->where(fn (Builder $q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', now()->toDateString()));
    }

    public function isActive(): bool
    {
        return (string) $this->status === self::STATUS_SIGNED
            && $this->terminated_at === null
            && ($this->valid_from === null || ! $this->valid_from->isFuture())
            && ($this->valid_until === null || ! $this->valid_until->isPast());
    }

    /** Çerçevede peşinen anlaşılmış öğrenci başı bedel; yoksa null. */
    public function standardFee(): ?float
    {
        $fee = $this->standard_student_fee_eur;

        return $fee !== null && $fee > 0 ? (float) $fee : null;
    }
}
