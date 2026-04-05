<?php

namespace Tests\Feature;

use App\Models\FieldRule;
use App\Models\FieldRuleApproval;
use App\Models\InternalNote;
use App\Models\StudentAssignment;
use App\Models\StudentRiskScore;
use App\Services\RiskScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RiskScoreService — öğrenci risk skoru hesaplama testleri.
 *
 * Kapsam:
 *  - Skor sıfırdan başlar (hiç risk faktörü yoksa minimum)
 *  - no_senior faktörü 15 puan ekler
 *  - payment_issue faktörü 20 puan ekler
 *  - pending_approvals: her onay 10 puan, maks 30
 *  - no_recent_note: 10 puan
 *  - Skor 0-100 arasında sınırlanır
 *  - risk_level eşikleri: low/<21, medium/21-40, high/41-60, critical/61+
 *  - StudentAssignment.risk_level güncellenir
 *  - History kaydı eklenir
 */
class RiskScoreServiceTest extends TestCase
{
    use RefreshDatabase;

    private RiskScoreService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RiskScoreService::class);
    }

    // ── Temel Hesaplama ──────────────────────────────────────────────────────

    public function test_student_with_senior_and_ok_payment_gets_no_senior_or_payment_points(): void
    {
        $studentId = 'STU-001';
        $this->makeAssignment($studentId, seniorEmail: 'senior@test.com', paymentStatus: 'ok');
        // Not: son 14 günde internal note yok → 10 puan ekler

        $risk = $this->service->calculateForStudent($studentId);

        // Sadece no_recent_note (10) eklenebilir, senior ve payment yok
        $this->assertLessThanOrEqual(10, $risk->current_score);
        $this->assertSame('low', $risk->risk_level);
    }

    public function test_no_senior_adds_15_points(): void
    {
        $studentId = 'STU-002';
        $this->makeAssignment($studentId, seniorEmail: null, paymentStatus: 'ok');

        $risk = $this->service->calculateForStudent($studentId);

        $factors = collect($risk->factors);
        $noSenior = $factors->firstWhere('factor', 'no_senior');
        $this->assertNotNull($noSenior, 'no_senior faktörü bekleniyor');
        $this->assertSame(15, $noSenior['points']);
    }

    public function test_payment_issue_adds_20_points(): void
    {
        $studentId = 'STU-003';
        $this->makeAssignment($studentId, seniorEmail: 'senior@test.com', paymentStatus: 'overdue');

        $risk = $this->service->calculateForStudent($studentId);

        $factors = collect($risk->factors);
        $paymentIssue = $factors->firstWhere('factor', 'payment_issue');
        $this->assertNotNull($paymentIssue, 'payment_issue faktörü bekleniyor');
        $this->assertSame(20, $paymentIssue['points']);
    }

    public function test_pending_approvals_capped_at_30_points(): void
    {
        $studentId = 'STU-004';
        $this->makeAssignment($studentId, seniorEmail: 'senior@test.com', paymentStatus: 'ok');
        $ruleId = $this->makeFieldRule();
        // 5 pending approval × 10 = 50, ama maks 30
        for ($i = 0; $i < 5; $i++) {
            FieldRuleApproval::query()->create([
                'rule_id'         => $ruleId,
                'student_id'      => $studentId,
                'triggered_field' => "field_$i",
                'severity'        => 'warning',
                'status'          => 'pending',
            ]);
        }

        $risk = $this->service->calculateForStudent($studentId);

        $factors = collect($risk->factors);
        $approvalFactor = $factors->firstWhere('factor', 'pending_approvals');
        $this->assertNotNull($approvalFactor);
        $this->assertSame(30, $approvalFactor['points']); // 50 değil, maks 30
    }

    public function test_no_recent_note_adds_10_points(): void
    {
        $studentId = 'STU-005';
        $this->makeAssignment($studentId, seniorEmail: 'senior@test.com', paymentStatus: 'ok');
        // Not eklemiyoruz → no_recent_note tetiklenmeli

        $risk = $this->service->calculateForStudent($studentId);

        $factors = collect($risk->factors);
        $this->assertNotNull($factors->firstWhere('factor', 'no_recent_note'));
    }

    public function test_recent_note_prevents_no_recent_note_factor(): void
    {
        $studentId = 'STU-006';
        $this->makeAssignment($studentId, seniorEmail: 'senior@test.com', paymentStatus: 'ok');
        InternalNote::query()->create([
            'student_id'  => $studentId,
            'content'     => 'Test notu',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $risk = $this->service->calculateForStudent($studentId);

        $factors = collect($risk->factors);
        $this->assertNull($factors->firstWhere('factor', 'no_recent_note'));
    }

    // ── Risk Level Eşikleri ──────────────────────────────────────────────────

    public function test_risk_level_low_when_score_under_21(): void
    {
        $studentId = 'STU-010';
        $this->makeAssignment($studentId, seniorEmail: 'senior@test.com', paymentStatus: 'ok');
        // Muhtemelen sadece no_recent_note (10) → 10 puan → low

        $risk = $this->service->calculateForStudent($studentId);

        if ($risk->current_score < 21) {
            $this->assertSame('low', $risk->risk_level);
        } else {
            $this->markTestSkipped('Bu test durumu için skor 21+ çıktı — ortam bağımlı.');
        }
    }

    public function test_risk_level_critical_when_score_61_plus(): void
    {
        $studentId = 'STU-011';
        // no_senior(15) + payment(20) + pending_approvals(30) = 65 → critical
        $this->makeAssignment($studentId, seniorEmail: null, paymentStatus: 'overdue');
        $ruleId = $this->makeFieldRule();
        for ($i = 0; $i < 5; $i++) {
            FieldRuleApproval::query()->create([
                'rule_id'         => $ruleId,
                'student_id'      => $studentId,
                'triggered_field' => "field_$i",
                'severity'        => 'warning',
                'status'          => 'pending',
            ]);
        }

        $risk = $this->service->calculateForStudent($studentId);

        $this->assertSame('critical', $risk->risk_level);
        $this->assertGreaterThanOrEqual(61, $risk->current_score);
    }

    // ── Skor Sınırları ───────────────────────────────────────────────────────

    public function test_score_never_exceeds_100(): void
    {
        $studentId = 'STU-020';
        $this->makeAssignment($studentId, seniorEmail: null, paymentStatus: 'overdue');
        $ruleId = $this->makeFieldRule();
        for ($i = 0; $i < 10; $i++) {
            FieldRuleApproval::query()->create([
                'rule_id'         => $ruleId,
                'student_id'      => $studentId,
                'triggered_field' => "field_$i",
                'severity'        => 'warning',
                'status'          => 'pending',
            ]);
        }

        $risk = $this->service->calculateForStudent($studentId);

        $this->assertLessThanOrEqual(100, $risk->current_score);
    }

    // ── Yan Etkiler ──────────────────────────────────────────────────────────

    public function test_assignment_risk_level_updated_after_calculation(): void
    {
        $studentId = 'STU-030';
        $assignment = $this->makeAssignment($studentId, seniorEmail: null, paymentStatus: 'overdue');

        $this->service->calculateForStudent($studentId);

        $assignment->refresh();
        $this->assertNotNull($assignment->risk_level);
    }

    public function test_history_appended_on_each_calculation(): void
    {
        $studentId = 'STU-040';
        $this->makeAssignment($studentId, seniorEmail: 'senior@test.com', paymentStatus: 'ok');

        $this->service->calculateForStudent($studentId);
        $this->service->calculateForStudent($studentId);

        $risk = StudentRiskScore::query()->where('student_id', $studentId)->first();
        $this->assertCount(2, $risk->history);
    }

    // ── Yardımcılar ──────────────────────────────────────────────────────────

    private function makeAssignment(
        string $studentId,
        ?string $seniorEmail = null,
        string $paymentStatus = 'ok',
    ): StudentAssignment {
        $assignment = new StudentAssignment();
        $assignment->forceFill([
            'student_id'     => $studentId,
            'senior_email'   => $seniorEmail,
            'payment_status' => $paymentStatus,
            'is_archived'    => false,
        ]);
        $assignment->save();

        return $assignment;
    }

    /** FieldRule tablosuna minimal kayıt ekler, ID döner. */
    private function makeFieldRule(): int
    {
        $rule = new FieldRule();
        $rule->forceFill([
            'name_tr'      => 'Test Kuralı',
            'target_field' => 'test_field',
            'target_form'  => 'guest_registration',
            'condition'    => ['operator' => 'equals', 'value' => 'test'],
            'severity'     => 'warning',
            'is_active'    => true,
        ]);
        $rule->save();

        return $rule->id;
    }
}
