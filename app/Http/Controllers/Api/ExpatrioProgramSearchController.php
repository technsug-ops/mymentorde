<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProgramCatalog\ProgramCatalogRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Form autocomplete endpoint — kaynak-bağımsız program kataloğunda arama.
 * URL: GET /api/expatrio/programs?q=<term>&limit=20[&source=expatrio|hk|all]
 *
 * Backward-compat: URL "expatrio" prefix'iyle (eski endpoint) ama internal
 * implementation ProgramCatalogRegistry üzerinden çoklu kaynak destekler.
 *
 * Response:
 *  {
 *    "items": [
 *      {"id":"...", "source":"expatrio", "label":"Course — University", "degree":"...", ...}
 *    ]
 *  }
 *
 * Rate limit: throttle:120,1 (dakikada 120 — autocomplete için yeterli).
 */
class ExpatrioProgramSearchController extends Controller
{
    public function __invoke(Request $request, ProgramCatalogRegistry $registry): JsonResponse
    {
        $term   = trim((string) $request->query('q', ''));
        $limit  = max(1, min(50, (int) $request->query('limit', 20)));
        $source = trim((string) $request->query('source', '')) ?: null;

        if (mb_strlen($term) < 2) {
            return response()->json(['items' => []]);
        }

        // source=all veya boş → aktif tüm kaynaklarda search
        // source=expatrio veya hk → sadece o kaynakta
        if ($source === null || $source === 'all') {
            $items = $registry->searchAll($term, $limit);
        } else {
            $items = $registry->getAdapter($source)->search($term, $limit);
        }

        // Backward-compat: eski endpoint sonuçlarda 'degree' field'ı bekler
        $items = array_map(static fn (array $i) => $i + ['degree' => $i['degree_specification'] ?? null], $items);

        return response()->json(['items' => $items]);
    }
}
