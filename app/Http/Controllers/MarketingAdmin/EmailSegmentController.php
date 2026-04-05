<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\Marketing\EmailCampaign;
use App\Models\Marketing\EmailSegment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmailSegmentController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'type' => (string) $request->query('type', 'all'),
            'status' => (string) $request->query('status', 'all'),
        ];

        $query = EmailSegment::query()->orderByDesc('id');
        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->where(function ($w) use ($q): void {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('zoho_list_id', 'like', "%{$q}%");
            });
        }
        if (in_array($filters['type'], ['manual', 'dynamic'], true)) {
            $query->where('type', $filters['type']);
        }
        if ($filters['status'] === 'active') {
            $query->where('is_active', true);
        } elseif ($filters['status'] === 'passive') {
            $query->where('is_active', false);
        }

        $rows = $query->paginate(15)->withQueryString();
        $editId = (int) $request->query('edit_id', 0);
        $editing = $editId > 0 ? EmailSegment::query()->find($editId) : null;

        return view('marketing-admin.email.segments.index', [
            'pageTitle' => 'E-posta Segmentleri',
            'title' => 'Segment Listesi',
            'rows' => $rows,
            'filters' => $filters,
            'editing' => $editing,
            'stats' => $this->stats(),
            'typeOptions' => ['manual', 'dynamic'],
            'userOptions' => User::query()->orderBy('name')->limit(300)->get(['id', 'name', 'email', 'role', 'is_active']),
        ]);
    }

    public function create()
    {
        return redirect('/mktg-admin/email/segments');
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request, true);

        $members = $this->resolveMemberIds(
            (string) $data['type'],
            (array) ($data['rules'] ?? []),
            (array) ($data['member_user_ids'] ?? [])
        );

        $row = EmailSegment::query()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'rules' => $data['rules'] ?? [],
            'member_user_ids' => $members,
            'estimated_size' => count($members),
            'last_calculated_at' => now(),
            'zoho_list_id' => $data['zoho_list_id'] ?? null,
            'zoho_synced' => $request->boolean('zoho_synced', false),
            'is_active' => $request->boolean('is_active', true),
            'created_by' => (int) $request->user()->id,
        ]);

        return $this->responseFor($request, ['ok' => true, 'id' => $row->id], 'Segment olusturuldu.', Response::HTTP_CREATED);
    }

    public function show(string $id)
    {
        return redirect('/mktg-admin/email/segments?edit_id='.$id);
    }

    public function edit(string $id)
    {
        return redirect('/mktg-admin/email/segments?edit_id='.$id);
    }

    public function update(Request $request, string $id)
    {
        $row = EmailSegment::query()->findOrFail($id);
        $data = $this->validatePayload($request, false);

        $type = (string) Arr::get($data, 'type', $row->type);
        $rules = array_key_exists('rules', $data) ? (array) $data['rules'] : (array) ($row->rules ?? []);
        $memberRaw = array_key_exists('member_user_ids', $data)
            ? (array) $data['member_user_ids']
            : (array) ($row->member_user_ids ?? []);

        $members = $this->resolveMemberIds($type, $rules, $memberRaw);

        $payload = [
            'name' => Arr::get($data, 'name', $row->name),
            'description' => Arr::get($data, 'description', $row->description),
            'type' => $type,
            'rules' => $rules,
            'member_user_ids' => $members,
            'estimated_size' => count($members),
            'last_calculated_at' => now(),
            'zoho_list_id' => Arr::get($data, 'zoho_list_id', $row->zoho_list_id),
            'zoho_synced' => $request->has('zoho_synced') ? $request->boolean('zoho_synced') : (bool) $row->zoho_synced,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : (bool) $row->is_active,
        ];

        $row->update($payload);

        return $this->responseFor($request, ['ok' => true, 'id' => $id], 'Segment guncellendi.');
    }

    public function destroy(Request $request, string $id)
    {
        $row = EmailSegment::query()->findOrFail($id);

        $isUsed = EmailCampaign::query()
            ->whereJsonContains('segment_ids', (int) $row->id)
            ->exists();
        if ($isUsed) {
            $row->update(['is_active' => false]);
            return $this->responseFor(
                $request,
                ['ok' => false, 'id' => $id, 'archived' => true],
                'Segment kampanyada kullanildigi icin silinemedi; pasif yapildi.',
                Response::HTTP_CONFLICT
            );
        }

        $row->delete();
        return $this->responseFor($request, ['ok' => true, 'id' => $id], 'Segment silindi.');
    }

    public function previewMembers(string $id)
    {
        $row = EmailSegment::query()->findOrFail($id);
        $memberIds = $this->resolveMemberIds(
            (string) $row->type,
            (array) ($row->rules ?? []),
            (array) ($row->member_user_ids ?? [])
        );
        $members = User::query()
            ->whereIn('id', $memberIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'is_active', 'student_id', 'dealer_code', 'senior_code']);

        return view('marketing-admin.email.segments.preview', [
            'pageTitle' => 'Segment Onizleme',
            'title' => 'Segment #'.$id.' uyeleri',
            'segment' => $row,
            'members' => $members,
            'memberCount' => $members->count(),
        ]);
    }

    private function validatePayload(Request $request, bool $isCreate): array
    {
        $rules = [
            'name' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => [$isCreate ? 'required' : 'sometimes', Rule::in(['manual', 'dynamic'])],
            'zoho_list_id' => ['nullable', 'string', 'max:190'],
            'is_active' => ['nullable'],
            'zoho_synced' => ['nullable'],
            'member_user_ids' => ['nullable'],
            'rules_text' => ['nullable', 'string'],
        ];
        $data = $request->validate($rules);

        $data['member_user_ids'] = $this->normalizeMemberIdsInput($request->input('member_user_ids', []));
        $data['rules'] = $this->normalizeRulesInput($request->input('rules_text', null));

        return $data;
    }

    private function normalizeMemberIdsInput(mixed $raw): array
    {
        if (is_array($raw)) {
            $arr = $raw;
        } else {
            $txt = trim((string) $raw);
            $arr = $txt === '' ? [] : explode(',', $txt);
        }
        return collect($arr)
            ->map(fn ($v) => (int) trim((string) $v))
            ->filter(fn ($v) => $v > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeRulesInput(mixed $raw): array
    {
        if ($raw === null || trim((string) $raw) === '') {
            return [];
        }
        $decoded = json_decode((string) $raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            throw ValidationException::withMessages([
                'rules_text' => 'rules_text gecerli JSON olmalidir. Ornek: {"role":"student","is_active":true}',
            ]);
        }
        return $decoded;
    }

    private function resolveMemberIds(string $type, array $rules, array $manualIds): array
    {
        if ($type !== 'dynamic') {
            return $this->validateUserIds($manualIds);
        }

        $query = User::query();
        $role = trim((string) Arr::get($rules, 'role', ''));
        if ($role !== '') {
            $query->where('role', $role);
        }
        if (Arr::has($rules, 'is_active')) {
            $query->where('is_active', (bool) Arr::get($rules, 'is_active'));
        }
        $studentType = trim((string) Arr::get($rules, 'student_type', ''));
        if ($studentType !== '') {
            $query->where('senior_type', $studentType);
        }
        $emailContains = trim((string) Arr::get($rules, 'email_contains', ''));
        if ($emailContains !== '') {
            $query->where('email', 'like', '%'.$emailContains.'%');
        }
        $limit = (int) Arr::get($rules, 'limit', 0);
        if ($limit > 0) {
            $query->limit($limit);
        }

        $dynamicIds = $query->orderBy('id')->pluck('id')->map(fn ($v) => (int) $v)->all();
        if ($dynamicIds === [] && $manualIds !== []) {
            return $this->validateUserIds($manualIds);
        }
        return array_values(array_unique($dynamicIds));
    }

    private function validateUserIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $exists = User::query()
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($v) => (int) $v)
            ->all();
        return array_values(array_unique($exists));
    }

    private function stats(): array
    {
        return [
            'total' => EmailSegment::query()->count(),
            'active' => EmailSegment::query()->where('is_active', true)->count(),
            'dynamic' => EmailSegment::query()->where('type', 'dynamic')->count(),
            'manual' => EmailSegment::query()->where('type', 'manual')->count(),
        ];
    }

    private function responseFor(Request $request, array $payload, string $statusMessage, int $statusCode = Response::HTTP_OK)
    {
        if ($request->expectsJson()) {
            return response()->json($payload, $statusCode);
        }

        return redirect('/mktg-admin/email/segments')->with('status', $statusMessage);
    }
}
