<?php

namespace App\Http\Controllers;

use App\Models\ManagerRequest;
use App\Models\MarketingTask;
use App\Models\User;
use App\Services\TaskAutomationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ManagerRequestController extends Controller
{
    public function __construct(private readonly TaskAutomationService $taskAutomationService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $role = (string) ($user->role ?? '');
        $isManager = $role === User::ROLE_MANAGER;
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;

        $filters = [
            'status' => trim((string) $request->query('status', '')),
            'priority' => trim((string) $request->query('priority', '')),
            'type' => trim((string) $request->query('type', '')),
        ];

        $rows = ManagerRequest::query()
            ->with(['requester:id,name,email,role', 'manager:id,name,email,role'])
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->when(!$isManager, function ($q) use ($user): void {
                $q->where(function ($sub) use ($user): void {
                    $sub->where('requester_user_id', (int) $user->id)
                        ->orWhere('target_manager_user_id', (int) $user->id);
                });
            })
            ->when($filters['status'] !== '', fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['priority'] !== '', fn ($q) => $q->where('priority', $filters['priority']))
            ->when($filters['type'] !== '', fn ($q) => $q->where('request_type', $filters['type']))
            ->latest()
            ->limit(200)
            ->get();

        $managers = User::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->where('role', User::ROLE_MANAGER)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $requestIds = $rows->pluck('id')
            ->map(static fn ($id) => (string) $id)
            ->filter(static fn (string $id) => $id !== '')
            ->values();

        $taskMap = MarketingTask::query()
            ->select(['id', 'source_id', 'status', 'department'])
            ->where('source_type', 'manager_request_created')
            ->whereIn('source_id', $requestIds)
            ->get()
            ->keyBy('source_id');

        return view('manager.requests.index', [
            'pageTitle' => 'Manager Request Center',
            'rows' => $rows,
            'filters' => $filters,
            'isManager' => $isManager,
            'taskMap' => $taskMap,
            'statusOptions' => $this->statusOptions(),
            'priorityOptions' => $this->priorityOptions(),
            'typeOptions' => $this->typeOptions(),
            'managers' => $managers,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $role = (string) optional($request->user())->role;
        if ($role === User::ROLE_MANAGER) {
            return redirect('/manager/requests')->withErrors([
                'request' => 'Manager bu ekrandan talep acmaz; gorev atamasi icin Task Board kullanin.',
            ]);
        }

        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;

        $data = $request->validate([
            'request_type' => ['required', 'string', 'max:64'],
            'subject' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'due_date' => ['nullable', 'date'],
            'target_manager_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($q) use ($companyId) {
                    $q->where('role', User::ROLE_MANAGER)
                        ->where('is_active', true);
                    if ($companyId > 0) {
                        $q->where('company_id', $companyId);
                    }
                }),
            ],
        ]);

        $targetManagerId = (int) ($data['target_manager_id'] ?? 0);
        if ($targetManagerId <= 0) {
            $targetManagerId = (int) User::query()
                ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
                ->where('role', User::ROLE_MANAGER)
                ->where('is_active', true)
                ->orderBy('id')
                ->value('id');
        }
        if ($targetManagerId <= 0) {
            return redirect('/manager/requests')->withErrors([
                'target_manager_id' => 'Aktif manager bulunamadi. Once manager kullanicisi tanimlayin.',
            ]);
        }

        $row = ManagerRequest::query()->create([
            'company_id' => $companyId > 0 ? $companyId : null,
            'requester_user_id' => (int) optional($request->user())->id ?: null,
            'target_manager_user_id' => $targetManagerId > 0 ? $targetManagerId : null,
            'request_type' => trim((string) $data['request_type']),
            'subject' => trim((string) $data['subject']),
            'description' => trim((string) ($data['description'] ?? '')),
            'status' => 'open',
            'priority' => (string) $data['priority'],
            'due_date' => $data['due_date'] ?? null,
            'requested_at' => now(),
            'source_type' => 'manual',
            'source_id' => null,
        ]);

        $this->taskAutomationService->ensureManagerRequestTask($row);

        return redirect('/manager/requests')->with('status', "Request #{$row->id} olusturuldu.");
    }

    public function updateStatus(Request $request, ManagerRequest $managerRequest): RedirectResponse
    {
        $role = (string) optional($request->user())->role;
        if ($role !== User::ROLE_MANAGER) {
            return redirect('/manager/requests')->withErrors([
                'request' => 'Talep durumu sadece manager tarafindan guncellenebilir.',
            ]);
        }
        $currentCompanyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        if ($currentCompanyId > 0 && (int) ($managerRequest->company_id ?? 0) !== $currentCompanyId) {
            abort(404);
        }

        $data = $request->validate([
            'status' => ['required', 'in:open,in_review,approved,rejected,done'],
            'decision_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $status = (string) $data['status'];
        $note = trim((string) ($data['decision_note'] ?? ''));
        if (in_array($status, ['approved', 'rejected', 'done'], true) && $note === '') {
            return redirect('/manager/requests')->withErrors([
                'decision_note' => 'Onay/ret/tamamlandi durumunda karar notu zorunludur.',
            ]);
        }

        $managerRequest->update([
            'status' => $status,
            'decision_note' => $note !== '' ? $note : null,
            'responded_at' => in_array($status, ['in_review', 'approved', 'rejected', 'done'], true)
                ? ($managerRequest->responded_at ?: now())
                : null,
            'resolved_at' => in_array($status, ['approved', 'rejected', 'done'], true) ? now() : null,
        ]);

        if (in_array($status, ['approved', 'rejected', 'done'], true)) {
            $this->taskAutomationService->markTasksDoneBySource('manager_request_created', (string) $managerRequest->id);
        } else {
            $this->taskAutomationService->ensureManagerRequestTask($managerRequest);
        }

        return redirect('/manager/requests')->with('status', "Request #{$managerRequest->id} guncellendi.");
    }

    /**
     * @return array<string,string>
     */
    private function statusOptions(): array
    {
        return [
            'open' => 'Acik',
            'in_review' => 'Incelemede',
            'approved' => 'Onaylandi',
            'rejected' => 'Reddedildi',
            'done' => 'Tamamlandi',
        ];
    }

    /**
     * @return array<string,string>
     */
    private function priorityOptions(): array
    {
        return [
            'low' => 'Dusuk',
            'normal' => 'Normal',
            'high' => 'Yuksek',
            'urgent' => 'Acil',
        ];
    }

    /**
     * @return array<string,string>
     */
    private function typeOptions(): array
    {
        return [
            'general' => 'Genel',
            'approval' => 'Onay Talebi',
            'advisory' => 'Danismanlik Talebi',
            'finance' => 'Finans Talebi',
            'operations' => 'Operasyon Talebi',
            'system' => 'Sistem Talebi',
            'marketing' => 'Marketing Talebi',
        ];
    }
}
