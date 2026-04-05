<?php

namespace App\Services;

use App\Models\GuestApplication;
use App\Models\GuestTimelineMilestone;
use Carbon\Carbon;

/**
 * K3 — Guest başvuru takvimi milestone üretici + .ics export.
 */
class GuestTimelineService
{
    /**
     * Guest için kişisel milestone'ları oluştur / güncelle.
     * Sözleşme imzalandığında veya paket seçildiğinde çağrılır.
     */
    public function generateMilestones(GuestApplication $guest): void
    {
        $targetTerm = strtolower($guest->target_term ?? 'winter');
        $year       = now()->year;

        $targetDate = match ($targetTerm) {
            'summer' => Carbon::create($year + 1, 4, 1),
            default  => Carbon::create($year, 10, 1),
        };

        // Hedef tarih 3 aydan kısa süreyorsa bir sonraki yıla taşı
        if ($targetDate->lt(now()->addMonths(3))) {
            $targetDate->addYear();
        }

        $milestones = [
            ['code' => 'form_complete',    'label' => 'Kayıt Formu Tamamla',        'target_date' => now()->addDays(7),                'category' => 'registration'],
            ['code' => 'docs_upload',      'label' => 'Zorunlu Belgeleri Yükle',    'target_date' => now()->addDays(14),               'category' => 'documents'],
            ['code' => 'package_select',   'label' => 'Paket Seç',                  'target_date' => now()->addDays(10),               'category' => 'contract'],
            ['code' => 'contract_sign',    'label' => 'Sözleşme İmzala',            'target_date' => now()->addDays(21),               'category' => 'contract'],
            ['code' => 'uni_apply',        'label' => 'Üniversite Başvurusu',       'target_date' => $targetDate->copy()->subMonths(4), 'category' => 'university'],
            ['code' => 'uni_assist',       'label' => 'Uni-Assist Başvurusu',       'target_date' => $targetDate->copy()->subMonths(3), 'category' => 'university'],
            ['code' => 'blocked_account',  'label' => 'Bloke Hesap Aç',            'target_date' => $targetDate->copy()->subMonths(2), 'category' => 'visa'],
            ['code' => 'health_insurance', 'label' => 'Sağlık Sigortası Başvurusu', 'target_date' => $targetDate->copy()->subMonths(2), 'category' => 'visa'],
            ['code' => 'visa_appointment', 'label' => 'Vize Randevusu',             'target_date' => $targetDate->copy()->subMonths(2), 'category' => 'visa'],
            ['code' => 'visa_apply',       'label' => 'Vize Başvurusu',             'target_date' => $targetDate->copy()->subWeeks(6),  'category' => 'visa'],
            ['code' => 'flight_book',      'label' => 'Uçak Bileti Al',             'target_date' => $targetDate->copy()->subWeeks(4),  'category' => 'travel'],
            ['code' => 'accommodation',    'label' => 'Konaklama Kesinleştir',      'target_date' => $targetDate->copy()->subWeeks(3),  'category' => 'travel'],
            ['code' => 'arrival',          'label' => "Almanya'ya Varış!",          'target_date' => $targetDate->copy()->subWeeks(1),  'category' => 'arrival'],
        ];

        foreach ($milestones as $idx => $m) {
            GuestTimelineMilestone::updateOrCreate(
                ['guest_application_id' => $guest->id, 'milestone_code' => $m['code']],
                [
                    'label'      => $m['label'],
                    'target_date'=> $m['target_date']->toDateString(),
                    'category'   => $m['category'],
                    'sort_order' => $idx,
                    'created_at' => now(),
                ]
            );
        }
    }

    /**
     * .ics (iCalendar) formatında takvim verisi üret.
     */
    public function exportIcs(GuestApplication $guest): string
    {
        $milestones = GuestTimelineMilestone::where('guest_application_id', $guest->id)
            ->orderBy('target_date')
            ->get();

        $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//MentorDE//Student Calendar//TR\r\nCALSCALE:GREGORIAN\r\n";

        foreach ($milestones as $m) {
            $date = Carbon::parse($m->target_date)->format('Ymd');
            $uid  = 'mentorde-' . $m->id . '-' . $guest->id . '@mentorde.de';
            $ics .= "BEGIN:VEVENT\r\n";
            $ics .= "UID:{$uid}\r\n";
            $ics .= "DTSTART;VALUE=DATE:{$date}\r\n";
            $ics .= "SUMMARY:{$m->label}\r\n";
            $ics .= "DESCRIPTION:MentorDE Almanya Başvuru Takvimi\r\n";
            $ics .= "CATEGORIES:" . strtoupper($m->category) . "\r\n";
            if ($m->completed_at) {
                $ics .= "STATUS:COMPLETED\r\n";
            }
            $ics .= "END:VEVENT\r\n";
        }

        $ics .= "END:VCALENDAR";
        return $ics;
    }

    /**
     * Milestone'u tamamlandı olarak işaretle.
     */
    public function complete(GuestApplication $guest, string $milestoneCode): void
    {
        GuestTimelineMilestone::where('guest_application_id', $guest->id)
            ->where('milestone_code', $milestoneCode)
            ->whereNull('completed_at')
            ->update(['completed_at' => now()]);
    }
}
