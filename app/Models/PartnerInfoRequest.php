<?php

namespace App\Models;

use App\Models\Concerns\SharedBetweenTwoCompanies;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Operasyonun partnerden istediği eksik bilgi/belge.
 *
 * ⚠ BelongsToCompany KULLANILMIYOR — bilerek.
 *
 * Bu kaydın iki sahibi var: talebi açan operasyon firması (`company_id`) ve
 * talep edilen partner (`partner_company_id`). Tek firmalı global kapsam
 * taraflardan birini her zaman kör ederdi — partner kendisine gelen talebi
 * göremezdi. Görünürlük sorgularda açıkça kuruluyor (bkz. scopeVisibleTo).
 */
class PartnerInfoRequest extends Model
{
    use SharedBetweenTwoCompanies;

    public const STATUS_OPEN      = 'open';
    public const STATUS_FULFILLED = 'fulfilled';

    public const SUBJECT_GUEST   = 'guest';
    public const SUBJECT_STUDENT = 'student';

    protected $fillable = [
        'company_id',
        'partner_company_id',
        'subject_type',
        'subject_id',
        'subject_name',
        'note',
        'due_at',
        'status',
        'created_by',
        'fulfilled_at',
    ];

    protected $casts = [
        'due_at'       => 'datetime',
        'fulfilled_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PartnerInfoRequestItem::class, 'request_id');
    }

    /**
     * Bir firmanın görebileceği talepler: ya kendisi açmıştır ya da kendisinden
     * istenmiştir.
     */
    public function scopeVisibleTo($query, int $companyId)
    {
        return $query->where(function ($q) use ($companyId) {
            $q->where('company_id', $companyId)
              ->orWhere('partner_company_id', $companyId);
        });
    }

    /** Partnere gelen (cevaplaması gereken) talepler. */
    public function scopeIncomingFor($query, int $companyId)
    {
        return $query->where('partner_company_id', $companyId);
    }

    /**
     * Kalemlerin tamamı geldiyse talebi kapat.
     *
     * Tek tek kalem cevaplandıkça çağrılır; başlık durumunu elle güncellemeye
     * bırakmak, "hepsi geldi ama talep hâlâ açık" gibi bir tutarsızlık üretir.
     */
    public function refreshStatus(): void
    {
        $pending = $this->items()->where('status', PartnerInfoRequestItem::STATUS_PENDING)->count();

        $this->update($pending === 0
            ? ['status' => self::STATUS_FULFILLED, 'fulfilled_at' => now()]
            : ['status' => self::STATUS_OPEN, 'fulfilled_at' => null]);
    }
}
