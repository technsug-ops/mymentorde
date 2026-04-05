<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DealerRevenueMilestone;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DealerRevenueMilestoneController extends Controller
{
    public function index()
    {
        return DealerRevenueMilestone::orderBy('sort_order')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'external_id' => ['required', 'string', 'max:32', 'unique:dealer_revenue_milestones,external_id'],
            'name_tr' => ['required', 'string', 'max:255'],
            'name_de' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'trigger_type' => ['required', 'string', 'max:32'],
            'trigger_condition' => ['nullable', 'array'],
            'revenue_type' => ['required', 'string', 'max:32'],
            'percentage' => ['nullable', 'numeric'],
            'fixed_amount' => ['nullable', 'numeric'],
            'fixed_currency' => ['nullable', 'string', 'max:8'],
            'applicable_dealer_types' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return response()->json(DealerRevenueMilestone::create($data), Response::HTTP_CREATED);
    }

    public function update(Request $request, DealerRevenueMilestone $dealerRevenueMilestone)
    {
        $data = $request->validate([
            'external_id' => ['sometimes', 'required', 'string', 'max:32', 'unique:dealer_revenue_milestones,external_id,'.$dealerRevenueMilestone->id],
            'name_tr' => ['sometimes', 'required', 'string', 'max:255'],
            'name_de' => ['sometimes', 'required', 'string', 'max:255'],
            'name_en' => ['sometimes', 'required', 'string', 'max:255'],
            'trigger_type' => ['sometimes', 'required', 'string', 'max:32'],
            'trigger_condition' => ['nullable', 'array'],
            'revenue_type' => ['sometimes', 'required', 'string', 'max:32'],
            'percentage' => ['nullable', 'numeric'],
            'fixed_amount' => ['nullable', 'numeric'],
            'fixed_currency' => ['nullable', 'string', 'max:8'],
            'applicable_dealer_types' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $dealerRevenueMilestone->update($data);
        return $dealerRevenueMilestone->refresh();
    }
}
