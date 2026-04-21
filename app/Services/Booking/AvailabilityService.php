<?php

namespace App\Services\Booking;

use App\Models\PublicBooking;
use App\Models\SeniorAvailabilityException;
use App\Models\SeniorAvailabilityPattern;
use App\Models\StudentAppointment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Slot engine'in veri toplayıcısı. DB okumalarını ayırmak + test edilebilir
 * yapmak için SlotGeneratorService'ten ayırdık.
 */
class AvailabilityService
{
    /** @return Collection<int, SeniorAvailabilityPattern> */
    public function loadPatterns(int $seniorUserId): Collection
    {
        return SeniorAvailabilityPattern::query()
            ->withoutGlobalScopes()
            ->where('senior_user_id', $seniorUserId)
            ->where('is_active', true)
            ->get();
    }

    /**
     * @return array<string, SeniorAvailabilityException> date → exception
     */
    public function loadExceptions(int $seniorUserId, string $fromDate, string $toDate): array
    {
        $rows = SeniorAvailabilityException::query()
            ->withoutGlobalScopes()
            ->where('senior_user_id', $seniorUserId)
            ->whereBetween('date', [$fromDate, $toDate])
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $map[$r->date->toDateString()] = $r;
        }
        return $map;
    }

    /**
     * Senior'ın o tarih aralığındaki dolu zaman dilimleri (public_bookings aktif +
     * student_appointments).
     *
     * Dönüş: [{starts_at: CarbonImmutable UTC, ends_at: CarbonImmutable UTC}, ...]
     *
     * @return Collection<int, array{starts_at: CarbonImmutable, ends_at: CarbonImmutable}>
     */
    public function loadBusyIntervals(
        int $seniorUserId,
        CarbonImmutable $from,
        CarbonImmutable $to
    ): Collection {
        $fromUtc = $from->setTimezone('UTC');
        $toUtc   = $to->setTimezone('UTC');

        $busy = collect();

        // 1. Public bookings (aktif)
        PublicBooking::query()
            ->withoutGlobalScopes()
            ->where('senior_user_id', $seniorUserId)
            ->whereIn('status', ['pending_confirm', 'confirmed'])
            ->where('starts_at', '<', $toUtc)
            ->where('ends_at', '>', $fromUtc)
            ->get(['starts_at', 'ends_at'])
            ->each(function ($row) use ($busy): void {
                $busy->push([
                    'starts_at' => CarbonImmutable::parse($row->starts_at)->setTimezone('UTC'),
                    'ends_at'   => CarbonImmutable::parse($row->ends_at)->setTimezone('UTC'),
                ]);
            });

        // 2. Student appointments — senior_email üzerinden eşleştir
        $seniorEmail = \App\Models\User::query()
            ->withoutGlobalScopes()
            ->where('id', $seniorUserId)
            ->value('email');

        if ($seniorEmail) {
            StudentAppointment::query()
                ->withoutGlobalScopes()
                ->whereRaw('lower(senior_email) = ?', [strtolower($seniorEmail)])
                ->whereNotIn('status', ['cancelled', 'canceled'])
                ->where('scheduled_at', '<', $toUtc)
                ->where('scheduled_at', '>=', $fromUtc->copy()->subHours(4))
                ->get(['scheduled_at', 'duration_minutes'])
                ->each(function ($row) use ($busy): void {
                    $start = CarbonImmutable::parse($row->scheduled_at)->setTimezone('UTC');
                    $end   = $start->copy()->addMinutes((int) ($row->duration_minutes ?? 30));
                    $busy->push([
                        'starts_at' => $start,
                        'ends_at'   => $end,
                    ]);
                });
        }

        return $busy;
    }
}
