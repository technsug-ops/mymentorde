<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Services\LandingInventoryService;
use Illuminate\View\View;

/**
 * Manager — sisteme bağlı public landing sayfaları envanteri.
 * Linklerin unutulmaması için kuratörlü registry + auto-discovery uyarısı.
 */
class LandingInventoryController extends Controller
{
    public function __construct(private readonly LandingInventoryService $svc) {}

    public function index(): View
    {
        $diff    = $this->svc->diff();
        $grouped = $this->svc->grouped();
        $registry = $this->svc->registry();

        return view('manager.landing-inventory.index', [
            'matched'  => $diff['matched'],
            'missing'  => $diff['missing'],
            'dead'     => $diff['dead'],
            'grouped'  => $grouped,
            'registry' => $registry,
            'totals'   => [
                'all'      => count($registry),
                'matched'  => count($diff['matched']),
                'missing'  => count($diff['missing']),
                'dead'     => count($diff['dead']),
            ],
        ]);
    }
}
