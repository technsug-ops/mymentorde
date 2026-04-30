<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProgramCatalog\ProgramCatalogRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Form autocomplete endpoint — canonical Program kataloğunda arama.
 *
 * URL: GET /api/expatrio/programs?q=<term>&limit=20[&source=expatrio|hk|all|canonical]
 *
 * Faz 1: Default kaynak 'canonical' — kanonik Program tablosundan
 * doğrudan search. Bu Expatrio'ya bağımlı değil; HK aktif olsa da aynı
 * search bütün programları döner. Backward-compat: source=expatrio
 * ile sadece Expatrio source linkli programlar.
 *
 * Backward-compat URL'i korundu (route adı api.expatrio.programs.search);
 * gelecekte api.programs.search'e migrate edilebilir.
 *
 * Response:
 *  {
 *    "items": [
 *      {"id":"<canonical-uuid>", "source":"canonical", "label":"Course — University", ...}
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
        $source = trim((string) $request->query('source', '')) ?: 'canonical';

        if (mb_strlen($term) < 2) {
            return response()->json(['items' => []]);
        }

        $items = match ($source) {
            'all'        => $registry->searchAll($term, $limit),
            'canonical'  => $registry->searchCanonical($term, $limit),
            default      => $registry->getAdapter($source)->search($term, $limit),
        };

        // Backward-compat: eski endpoint sonuçlarda 'degree' field'ı bekler
        $items = array_map(static fn (array $i) => $i + ['degree' => $i['degree_specification'] ?? null], $items);

        return response()->json(['items' => $items]);
    }
}
