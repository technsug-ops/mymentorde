<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dealer;
use App\Models\StudentAssignment;
use App\Models\StudentType;
use App\Models\User;
use App\Support\SystematicInput;
use App\Services\TaskAutomationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentAssignmentController extends Controller
{
    public function generateStudentId(Request $request)
    {
        $data = $request->validate([
            'student_type' => ['required', 'string', 'max:32'],
        ]);

        return response()->json([
            'student_id' => $this->generateStudentIdentityFromType((string) $data['student_type'])['student_id'],
        ]);
    }

    public function branches()
    {
        return StudentAssignment::query()
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->select('branch')
            ->distinct()
            ->orderBy('branch')
            ->pluck('branch')
            ->values();
    }

    public function index(Request $request)
    {
        $studentId = trim((string) $request->query('student_id', ''));
        $seniorEmail = (string) $request->query('senior_email', '');
        $branch = trim((string) $request->query('branch', ''));
        $dealerId = trim((string) $request->query('dealer_id', ''));
        $archived = $request->query('archived');

        return StudentAssignment::query()
            ->when($studentId !== '', fn ($q) => $q->where('student_id', 'like', "%{$studentId}%"))
            ->when($seniorEmail !== '', fn ($q) => $q->where('senior_email', $seniorEmail))
            ->when($branch !== '', fn ($q) => $q->where('branch', 'like', "%{$branch}%"))
            ->when($dealerId !== '', fn ($q) => $q->where('dealer_id', 'like', "%{$dealerId}%"))
            ->when($archived !== null, fn ($q) => $q->where('is_archived', filter_var($archived, FILTER_VALIDATE_BOOLEAN)))
            ->latest()
            ->limit(300)
            ->get();
    }

    public function upsert(Request $request, TaskAutomationService $taskAutomation)
    {
        $data = $request->validate([
            'student_id' => ['nullable', 'string', 'max:64'],
            'senior_email' => [
                'nullable',
                'email',
                Rule::exists('users', 'email')->where(fn ($q) => $q->whereIn('role', ['senior', 'mentor'])->where('is_active', true)),
            ],
            'branch' => ['nullable', 'string', 'max:64'],
            'risk_level' => ['nullable', 'string', 'max:16'],
            'payment_status' => ['nullable', 'string', 'max:32'],
            'dealer_id' => ['nullable', 'string', 'max:64'],
            'student_type' => ['nullable', 'string', 'max:32'],
            'force_unassign' => ['nullable', 'boolean'],
        ]);

        $studentId = trim((string) ($data['student_id'] ?? ''));
        $generatedInternalSequence = null;
        if ($studentId === '') {
            $studentType = trim((string) ($data['student_type'] ?? ''));
            if ($studentType === '') {
                abort(422, 'student_id bos ise student_type zorunlu.');
            }
            $generated = $this->generateStudentIdentityFromType($studentType);
            $studentId = $generated['student_id'];
            $generatedInternalSequence = $generated['internal_sequence'];
            $data['student_id'] = $studentId;
        } else {
            $data['student_id'] = SystematicInput::upperId($studentId, 'student_id');
        }

        $existing = StudentAssignment::query()
            ->where('student_id', (string) $data['student_id'])
            ->first();

        $forceUnassign = (bool) ($data['force_unassign'] ?? false);

        if ($forceUnassign) {
            $data['senior_email'] = null;
        } elseif (empty($data['senior_email'])) {
            $data['senior_email'] = (!$existing || $existing->is_archived)
                ? $this->pickAutoSeniorEmail()
                : $existing->senior_email;
        }

        if (!empty($data['dealer_id'])) {
            $requestedDealer = SystematicInput::upperId((string) $data['dealer_id'], 'dealer_id');
            $data['dealer_id'] = $requestedDealer;
            $existingDealer = $existing ? (string) ($existing->dealer_id ?? '') : '';
            $dealerExists = Dealer::query()
                ->where('code', $requestedDealer)
                ->where('is_active', true)
                ->exists();

            $sameAsExisting = $existingDealer !== '' && $existingDealer === $requestedDealer;
            if (!$dealerExists && !$sameAsExisting) {
                abort(422, 'Dealer code gecersiz veya pasif.');
            }
        }

        if (!empty($data['senior_email'])) {
            $senior = User::query()
                ->whereIn('role', ['senior', 'mentor'])
                ->where('email', (string) $data['senior_email'])
                ->first();

            if ($senior && !$senior->is_active) {
                abort(422, "Senior pasif: {$senior->email}. Bu seniora atama yapilamaz.");
            }

            if ($senior && $senior->max_capacity) {
                $currentActiveCount = StudentAssignment::query()
                    ->where('senior_email', (string) $data['senior_email'])
                    ->where('is_archived', false)
                    ->count();

                $beforeCountsForSenior = $existing
                    && !$existing->is_archived
                    && $existing->senior_email === (string) $data['senior_email'];
                $afterCountsForSenior = !$existing || !$existing->is_archived;
                $neededSlots = (!$beforeCountsForSenior && $afterCountsForSenior) ? 1 : 0;

                if (($currentActiveCount + $neededSlots) > (int) $senior->max_capacity) {
                    abort(422, "Kapasite dolu: {$senior->email} icin aktif {$currentActiveCount}/{$senior->max_capacity}. Atama yapilamadi.");
                }
            }
        }

        $row = StudentAssignment::query()->updateOrCreate(
            ['student_id' => (string) $data['student_id']],
            [
                'senior_email' => $data['senior_email'] ?? null,
                'internal_sequence' => $generatedInternalSequence ?? ($existing->internal_sequence ?? null),
                'branch' => $data['branch'] ?? ($existing->branch ?? null),
                'risk_level' => $data['risk_level'] ?? ($existing->risk_level ?? 'normal'),
                'payment_status' => $data['payment_status'] ?? ($existing->payment_status ?? 'ok'),
                'dealer_id' => $data['dealer_id'] ?? ($existing->dealer_id ?? null),
                'student_type' => $data['student_type'] ?? ($existing->student_type ?? null),
            ]
        );

        $taskAutomation->ensureStudentAssignmentTask($row);

        return response()->json($row->fresh());
    }

    public function bulkAssign(Request $request, TaskAutomationService $taskAutomation)
    {
        $data = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['string', 'max:64'],
            'senior_email' => [
                'required',
                'email',
                Rule::exists('users', 'email')->where(fn ($q) => $q->whereIn('role', ['senior', 'mentor'])->where('is_active', true)),
            ],
            'branch' => ['nullable', 'string', 'max:64'],
            'dealer_id' => ['nullable', 'string', 'max:64'],
        ]);

        $senior = User::query()
            ->whereIn('role', ['senior', 'mentor'])
            ->where('email', (string) $data['senior_email'])
            ->first();
        if ($senior && !$senior->is_active) {
            abort(422, "Senior pasif: {$senior->email}. Toplu atama yapilamaz.");
        }
        if ($senior && $senior->max_capacity) {
            $studentIds = collect($data['student_ids'])
                ->map(fn ($v) => (string) $v)
                ->filter()
                ->unique()
                ->values();

            $existingRows = StudentAssignment::query()
                ->whereIn('student_id', $studentIds)
                ->get(['student_id', 'senior_email', 'is_archived'])
                ->keyBy('student_id');

            $currentActiveCount = StudentAssignment::query()
                ->where('senior_email', (string) $data['senior_email'])
                ->where('is_archived', false)
                ->count();

            $neededSlots = 0;
            foreach ($studentIds as $studentId) {
                $existing = $existingRows->get($studentId);
                $beforeCountsForSenior = $existing
                    && !$existing->is_archived
                    && $existing->senior_email === (string) $data['senior_email'];
                $afterCountsForSenior = !$existing || !$existing->is_archived;

                if (!$beforeCountsForSenior && $afterCountsForSenior) {
                    $neededSlots++;
                }
            }

            if (($currentActiveCount + $neededSlots) > (int) $senior->max_capacity) {
                abort(422, "Kapasite dolu: {$senior->email} icin aktif {$currentActiveCount}/{$senior->max_capacity}. Toplu atama yapilamadi.");
            }
        }

        if (!empty($data['dealer_id'])) {
            $data['dealer_id'] = SystematicInput::upperId((string) $data['dealer_id'], 'dealer_id');
            $dealerExists = Dealer::query()
                ->where('code', (string) $data['dealer_id'])
                ->where('is_active', true)
                ->exists();
            if (!$dealerExists) {
                abort(422, 'Dealer code gecersiz veya pasif.');
            }
        }

        $affected = 0;
        foreach ($data['student_ids'] as $studentId) {
            $studentId = SystematicInput::upperId((string) $studentId, 'student_ids');
            $row = StudentAssignment::query()->updateOrCreate(
                ['student_id' => (string) $studentId],
                [
                    'senior_email' => (string) $data['senior_email'],
                    'branch' => $data['branch'] ?? null,
                    'risk_level' => 'normal',
                    'payment_status' => 'ok',
                    'dealer_id' => $data['dealer_id'] ?? null,
                ]
            );
            $taskAutomation->ensureStudentAssignmentTask($row);
            $affected++;
        }

        return response()->json(['affected' => $affected]);
    }

    public function autoAssign(Request $request, TaskAutomationService $taskAutomation)
    {
        $data = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['string', 'max:64'],
            'branch' => ['nullable', 'string', 'max:64'],
            'dealer_id' => ['nullable', 'string', 'max:64'],
        ]);

        if (!empty($data['dealer_id'])) {
            $data['dealer_id'] = SystematicInput::upperId((string) $data['dealer_id'], 'dealer_id');
            $dealerExists = Dealer::query()
                ->where('code', (string) $data['dealer_id'])
                ->where('is_active', true)
                ->exists();
            if (!$dealerExists) {
                abort(422, 'Dealer code gecersiz veya pasif.');
            }
        }

        $studentIds = collect($data['student_ids'])
            ->map(fn ($v) => SystematicInput::upperId((string) $v, 'student_ids'))
            ->filter()
            ->unique()
            ->values();

        $pool = $this->buildAutoAssignPool();
        if ($pool->isEmpty()) {
            abort(422, 'Uygun otomatik senior bulunamadi. Aktif + oto atama acik senior gerekli.');
        }

        $affected = 0;
        $alreadyAssigned = [];
        $newlyAssigned = [];
        $unassigned = [];
        $unassignedDetails = [];

        foreach ($studentIds as $studentId) {
            $existing = StudentAssignment::query()
                ->where('student_id', $studentId)
                ->first();

            $selectedEmail = null;
            if ($existing && !$existing->is_archived && !empty($existing->senior_email)) {
                $existingPoolIndex = $pool->search(fn ($r) => $r['email'] === $existing->senior_email);
                if ($existingPoolIndex !== false) {
                    $selectedEmail = $existing->senior_email;
                }
            }

            if (!$selectedEmail) {
                $selectedEmail = $this->pickAutoSeniorEmail($pool);
            }

            if (!$selectedEmail) {
                $unassigned[] = $studentId;
                $unassignedDetails[] = [
                    'student_id' => $studentId,
                    'reason' => $this->getAutoAssignUnavailableReason($pool),
                ];
                continue;
            }

            $isAlreadyAssigned = $existing
                && !$existing->is_archived
                && $existing->senior_email === $selectedEmail;

            $row = StudentAssignment::query()->updateOrCreate(
                ['student_id' => $studentId],
                [
                    'senior_email' => $selectedEmail,
                    'branch' => $data['branch'] ?? null,
                    'risk_level' => 'normal',
                    'payment_status' => 'ok',
                    'dealer_id' => $data['dealer_id'] ?? null,
                ]
            );
            $taskAutomation->ensureStudentAssignmentTask($row);

            $poolIndex = $pool->search(fn ($r) => $r['email'] === $selectedEmail);
            if ($poolIndex !== false) {
                $row = $pool[$poolIndex];
                $row['load'] = (int) $row['load'] + 1;
                $pool[$poolIndex] = $row;
            }

            $affected++;
            if ($isAlreadyAssigned) {
                $alreadyAssigned[] = $studentId;
            } else {
                $newlyAssigned[] = $studentId;
            }
        }

        return response()->json([
            'affected' => $affected,
            'newly_assigned' => $newlyAssigned,
            'already_assigned' => $alreadyAssigned,
            'unassigned' => $unassigned,
            'unassigned_details' => $unassignedDetails,
        ]);
    }

    public function archive(StudentAssignment $studentAssignment, Request $request)
    {
        $studentAssignment->update([
            'is_archived' => true,
            'archived_by' => (string) optional($request->user())->email,
            'archived_at' => now(),
        ]);

        return response()->json($studentAssignment->fresh());
    }

    public function unarchive(StudentAssignment $studentAssignment)
    {
        $studentAssignment->update([
            'is_archived' => false,
            'archived_by' => null,
            'archived_at' => null,
        ]);

        return response()->json($studentAssignment->fresh());
    }

    private function buildAutoAssignPool()
    {
        $seniors = User::query()
            ->whereIn('role', ['senior', 'mentor'])
            ->where('is_active', true)
            ->where('auto_assign_enabled', true)
            ->orderBy('id')
            ->get(['email', 'max_capacity']);

        // Backward-compatible fallback:
        // In some legacy/dev data sets auto_assign flag may be null/false by default.
        // If strict pool is empty, fall back to active senior/mentor users.
        if ($seniors->isEmpty()) {
            $seniors = User::query()
                ->whereIn('role', ['senior', 'mentor'])
                ->where('is_active', true)
                ->orderBy('id')
                ->get(['email', 'max_capacity']);
        }

        $emails = $seniors->pluck('email')->filter()->values();
        $activeCounts = StudentAssignment::query()
            ->whereIn('senior_email', $emails)
            ->where('is_archived', false)
            ->selectRaw('senior_email, COUNT(*) as total')
            ->groupBy('senior_email')
            ->pluck('total', 'senior_email');

        return $seniors->map(function (User $s) use ($activeCounts) {
            return [
                'email' => (string) $s->email,
                'max_capacity' => $s->max_capacity ? (int) $s->max_capacity : null,
                'load' => (int) ($activeCounts[$s->email] ?? 0),
            ];
        })->values();
    }

    private function pickAutoSeniorEmail($pool = null): ?string
    {
        $pool = $pool ?: $this->buildAutoAssignPool();
        $eligible = collect($pool)
            ->filter(function (array $r) {
                if (empty($r['email'])) {
                    return false;
                }
                if (empty($r['max_capacity'])) {
                    return true;
                }

                return (int) $r['load'] < (int) $r['max_capacity'];
            })
            ->sortBy([
                fn ($a, $b) => ((int) $a['load']) <=> ((int) $b['load']),
                fn ($a, $b) => strcmp((string) $a['email'], (string) $b['email']),
            ])
            ->values();

        return $eligible->isEmpty() ? null : (string) $eligible[0]['email'];
    }

    private function getAutoAssignUnavailableReason($pool): string
    {
        $rows = collect($pool);
        if ($rows->isEmpty()) {
            return 'Aktif ve oto-atama acik senior bulunamadi.';
        }

        $hasUnlimited = $rows->contains(function (array $r) {
            return empty($r['max_capacity']);
        });
        if ($hasUnlimited) {
            return 'Atama yapilamadi. Sistem tekrar deneyin.';
        }

        return 'Tum uygun seniorlarin kapasitesi dolu.';
    }

    private function generateStudentIdentityFromType(string $studentTypeCode): array
    {
        $input = strtoupper(trim($studentTypeCode));
        $studentType = StudentType::query()
            ->where('code', strtolower($input))
            ->orWhere('code', $input)
            ->orWhere('id_prefix', $input)
            ->first();
        if (!$studentType) {
            abort(422, 'Gecersiz student_type.');
        }

        $prefix = SystematicInput::idPrefix((string) $studentType->id_prefix, 'id_prefix');
        $year = now()->format('y');
        $month = now()->format('m');
        $base = "{$prefix}-{$year}-{$month}";
        $nextSequence = ((int) StudentAssignment::query()->max('internal_sequence')) + 1;

        do {
            $token = strtoupper(substr(hash('crc32b', "{$base}-{$nextSequence}"), 0, 4));
            $candidate = "{$base}-{$token}";
            if (!StudentAssignment::query()->where('student_id', $candidate)->exists()) {
                break;
            }
            $nextSequence++;
        } while (StudentAssignment::query()->where('student_id', $candidate)->exists());

        return [
            'student_id' => $candidate,
            'internal_sequence' => $nextSequence,
        ];
    }
}
