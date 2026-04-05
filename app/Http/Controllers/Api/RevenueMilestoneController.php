<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RevenueMilestone;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RevenueMilestoneController extends Controller
{
    public function index()
    {
        return RevenueMilestone::orderBy('sort_order')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'external_id' => ['required', 'string', 'max:32', 'unique:revenue_milestones,external_id'],
            'name_tr' => ['required', 'string', 'max:255'],
            'name_de' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'description_tr' => ['nullable', 'string'],
            'description_de' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'trigger_type' => ['required', 'string', 'max:32'],
            'trigger_condition' => ['nullable', 'array'],
            'revenue_type' => ['required', 'string', 'max:32'],
            'percentage' => ['nullable', 'numeric'],
            'fixed_amount' => ['nullable', 'numeric'],
            'fixed_currency' => ['nullable', 'string', 'max:8'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_required' => ['nullable', 'boolean'],
            'created_by' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json(RevenueMilestone::create($data), Response::HTTP_CREATED);
    }

    public function update(Request $request, RevenueMilestone $revenueMilestone)
    {
        $data = $request->validate([
            'external_id' => ['sometimes', 'required', 'string', 'max:32', 'unique:revenue_milestones,external_id,'.$revenueMilestone->id],
            'name_tr' => ['sometimes', 'required', 'string', 'max:255'],
            'name_de' => ['sometimes', 'required', 'string', 'max:255'],
            'name_en' => ['sometimes', 'required', 'string', 'max:255'],
            'description_tr' => ['nullable', 'string'],
            'description_de' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'trigger_type' => ['sometimes', 'required', 'string', 'max:32'],
            'trigger_condition' => ['nullable', 'array'],
            'revenue_type' => ['sometimes', 'required', 'string', 'max:32'],
            'percentage' => ['nullable', 'numeric'],
            'fixed_amount' => ['nullable', 'numeric'],
            'fixed_currency' => ['nullable', 'string', 'max:8'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_required' => ['nullable', 'boolean'],
            'created_by' => ['nullable', 'string', 'max:255'],
        ]);

        $revenueMilestone->update($data);
        return $revenueMilestone->refresh();
    }
}
