<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompanyContextController extends Controller
{
    public function index(Request $request)
    {
        $rows = Company::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_active']);

        return response()->json([
            'current_company_id' => (int) $request->session()->get('current_company_id', 0),
            'companies' => $rows,
        ]);
    }

    public function switch(Request $request)
    {
        $data = $request->validate([
            'company_id' => ['required', 'integer'],
        ]);

        $company = Company::query()
            ->where('id', (int) $data['company_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $request->session()->put('current_company_id', (int) $company->id);

        return response()->json([
            'ok' => true,
            'current_company_id' => (int) $company->id,
            'company' => $company,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'code' => ['required', 'string', 'max:40', 'alpha_dash', Rule::unique('companies', 'code')],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $row = Company::query()->create([
            'name' => trim((string) $data['name']),
            'code' => strtolower(trim((string) $data['code'])),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return response()->json([
            'ok' => true,
            'company' => $row,
        ], 201);
    }

    public function update(Request $request, Company $company)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:190'],
            'code' => ['sometimes', 'required', 'string', 'max:40', 'alpha_dash', Rule::unique('companies', 'code')->ignore($company->id)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $payload = [];
        if (array_key_exists('name', $data)) {
            $payload['name'] = trim((string) $data['name']);
        }
        if (array_key_exists('code', $data)) {
            $payload['code'] = strtolower(trim((string) $data['code']));
        }
        if (array_key_exists('is_active', $data)) {
            $payload['is_active'] = (bool) $data['is_active'];
        }

        if (!empty($payload)) {
            $company->update($payload);
        }

        return response()->json([
            'ok' => true,
            'company' => $company->fresh(),
        ]);
    }
}
