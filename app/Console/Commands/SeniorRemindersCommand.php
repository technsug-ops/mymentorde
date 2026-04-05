<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\GuestTicket;
use App\Models\ProcessOutcome;
use App\Models\StudentAppointment;
use App\Models\StudentAssignment;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SeniorRemindersCommand extends Command
{
    protected $signature   = 'senior:send-reminders {--dry-run : List reminders without sending}';
    protected $description = 'Sends daily reminder notifications to seniors for pending actions';

    public function handle(NotificationService $notificationService): int
    {
        $dryRun      = (bool) $this->option('dry-run');
        $assignments = StudentAssignment::where('is_archived', false)->get();
        $grouped     = $assignments->groupBy(fn ($a) => strtolower((string) $a->senior_email));
        $sent        = 0;

        foreach ($grouped as $email => $students) {
            $senior = User::where('email', $email)->first();
            if (!$senior) continue;

            $studentIds = $students->pluck('student_id')->filter()->unique();
            $reminders  = collect();

            // 1. 48+ saat bekleyen belge onayı
            $pendingDocs = Document::whereIn('student_id', $studentIds->all())
                ->where('status', 'uploaded')
                ->where('created_at', '<', now()->subHours(48))
                ->count();
            if ($pendingDocs > 0) {
                $reminders->push("📄 {$pendingDocs} belge 48+ saattir onay bekliyor.");
            }

            // 2. 24+ saat bekleyen randevu talebi
            $pendingApts = StudentAppointment::whereIn('student_id', $studentIds->all())
                ->where('status', 'pending')
                ->where('created_at', '<', now()->subHours(24))
                ->count();
            if ($pendingApts > 0) {
                $reminders->push("📅 {$pendingApts} randevu talebi 24+ saattir onay bekliyor.");
            }

            // 3. 14 gündür inaktif öğrenci
            $inactive = $students->filter(fn ($a) => $a->updated_at->lt(now()->subDays(14)))->count();
            if ($inactive > 0) {
                $reminders->push("🔕 {$inactive} öğrenciniz 14+ gündür inaktif — iletişime geçin.");
            }

            // 4. 30 gün içinde vize deadline'ı olan öğrenci
            $visaDeadlines = ProcessOutcome::whereIn('student_id', $studentIds->all())
                ->where('process_step', 'visa_application')
                ->whereNotNull('deadline')
                ->where('deadline', '>', now())
                ->where('deadline', '<', now()->addDays(30))
                ->count();
            if ($visaDeadlines > 0) {
                $reminders->push("⏰ {$visaDeadlines} öğrencinizin vize deadline'ı 30 gün içinde.");
            }

            // 5. Yanıt bekleyen biletler
            $waitingTickets = GuestTicket::whereIn('guest_application_id', function ($q) use ($studentIds) {
                $q->select('id')->from('guest_applications')->whereIn('converted_student_id', $studentIds->all());
            })->where('status', 'waiting_response')->count();
            if ($waitingTickets > 0) {
                $reminders->push("💬 {$waitingTickets} bilet yanıtınızı bekliyor.");
            }

            if ($reminders->isEmpty()) continue;

            $body = $reminders->join("\n");

            if ($dryRun) {
                $this->line("  [{$email}] " . $reminders->count() . " hatırlatma");
                foreach ($reminders as $r) {
                    $this->line("    - {$r}");
                }
                continue;
            }

            $notificationService->send([
                'channel'    => 'in_app',
                'category'   => 'senior_daily_reminder',
                'user_id'    => $senior->id,
                'subject'    => 'Günlük Hatırlatma',
                'body'       => $body,
                'source_type'=> 'senior_daily_reminder',
                'source_id'  => now()->format('Y-m-d'),
            ]);

            $notificationService->send([
                'channel'         => 'email',
                'category'        => 'senior_daily_reminder',
                'user_id'         => $senior->id,
                'recipient_email' => $email,
                'recipient_name'  => $senior->name,
                'subject'         => 'MentorDE — Günlük Hatırlatma',
                'body'            => $body,
                'source_type'     => 'senior_daily_reminder',
                'source_id'       => now()->format('Y-m-d'),
            ]);

            $sent++;
        }

        $this->info($dryRun ? 'Dry-run complete.' : "Reminders sent to {$sent} seniors.");
        return 0;
    }
}
