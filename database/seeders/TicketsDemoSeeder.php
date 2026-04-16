<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Guest ticket demo verisi — /manager/ticket-analytics ekranını test etmek için.
 * Status/priority/department/SLA/first_response_at karışımı gerçekçi senaryolar.
 */
class TicketsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = 1;
        $statuses = ['open', 'in_progress', 'resolved', 'closed'];
        $priorities = ['low', 'medium', 'high', 'urgent'];
        $departments = ['support', 'finance', 'admissions', 'technical', 'documents'];

        $subjects = [
            'Belge yükleme sorunu yaşıyorum',
            'Vize randevusu hakkında bilgi',
            'Ödeme faturası geç mi?',
            'Dil kursu yönlendirmesi',
            'Uni-Assist başvuru durumu',
            'İkamet adres güncelleme',
            'Portal\'a giriş yapamıyorum',
            'Sözleşme değişiklik talebi',
            'APS evrak gönderimi',
            'Blokeli hesap nasıl açılır',
            'Schufa belgesi ne zaman hazır olur',
            'Anmeldung randevu talebi',
            'Mentör değişiklik talebi',
            'Danışman görüşme randevusu',
            'APOSTİL sorunu',
        ];

        $messages = [
            'Bu konuda destek bekliyorum, mümkün olduğunca hızlı yanıt alabilir miyim?',
            'Sistem bir hata verdi, screenshot ekledim.',
            'Acil değil ama bilgilendirme yaparsanız sevinirim.',
            'Aynı problem 2. defa olduğu için yazıyorum.',
            'Danışmanımdan yanıt alamadım 3 gündür.',
            'Dün gece saat 23\'te yaptım, hâlâ onay gelmedi.',
        ];

        $guestIds = DB::table('guest_applications')->limit(15)->pluck('id')->all();
        if (empty($guestIds)) {
            $this->command?->warn('TicketsDemoSeeder: guest_applications boş.');
            return;
        }

        $users = DB::table('users')->whereIn('role', ['senior', 'staff', 'manager'])->pluck('id')->all();
        $assigneeIds = empty($users) ? [1] : $users;

        $inserted = 0;
        for ($i = 0; $i < 28; $i++) {
            $status = $statuses[array_rand($statuses)];
            $priority = $priorities[array_rand($priorities)];
            $daysAgo = random_int(0, 30);
            // DST güvenli: startOfDay + 4-22 saat (Türkiye 02:00-03:00 DST skip hours'u atla)
            $created = Carbon::now()->subDays($daysAgo)->startOfDay()->addHours(random_int(4, 22))->addMinutes(random_int(0, 59));

            // first_response_at: 60% tickets responded within 0-24 hours
            $firstResp = null;
            if ($status !== 'open' || random_int(0, 1) === 0) {
                $firstResp = $created->copy()->addHours(random_int(1, 48))->addMinutes(random_int(0, 59));
            }

            // closed_at: only for resolved/closed
            $closedAt = null;
            if (in_array($status, ['resolved', 'closed'])) {
                $closedAt = $created->copy()->addDays(random_int(1, 14))->addHours(random_int(0, 23));
                if ($closedAt->gt(now())) $closedAt = now();
            }

            // SLA: 60% of tickets have SLA
            $slaHours = random_int(0, 1) === 0 ? [24, 48, 72, 96][array_rand([24, 48, 72, 96])] : null;
            $slaDueAt = $slaHours ? $created->copy()->addHours($slaHours) : null;

            DB::table('guest_tickets')->insert([
                'company_id'          => $companyId,
                'guest_application_id' => $guestIds[array_rand($guestIds)],
                'subject'             => $subjects[array_rand($subjects)],
                'message'             => $messages[array_rand($messages)],
                'status'              => $status,
                'priority'            => $priority,
                'department'          => $departments[array_rand($departments)],
                'assigned_user_id'    => $assigneeIds[array_rand($assigneeIds)],
                'created_by_email'    => 'guest' . random_int(1, 99) . '@example.com',
                'first_response_at'   => $firstResp,
                'closed_at'           => $closedAt,
                'last_replied_at'     => $firstResp,
                'sla_hours'           => $slaHours,
                'sla_due_at'          => $slaDueAt,
                'created_at'          => $created,
                'updated_at'          => $closedAt ?? $firstResp ?? $created,
            ]);
            $inserted++;
        }

        $this->command?->info("TicketsDemoSeeder: $inserted guest ticket inserted.");
    }
}
