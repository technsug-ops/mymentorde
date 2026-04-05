<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeadSourceOption;
use App\Support\SystematicInput;
use Illuminate\Http\Request;

class LeadSourceOptionController extends Controller
{
    public function index(Request $request)
    {
        $activeOnly = filter_var($request->query('active_only', false), FILTER_VALIDATE_BOOLEAN);

        return LeadSourceOption::query()
            ->when($activeOnly, fn ($q) => $q->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get(['id', 'code', 'label', 'sort_order', 'is_active', 'created_at']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64', 'unique:lead_source_options,code'],
            'label' => ['nullable', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $code = SystematicInput::codeLower((string) $data['code'], 'code');
        $label = trim((string) ($data['label'] ?? ''));
        if ($label === '') {
            $label = $this->humanizeCode($code);
        }

        $row = LeadSourceOption::query()->create([
            'code' => $code,
            'label' => $label,
            'sort_order' => (int) ($data['sort_order'] ?? 100),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'created_by' => (string) optional($request->user())->email,
            'updated_by' => (string) optional($request->user())->email,
        ]);

        return response()->json($row->fresh(), 201);
    }

    public function update(LeadSourceOption $leadSourceOption, Request $request)
    {
        $data = $request->validate([
            'code' => ['sometimes', 'required', 'string', 'max:64', 'unique:lead_source_options,code,'.$leadSourceOption->id],
            'label' => ['nullable', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $code = $leadSourceOption->code;
        if (array_key_exists('code', $data)) {
            $code = SystematicInput::codeLower((string) $data['code'], 'code');
            $data['code'] = $code;
        }
        if (array_key_exists('label', $data)) {
            $label = trim((string) ($data['label'] ?? ''));
            $data['label'] = $label !== '' ? $label : $this->humanizeCode((string) $code);
        }
        $data['updated_by'] = (string) optional($request->user())->email;

        $leadSourceOption->update($data);

        return response()->json($leadSourceOption->fresh());
    }

    private function humanizeCode(string $code): string
    {
        $normalized = preg_replace('/[^a-z0-9]+/i', ' ', strtolower(trim($code))) ?? '';
        $normalized = trim($normalized);
        if ($normalized === '') {
            return 'Unknown';
        }

        return collect(explode(' ', $normalized))
            ->filter(fn ($part) => $part !== '')
            ->map(fn ($part) => ucfirst($part))
            ->implode(' ');
    }
}
