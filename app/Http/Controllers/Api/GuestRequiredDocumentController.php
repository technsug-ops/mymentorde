<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuestRequiredDocument;
use App\Support\SystematicInput;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GuestRequiredDocumentController extends Controller
{
    public function index(Request $request)
    {
        $applicationType = trim((string) $request->query('application_type', ''));

        return GuestRequiredDocument::query()
            ->when($applicationType !== '', fn ($q) => $q->where('application_type', $applicationType))
            ->orderBy('application_type')
            ->orderBy('sort_order')
            ->orderBy('document_code')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'application_type' => ['required', 'string', 'max:64'],
            'document_code' => ['required', 'string', 'max:64'],
            'category_code' => ['required', 'string', 'max:64', Rule::exists('document_categories', 'code')],
            'name' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_required' => ['nullable', 'boolean'],
            'accepted' => ['nullable', 'string', 'max:120'],
            'max_mb' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['application_type'] = SystematicInput::codeLower((string) $data['application_type'], 'application_type');
        $data['document_code'] = SystematicInput::codeUpper((string) $data['document_code'], 'document_code');
        $data['category_code'] = SystematicInput::codeUpper((string) $data['category_code'], 'category_code');

        $exists = GuestRequiredDocument::query()
            ->where('application_type', $data['application_type'])
            ->where('document_code', $data['document_code'])
            ->exists();
        abort_if($exists, 422, 'Bu application_type + document_code zaten tanimli.');

        $row = GuestRequiredDocument::query()->create([
            'application_type' => $data['application_type'],
            'document_code' => $data['document_code'],
            'category_code' => $data['category_code'],
            'name' => trim((string) $data['name']),
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'is_required' => (bool) ($data['is_required'] ?? true),
            'accepted' => trim((string) ($data['accepted'] ?? 'pdf,jpg,png')),
            'max_mb' => (int) ($data['max_mb'] ?? 10),
            'sort_order' => (int) ($data['sort_order'] ?? 100),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return response()->json($row, Response::HTTP_CREATED);
    }

    public function update(GuestRequiredDocument $guestRequiredDocument, Request $request)
    {
        $data = $request->validate([
            'application_type' => ['nullable', 'string', 'max:64'],
            'document_code' => ['nullable', 'string', 'max:64'],
            'category_code' => ['nullable', 'string', 'max:64', Rule::exists('document_categories', 'code')],
            'name' => ['nullable', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_required' => ['nullable', 'boolean'],
            'accepted' => ['nullable', 'string', 'max:120'],
            'max_mb' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (array_key_exists('application_type', $data)) {
            $data['application_type'] = SystematicInput::codeLower((string) $data['application_type'], 'application_type');
        }
        if (array_key_exists('document_code', $data)) {
            $data['document_code'] = SystematicInput::codeUpper((string) $data['document_code'], 'document_code');
        }
        if (array_key_exists('category_code', $data)) {
            $data['category_code'] = SystematicInput::codeUpper((string) $data['category_code'], 'category_code');
        }

        $nextAppType = $data['application_type'] ?? (string) $guestRequiredDocument->application_type;
        $nextDocCode = $data['document_code'] ?? (string) $guestRequiredDocument->document_code;
        $duplicate = GuestRequiredDocument::query()
            ->where('application_type', $nextAppType)
            ->where('document_code', $nextDocCode)
            ->where('id', '!=', (int) $guestRequiredDocument->id)
            ->exists();
        abort_if($duplicate, 422, 'Bu application_type + document_code zaten tanimli.');

        $guestRequiredDocument->fill($data)->save();
        return $guestRequiredDocument->fresh();
    }

    public function destroy(GuestRequiredDocument $guestRequiredDocument)
    {
        $guestRequiredDocument->delete();
        return response()->json(['ok' => true]);
    }

    public function publish(Request $request)
    {
        $data = $request->validate([
            'application_type' => ['nullable', 'string', 'max:64'],
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer', 'min:1'],
            'replace' => ['nullable', 'boolean'],
        ]);

        $applicationType = strtolower(trim((string) ($data['application_type'] ?? '')));
        $ids = collect($data['ids'] ?? [])->map(fn ($v) => (int) $v)->filter(fn ($v) => $v > 0)->values()->all();
        $replace = (bool) ($data['replace'] ?? false);

        $baseQuery = GuestRequiredDocument::query();
        if ($applicationType !== '') {
            $baseQuery->where('application_type', $applicationType);
        }

        if (!empty($ids)) {
            $baseQuery->whereIn('id', $ids);
        }

        if ($replace && $applicationType !== '') {
            DB::transaction(function () use ($applicationType, $ids): void {
                GuestRequiredDocument::query()
                    ->where('application_type', $applicationType)
                    ->update(['is_active' => false]);

                if (!empty($ids)) {
                    GuestRequiredDocument::query()
                        ->where('application_type', $applicationType)
                        ->whereIn('id', $ids)
                        ->update(['is_active' => true]);
                }
            });
        } else {
            $baseQuery->update(['is_active' => true]);
        }

        $activeCount = GuestRequiredDocument::query()
            ->when($applicationType !== '', fn ($q) => $q->where('application_type', $applicationType))
            ->where('is_active', true)
            ->count();

        return response()->json([
            'ok' => true,
            'application_type' => $applicationType !== '' ? $applicationType : null,
            'active_count' => $activeCount,
        ]);
    }
}
