<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DealerType;
use App\Support\SystematicInput;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DealerTypeController extends Controller
{
    public function index()
    {
        return DealerType::orderBy('sort_order')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name_tr' => ['required', 'string', 'max:255'],
            'name_de' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:64', 'unique:dealer_types,code'],
            'description_tr' => ['nullable', 'string'],
            'description_de' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'permissions' => ['nullable', 'array'],
            'default_commission_config' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'created_by' => ['nullable', 'string', 'max:255'],
        ]);

        $data['code'] = SystematicInput::codeLower((string) $data['code'], 'code');

        $dealerType = DealerType::create($data);

        return response()->json($dealerType, Response::HTTP_CREATED);
    }

    public function update(Request $request, DealerType $dealerType)
    {
        $data = $request->validate([
            'name_tr' => ['sometimes', 'required', 'string', 'max:255'],
            'name_de' => ['sometimes', 'required', 'string', 'max:255'],
            'name_en' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['sometimes', 'required', 'string', 'max:64', 'unique:dealer_types,code,' . $dealerType->id],
            'description_tr' => ['nullable', 'string'],
            'description_de' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'permissions' => ['nullable', 'array'],
            'default_commission_config' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'created_by' => ['nullable', 'string', 'max:255'],
        ]);

        if (array_key_exists('code', $data)) {
            $data['code'] = SystematicInput::codeLower((string) $data['code'], 'code');
        }

        $dealerType->update($data);

        return $dealerType->refresh();
    }
}
