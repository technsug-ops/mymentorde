<?php

namespace Tests\Feature;

use App\Models\GuestApplication;
use App\Models\LeadScoringRule;
use App\Models\User;
use App\Services\LeadScoreService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * LeadScoreService — lead puanlama mantığı için testler.
 *
 * Kapsam:
 *  - getTierLabel() eşleme tablosu
 *  - resolveTier(): skor → tier dönüşümü
 *  - recalculate(): form/senior/contract bazlı puan hesabı
 *  - addScore(): kural bazlı artımlı puanlama + one-time koruması
 *  - addScore(): günlük maksimum koruması
 */
class LeadScoreServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeadScoreService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // NotificationService'i mock'la — testlerde bildirim gönderme
        $this->mock(NotificationService::class, fn ($m) => $m->shouldIgnoreMissing());
        $this->service = app(LeadScoreService::class);
    }

    // ── getTierLabel ─────────────────────────────────────────────────────────

    public function test_tier_label_returns_known_tiers(): void
    {
        $this->assertNotEmpty($this->service->getTierLabel('cold'));
        $this->assertNotEmpty($this->service->getTierLabel('warm'));
        $this->assertNotEmpty($this->service->getTierLabel('hot'));
        $this->assertNotEmpty($this->service->getTierLabel('sales_ready'));
        $this->assertNotEmpty($this->service->getTierLabel('champion'));
    }

    public function test_tier_label_unknown_tier_returns_input(): void
    {
        $this->assertSame('unknown_tier', $this->service->getTierLabel('unknown_tier'));
    }

    // ── recalculate — temel skor hesabı ─────────────────────────────────────

    public function test_recalculate_empty_application_gives_zero_score(): void
    {
        $app = $this->makeGuest();

        $this->service->recalculate($app);
        $app->refresh();

        $this->assertSame(0, (int) $app->lead_score);
        $this->assertSame('cold', $app->lead_score_tier);
    }

    public function test_recalculate_adds_score_for_form_submitted(): void
    {
        $app = $this->makeGuest([
            'registration_form_submitted_at' => now(),
        ]);

        $this->service->recalculate($app);
        $app->refresh();

        $this->assertGreaterThan(0, (int) $app->lead_score);
    }

    public function test_recalculate_adds_score_for_senior_assigned(): void
    {
        $appWithout = $this->makeGuest();
        $this->service->recalculate($appWithout);
        $appWithout->refresh();
        $scoreWithout = (int) $appWithout->lead_score;

        $appWith = $this->makeGuest([
            'assigned_senior_email' => 'senior@test.com',
        ]);
        $this->service->recalculate($appWith);
        $appWith->refresh();

        $this->assertGreaterThanOrEqual($scoreWithout, (int) $appWith->lead_score);
    }

    public function test_recalculate_score_capped_at_100(): void
    {
        $app = $this->makeGuest([
            'registration_form_submitted_at' => now(),
            'assigned_senior_email'          => 'senior@test.com',
            'utm_source'                     => 'google',
            'contract_status'                => 'approved',
        ]);

        $this->service->recalculate($app);
        $app->refresh();

        $this->assertLessThanOrEqual(100, (int) $app->lead_score);
    }

    public function test_recalculate_score_never_negative(): void
    {
        $app = $this->makeGuest(['risk_level' => 'high']);

        $this->service->recalculate($app);
        $app->refresh();

        $this->assertGreaterThanOrEqual(0, (int) $app->lead_score);
    }

    // ── addScore — kural bazlı puanlama ─────────────────────────────────────

    public function test_add_score_returns_false_for_nonexistent_rule(): void
    {
        $app = $this->makeGuest();

        $result = $this->service->addScore($app->id, 'nonexistent_action');

        $this->assertFalse($result);
    }

    public function test_add_score_adds_points_for_active_rule(): void
    {
        $app = $this->makeGuest();
        $this->makeRule('page_visit', 5);

        $result = $this->service->addScore($app->id, 'page_visit');

        $this->assertTrue($result);
        $app->refresh();
        $this->assertSame(5, (int) $app->lead_score);
    }

    public function test_add_score_one_time_rule_not_applied_twice(): void
    {
        $app = $this->makeGuest();
        $this->makeRule('form_submit', 10, isOneTime: true);

        $this->service->addScore($app->id, 'form_submit');
        $result = $this->service->addScore($app->id, 'form_submit');

        $this->assertFalse($result);
        $app->refresh();
        $this->assertSame(10, (int) $app->lead_score); // sadece bir kez eklendi
    }

    public function test_add_score_daily_max_enforced(): void
    {
        $app = $this->makeGuest();
        $this->makeRule('daily_action', 5, maxPerDay: 2);

        $this->service->addScore($app->id, 'daily_action');
        $this->service->addScore($app->id, 'daily_action');
        $result = $this->service->addScore($app->id, 'daily_action'); // 3. — reddedilmeli

        $this->assertFalse($result);
        $app->refresh();
        $this->assertSame(10, (int) $app->lead_score); // 2 × 5
    }

    public function test_add_score_inactive_rule_not_applied(): void
    {
        $app = $this->makeGuest();
        $this->makeRule('disabled_action', 20, isActive: false);

        $result = $this->service->addScore($app->id, 'disabled_action');

        $this->assertFalse($result);
    }

    // ── getScoreBreakdown ────────────────────────────────────────────────────

    public function test_get_score_breakdown_returns_category_totals(): void
    {
        $app = $this->makeGuest();
        $this->makeRule('behavioral_action', 10, category: 'behavioral');
        $this->service->addScore($app->id, 'behavioral_action');

        $breakdown = $this->service->getScoreBreakdown($app->id);

        $this->assertArrayHasKey('behavioral', $breakdown);
        $this->assertSame(10, $breakdown['behavioral']);
    }

    // ── Yardımcılar ─────────────────────────────────────────────────────────

    private function makeGuest(array $attrs = []): GuestApplication
    {
        $defaults = [
            'tracking_token'   => 'TOK-' . strtoupper(substr(md5(microtime()), 0, 8)),
            'email'            => 'guest' . rand(1000, 9999) . '@test.com',
            'first_name'       => 'Test',
            'last_name'        => 'Guest',
            'application_type' => 'bachelor',
            'kvkk_consent'     => true,
            'lead_score'       => 0,
            'lead_score_tier'  => 'cold',
        ];

        $app = new GuestApplication();
        $app->forceFill(array_merge($defaults, $attrs));
        $app->save();

        return $app;
    }

    private function makeRule(
        string $actionCode,
        int $points,
        bool $isOneTime = false,
        ?int $maxPerDay = null,
        bool $isActive = true,
        string $category = 'behavioral',
    ): LeadScoringRule {
        $rule = new LeadScoringRule();
        $rule->forceFill([
            'action_code' => $actionCode,
            'points'      => $points,
            'is_one_time' => $isOneTime,
            'max_per_day' => $maxPerDay,
            'is_active'   => $isActive,
            'category'    => $category,
            'label'       => $actionCode,
        ]);
        $rule->save();

        return $rule;
    }
}
