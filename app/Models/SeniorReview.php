<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Marketplace Phase 7 — Senior review / rating sistemi.
 *
 * Bir senior icin invitee'lerin birakti?i 1-5 yildizli puan + opsiyonel
 * title/body. public_booking_id unique — her rezervasyon icin tek review.
 *
 * Model event'leri (saved/deleted) ilgili senior'un senior_booking_settings
 * tablosundaki avg_rating + total_reviews cache kolonlarini recompute eder.
 */
class SeniorReview extends Model
{
    use BelongsToCompany;

    protected $table = 'senior_reviews';

    protected $fillable = [
        'company_id',
        'public_booking_id',
        'senior_user_id',
        'reviewer_email',
        'reviewer_name',
        'rating',
        'title',
        'body',
        'is_public',
        'is_verified',
        'moderation_status',
        'moderation_note',
        'submitted_at',
        'moderated_at',
    ];

    protected $casts = [
        'is_public'        => 'boolean',
        'is_verified'      => 'boolean',
        'rating'           => 'integer',
        'submitted_at'     => 'datetime',
        'moderated_at'     => 'datetime',
    ];

    /** Recompute throttle — ayni request'te tekrarli save'lerde tek recompute. */
    private static array $recomputeQueue = [];

    protected static function booted(): void
    {
        static::saved(function (SeniorReview $review): void {
            self::scheduleRecompute((int) $review->senior_user_id);
        });
        static::deleted(function (SeniorReview $review): void {
            self::scheduleRecompute((int) $review->senior_user_id);
        });
    }

    public function publicBooking(): BelongsTo
    {
        return $this->belongsTo(PublicBooking::class, 'public_booking_id');
    }

    public function senior(): BelongsTo
    {
        return $this->belongsTo(User::class, 'senior_user_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function scopePublic(Builder $q): Builder
    {
        return $q->where('is_public', true);
    }

    public function scopeApproved(Builder $q): Builder
    {
        return $q->where('moderation_status', 'approved');
    }

    public function scopeVerified(Builder $q): Builder
    {
        return $q->where('is_verified', true);
    }

    /**
     * Sa?lanan senior icin avg_rating + total_reviews + total_completed_bookings
     * cache kolonlarini ham SQL'den hesaplayip senior_booking_settings'e yazar.
     *
     * Idempotent. Hata sessizce log'lanir, eski cache kalir.
     */
    public static function recomputeStats(int $seniorUserId): void
    {
        if ($seniorUserId <= 0) {
            return;
        }

        try {
            $row = self::query()
                ->withoutGlobalScopes()
                ->where('senior_user_id', $seniorUserId)
                ->where('is_public', true)
                ->where('moderation_status', 'approved')
                ->selectRaw('COUNT(*) AS cnt, AVG(rating) AS avg_rt')
                ->first();

            $cnt = (int) ($row->cnt ?? 0);
            $avg = $cnt > 0 ? round((float) $row->avg_rt, 2) : null;

            $completed = (int) DB::table('public_bookings')
                ->where('senior_user_id', $seniorUserId)
                ->whereIn('status', ['confirmed', 'completed'])
                ->count();

            SeniorBookingSetting::query()
                ->withoutGlobalScopes()
                ->where('senior_user_id', $seniorUserId)
                ->update([
                    'avg_rating'               => $avg,
                    'total_reviews'            => $cnt,
                    'total_completed_bookings' => $completed,
                ]);
        } catch (\Throwable $e) {
            Log::warning('senior_review.recompute_failed', [
                'senior_user_id' => $seniorUserId,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    /**
     * Ayni request icinde ayni senior icin birden fazla save olursa
     * recompute'u sona kadar erteleyip tek seferde calistir.
     */
    private static function scheduleRecompute(int $seniorUserId): void
    {
        if ($seniorUserId <= 0) {
            return;
        }
        if (isset(self::$recomputeQueue[$seniorUserId])) {
            return;
        }
        self::$recomputeQueue[$seniorUserId] = true;

        // Hemen recompute — DB transaction icinde guvenli.
        self::recomputeStats($seniorUserId);

        unset(self::$recomputeQueue[$seniorUserId]);
    }
}
