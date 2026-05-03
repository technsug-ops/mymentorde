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

            // ── A/B test variant assignment + override ──
            // step.ab_test_id varsa: variant assign et, subject + view_path override'la
            $variantSubject = null;
            $variantViewPath = null;
            $variantCode = null;
            if (! empty($step->ab_test_id)) {
                $variant = $this->assignVariantForEnrollment($step->ab_test_id, $enrollment);
                if ($variant) {
                    $variantCode = $variant->variant_code;
                    $cfg = is_array($variant->variant_config) ? $variant->variant_config : [];
                    $variantSubject = $cfg['subject'] ?? null;
                    $variantViewPath = $cfg['view_path'] ?? null;
                }
            }

            $finalSubject = $variantSubject ?: $step->subject_override;
            $finalViewPath = $variantViewPath ?: $step->view_path;

            $body = '';
            try {
                if (! empty($finalViewPath) && view()->exists($finalViewPath)) {
                    $body = view($finalViewPath, $context)->render();
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
                'subject'         => $finalSubject,
                'body'            => $body,
                'variables'       => [
                    'drip_sequence_id' => $enrollment->drip_sequence_id,
                    'step_order'       => $step->step_order,
                    'view_path'        => $finalViewPath,
                    'ab_test_id'       => $step->ab_test_id,
                    'variant_code'     => $variantCode,
                ],
                'status'          => 'queued',
                'queued_at'       => now(),
                'source_type'     => 'email_drip',
                'source_id'       => (string) $enrollment->id,
                'triggered_by'    => 'system',
            ]);

            // Variant impressions++ (gerçekten dispatch'a kondu)
            if ($variantCode) {
                \App\Models\ABTestVariant::query()
                    ->where('ab_test_id', $step->ab_test_id)
                    ->where('variant_code', $variantCode)
                    ->increment('impressions');
            }

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

    /**
     * Enrollment için A/B test variant assignment yap, ABTestVariant döner.
     * - Idempotent: aynı enrollment+test için tekrar assign etmez
     * - traffic_split JSON varsa weighted random, yoksa eşit dağıtım
     * - Test durumu 'running' değilse null döner (impression sayma)
     */
    private function assignVariantForEnrollment(int $abTestId, EmailDripEnrollment $enrollment): ?\App\Models\ABTestVariant
    {
        $test = \App\Models\ABTest::find($abTestId);
        if (! $test || $test->status !== 'running') {
            return null;
        }

        $variants = \App\Models\ABTestVariant::where('ab_test_id', $abTestId)->get();
        if ($variants->isEmpty()) return null;

        // Mevcut atama var mı? (idempotent)
        $existingQ = \App\Models\ABTestAssignment::where('ab_test_id', $abTestId);
        if ($enrollment->uni_match_response_id) {
            $existingQ->where('uni_match_response_id', $enrollment->uni_match_response_id);
        } elseif ($enrollment->guest_application_id) {
            $existingQ->where('guest_application_id', $enrollment->guest_application_id);
        } else {
            return null;
        }
        $existing = $existingQ->first();

        if ($existing) {
            return $variants->firstWhere('variant_code', $existing->variant_code);
        }

        // Yeni assignment — weighted random
        $picked = $this->pickWeightedVariant($variants, $test->traffic_split);

        \App\Models\ABTestAssignment::create([
            'ab_test_id'            => $abTestId,
            'guest_application_id'  => $enrollment->guest_application_id,
            'uni_match_response_id' => $enrollment->uni_match_response_id,
            'variant_code'          => $picked->variant_code,
            'converted'             => false,
            'assigned_at'           => now(),
        ]);

        return $picked;
    }

    /**
     * Weighted random variant seçimi.
     * traffic_split: ['A' => 50, 'B' => 50] formatında. Boşsa eşit dağıtım.
     */
    private function pickWeightedVariant($variants, $trafficSplit): \App\Models\ABTestVariant
    {
        $weights = is_array($trafficSplit) && ! empty($trafficSplit) ? $trafficSplit : null;
        if ($weights === null) {
            return $variants->random();
        }

        $weighted = [];
        foreach ($variants as $v) {
            $w = (int) ($weights[$v->variant_code] ?? 0);
            for ($i = 0; $i < max(1, $w); $i++) $weighted[] = $v;
        }
        return $weighted[array_rand($weighted)] ?? $variants->random();
    }
}
