<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuestRegistrationField;
use App\Services\GuestRegistrationFieldSchemaService;
use App\Support\SystematicInput;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GuestRegistrationFieldController extends Controller
{
    public function __construct(
        private readonly GuestRegistrationFieldSchemaService $schemaService
    ) {
    }

    public function index(Request $request)
    {
        $companyId = $this->currentCompanyId();
        $this->schemaService->ensureDefaults($companyId);

        $sectionKey = trim((string) $request->query('section_key', ''));
        $active = trim((string) $request->query('is_active', ''));

        return GuestRegistrationField::query()
            ->where('company_id', $companyId > 0 ? $companyId : 0)
            ->when($sectionKey !== '', fn ($q) => $q->where('section_key', $sectionKey))
            ->when($active !== '', fn ($q) => $q->where('is_active', in_array(strtolower($active), ['1', 'true', 'yes'], true)))
            ->orderBy('section_order')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'section_key' => ['required', 'string', 'max:80'],
            'section_title' => ['required', 'string', 'max:140'],
            'section_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'field_key' => ['required', 'string', 'max:100'],
            'label' => ['required', 'string', 'max:190'],
            'type' => ['required', Rule::in(['text', 'email', 'date', 'select', 'textarea'])],
            'is_required' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'max_length' => ['nullable', 'integer', 'min:10', 'max:10000'],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'help_text' => ['nullable', 'string', 'max:500'],
            'options_json' => ['nullable'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $companyId = $this->currentCompanyId();
        $data['section_key'] = SystematicInput::codeLower((string) $data['section_key'], 'section_key', 80);
        $data['field_key'] = SystematicInput::codeLower((string) $data['field_key'], 'field_key', 100);
        $data['section_title'] = trim((string) $data['section_title']);
        $data['label'] = trim((string) $data['label']);
        $data['placeholder'] = trim((string) ($data['placeholder'] ?? '')) ?: null;
        $data['help_text'] = trim((string) ($data['help_text'] ?? '')) ?: null;
        $data['company_id'] = $companyId > 0 ? $companyId : 0;
        $data['section_order'] = (int) ($data['section_order'] ?? 100);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 100);
        $data['is_required'] = (bool) ($data['is_required'] ?? false);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        $data['is_system'] = false;
        $data['options_json'] = $this->normalizeOptions($data['options_json'] ?? null);

        $exists = GuestRegistrationField::query()
            ->where('company_id', $data['company_id'])
            ->where('field_key', $data['field_key'])
            ->exists();
        abort_if($exists, 422, 'Bu field_key bu firma icin zaten tanimli.');

        $row = GuestRegistrationField::query()->create($data);
        return response()->json($row, Response::HTTP_CREATED);
    }

    public function update(GuestRegistrationField $guestRegistrationField, Request $request)
    {
        $this->assertCompanyAccess($guestRegistrationField);

        $data = $request->validate([
            'section_key' => ['nullable', 'string', 'max:80'],
            'section_title' => ['nullable', 'string', 'max:140'],
            'section_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'field_key' => ['nullable', 'string', 'max:100'],
            'label' => ['nullable', 'string', 'max:190'],
            'type' => ['nullable', Rule::in(['text', 'email', 'date', 'select', 'textarea'])],
            'is_required' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'max_length' => ['nullable', 'integer', 'min:10', 'max:10000'],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'help_text' => ['nullable', 'string', 'max:500'],
            'options_json' => ['nullable'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (array_key_exists('section_key', $data)) {
            $data['section_key'] = SystematicInput::codeLower((string) $data['section_key'], 'section_key', 80);
        }
        if (array_key_exists('field_key', $data)) {
            $data['field_key'] = SystematicInput::codeLower((string) $data['field_key'], 'field_key', 100);
        }
        if (array_key_exists('section_title', $data)) {
            $data['section_title'] = trim((string) $data['section_title']);
        }
        if (array_key_exists('label', $data)) {
            $data['label'] = trim((string) $data['label']);
        }
        if (array_key_exists('placeholder', $data)) {
            $data['placeholder'] = trim((string) ($data['placeholder'] ?? '')) ?: null;
        }
        if (array_key_exists('help_text', $data)) {
            $data['help_text'] = trim((string) ($data['help_text'] ?? '')) ?: null;
        }
        if (array_key_exists('options_json', $data)) {
            $data['options_json'] = $this->normalizeOptions($data['options_json']);
        }

        $nextFieldKey = (string) ($data['field_key'] ?? $guestRegistrationField->field_key);
        $duplicate = GuestRegistrationField::query()
            ->where('company_id', (int) $guestRegistrationField->company_id)
            ->where('field_key', $nextFieldKey)
            ->where('id', '!=', (int) $guestRegistrationField->id)
            ->exists();
        abort_if($duplicate, 422, 'Bu field_key bu firma icin zaten tanimli.');

        $guestRegistrationField->fill($data)->save();
        return $guestRegistrationField->fresh();
    }

    public function destroy(GuestRegistrationField $guestRegistrationField)
    {
        $this->assertCompanyAccess($guestRegistrationField);
        $guestRegistrationField->delete();
        return response()->json(['ok' => true]);
    }

    public function move(GuestRegistrationField $guestRegistrationField, Request $request)
    {
        $this->assertCompanyAccess($guestRegistrationField);
        $data = $request->validate([
            'direction' => ['required', Rule::in(['up', 'down'])],
        ]);

        $direction = (string) $data['direction'];
        $companyId = (int) $guestRegistrationField->company_id;
        $sectionKey = (string) $guestRegistrationField->section_key;
        $sort = (int) $guestRegistrationField->sort_order;
        $id = (int) $guestRegistrationField->id;

        $neighbor = GuestRegistrationField::query()
            ->where('company_id', $companyId)
            ->where('section_key', $sectionKey)
            ->when($direction === 'up', function ($q) use ($sort, $id) {
                $q->where(function ($qq) use ($sort, $id) {
                    $qq->where('sort_order', '<', $sort)
                        ->orWhere(function ($qqq) use ($sort, $id) {
                            $qqq->where('sort_order', $sort)->where('id', '<', $id);
                        });
                })
                ->orderByDesc('sort_order')
                ->orderByDesc('id');
            })
            ->when($direction === 'down', function ($q) use ($sort, $id) {
                $q->where(function ($qq) use ($sort, $id) {
                    $qq->where('sort_order', '>', $sort)
                        ->orWhere(function ($qqq) use ($sort, $id) {
                            $qqq->where('sort_order', $sort)->where('id', '>', $id);
                        });
                })
                ->orderBy('sort_order')
                ->orderBy('id');
            })
            ->first();

        if (!$neighbor) {
            return response()->json(['ok' => true, 'moved' => false, 'message' => 'Sinira ulasildi.']);
        }

        DB::transaction(function () use ($guestRegistrationField, $neighbor): void {
            $currentSort = (int) $guestRegistrationField->sort_order;
            $neighborSort = (int) $neighbor->sort_order;
            $guestRegistrationField->forceFill(['sort_order' => $neighborSort])->save();
            $neighbor->forceFill(['sort_order' => $currentSort])->save();
        });

        return response()->json(['ok' => true, 'moved' => true]);
    }

    public function clone(GuestRegistrationField $guestRegistrationField)
    {
        $this->assertCompanyAccess($guestRegistrationField);
        $companyId = (int) $guestRegistrationField->company_id;
        $baseKey = (string) $guestRegistrationField->field_key;
        $newKey = $baseKey.'_copy';
        $i = 1;
        while (GuestRegistrationField::query()->where('company_id', $companyId)->where('field_key', $newKey)->exists()) {
            $newKey = $baseKey.'_copy'.$i;
            $i++;
        }

        $clone = GuestRegistrationField::query()->create([
            'company_id' => $companyId,
            'section_key' => (string) $guestRegistrationField->section_key,
            'section_title' => (string) $guestRegistrationField->section_title,
            'section_order' => (int) $guestRegistrationField->section_order,
            'field_key' => $newKey,
            'label' => (string) $guestRegistrationField->label.' (Kopya)',
            'type' => (string) $guestRegistrationField->type,
            'is_required' => (bool) $guestRegistrationField->is_required,
            'sort_order' => (int) $guestRegistrationField->sort_order + 1,
            'max_length' => $guestRegistrationField->max_length,
            'placeholder' => $guestRegistrationField->placeholder,
            'help_text' => $guestRegistrationField->help_text,
            'options_json' => $guestRegistrationField->options_json,
            'is_active' => (bool) $guestRegistrationField->is_active,
            'is_system' => false,
        ]);

        return response()->json($clone, Response::HTTP_CREATED);
    }

    private function normalizeOptions(mixed $raw): ?array
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (is_array($raw)) {
            return collect($raw)
                ->map(function ($row) {
                    if (is_string($row)) {
                        $v = trim($row);
                        return $v === '' ? null : ['value' => $v, 'label' => $v];
                    }
                    if (is_array($row)) {
                        $value = trim((string) ($row['value'] ?? ''));
                        $label = trim((string) ($row['label'] ?? $value));
                        if ($value === '') {
                            return null;
                        }
                        return ['value' => $value, 'label' => $label === '' ? $value : $label];
                    }
                    return null;
                })
                ->filter()
                ->values()
                ->all();
        }

        $txt = trim((string) $raw);
        if ($txt === '') {
            return null;
        }
        $decoded = json_decode($txt, true);
        if (is_array($decoded)) {
            return $this->normalizeOptions($decoded);
        }

        $parts = collect(preg_split('/[,;\n]+/', $txt) ?: [])
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->values();
        if ($parts->isEmpty()) {
            return null;
        }
        return $parts->map(fn ($v) => ['value' => $v, 'label' => $v])->all();
    }

    private function currentCompanyId(): int
    {
        return app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
    }

    private function assertCompanyAccess(GuestRegistrationField $row): void
    {
        $companyId = $this->currentCompanyId();
        if ($companyId > 0 && (int) $row->company_id !== $companyId) {
            abort(403, 'Bu alan farkli firmaya ait.');
        }
    }
}
