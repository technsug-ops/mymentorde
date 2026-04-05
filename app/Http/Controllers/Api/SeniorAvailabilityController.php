<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentAppointment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeniorAvailabilityController extends Controller
{
    // Her senior için varsayılan çalışma saatleri (Pzt-Cum 09:00-18:00, 45 dk slot, 15 dk buffer)
    private const DEFAULT_WORK_START = '09:00';
    private const DEFAULT_WORK_END   = '18:00';
    private const SLOT_MINUTES       = 45;
    private const BUFFER_MINUTES     = 15;
    private const WORK_DAYS          = [1, 2, 3, 4, 5]; // Carbon: 1=Pazartesi, 5=Cuma

    /**
     * GET /api/senior/{seniorId}/available-slots?date=2026-03-15
     *
     * Verilen tarihe ait müsait randevu slotlarını döner.
     * Yalnızca auth kullanıcısı kendi şirketindeki senior'ı sorgulayabilir.
     */
    public function availableSlots(Request $request, int $seniorId): JsonResponse
    {
        $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $date = Carbon::createFromFormat('Y-m-d', $request->input('date'))->startOfDay();

        // Geçmiş tarih kontrolü
        if ($date->lt(Carbon::today())) {
            return response()->json(['error' => 'Geçmiş tarih sorgulanamaz.'], 422);
        }

        // Hafta sonu kontrolü
        if (!in_array($date->dayOfWeek, self::WORK_DAYS, true)) {
            return response()->json([
                'date'            => $date->toDateString(),
                'available_slots' => [],
                'note'            => 'Seçilen gün çalışma günü değil.',
            ]);
        }

        // Senior varlık kontrolü (aynı şirketten)
        $senior = User::query()
            ->where('id', $seniorId)
            ->where('role', 'senior')
            ->where(function ($q) use ($request): void {
                if ($request->user()?->company_id) {
                    $q->where('company_id', $request->user()->company_id);
                }
            })
            ->first();

        if (!$senior) {
            return response()->json(['error' => 'Senior bulunamadı.'], 404);
        }

        // Mevcut randevuları çek
        $bookedSlots = StudentAppointment::query()
            ->where('senior_email', $senior->email)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereDate('scheduled_at', $date->toDateString())
            ->get(['scheduled_at', 'duration_minutes']);

        // Slotları oluştur
        $slots       = $this->generateSlots($date, $bookedSlots);

        return response()->json([
            'date'            => $date->toDateString(),
            'senior_id'       => $seniorId,
            'senior_name'     => $senior->name,
            'available_slots' => $slots,
        ]);
    }

    /**
     * Verilen gün için müsait zaman slotlarını hesaplar.
     *
     * @param  \Carbon\Carbon                                        $date
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Models\StudentAppointment> $bookedSlots
     * @return array<int, array{time: string, datetime: string, available: bool}>
     */
    private function generateSlots(Carbon $date, $bookedSlots): array
    {
        $start    = $date->copy()->setTimeFromTimeString(self::DEFAULT_WORK_START);
        $end      = $date->copy()->setTimeFromTimeString(self::DEFAULT_WORK_END);
        $stepMins = self::SLOT_MINUTES + self::BUFFER_MINUTES;

        // Dolu aralıkları hesapla
        $busyRanges = $bookedSlots->map(function ($appt): array {
            $from = Carbon::parse($appt->scheduled_at);
            $to   = $from->copy()->addMinutes((int) ($appt->duration_minutes ?: self::SLOT_MINUTES) + self::BUFFER_MINUTES);
            return ['from' => $from, 'to' => $to];
        });

        $slots   = [];
        $current = $start->copy();

        while ($current->copy()->addMinutes(self::SLOT_MINUTES)->lte($end)) {
            $slotEnd    = $current->copy()->addMinutes(self::SLOT_MINUTES);
            $isAvailable = $busyRanges->every(function (array $range) use ($current, $slotEnd): bool {
                // Çakışma yoksa müsait: slot_end <= range_from || slot_start >= range_to
                return $slotEnd->lte($range['from']) || $current->gte($range['to']);
            });

            $slots[] = [
                'time'      => $current->format('H:i'),
                'datetime'  => $current->toIso8601String(),
                'available' => $isAvailable,
            ];

            $current->addMinutes($stepMins);
        }

        return array_values(array_filter($slots, fn(array $s): bool => $s['available']));
    }
}
