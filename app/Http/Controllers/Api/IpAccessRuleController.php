<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IpAccessRule;
use Illuminate\Http\Request;

/**
 * K2 — IP Bazlı Erişim Kontrolü (Manager)
 */
class IpAccessRuleController extends Controller
{
    public function index(): \Illuminate\Http\JsonResponse
    {
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;

        $rules = IpAccessRule::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->orderByDesc('id')
            ->get();

        return response()->json(['ok' => true, 'data' => $rules]);
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'rule_type'        => 'required|in:whitelist,blacklist',
            'ip_range'         => 'required|string|max:45',
            'description'      => 'nullable|string|max:180',
            'applies_to_roles' => 'nullable|array',
            'is_active'        => 'boolean',
        ]);

        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;

        $rule = IpAccessRule::create(array_merge($data, [
            'company_id'  => $companyId,
            'created_by'  => $request->user()?->email,
        ]));

        return response()->json(['ok' => true, 'data' => $rule], 201);
    }

    public function destroy(int $id): \Illuminate\Http\JsonResponse
    {
        IpAccessRule::findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }
}
