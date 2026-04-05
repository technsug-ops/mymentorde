<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StudentCardService;
use App\Services\StudentSearchService;
use Illuminate\Http\Request;

class StudentCardController extends Controller
{
    public function show(string $studentId, StudentCardService $cardService)
    {
        return response()->json($cardService->build($studentId));
    }

    public function search(Request $request, StudentSearchService $searchService)
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        return response()->json([
            'query' => $data['q'],
            'items' => $searchService->search($data['q']),
        ]);
    }
}
