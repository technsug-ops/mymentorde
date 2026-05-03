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
            ->with(['sequence.steps', 'guest', 'uniMatchResponse'])
            ->limit(100)
            ->get();

        if ($this->option('dry-run')) {
            $this->info("Dry-run: {$due->count()} enrollment işlenecek.");
            return 0;
        }

        $processed = 0;

        foreach ($due as $enrollment) {
            // Sequence yoksa veya hem guest hem unimatch_response NULL ise skip
            if (! $enrollment->sequence) continue;
            if (! $enrollment->guest_application_id && ! $enrollment->uni_match_response_id) continue;

            // UniMatch response convert oldu mu? Eğer evetse cancel
            if ($enrollment->uni_match_response_id && $enrollment->uniMatchResponse?->converted_to_guest_id) {
                $enrollment->update(['status' => 'converted', 'completed_at' => now()]);
                continue;
            }

            $recipientEmail = $enrollment->getRecipientEmail();
            $recipientName  = $enrollment->getRecipientName();
            if (empty($recipientEmail)) continue;

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

            // Body'yi render et — view_path varsa Blade view, yoksa template body, yoksa marker
            $context = $enrollment->getTemplateContext();
            $body = '';
            try {
                if (! empty($step->view_path) && view()->exists($step->view_path)) {
                    $body = view($step->view_path, $context)->render();
                } elseif ($step->template_id) {
                    $template = \App\Models\Marketing\EmailTemplate::find($step->template_id);
                    $body = (string) ($template?->body_tr ?? $template?->body_en ?? '');
                }
            } catch (\Throwable $e) {
                \Log::warning('Drip body render failed', ['enrollment' => $enrollment->id, 'step' => $step->id, 'error' => $e->getMessage()]);
                $body = '';
            }

            // NotificationDispatch kuyruğuna al — body rendered HTML
            NotificationDispatch::create([
                'template_id'     => $step->template_id,
                'channel'         => 'email',
                'category'        => 'drip_campaign',
                'student_id'      => null,
                'recipient_email' => $recipientEmail,
                'recipient_name'  => $recipientName,
                'subject'         => $step->subject_override ?? null,
                'body'            => $body,
                'variables'       => [
                    'drip_sequence_id' => $enrollment->drip_sequence_id,
                    'step_order'       => $step->step_order,
                    'view_path'        => $step->view_path,
                ],
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
