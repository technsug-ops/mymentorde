<?php

namespace App\Http\Controllers;

use App\Models\DmMessage;
use App\Models\DmThread;
use App\Support\FileUploadRules;
use App\Models\GuestApplication;
use App\Models\GuestTicket;
use App\Models\NotificationDispatch;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\TaskAutomationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MessageCenterController extends Controller
{
    public function __construct(
        private readonly TaskAutomationService $taskAutomationService
    ) {
    }

    public function index(Request $request, ?string $department = null)
    {
        $user = $request->user();
        abort_if(!$user, 401);

        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $isSalesStaff = (string) $user->role === User::ROLE_SALES_STAFF;
        $type = strtolower(trim((string) $request->query('type', '')));
        $status = strtolower(trim((string) $request->query('status', 'open')));
        $routeDepartment = strtolower(trim((string) $department));
        $department = $isSalesStaff
            ? 'marketing'
            : ($routeDepartment !== ''
                ? $routeDepartment
                : strtolower(trim((string) $request->query('department', ''))));
        $advisorId = (int) ($request->query('advisor_id') ?: 0);
        $q = trim((string) $request->query('q', ''));

        $threads = DmThread::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->when(in_array($type, ['guest', 'student'], true), fn ($q) => $q->where('thread_type', $type))
            ->when(in_array($status, ['open', 'closed'], true), fn ($q) => $q->where('status', $status))
            ->when(in_array($department, ['operations', 'finance', 'advisory', 'marketing', 'system'], true), fn ($q) => $q->where('department', $department))
            ->when($advisorId > 0, fn ($q) => $q->where('advisor_user_id', $advisorId))
            ->when($q !== '', function ($query) use ($q): void {
                $needle = '%'.$q.'%';
                $query->where(function ($sub) use ($needle): void {
                    $sub->where('student_id', 'like', $needle)
                        ->orWhere('last_message_preview', 'like', $needle)
                        ->orWhereRaw("cast(guest_application_id as text) like ?", [$needle]);
                });
            })
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->limit(300)
            ->get();

        $selectedId = (int) ($request->query('thread_id') ?: 0);
        $selected = $selectedId > 0 ? $threads->firstWhere('id', $selectedId) : $threads->first();
        $messages = collect();
        if ($selected) {
            $messages = DmMessage::query()
                ->where('thread_id', (int) $selected->id)
                ->orderBy('id')
                ->limit(250)
                ->get();

            DmMessage::query()
                ->where('thread_id', (int) $selected->id)
                ->where('is_read_by_advisor', false)
                ->whereNotIn('sender_role', [User::ROLE_SENIOR, User::ROLE_MENTOR, User::ROLE_MANAGER, User::ROLE_OPERATIONS_ADMIN, User::ROLE_OPERATIONS_STAFF])
                ->update(['is_read_by_advisor' => true]);
        }

        $advisors = User::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('role', [User::ROLE_SENIOR, User::ROLE_MENTOR, User::ROLE_MANAGER, User::ROLE_OPERATIONS_ADMIN, User::ROLE_OPERATIONS_STAFF])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        $summary = [
            'total' => (int) DmThread::query()->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))->count(),
            'open' => (int) DmThread::query()->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))->where('status', 'open')->count(),
            'closed' => (int) DmThread::query()->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))->where('status', 'closed')->count(),
            'overdue' => (int) DmThread::query()
                ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
                ->where('status', 'open')
                ->whereNotNull('next_response_due_at')
                ->where('next_response_due_at', '<', now())
                ->count(),
            'unassigned' => (int) DmThread::query()
                ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
                ->where(function ($q) {
                    $q->whereNull('advisor_user_id')->orWhere('advisor_user_id', 0);
                })
                ->count(),
            'unread_for_advisor' => (int) DmMessage::query()
                ->where('is_read_by_advisor', false)
                ->whereIn('thread_id', $threads->pluck('id')->all())
                ->count(),
            'unread_for_participant' => (int) DmMessage::query()
                ->where('is_read_by_participant', false)
                ->whereIn('thread_id', $threads->pluck('id')->all())
                ->count(),
        ];

        $unreadAdvisorMap = DmMessage::query()
            ->where('is_read_by_advisor', false)
            ->whereIn('thread_id', $threads->pluck('id')->all())
            ->selectRaw('thread_id, COUNT(*) as total')
            ->groupBy('thread_id')
            ->pluck('total', 'thread_id');

        return view('messages.center', [
            'threads' => $threads,
            'selectedThread' => $selected,
            'messages' => $messages,
            'advisors' => $advisors,
            'summary' => $summary,
            'filters' => [
                'type' => $type,
                'status' => $status,
                'department' => $department,
                'route_department' => $routeDepartment,
                'advisor_id' => $advisorId,
                'q' => $q,
            ],
            'quickReplies' => $this->quickReplies(),
            'unreadAdvisorMap' => $unreadAdvisorMap,
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $user = $request->user();
        abort_if(!$user, 401);

        $data = $request->validate([
            'thread_ids' => ['required', 'array', 'min:1'],
            'thread_ids.*' => ['integer'],
            'status' => ['nullable', 'in:open,closed'],
            'advisor_user_id' => ['nullable', 'integer', 'min:1'],
            'department' => ['nullable', 'in:operations,finance,advisory,marketing,system'],
            'sla_hours' => ['nullable', 'integer', 'min:1', 'max:168'],
        ]);

        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $ids = collect((array) $data['thread_ids'])->map(fn ($v) => (int) $v)->filter(fn ($v) => $v > 0)->values();
        if ($ids->isEmpty()) {
            return back()->withErrors(['bulk' => 'Toplu guncelleme icin thread secin.']);
        }

        $advisorId = (int) ($data['advisor_user_id'] ?? 0);
        if ($advisorId > 0) {
            $advisor = User::query()->where('id', $advisorId)->where('is_active', true)->first();
            if (!$advisor) {
                return back()->withErrors(['bulk' => 'Secilen danisman bulunamadi.']);
            }
        }

        $rows = DmThread::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('id', $ids->all())
            ->get();

        $updated = 0;
        foreach ($rows as $row) {
            $payload = [];
            if (array_key_exists('status', $data) && (string) ($data['status'] ?? '') !== '') {
                $payload['status'] = (string) $data['status'];
            }
            if (array_key_exists('department', $data) && (string) ($data['department'] ?? '') !== '') {
                $payload['department'] = (string) $data['department'];
            }
            if (array_key_exists('sla_hours', $data) && (int) ($data['sla_hours'] ?? 0) > 0) {
                $payload['sla_hours'] = (int) $data['sla_hours'];
            }
            if ($advisorId > 0) {
                $payload['advisor_user_id'] = $advisorId;
            }

            if (!empty($payload)) {
                $row->forceFill($payload)->save();
                $updated++;
            }
        }

        return back()->with('status', "{$updated} thread toplu guncellendi.");
    }

    public function bulkMarkRead(Request $request)
    {
        $user = $request->user();
        abort_if(!$user, 401);

        $data = $request->validate([
            'thread_ids' => ['required', 'array', 'min:1'],
            'thread_ids.*' => ['integer'],
        ]);

        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $ids = collect((array) $data['thread_ids'])->map(fn ($v) => (int) $v)->filter(fn ($v) => $v > 0)->values();
        if ($ids->isEmpty()) {
            return back()->withErrors(['bulk' => 'Toplu okundu icin thread secin.']);
        }

        $threadIds = DmThread::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('id', $ids->all())
            ->pluck('id')
            ->all();

        $affected = DmMessage::query()
            ->whereIn('thread_id', $threadIds)
            ->where('is_read_by_advisor', false)
            ->update(['is_read_by_advisor' => true]);

        return back()->with('status', "{$affected} mesaj okundu olarak isaretlendi.");
    }

    public function assignAdvisor(Request $request, DmThread $thread)
    {
        $user = $request->user();
        abort_if(!$user, 401);

        $data = $request->validate([
            'advisor_user_id' => ['required', 'integer', 'min:1'],
            'department' => ['nullable', 'in:operations,finance,advisory,marketing,system'],
            'sla_hours' => ['nullable', 'integer', 'min:1', 'max:168'],
        ]);

        $advisorId = (int) $data['advisor_user_id'];
        $advisor = User::query()->where('id', $advisorId)->where('is_active', true)->first();
        abort_if(!$advisor, 422, 'Secilen danisman bulunamadi.');

        $thread->forceFill([
            'advisor_user_id' => $advisorId,
            'department' => (string) ($data['department'] ?? $thread->department ?: 'advisory'),
            'sla_hours' => (int) ($data['sla_hours'] ?? $thread->sla_hours ?: 24),
            'status' => 'open',
        ])->save();

        return back()->with('status', 'Danisman atamasi guncellendi.');
    }

    public function updateStatus(Request $request, DmThread $thread)
    {
        $user = $request->user();
        abort_if(!$user, 401);

        $data = $request->validate([
            'status' => ['required', 'in:open,closed'],
        ]);

        $thread->forceFill([
            'status' => (string) $data['status'],
        ])->save();

        return back()->with('status', 'Thread durumu guncellendi.');
    }

    public function convertToTicket(Request $request, DmThread $thread)
    {
        $user = $request->user();
        abort_if(!$user, 401);

        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        if ($companyId > 0 && (int) ($thread->company_id ?? 0) !== $companyId) {
            abort(404);
        }

        if ((string) $thread->thread_type !== 'guest') {
            return back()->withErrors(['convert_ticket' => 'Sadece guest thread ticketa cevrilebilir.']);
        }

        $guest = GuestApplication::query()->find((int) ($thread->guest_application_id ?? 0));
        if (!$guest) {
            return back()->withErrors(['convert_ticket' => 'Guest kaydi bulunamadi.']);
        }

        $lastParticipantMessage = DmMessage::query()
            ->where('thread_id', (int) $thread->id)
            ->where('sender_role', 'guest')
            ->latest('id')
            ->first();

        $subject = 'DM Thread #'.$thread->id.' talebi';
        $body = trim((string) ($lastParticipantMessage?->message ?? $thread->last_message_preview ?? 'Mesaj merkezi talebi'));
        if ($body === '') {
            $body = 'Mesaj merkezi talebi';
        }

        $ticket = GuestTicket::query()->create([
            'company_id' => (int) ($thread->company_id ?: 0),
            'guest_application_id' => (int) $guest->id,
            'subject' => $subject,
            'message' => $body,
            'status' => 'open',
            'priority' => 'normal',
            'department' => (string) ($thread->department ?: 'operations'),
            'assigned_user_id' => (int) ($thread->advisor_user_id ?: 0) ?: null,
            'created_by_email' => (string) ($guest->email ?? ''),
            'last_replied_at' => now(),
            'routed_at' => now(),
        ]);

        $this->taskAutomationService->ensureGuestTicketTask($guest, $ticket);
        if ((int) ($ticket->assigned_user_id ?? 0) > 0) {
            $this->taskAutomationService->reassignTasksBySource('guest_ticket_opened', (string) $ticket->id, (int) $ticket->assigned_user_id);
        }

        return redirect('/tickets-center')->with('status', "Thread #{$thread->id} ticket #{$ticket->id} olarak olusturuldu.");
    }

    public function send(Request $request, DmThread $thread)
    {
        $user = $request->user();
        abort_if(!$user, 401);

        $data = $request->validate([
            'body'       => ['nullable', 'string', 'max:5000'],
            'message'    => ['nullable', 'string', 'max:5000'],
            'attachment' => FileUploadRules::attachment(),
        ]);

        $messageText = trim((string) ($data['body'] ?? $data['message'] ?? ''));

        if ($messageText === '' && !$request->hasFile('attachment')) {
            return back()->withErrors(['message' => 'Mesaj veya dosya eki gerekli.']);
        }

        $attachmentPath = null;
        $attachmentName = null;
        $attachmentMime = null;
        $attachmentSizeKb = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $file->store("dm-attachments/{$thread->id}", 'public');
            $attachmentName = $file->getClientOriginalName();
            $attachmentMime = $file->getMimeType();
            $attachmentSizeKb = (int) ceil($file->getSize() / 1024);
        }

        $msg = DmMessage::query()->create([
            'thread_id'                 => (int) $thread->id,
            'sender_user_id'            => (int) $user->id,
            'sender_role'               => (string) $user->role,
            'message'                   => $messageText !== '' ? $messageText : null,
            'is_read_by_advisor'        => true,
            'is_read_by_participant'    => false,
            'attachment_original_name'  => $attachmentName,
            'attachment_storage_path'   => $attachmentPath,
            'attachment_mime'           => $attachmentMime,
            'attachment_size_kb'        => $attachmentSizeKb,
        ]);

        $preview = $messageText !== ''
            ? Str::limit($messageText, 220, '...')
            : '📎 '.$attachmentName;

        try {
            $thread->forceFill([
                'last_message_preview' => $preview,
                'last_message_at'      => now(),
                'status'               => 'open',
                'last_advisor_reply_at' => now(),
                'next_response_due_at' => null,
            ])->save();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('messages-center.send: thread update failed', [
                'thread_id' => (int) $thread->id,
                'error'     => $e->getMessage(),
            ]);
        }

        // Notifications + auto-reply are side-effects — a failure here must NOT
        // break the send response. The message is already persisted.
        try {
            $this->queueParticipantNotification($thread, $preview, (string) $user->email);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('messages-center.send: notification failed', [
                'thread_id' => (int) $thread->id,
                'error'     => $e->getMessage(),
            ]);
        }

        // Participant (guest/student) mesaj gönderdi → advisor away ise auto-reply
        $isParticipantSender = in_array($user->role, ['guest', 'student'], true);
        if ($isParticipantSender) {
            try {
                \App\Services\AutoReplyService::checkDmThread($thread, (int) $user->id);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('messages-center.send: auto-reply failed', [
                    'thread_id' => (int) $thread->id,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'id'          => (int) $msg->id,
                'body'        => $msg->message,
                'created_at'  => $msg->created_at?->toISOString(),
                'attachment_name' => $msg->attachment_original_name,
                'attachment_url'  => $msg->attachment_storage_path
                    ? Storage::url($msg->attachment_storage_path)
                    : null,
            ]);
        }

        return back()->with('status', 'Mesaj gonderildi.');
    }

    /**
     * @return array<int,string>
     */
    private function quickReplies(): array
    {
        return [
            'Merhaba, mesajinizi aldik. En kisa surede donus yapacagiz.',
            'Belgeniz inceleniyor. Eksik olursa bu kanaldan bilgi verecegiz.',
            'Talebiniz ilgili departmana yonlendirildi.',
            'Ek bilgi gerekiyor. Lutfen detaylari bu mesaja cevap olarak paylasin.',
            'Islem tamamlandi. Farkli bir konuda yardim gerekirse yazabilirsiniz.',
        ];
    }

    private function queueParticipantNotification(DmThread $thread, string $message, string $triggeredBy): void
    {
        $recipientEmail = null;
        $recipientName = null;
        $studentId = null;

        if ((string) $thread->thread_type === 'guest') {
            $guest = \App\Models\GuestApplication::query()->find((int) ($thread->guest_application_id ?? 0));
            if ($guest) {
                $recipientEmail = (string) ($guest->email ?? '');
                $recipientName = trim((string) (($guest->first_name ?? '').' '.($guest->last_name ?? '')));
                $studentId = trim((string) ($guest->converted_student_id ?? ''));
            }
        } else {
            $studentId = trim((string) ($thread->student_id ?? ''));
            if ($studentId !== '') {
                $studentUser = User::query()
                    ->where('student_id', $studentId)
                    ->where('is_active', true)
                    ->first();
                if ($studentUser) {
                    $recipientEmail = (string) ($studentUser->email ?? '');
                    $recipientName = (string) ($studentUser->name ?? '');
                }
            }
        }

        if (trim((string) $recipientEmail) === '') {
            return;
        }

        $this->notificationService->send([
            'channel'         => 'email',
            'category'        => 'conversation',
            'student_id'      => $studentId ?: null,
            'recipient_email' => $recipientEmail,
            'recipient_name'  => $recipientName ?: null,
            'subject'         => 'MentorDE Mesaj Merkezi - Yeni Yanit',
            'body'            => 'Mesaj merkezinizde yeni bir yanit var: '.Str::limit($message, 180, '...'),
            'variables'       => [
                'thread_id'   => (int) $thread->id,
                'thread_type' => (string) $thread->thread_type,
            ],
            'source_type'  => 'conversation_message',
            'source_id'    => (string) $thread->id,
            'triggered_by' => $triggeredBy,
        ]);
    }
}
