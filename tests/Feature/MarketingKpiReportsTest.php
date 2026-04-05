<?php

namespace Tests\Feature;

use App\Models\LeadSourceDatum;
use App\Models\MarketingCampaign;
use App\Models\MarketingReport;
use App\Models\StudentRevenue;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingKpiReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_kpi_reports_flow_works_end_to_end(): void
    {
        $user = User::factory()->create([
            'role' => 'marketing_admin',
            'is_active' => true,
            'email' => 'mktg-admin@mentorde.local',
        ]);

        MarketingCampaign::query()->create([
            'name' => 'Welcome Campaign',
            'channel' => 'google_ads',
            'status' => 'active',
            'budget' => 1200,
            'spent_amount' => 600,
            'currency' => 'EUR',
        ]);

        $leadA = LeadSourceDatum::query()->create([
            'guest_id' => 'kpi-guest-1',
            'initial_source' => 'google',
            'verified_source' => 'google',
            'funnel_converted' => true,
        ]);
        $leadA->timestamps = false;
        $leadA->created_at = Carbon::parse('2026-02-10 10:00:00');
        $leadA->updated_at = Carbon::parse('2026-02-10 10:00:00');
        $leadA->save();

        $leadB = LeadSourceDatum::query()->create([
            'guest_id' => 'kpi-guest-2',
            'initial_source' => 'instagram',
            'verified_source' => null,
            'funnel_converted' => false,
        ]);
        $leadB->timestamps = false;
        $leadB->created_at = Carbon::parse('2026-02-10 12:30:00');
        $leadB->updated_at = Carbon::parse('2026-02-10 12:30:00');
        $leadB->save();

        $revenue = StudentRevenue::query()->create([
            'student_id' => 'BCS100001',
            'total_earned' => 2000,
        ]);
        $revenue->timestamps = false;
        $revenue->created_at = Carbon::parse('2026-02-10 15:00:00');
        $revenue->updated_at = Carbon::parse('2026-02-10 15:00:00');
        $revenue->save();

        $this->actingAs($user)
            ->get('/mktg-admin/kpi?start_date=2026-02-10&end_date=2026-02-10')
            ->assertOk()
            ->assertSee('KPI & Raporlar')
            ->assertSee('Lead')
            ->assertSee('Dönüştürülen');

        $this->actingAs($user)
            ->post('/mktg-admin/reports/generate', [
                'report_type' => 'kpi_snapshot',
                'start_date' => '2026-02-10',
                'end_date' => '2026-02-10',
            ])
            ->assertRedirect('/mktg-admin/reports');

        $report = MarketingReport::query()->latest()->first();
        $this->assertNotNull($report);
        $this->assertSame('kpi_snapshot', $report->report_type);
        $this->assertSame('mktg-admin@mentorde.local', $report->created_by);

        $this->actingAs($user)
            ->get('/mktg-admin/reports?report_type=kpi_snapshot')
            ->assertOk()
            ->assertSee('Oluşturulan Raporlar')
            ->assertSee('#'.$report->id);

        $jsonResponse = $this->actingAs($user)
            ->get("/mktg-admin/reports/{$report->id}/download/json");
        $jsonResponse->assertOk()
            ->assertJsonPath('id', $report->id)
            ->assertJsonPath('report_type', 'kpi_snapshot');

        $csvResponse = $this->actingAs($user)
            ->get("/mktg-admin/reports/{$report->id}/download/csv");
        $csvResponse->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csvBody = $csvResponse->streamedContent();
        $this->assertStringContainsString('MentorDE Marketing Report', $csvBody);
        $this->assertStringContainsString('KPI', $csvBody);
    }
}
