<?php

namespace App\Console\Commands;

use App\Models\Marketing\EmailDripEnrollment;
use App\Models\NotificationDispatch;
use Illuminate\Console\Command;

class ProcessEmailDripCommand extends Command
{
    protected $signature = 'email:process-drip {--dry-run : Sadece sayıyı göster, gönderme}';
    protected $description = 'Drip kampanya adımlarını işle ve bekleyen emailleri gönder.';

    public function handle(): int
    {
        $due = EmailDripEnrollment::where('status', 'active')
            ->where('next_send_at', '<=', now())
            ->with(['sequence.steps', 'guest'])
            ->limit(100)
            ->get();

        if ($this->option('dry-run')) {
            $this->info("Dry-run: {$due->count()} enrollment işlenecek.");
            return 0;
        }

        $processed = 0;

        foreach ($due as $enrollment) {
            if (! $enrollment->sequence || ! $enrollment->guest) {
                continue;
            }

            $nextStepOrder = $enrollment->current_step + 1;
            $step = $enrollment->sequence->steps
                ->where('step_order', $nextStepOrder)
                ->where('is_active', true)
                ->first();

            if (! $step) {
                $enrollment->update(['status' => 'completed', 'completed_at' => now()]);
                $processed++;
                continue;
            }

            // NotificationDispatch kuyruğuna al
            NotificationDispatch::create([
                'template_id'     => $step->template_id,
                'channel'         => 'email',
                'category'        => 'drip_campaign',
                'student_id'      => null,
                'recipient_email' => $enrollment->guest->email ?? null,
                'recipient_name'  => trim(($enrollment->guest->first_name ?? '') . ' ' . ($enrollment->guest->last_name ?? '')),
                'subject'         => $step->subject_override ?? null,
                'body'            => "drip_step:{$step->id}",
                'variables'       => ['drip_sequence_id' => $enrollment->drip_sequence_id, 'step_order' => $step->step_order],
                'status'          => 'queued',
                'queued_at'       => now(),
                'source_type'     => 'email_drip',
                'source_id'       => (string) $enrollment->id,
                'triggered_by'    => 'system',
            ]);

            // Sonraki adımı hesapla
            $nextStep = $enrollment->sequence->steps
                ->where('step_order', $step->step_order + 1)
                ->where('is_active', true)
                ->first();

            $enrollment->update([
                'current_step' => $step->step_order,
                'next_send_at' => $nextStep ? now()->addHours($nextStep->delay_hours) : null,
                'status'       => $nextStep ? 'active' : 'completed',
                'completed_at' => $nextStep ? null : now(),
            ]);

            $processed++;
        }

        $this->info("Drip işlendi: {$processed} enrollment.");
        return 0;
    }
}
