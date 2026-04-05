<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dealer;
use App\Models\DealerStudentRevenue;
use App\Models\DealerTypeHistory;
use App\Models\StudentAssignment;
use App\Support\ApiResponse;
use App\Support\SystematicInput;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DealerController extends Controller
{
    public function index(Request $request)
    {
        $activeOnly = filter_var($request->query('active_only', false), FILTER_VALIDATE_BOOLEAN);
        $status = strtolower(trim((string) $request->query('status', 'all')));

        return Dealer::query()
            ->when($activeOnly, fn ($q) => $q->where('is_active', true)->where('is_archived', false))
            ->when($status === 'active', fn ($q) => $q->where('is_archived', false)->where('is_active', true))
            ->when($status === 'passive', fn ($q) => $q->where('is_archived', false)->where('is_active', false))
            ->when($status === 'archived', fn ($q) => $q->where('is_archived', true))
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'dealer_type_code', 'is_active', 'is_archived', 'archived_by', 'archived_at', 'created_at']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'dealer_type_code' => ['required', 'string', 'max:64', Rule::exists('dealer_types', 'code')],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['dealer_type_code'] = SystematicInput::codeLower((string) $data['dealer_type_code'], 'dealer_type_code');

        $generated = $this->generateDealerCode((string) ($data['dealer_type_code'] ?? ''));

        $row = Dealer::query()->create([
            'code' => $generated['code'],
            'internal_sequence' => $generated['internal_sequence'],
            'name' => trim((string) $data['name']),
            'dealer_type_code' => $data['dealer_type_code'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'is_archived' => false,
        ]);

        return response()->json($row->fresh(), 201);
    }

    public function typeHistory(Request $request)
    {
        $dealerCode = trim((string) $request->query('dealer_code', ''));

        return DealerTypeHistory::query()
            ->when($dealerCode !== '', fn ($q) => $q->where('dealer_code', $dealerCode))
            ->orderByDesc('changed_at')
            ->limit(200)
            ->get(['id', 'dealer_id', 'dealer_code', 'old_type_code', 'new_type_code', 'changed_by', 'changed_at']);
    }

    public function update(Dealer $dealer, Request $request)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'dealer_type_code' => ['nullable', 'string', 'max:64', Rule::exists('dealer_types', 'code')],
            'is_active' => ['nullable', 'boolean'],
        ]);
        if (array_key_exists('name', $data)) {
            $data['name'] = trim((string) $data['name']);
        }
        if (array_key_exists('dealer_type_code', $data) && $data['dealer_type_code'] !== null) {
            $data['dealer_type_code'] = SystematicInput::codeLower((string) $data['dealer_type_code'], 'dealer_type_code');
        }

        $oldType = $dealer->dealer_type_code;
        $dealer->update($data);
        $newType = $dealer->fresh()->dealer_type_code;
        if ($oldType !== $newType) {
            DealerTypeHistory::query()->create([
                'dealer_id' => $dealer->id,
                'dealer_code' => $dealer->code,
                'old_type_code' => $oldType,
                'new_type_code' => $newType,
                'changed_by' => optional($request->user())->email,
                'changed_at' => now(),
            ]);
        }

        return $dealer->fresh();
    }

    public function archive(Dealer $dealer, Request $request)
    {
        $dealer->update([
            'is_archived' => true,
            'is_active' => false,
            'archived_by' => (string) optional($request->user())->email,
            'archived_at' => now(),
        ]);

        return response()->json($dealer->fresh());
    }

    public function unarchive(Dealer $dealer)
    {
        $dealer->update([
            'is_archived' => false,
            'archived_by' => null,
            'archived_at' => null,
        ]);

        return response()->json($dealer->fresh());
    }

    public function destroy(Dealer $dealer)
    {
        $code = (string) $dealer->code;
        $assignmentRefs = StudentAssignment::query()->where('dealer_id', $code)->count();
        $revenueRefs = DealerStudentRevenue::query()->where('dealer_id', $code)->count();
        $totalRefs = $assignmentRefs + $revenueRefs;

        if ($totalRefs > 0) {
            return ApiResponse::error(
                ApiResponse::ERR_DEALER_CONFLICT,
                "Dealer silinemez. Referans var (assignment: {$assignmentRefs}, revenue: {$revenueRefs}). Arsivleyin."
            );
        }

        $dealer->delete();
        return ApiResponse::ok();
    }

    private function buildPrefix(string $dealerTypeCode): string
    {
        $clean = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($dealerTypeCode)));
        if ($clean === null || $clean === '') {
            return 'DLR';
        }

        $prefix = substr($clean, 0, 3);
        if (strlen($prefix) < 3) {
            $prefix = str_pad($prefix, 3, 'X');
        }

        return $prefix;
    }

    private function generateDealerCode(string $dealerTypeCode): array
    {
        $prefix = $this->buildPrefix($dealerTypeCode);
        $year = now()->format('y');
        $month = now()->format('m');
        $base = "{$prefix}-{$year}-{$month}";
        $nextSequence = ((int) Dealer::query()->max('internal_sequence')) + 1;

        do {
            $token = strtoupper(substr(hash('crc32b', "{$base}-{$nextSequence}"), 0, 4));
            $candidate = "{$base}-{$token}";
            if (!Dealer::query()->where('code', $candidate)->exists()) {
                break;
            }
            $nextSequence++;
        } while (Dealer::query()->where('code', $candidate)->exists());

        return [
            'code' => $candidate,
            'internal_sequence' => $nextSequence,
        ];
    }
}
