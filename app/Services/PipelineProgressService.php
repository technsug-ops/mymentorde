<?php

namespace App\Services;

use App\Models\GuestApplication;
use App\Models\ProcessOutcome;
use App\Models\StudentInstitutionDocument;
use App\Models\StudentUniversityApplication;

/**
 * Senior aksiyonlarına göre GuestApplication.lead_status'u otomatik ilerletir.
 * Hiçbir zaman statüsü düşürmez — sadece daha ileri bir adıma geçer.
 */
class PipelineProgressService
{
    /**
     * Statü ağırlıkları config/pipeline_mapping.php'den okunur.
     */
    private function stageWeights(): array
    {
        return config('pipeline_mapping.stage_weights', [
            'new' => 0.10, 'contacted' => 0.20, 'verified' => 0.30,
            'follow_up' => 0.35, 'interested' => 0.45, 'qualified' => 0.55,
            'sales_ready' => 0.70, 'champion' => 0.90,
        ]);
    }

    public function advanceFromProcessOutcome(ProcessOutcome $outcome): void
    {
        $app = $this->findApp($outcome->student_id);
        if (!$app) return;

        $mapping = config('pipeline_mapping.outcome_to_stage', [
            'acceptance' => 'qualified', 'conditional_acceptance' => 'qualified',
        ]);
        $target = $mapping[$outcome->outcome_type] ?? null;

        if ($target) {
            $this->advanceTo($app, $target);
        }
    }

    public function advanceFromUniversityApplication(StudentUniversityApplication $ua): void
    {
        $app = $this->findApp($ua->student_id);
        if (!$app) return;

        $mapping = config('pipeline_mapping.uni_status_to_stage', [
            'accepted' => 'sales_ready', 'conditional_accepted' => 'sales_ready',
        ]);
        $target = $mapping[$ua->status] ?? null;

        if ($target) {
            $this->advanceTo($app, $target);
        }
    }

    public function advanceFromInstitutionDocument(StudentInstitutionDocument $doc): void
    {
        $visaCode   = config('pipeline_mapping.visa_document_code', 'VIS-ERTEIL');
        $visaTarget = config('pipeline_mapping.visa_target_stage', 'champion');

        if ($doc->document_type_code !== $visaCode) return;
        if (in_array($doc->status, ['expected', 'archived'], true)) return;

        $app = $this->findApp($doc->student_id);
        if (!$app) return;

        $this->advanceTo($app, $visaTarget);
    }

    // ── Private ──────────────────────────────────────────────────────────────

    private function findApp(string $studentId): ?GuestApplication
    {
        return GuestApplication::where('converted_student_id', $studentId)->first();
    }

    /**
     * Sadece ileri yönde güncelle — mevcut ağırlık hedef ağırlıktan küçükse geç.
     */
    private function advanceTo(GuestApplication $app, string $targetStatus): void
    {
        $weights       = $this->stageWeights();
        $currentWeight = $weights[$app->lead_status] ?? 0;
        $targetWeight  = $weights[$targetStatus] ?? 0;

        if ($targetWeight > $currentWeight) {
            $app->withoutTimestamps(function () use ($app, $targetStatus): void {
                $app->update(['lead_status' => $targetStatus]);
            });
        }
    }
}
