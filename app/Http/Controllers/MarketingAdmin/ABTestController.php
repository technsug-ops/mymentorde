<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\ABTest;
use App\Models\ABTestVariant;
use App\Services\ABTestingService;
use Illuminate\Http\Request;

class ABTestController extends Controller
{
    public function __construct(private ABTestingService $abTesting) {}

    public function index(): \Illuminate\View\View
    {
        $tests = ABTest::withoutTrashed()
            ->withCount('assignments')
            ->latest()
            ->paginate(20);

        $statusLabels = [
            'draft'            => 'Taslak',
            'pending_approval' => 'Onay Bekliyor',
            'running'          => 'Çalışıyor',
            'paused'           => 'Duraklatıldı',
            'completed'        => 'Tamamlandı',
            'winner_applied'   => 'Kazanan Uygulandı',
        ];
        $statusColors = [
            'draft'            => 'info',
            'pending_approval' => 'warn',
            'running'          => 'ok',
            'paused'           => 'pending',
            'completed'        => 'info',
            'winner_applied'   => 'ok',
        ];
        $typeLabels = [
            'email_subject'   => 'Email Konu',
            'email_content'   => 'Email İçerik',
            'landing_page'    => 'Landing Page',
            'cms_title'       => 'CMS Başlık',
            'workflow_split'  => 'Workflow Bölünme',
            'package_display' => 'Paket Gösterimi',
        ];

        return view('marketing-admin.abtests.index', compact(
            'tests', 'statusLabels', 'statusColors', 'typeLabels'
        ));
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'test_type'       => 'required|string',
            'primary_metric'  => 'required|string',
            'min_sample_size' => 'integer|min:10',
            'confidence_level' => 'numeric|between:0.8,0.99',
            'auto_winner'     => 'boolean',
        ]);

        $test = ABTest::create([
            ...$data,
            'status'        => 'draft',
            'traffic_split' => ['A' => 50, 'B' => 50],
            'created_by'    => auth()->id(),
        ]);

        // Create default A and B variants
        ABTestVariant::create(['ab_test_id' => $test->id, 'variant_code' => 'A', 'variant_config' => []]);
        ABTestVariant::create(['ab_test_id' => $test->id, 'variant_code' => 'B', 'variant_config' => []]);

        return redirect("/mktg-admin/abtests/{$test->id}")->with('success', 'A/B Test oluşturuldu.');
    }

    public function show(ABTest $abtest): \Illuminate\View\View
    {
        $abtest->load('variants');
        $significance = $this->abTesting->checkSignificance($abtest->id);

        $metricLabels = [
            'open_rate'       => 'Açılma Oranı',
            'click_rate'      => 'Tıklama Oranı',
            'conversion_rate' => 'Dönüşüm Oranı',
        ];

        return view('marketing-admin.abtests.show', compact('abtest', 'significance', 'metricLabels'));
    }

    public function activate(ABTest $abtest): \Illuminate\Http\RedirectResponse
    {
        if (! in_array($abtest->status, ['draft', 'pending_approval', 'paused'], true)) {
            return back()->with('error', 'Bu durumdaki test başlatılamaz.');
        }
        $abtest->update(['status' => 'running', 'started_at' => now(), 'approved_by' => auth()->id()]);
        return back()->with('success', 'A/B Test başlatıldı.');
    }

    public function applyWinner(ABTest $abtest): \Illuminate\Http\RedirectResponse
    {
        $result = $this->abTesting->applyWinner($abtest->id);

        if (! $result) {
            return back()->with('error', 'İstatistiksel anlamlılık henüz sağlanmadı.');
        }

        return back()->with('success', "Kazanan varyant uygulandı: {$abtest->fresh()->winner_variant}");
    }
}
