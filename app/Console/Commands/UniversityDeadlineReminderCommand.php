<?php

namespace App\Console\Commands;

use App\Models\StudentUniversityApplication;
use App\Models\User;
use App\Jobs\SendBulkNotificationJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UniversityDeadlineReminderCommand extends Command
{
    protected $signature   = 'university:deadline-reminder';
    protected $description = 'Üniversite başvuru deadline\'larını kontrol eder ve hatırlatma gönderir';

    public function handle(): void
    {
        // 7 gün kalan
        $in7 = StudentUniversityApplication::whereNotNull('deadline')
            ->whereDate('deadline', now()->addDays(7)->toDateString())
            ->where('status', '!=', 'submitted')
            ->get();

        // 1 gün kalan
        $in1 = StudentUniversityApplication::whereNotNull('deadline')
            ->whereDate('deadline', now()->addDay()->toDateString())
            ->where('status', '!=', 'submitted')
            ->get();

        $sent7 = 0;
        foreach ($in7 as $app) {
            $student = User::find($app->student_id, ['id', 'name', 'email']);
            if ($student) {
                SendBulkNotificationJob::dispatch(
                    [$student->id],
                    '📅 Üniversite Başvuru Tarihi Yaklaşıyor',
                    "{$app->university_name} için son başvuru tarihi 7 gün içinde! Başvurunuzu tamamlamayı unutmayın.",
                    'db'
                );
                $sent7++;
            }
        }

        $sent1 = 0;
        foreach ($in1 as $app) {
            $student = User::find($app->student_id, ['id', 'name', 'email']);
            if ($student) {
                SendBulkNotificationJob::dispatch(
                    [$student->id],
                    '⚠️ Son 1 Gün! Üniversite Başvuru Tarihi Yarın',
                    "{$app->university_name} için son başvuru tarihi YARIN! Hemen tamamlayın.",
                    'db'
                );
                $sent1++;
            }
        }

        $this->info("7 günlük: {$sent7}, 1 günlük: {$sent1} hatırlatma gönderildi.");
    }
}
