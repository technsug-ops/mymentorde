<?php

namespace Tests\Feature;

use App\Models\GuestApplication;
use App\Models\StudentRevenue;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingSalesPipelineLiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_pipeline_pages_render_live_metrics(): void
    {
        $user = User::factory()->create([
            'role' => 'marketing_admin',
            'is_active' => true,
        ]);

        (new GuestApplication)->forceFill([
            'tracking_token' => 'PIPE001A',
            'first_name' => 'Ali',
            'last_name' => 'A',
            'email' => 'ali@example.test',
            'application_type' => 'bachelor',
            'lead_source' => 'google',
            'lead_status' => 'new',
            'converted_to_student' => false,
            'is_archived' => false,
        ])->save();

        (new GuestApplication)->forceFill([
            'tracking_token' => 'PIPE001B',
            'first_name' => 'Ayse',
            'last_name' => 'B',
            'email' => 'ayse@example.test',
            'application_type' => 'master',
            'lead_source' => 'instagram',
            'lead_status' => 'contacted',
            'converted_to_student' => false,
            'is_archived' => false,
        ])->save();

        $archived = new GuestApplication;
        $archived->forceFill([
            'tracking_token' => 'PIPE001C',
            'first_name' => 'Can',
            'last_name' => 'C',
            'email' => 'can@example.test',
            'application_type' => 'bachelor',
            'lead_source' => 'organic',
            'lead_status' => 'new',
            'converted_to_student' => false,
            'is_archived' => true,
            'archive_reason' => 'stale_180_days',
            'created_at' => Carbon::now()->subDays(90),
            'updated_at' => Carbon::now()->subDays(30),
        ]);
        $archived->timestamps = false;
        $archived->save();

        $staleOpen = new GuestApplication;
        $staleOpen->forceFill([
            'tracking_token' => 'PIPE001D',
            'first_name' => 'Deniz',
            'last_name' => 'D',
            'email' => 'deniz@example.test',
            'application_type' => 'bachelor',
            'lead_source' => 'google',
            'lead_status' => 'docs_pending',
            'converted_to_student' => false,
            'is_archived' => false,
            'created_at' => Carbon::now()->subDays(50),
            'updated_at' => Carbon::now()->subDays(10),
        ]);
        $staleOpen->timestamps = false;
        $staleOpen->save();

        $recentRecovery = new GuestApplication;
        $recentRecovery->forceFill([
            'tracking_token' => 'PIPE001E',
            'first_name' => 'Ece',
            'last_name' => 'E',
            'email' => 'ece@example.test',
            'application_type' => 'bachelor',
            'lead_source' => 'google',
            'lead_status' => 'evaluating',
            'converted_to_student' => false,
            'is_archived' => false,
            'created_at' => Carbon::now()->subDays(20),
            'updated_at' => Carbon::now()->subDays(5),
        ]);
        $recentRecovery->timestamps = false;
        $recentRecovery->save();

        $converted = new GuestApplication;
        $converted->forceFill([
            'tracking_token' => 'PIPE001F',
            'first_name' => 'Fatma',
            'last_name' => 'F',
            'email' => 'fatma@example.test',
            'application_type' => 'master',
            'lead_source' => 'google',
            'lead_status' => 'contract_signed',
            'converted_to_student' => true,
            'is_archived' => false,
            'created_at' => Carbon::now()->subDays(12),
            'updated_at' => Carbon::now()->subDays(2),
        ]);
        $converted->timestamps = false;
        $converted->save();

        StudentRevenue::query()->create([
            'student_id' => 'BCS-26-02-PIPE',
            'package_total_price' => 4000,
            'total_earned' => 1200,
            'total_pending' => 800,
        ]);

        $this->actingAs($user)
            ->get('/mktg-admin/pipeline')
            ->assertOk()
            ->assertSee('Genel Bakış')
            ->assertSee('Lead Durum')
            ->assertSee('docs_pending');

        $this->actingAs($user)
            ->get('/mktg-admin/pipeline/value')
            ->assertOk()
            ->assertSee('Pipeline Value')
            ->assertSee('Potansiyel Gelir')
            ->assertSee('Gerçekleşen Gelir');

        $this->actingAs($user)
            ->get('/mktg-admin/pipeline/loss-analysis')
            ->assertOk()
            ->assertSee('Loss Analysis')
            ->assertSee('stale_180_days')
            ->assertSee('Recovery Adayları');

        $this->actingAs($user)
            ->get('/mktg-admin/pipeline/conversion-time')
            ->assertOk()
            ->assertSee('Conversion Time')
            ->assertSee('Kaynak')
            ->assertSee('google');
    }
}
