<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Services\AttributionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttributionController extends Controller
{
    public function __construct(private AttributionService $attribution) {}

    public function index(Request $request): \Illuminate\View\View
    {
        $model     = $request->query('model', 'position_based');
        $days      = (int) $request->query('days', 30);
        $startDate = now()->subDays($days)->toDateString();
        $endDate   = now()->toDateString();

        $channelSummary = $this->attribution->getChannelSummary($model, $startDate, $endDate);

        // Touchpoint count by channel
        $touchpointsByChannel = DB::table('lead_touchpoints')
            ->selectRaw('channel, COUNT(*) as total')
            ->where('touched_at', '>=', $startDate)
            ->groupBy('channel')
            ->orderByDesc('total')
            ->get();

        // Average touchpoints per conversion
        $avgTouches = DB::table('lead_touchpoints')
            ->join('guest_applications', 'lead_touchpoints.guest_application_id', '=', 'guest_applications.id')
            ->where('guest_applications.contract_status', 'approved')
            ->where('lead_touchpoints.touched_at', '>=', $startDate)
            ->selectRaw('lead_touchpoints.guest_application_id, COUNT(*) as touches')
            ->groupBy('lead_touchpoints.guest_application_id')
            ->pluck('touches')
            ->avg();

        $models = [
            'first_touch'    => 'First Touch',
            'last_touch'     => 'Last Touch',
            'linear'         => 'Linear',
            'time_decay'     => 'Time Decay',
            'position_based' => 'Position Based (U-shaped)',
        ];

        return view('marketing-admin.attribution.index', compact(
            'channelSummary', 'touchpointsByChannel', 'avgTouches', 'model', 'models', 'days'
        ));
    }

    public function compare(Request $request): \Illuminate\View\View
    {
        $days      = (int) $request->query('days', 30);
        $startDate = now()->subDays($days)->toDateString();
        $endDate   = now()->toDateString();

        $models  = ['first_touch', 'last_touch', 'linear', 'time_decay', 'position_based'];
        $results = [];

        foreach ($models as $model) {
            $results[$model] = $this->attribution->getChannelSummary($model, $startDate, $endDate);
        }

        // Collect all channels
        $channels = [];
        foreach ($results as $modelData) {
            foreach ($modelData as $row) {
                $channels[] = $row['channel'];
            }
        }
        $channels = array_unique($channels);

        $modelLabels = [
            'first_touch'    => 'First Touch',
            'last_touch'     => 'Last Touch',
            'linear'         => 'Linear',
            'time_decay'     => 'Time Decay',
            'position_based' => 'Position Based',
        ];

        return view('marketing-admin.attribution.compare', compact('results', 'channels', 'modelLabels', 'days'));
    }
}
