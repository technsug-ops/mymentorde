<?php

namespace App\Services\Booking;

use App\Models\PublicBooking;
use App\Models\SeniorAvailabilityException;
use App\Models\SeniorAvailabilityPattern;
use App\Models\SeniorBookingSetting;
use App\Models\StudentAppointment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Senior'ın pattern + exception + mevcut randevularından boş slot listesi üretir.
 *
 * Kullanım:
 *   $svc  = app(SlotGeneratorService::class);
 *   $days = $svc->generateForSenior($seniorUserId, CarbonImmutable::today(), CarbonImmutable::today()->addDays(30));
 *
 * Çıktı:
 *   [
 *     '2026-04-23' => [
 *       ['starts_at' => '09:00', 'ends_at' => '09:30', 'iso_starts_at' => '2026-04-23T09:00:00+02:00'],
 *       ...
 *     ],
 *     ...
 *   ]
 *
 * Zaman:
 *   - Pattern start/end: senior'ın timezone'ında (DB'deki time değerleri lokal TZ).
 *   - Çakışma kontrolü: UTC üzerinden yapılır (public_bookings + student_appointments UTC saklanır).
 *   - Dönen slot'lar senior TZ'sinde (is_starts_at ISO8601 string).
 */
class SlotGeneratorService
{
    public function __construct(
        private readonly AvailabilityService $availability = new AvailabilityService()
    ) {
    }

    /**
     * @return array<string, array<int, array{starts_at:string, ends_at:string, iso_starts_at:string, iso_ends_at:string}>>
     */
    public function generateForSenior(
        int $seniorUserId,
        CarbonImmutable $from,
        CarbonImmutable $to,
        bool $useCache = true
    ): array {
        if ($from->greaterThan($to)) {
            return [];
        }

        $settings = $this->resolveSettings($seniorUserId);
        if (!$settings || !$settings->is_active) {
            return [];
        }

        $tz = $settings->timezone ?: 'Europe/Berlin';

        // Kullanıcının TZ'sinde today gününü üret, min notice'tan önce slot kapat
        $nowTz       = CarbonImmutable::now($tz);
        $minNoticeAt = $nowTz->addHours((int) $settings->min_notice_hours);

        // max_future_days limit — senior ayarına uy
        $maxFutureAt = $nowTz->addDays((int) $settings->max_future_days)->endOfDay();
        if ($to->greaterThan($maxFutureAt)) {
            $to = $maxFutureAt;
        }

        $cacheKey = "booking:slots:{$seniorUserId}:{$from->toDateString()}:{$to->toDateString()}";
        if ($useCache) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        // Pattern & exceptions & busy aralıkları tek seferde çek
        $patterns   = $this->availability->loadPatterns($seniorUserId);
        $exceptions = $this->availability->loadExceptions(
            $seniorUserId,
            $from->toDateString(),
            $to->toDateString()
        );
        $busySlots = $this->availability->loadBusyIntervals(
            $seniorUserId,
            $from->startOfDay(),
            $to->endOfDay()
        );

        $result = [];
        foreach (CarbonPeriod::create($from->startOfDay(), $to->startOfDay()) as $day) {
            $dayDate = CarbonImmutable::instance($day)->toDateString();
            $slots   = $this->generateForDay(
                $day,
                $tz,
                $settings,
                $patterns,
                $exceptions[$dayDate] ?? null,
                $busySlots,
                $minNoticeAt
            );
            if (!empty($slots)) {
                $result[$dayDate] = $slots;
            }
        }

        if ($useCache) {
            Cache::put($cacheKey, $result, now()->addMinutes(5));
        }

        return $result;
    }

    private function generateForDay(
        CarbonImmutable|\DateTimeInterface $day,
        string $tz,
        SeniorBookingSetting $settings,
        Collection $patterns,
        ?SeniorAvailabilityException $exception,
        Collection $busySlots,
        CarbonImmutable $minNoticeAt
    ): array {
        $dayImmutable = CarbonImmutable::instance($day)->setTimezone($tz)->startOfDay();
        $weekday      = (int) $dayImmutable->dayOfWeekIso - 1; // 1..7 → 0..6

        // Exception handling
        if ($exception && $exception->is_blocked) {
            return [];
        }

        // Pattern range'lerini belirle
        $ranges = [];
        if ($exception && !$exception->is_blocked
            && $exception->override_start_time && $exception->override_end_time) {
            $ranges[] = [
                'start' => (string) $exception->override_start_time,
                'end'   => (string) $exception->override_end_time,
            ];
        } else {
            foreach ($patterns as $p) {
                if ((int) $p->weekday !== $weekday || !$p->is_active) {
                    continue;
                }
                $ranges[] = [
                    'start' => (string) $p->start_time,
                    'end'   => (string) $p->end_time,
                ];
            }
        }

        if (empty($ranges)) {
            return [];
        }

        $slotMinutes   = max(15, (int) $settings->slot_duration);
        $bufferMinutes = max(0, (int) $settings->buffer_minutes);
        $output = [];

        foreach ($ranges as $range) {
            $rangeStart = $dayImmutable->setTimeFromTimeString($range['start']);
            $rangeEnd   = $dayImmutable->setTimeFromTimeString($range['end']);

            if ($rangeEnd->lessThanOrEqualTo($rangeStart)) {
                continue;
            }

            $cursor = $rangeStart;
            while ($cursor->copy()->addMinutes($slotMinutes)->lessThanOrEqualTo($rangeEnd)) {
                $slotStart = $cursor;
                $slotEnd   = $cursor->copy()->addMinutes($slotMinutes);

                // Min notice
                if ($slotStart->lessThan($minNoticeAt)) {
                    $cursor = $slotEnd->addMinutes($bufferMinutes);
                    continue;
                }

                // Busy çakışma — UTC'ye çevir, kıyasla
                $slotStartUtc = $slotStart->setTimezone('UTC');
                $slotEndUtc   = $slotEnd->setTimezone('UTC');

                if ($this->overlapsBusy($slotStartUtc, $slotEndUtc, $busySlots, $bufferMinutes)) {
                    $cursor = $slotEnd->addMinutes($bufferMinutes);
                    continue;
                }

                $output[] = [
                    'starts_at'     => $slotStart->format('H:i'),
                    'ends_at'       => $slotEnd->format('H:i'),
                    'iso_starts_at' => $slotStart->toIso8601String(),
                    'iso_ends_at'   => $slotEnd->toIso8601String(),
                ];

                $cursor = $slotEnd->addMinutes($bufferMinutes);
            }
        }

        return $output;
    }

    /**
     * Slot bir busy interval ile çakışıyor mu? Buffer da dahil (±buffer dk).
     *
     * @param Collection<int, array{starts_at: CarbonImmutable, ends_at: CarbonImmutable}> $busySlots
     */
    private function overlapsBusy(
        CarbonImmutable $slotStartUtc,
        CarbonImmutable $slotEndUtc,
        Collection $busySlots,
        int $bufferMinutes
    ): bool {
        $paddedStart = $slotStartUtc->copy()->subMinutes($bufferMinutes);
        $paddedEnd   = $slotEndUtc->copy()->addMinutes($bufferMinutes);

        foreach ($busySlots as $busy) {
            $busyStart = $busy['starts_at'];
            $busyEnd   = $busy['ends_at'];
            if ($busyEnd->lessThanOrEqualTo($paddedStart) || $busyStart->greaterThanOrEqualTo($paddedEnd)) {
                continue;
            }
            return true;
        }
        return false;
    }

    private function resolveSettings(int $seniorUserId): ?SeniorBookingSetting
    {
        return SeniorBookingSetting::query()
            ->withoutGlobalScopes()
            ->where('senior_user_id', $seniorUserId)
            ->first();
    }
}
