<?php

namespace App\Http\Controllers;

use App\Models\GuestApplication;
use App\Models\GuestTicket;
use App\Models\DmMessage;
use App\Models\DmThread;
use App\Models\MarketingTask;
use App\Models\User;
use App\Services\EventLogService;
use App\Services\TaskAutomationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TicketCenterController extends Controller
{
    public function __construct(
        private readonly TaskAutomationService $taskAutomationService,
        private readonly EventLogService $eventLogService
    )
    {
    }

    public function index(Request $request, ?string $department = null)
    {
        $this->authorizeView($request);
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $roleScopedDepartment = $this->resolveScopedDepartmentForRole((string) optional($request->user())->role);

        $routeDepartment = trim((string) $department);
        if ($routeDepartment !== '' && !array_key_exists($routeDepartment, $this->departmentOptions())) {
            abort(404);
        }
        if ($roleScopedDepartment !== null && $routeDepartment !== '' && $routeDepartment !== $roleScopedDepartment) {
            return redirect('/tickets-center/' . $roleScopedDepartment);
        }

        $filters = [
            'status' => trim((string) $request->query('status', '')),
            'department' => $routeDepartment !== ''
                ? $routeDepartment
                : trim((string) $request->query('department', '')),
            'priority' => trim((string) $request->query('priority', '')),
            'q' => trim((string) $request->query('q', '')),
        ];
        if ($roleScopedDepartment !== null) {
            $filters['department'] = $roleScopedDepartment;
        }

        $rows = GuestTicket::query()
            ->with([
                'guestApplication:id,first_name,last_name,email',
                'assignedUser:id,name,email,role',
                'replies' => fn ($q) => $q->latest()->limit(3),
            ])
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->when($filters['status'] !== '', fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['department'] !== '', fn ($q) => $q->where('department', $filters['department']))
            ->when($filters['priority'] !== '', fn ($q) => $q->where('priority', $filters['priority']))
            ->when($filters['q'] !== '', function ($q) use ($filters): void {
                $needle = '%'.$filters['q'].'%';
                $q->where(function ($sub) use ($needle): void {
                    $sub->where('subject', 'like', $needle)
                        ->orWhere('message', 'like', $needle)
                        ->orWhere('created_by_email', 'like', $needle)
                        ->orWhereHas('guestApplication', function ($gq) use ($needle): void {
                            $gq->where('email', 'like', $needle)
                                ->orWhere('first_name', 'like', $needle)
                                ->orWhere('last_name', 'like', $needle);
                        });
                });
            })
            ->orderByRaw("CASE WHEN status = 'closed' THEN 1 ELSE 0 END ASC")
            ->orderByDesc('last_replied_at')
            ->orderByDesc('id')
            ->limit(250)
            ->get();
        $rows = $rows->map(function (GuestTicket $row) {
            $createdAt = $row->created_at ? Carbon::parse($row->created_at) : null;
            $firstResponseAt = $row->first_response_at ? Carbon::parse($row->first_response_at) : null;
            $closedAt = $row->closed_at ? Carbon::parse($row->closed_at) : null;
            $row->sla_first_response_hours = ($createdAt && $firstResponseAt)
                ? round($createdAt->diffInMinutes($firstResponseAt) / 60, 1)
                : null;
            $row->sla_resolution_hours = ($createdAt && $closedAt)
                ? round($createdAt->diffInMinutes($closedAt) / 60, 1)
                : null;
            return $row;
        });

        return view('tickets.center', [
            'pageTitle' => 'Ticket Center',
            'rows' => $rows,
            'filters' => $filters,
            'statusOptions' => $this->statusOptions(),
            'departmentOptions' => $this->departmentOptions(),
            'priorityOptions' => $this->priorityOptions(),
            'users' => $this->users(),
            'routeDepartment' => $routeDepartment,
            'roleScopedDepartment' => $roleScopedDepartment,
        ]);
    }

    public function routeTicket(Request $request, GuestTicket $ticket): RedirectResponse
    {
        $this->authorizeManage($request);
        $currentCompanyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        if ($currentCompanyId > 0 && (int) ($ticket->company_id ?? 0) !== $currentCompanyId) {
            abort(404);
        }
        $roleScopedDepartment = $this->resolveScopedDepartmentForRole((string) optional($request->user())->role);
        if ($roleScopedDepartment !== null && (string) ($ticket->department ?? 'operations') !== $roleScopedDepartment) {
            return $this->redirectBackToCenter($request)->withErrors(['route' => 'Bu ticket üzerinde işlem yetkiniz yok.']);
        }

        $data = $request->validate([
            'department' => ['required', 'in:operations,finance,advisory,marketing,system'],
            'assignee_email' => ['nullable', 'email'],
            'status' => ['nullable', 'in:open,in_progress,waiting_response,closed'],
            'sla_hours' => ['nullable', 'integer', 'in:4,8,24,48,72,168'],
        ]);

        $result = $this->routeSingleTicket(
            ticket: $ticket,
            department: $roleScopedDepartment ?? (string) $data['department'],
            assigneeEmail: (string) ($data['assignee_email'] ?? ''),
            status: (string) ($data['status'] ?? $ticket->status ?? 'open'),
            autoAssign: trim((string) $request->input('auto_assign', '')) === '1',
            actorEmail: (string) optional($request->user())->email,
            slaHours: isset($data['sla_hours']) ? (int) $data['sla_hours'] : null,
        );
        if ($result['error'] !== null) {
            return $this->redirectBackToCenter($request)->withErrors(['assignee_email' => $result['error']]);
        }

        return $this->redirectBackToCenter($request)->with('status', "Ticket #{$ticket->id} yonlendirildi.");
    }

    public function bulkRoute(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);
        $currentCompanyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $roleScopedDepartment = $this->resolveScopedDepartmentForRole((string) optional($request->user())->role);

        $data = $request->validate([
            'ticket_ids' => ['required', 'array', 'min:1'],
            'ticket_ids.*' => ['integer'],
            'department' => ['required', 'in:operations,finance,advisory,marketing,system'],
            'assignee_email' => ['nullable', 'email'],
            'status' => ['nullable', 'in:open,in_progress,waiting_response,closed'],
            'auto_assign' => ['nullable', 'boolean'],
            'sla_hours' => ['nullable', 'integer', 'in:4,8,24,48,72,168'],
        ]);

        $ids = collect((array) $data['ticket_ids'])->map(fn ($v) => (int) $v)->filter(fn ($v) => $v > 0)->values();
        if ($ids->isEmpty()) {
            return $this->redirectBackToCenter($request)->withErrors(['ticket_ids' => 'Toplu yonlendirme icin ticket secin.']);
        }

        $tickets = GuestTicket::query()
            ->whereIn('id', $ids->all())
            ->when($currentCompanyId > 0, fn ($q) => $q->where('company_id', $currentCompanyId))
            ->get();
        $updated = 0;
        $errors = [];
        foreach ($tickets as $ticket) {
            if ($roleScopedDepartment !== null && (string) ($ticket->department ?? 'operations') !== $roleScopedDepartment) {
                $errors[] = "Ticket #{$ticket->id}: departman disi kayit.";
                continue;
            }
            $result = $this->routeSingleTicket(
                ticket: $ticket,
                department: $roleScopedDepartment ?? (string) $data['department'],
                assigneeEmail: (string) ($data['assignee_email'] ?? ''),
                status: (string) ($data['status'] ?? $ticket->status ?? 'open'),
                autoAssign: (bool) ($data['auto_assign'] ?? false),
                actorEmail: (string) optional($request->user())->email,
                slaHours: isset($data['sla_hours']) ? (int) $data['sla_hours'] : null,
            );
            if ($result['error'] !== null) {
                $errors[] = "Ticket #{$ticket->id}: ".$result['error'];
                continue;
            }
            $updated++;
        }

        if (!empty($errors)) {
            return $this->redirectBackToCenter($request)
                ->withErrors(['bulk_route' => implode(' | ', $errors)])
                ->with('status', "{$updated} ticket yonlendirildi.");
        }

        return $this->redirectBackToCenter($request)->with('status', "{$updated} ticket yonlendirildi.");
    }

    public function bulkStatus(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);
        $currentCompanyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $roleScopedDepartment = $this->resolveScopedDepartmentForRole((string) optional($request->user())->role);

        $data = $request->validate([
            'ticket_ids' => ['required', 'array', 'min:1'],
            'ticket_ids.*' => ['integer'],
            'status' => ['required', 'in:open,closed'],
        ]);

        $ids = collect((array) $data['ticket_ids'])->map(fn ($v) => (int) $v)->filter(fn ($v) => $v > 0)->values();
        if ($ids->isEmpty()) {
            return $this->redirectBackToCenter($request)->withErrors(['ticket_ids' => 'Toplu durum degisikligi icin ticket secin.']);
        }

        $tickets = GuestTicket::query()
            ->whereIn('id', $ids->all())
            ->when($currentCompanyId > 0, fn ($q) => $q->where('company_id', $currentCompanyId))
            ->get();
        $updated = 0;
        foreach ($tickets as $ticket) {
            if ($roleScopedDepartment !== null && (string) ($ticket->department ?? 'operations') !== $roleScopedDepartment) {
                continue;
            }
            $status = (string) $data['status'];
            $ticket->forceFill([
                'status' => $status,
                'last_replied_at' => now(),
                'closed_at' => $status === 'closed' ? now() : null,
            ])->save();

            if ($status === 'closed') {
                $this->taskAutomationService->markTasksDoneBySource('guest_ticket_opened', (string) $ticket->id);
            } else {
                $this->taskAutomationService->reopenTasksBySource('guest_ticket_opened', (string) $ticket->id);
            }
            $updated++;
        }

        return $this->redirectBackToCenter($request)->with('status', "{$updated} ticket durumu toplu guncellendi.");
    }

    public function convertToDm(Request $request, GuestTicket $ticket): RedirectResponse
    {
        $this->authorizeManage($request);
        $currentCompanyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        if ($currentCompanyId > 0 && (int) ($ticket->company_id ?? 0) !== $currentCompanyId) {
            abort(404);
        }

        $guest = GuestApplication::query()->find((int) ($ticket->guest_application_id ?? 0));
        if (!$guest) {
            return $this->redirectBackToCenter($request)->withErrors(['convert_dm' => 'Guest kaydi bulunamadi.']);
        }

        $thread = DmThread::query()
            ->where('company_id', (int) ($ticket->company_id ?: 0))
            ->where('thread_type', 'guest')
            ->where('guest_application_id', (int) $guest->id)
            ->first();

        if (!$thread) {
            $thread = DmThread::query()->create([
                'company_id' => (int) ($ticket->company_id ?: 0),
                'thread_type' => 'guest',
                'guest_application_id' => (int) $guest->id,
                'advisor_user_id' => (int) ($ticket->assigned_user_id ?: 0) ?: null,
                'initiated_by_user_id' => (int) optional($request->user())->id ?: null,
                'status' => 'open',
                'department' => (string) ($ticket->department ?: 'operations'),
                'sla_hours' => 24,
            ]);
        } elseif ((int) ($thread->advisor_user_id ?? 0) <= 0 && (int) ($ticket->assigned_user_id ?? 0) > 0) {
            $thread->forceFill(['advisor_user_id' => (int) $ticket->assigned_user_id])->save();
        }

        $already = DmMessage::query()
            ->where('thread_id', (int) $thread->id)
            ->where('sender_role', 'guest')
            ->where('message', 'like', '%[Ticket#'.(int) $ticket->id.'%')
            ->exists();

        if (!$already) {
            $text = "[Ticket#{$ticket->id} | {$ticket->subject}]\n".trim((string) $ticket->message);
            DmMessage::query()->create([
                'thread_id' => (int) $thread->id,
                'sender_user_id' => null,
                'sender_role' => 'guest',
                'message' => $text,
                'is_read_by_advisor' => false,
                'is_read_by_participant' => true,
            ]);

            $thread->forceFill([
                'status' => 'open',
                'last_message_preview' => Str::limit($text, 220, '...'),
                'last_message_at' => now(),
                'last_participant_message_at' => now(),
                'next_response_due_at' => now()->addHours((int) ($thread->sla_hours ?: 24)),
            ])->save();
        }

        return redirect()->route('messages.center', ['thread_id' => (int) $thread->id])
            ->with('status', "Ticket #{$ticket->id} mesaj thread'ine tasindi.");
    }

    private function authorizeView(Request $request): void
    {
        $user = $request->user();
        $role = (string) ($user->role ?? '');
        $allowedRoles = [
            User::ROLE_MANAGER,
            User::ROLE_SYSTEM_ADMIN,
            User::ROLE_SYSTEM_STAFF,
            User::ROLE_SENIOR,
            User::ROLE_MENTOR,
            User::ROLE_OPERATIONS_ADMIN,
            User::ROLE_OPERATIONS_STAFF,
            User::ROLE_FINANCE_ADMIN,
            User::ROLE_FINANCE_STAFF,
        ];
        $hasPermission = method_exists($user, 'hasPermissionCode') && $user->hasPermissionCode('ticket.center.view');
        if (!in_array($role, $allowedRoles, true) && !$hasPermission) {
            abort(403, 'Ticket Center goruntuleme yetkiniz yok.');
        }
    }

    private function authorizeManage(Request $request): void
    {
        $user = $request->user();
        $isManager = (string) ($user->role ?? '') === User::ROLE_MANAGER;
        $hasPermission = method_exists($user, 'hasPermissionCode') && $user->hasPermissionCode('ticket.center.route');
        if (!$isManager && !$hasPermission) {
            abort(403, 'Ticket yonlendirme yetkiniz yok.');
        }
    }

    private function users()
    {
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        return User::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);
    }

    private function redirectBackToCenter(Request $request): RedirectResponse
    {
        $currentDepartment = trim((string) $request->input('current_department', ''));
        if ($currentDepartment !== '' && array_key_exists($currentDepartment, $this->departmentOptions())) {
            return redirect('/tickets-center/'.$currentDepartment);
        }
        return redirect('/tickets-center');
    }

    /**
     * @return array{error:?string}
     */
    private function routeSingleTicket(
        GuestTicket $ticket,
        string $department,
        string $assigneeEmail,
        string $status,
        bool $autoAssign,
        string $actorEmail,
        ?int $slaHours = null
    ): array {
        $department = trim($department);
        $assigneeEmail = strtolower(trim($assigneeEmail));
        $status = trim($status);
        $companyId = (int) ($ticket->company_id ?: (app()->bound('current_company_id') ? (int) app('current_company_id') : 0));

        $assigneeUserId = null;
        if ($assigneeEmail !== '') {
            $assignee = User::query()
                ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
                ->where('email', strtolower($assigneeEmail))
                ->where('is_active', true)
                ->first();
            if (!$assignee) {
                return ['error' => 'Bu email ile aktif kullanici bulunamadi.'];
            }
            if (!$this->canUserHandleDepartment($assignee, $department)) {
                return ['error' => 'Secilen kullanici bu departman ticketlarini alamaz.'];
            }
            $assigneeUserId = (int) $assignee->id;
        } elseif ($autoAssign) {
            $autoUser = $this->resolveAutoAssigneeByDepartment($department, $companyId);
            $assigneeUserId = $autoUser ? (int) $autoUser->id : null;
        }

        $slaDueAt = null;
        if ($slaHours !== null && $slaHours > 0 && $status !== 'closed') {
            $slaDueAt = now()->addHours($slaHours);
        }

        $ticket->forceFill([
            'department' => $department,
            'assigned_user_id' => $assigneeUserId,
            'status' => $status,
            'last_replied_at' => now(),
            'routed_at' => now(),
            'closed_at' => $status === 'closed' ? now() : null,
            'sla_due_at' => $slaDueAt ?? $ticket->sla_due_at,
            'sla_hours' => $slaHours ?? $ticket->sla_hours,
        ])->save();

        $guest = GuestApplication::query()->find((int) $ticket->guest_application_id);
        if ($guest) {
            $task = $this->taskAutomationService->ensureGuestTicketTask($guest, $ticket);
            if ($task && $assigneeUserId) {
                $this->taskAutomationService->reassignTasksBySource('guest_ticket_opened', (string) $ticket->id, $assigneeUserId);
            }
            if ($status === 'closed') {
                $this->taskAutomationService->markTasksDoneBySource('guest_ticket_opened', (string) $ticket->id);
            } else {
                $this->taskAutomationService->reopenTasksBySource('guest_ticket_opened', (string) $ticket->id);
            }
        }

        $this->eventLogService->log(
            eventType: 'guest_ticket_routed',
            entityType: 'guest_ticket',
            entityId: (string) $ticket->id,
            message: "Ticket #{$ticket->id} yonlendirildi.",
            meta: [
                'department' => $department,
                'assigned_user_id' => $assigneeUserId,
                'status' => $status,
                'auto_assign' => $autoAssign,
            ],
            actorEmail: $actorEmail,
            companyId: (int) ($ticket->company_id ?: 0)
        );

        return ['error' => null];
    }

    private function resolveAutoAssigneeByDepartment(string $department, int $companyId): ?User
    {
        $roles = $this->departmentAssignableRoles($department);

        $users = User::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('role', $roles)
            ->where('is_active', true)
            ->get(['id', 'name', 'email', 'role']);
        if ($users->isEmpty()) {
            return User::query()
                ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
                ->where('role', User::ROLE_MANAGER)
                ->where('is_active', true)
                ->orderBy('id')
                ->first();
        }

        $openCounts = MarketingTask::query()
            ->withoutGlobalScope('company')
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->where('department', $department)
            ->whereIn('status', ['todo', 'in_progress', 'blocked'])
            ->whereIn('assigned_user_id', $users->pluck('id')->all())
            ->selectRaw('assigned_user_id, COUNT(*) as total')
            ->groupBy('assigned_user_id')
            ->pluck('total', 'assigned_user_id');

        return $users
            ->sortBy(fn (User $u) => (int) ($openCounts[(int) $u->id] ?? 0))
            ->first();
    }

    /**
     * @return array<string,string>
     */
    private function statusOptions(): array
    {
        return [
            'open' => 'Acik',
            'in_progress' => 'Islemde',
            'waiting_response' => 'Yanit Bekleniyor',
            'closed' => 'Kapali',
        ];
    }

    /**
     * @return array<string,string>
     */
    private function departmentOptions(): array
    {
        return [
            'operations' => 'Operasyon',
            'finance' => 'Finans',
            'advisory' => 'Danismanlik',
            'marketing' => 'Marketing',
            'system' => 'Sistem',
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

    private function resolveScopedDepartmentForRole(string $role): ?string
    {
        return match ($role) {
            User::ROLE_OPERATIONS_ADMIN, User::ROLE_OPERATIONS_STAFF => 'operations',
            User::ROLE_FINANCE_ADMIN, User::ROLE_FINANCE_STAFF => 'finance',
            User::ROLE_MARKETING_ADMIN, User::ROLE_MARKETING_STAFF, User::ROLE_SALES_ADMIN, User::ROLE_SALES_STAFF => 'marketing',
            User::ROLE_SENIOR, User::ROLE_MENTOR => 'advisory',
            default => null,
        };
    }

    /**
     * @return array<int,string>
     */
    private function departmentAssignableRoles(string $department): array
    {
        return match ($department) {
            'finance' => [User::ROLE_FINANCE_ADMIN, User::ROLE_FINANCE_STAFF, User::ROLE_MANAGER],
            'advisory' => [User::ROLE_SENIOR, User::ROLE_MENTOR, User::ROLE_MANAGER],
            'marketing' => [User::ROLE_MARKETING_ADMIN, User::ROLE_MARKETING_STAFF, User::ROLE_SALES_ADMIN, User::ROLE_SALES_STAFF, User::ROLE_MANAGER],
            'system' => [User::ROLE_SYSTEM_ADMIN, User::ROLE_SYSTEM_STAFF, User::ROLE_MANAGER],
            default => [User::ROLE_OPERATIONS_ADMIN, User::ROLE_OPERATIONS_STAFF, User::ROLE_MANAGER],
        };
    }

    private function canUserHandleDepartment(User $user, string $department): bool
    {
        return in_array((string) $user->role, $this->departmentAssignableRoles($department), true);
    }
}
