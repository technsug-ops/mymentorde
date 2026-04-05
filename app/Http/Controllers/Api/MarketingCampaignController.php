<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketingCampaign;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MarketingCampaignController extends Controller
{
    public function index()
    {
        return MarketingCampaign::orderByDesc('created_at')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'channel' => ['required', 'string', 'max:32'],
            'budget' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'target_audience' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:32'],
            'metrics' => ['nullable', 'array'],
            'linked_cms_content_ids' => ['nullable', 'array'],
        ]);

        $data['created_by'] = (string) optional($request->user())->email;

        return response()->json(MarketingCampaign::create($data), Response::HTTP_CREATED);
    }

    public function update(Request $request, MarketingCampaign $marketingCampaign)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'channel' => ['sometimes', 'required', 'string', 'max:32'],
            'budget' => ['sometimes', 'required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'target_audience' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:32'],
            'metrics' => ['nullable', 'array'],
            'linked_cms_content_ids' => ['nullable', 'array'],
        ]);

        $marketingCampaign->update($data);
        return $marketingCampaign->refresh();
    }

    public function pause(Request $request, MarketingCampaign $marketingCampaign)
    {
        if ($marketingCampaign->status === 'paused') {
            return response()->json(['ok' => true, 'status' => 'paused', 'message' => 'Kampanya zaten durdurulmuş.']);
        }

        $marketingCampaign->update(['status' => 'paused']);

        return response()->json(['ok' => true, 'status' => 'paused', 'id' => $marketingCampaign->id]);
    }

    public function resume(Request $request, MarketingCampaign $marketingCampaign)
    {
        if ($marketingCampaign->status === 'active') {
            return response()->json(['ok' => true, 'status' => 'active', 'message' => 'Kampanya zaten aktif.']);
        }

        $marketingCampaign->update(['status' => 'active']);

        return response()->json(['ok' => true, 'status' => 'active', 'id' => $marketingCampaign->id]);
    }

    public function destroy(Request $request, MarketingCampaign $marketingCampaign)
    {
        $role = (string) optional($request->user())->role;
        if (!in_array($role, ['manager', 'marketing_admin'], true)) {
            abort(Response::HTTP_FORBIDDEN, 'Kampanya silme yetkiniz yok.');
        }

        $marketingCampaign->delete();
        return response()->json(['ok' => true]);
    }
}
