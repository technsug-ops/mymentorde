<?php

namespace App\Http\Controllers;

use App\Models\DmMessage;
use App\Models\DmThread;
use App\Support\FileUploadRules;
use App\Models\GuestApplication;
use App\Models\NotificationDispatch;
use App\Models\StudentAssignment;
use App\Models\User;
use App\Services\GuestResolverService;
use App\Services\NotificationService;
use App\Services\TaskAutomationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ConversationController extends Controller
{
    public function __construct(
        private readonly TaskAutomationService $taskAutomationService,
        private readonly NotificationService $notificationService,
        private readonly GuestResolverService $guestResolver,
    ) {
    }

    public function guest(Request $request)
    {
        $user = $request->user();
        $guest = $this->resolveGuest($user?->email ?? '');
        if (!$guest) {
            return redirect()->route('guest.dashboard')
                ->with('warning', 'Başvuru kaydınız henüz oluşturulmadı. Danışmanınızla iletişime geçin.');
        }

        $thread = $this->resolveOrCreateGuestThread($guest, (int) ($user->id ?? 0));
        $q = trim((string) $request->query('q', ''));
        $messages = DmMessage::query()
            ->where('thread_id', (int) $thread->id)
            ->when($q !== '', fn ($query) => $query->where('message', 'like', '%'.$q.'%'))
            ->orderBy('id')
            ->limit(150)
            ->get();

        DmMessage::query()
            ->where('thread_id', (int) $thread->id)
            ->where('is_read_by_participant', false)
            ->where('sender_role', '!=', User::ROLE_GUEST)
            ->update(['is_read_by_participant' => true]);

        $advisor = $thread->advisor_user_id
            ? User::query()->find($thread->advisor_user_id, ['id', 'name', 'email', 'role'])
            : null;

        return view('guest.messages', [
            'guest' => $guest,
            'user' => $user,
            'thread' => $thread,
            'messages' => $messages,
            'advisor' => $advisor,
            'search' => $q,
            'guestDmUnread' => 0,
            'profileCompletionPercent' => 0,
        ]);
    }

    public function guestSend(Request $request)
    {
        $user = $request->user();
        $guest = $this->resolveGuest($user?->email ?? '');
        abort_if(!$guest, 404, 'Guest kaydi bulunamadi.');

        $thread = $this->resolveOrCreateGuestThread($guest, (int) ($user->id ?? 0));
        $this->storeMessage($request, $thread, User::ROLE_GUEST, (int) ($user->id ?? 0), true);

        return redirect()->route('guest.messages')->with('status', 'Mesaj gonderildi.');
    }

    public function student(Request $request)
    {
        $user = $request->user();
        $studentId = trim((string) ($user->student_id ?? ''));
        if ($studentId === '') {
            $guest = $this->resolveGuest($user?->email ?? '');
            $studentId = trim((string) ($guest?->converted_student_id ?? ''));
        }
        abort_if($studentId === '', 404, 'Student kaydi bulunamadi.');

        $thread = $this->resolveOrCreateStudentThread($studentId, (int) ($user->id ?? 0));
        $q = trim((string) $request->query('q', ''));
        $messages = DmMessage::query()
            ->where('thread_id', (int) $thread->id)
            ->when($q !== '', fn ($query) => $query->where('message', 'like', '%'.$q.'%'))
            ->orderBy('id')
            ->limit(180)
            ->get();

        DmMessage::query()
            ->where('thread_id', (int) $thread->id)
            ->where('is_read_by_participant', false)
            ->where('sender_role', '!=', User::ROLE_STUDENT)
            ->update(['is_read_by_participant' => true]);

        $advisor = $thread->advisor_user_id
            ? User::query()->find($thread->advisor_user_id, ['id', 'name', 'email', 'role'])
            : null;

        return view('student.messages', [
            'thread' => $thread,
            'messages' => $messages,
            'advisor' => $advisor,
            'studentId' => $studentId,
            'search' => $q,
        ]);
    }

    public function studentSend(Request $request)
    {
        $user = $request->user();
        $studentId = trim((string) ($user->student_id ?? ''));
        if ($studentId === '') {
            $guest = $this->resolveGuest($user?->email ?? '');
            $studentId = trim((string) ($guest?->converted_student_id ?? ''));
        }
        abort_if($studentId === '', 404, 'Student kaydi bulunamadi.');

        $thread = $this->resolveOrCreateStudentThread($studentId, (int) ($user->id ?? 0));
        $this->storeMessage($request, $thread, User::ROLE_STUDENT, (int) ($user->id ?? 0), true);

        return redirect()->route('student.messages')->with('status', 'Mesaj gonderildi.');
    }

    public function senior(Request $request)
    {
        $user = $request->user();
        abort_if(!$user, 401);
        abort_if(!$user->hasAnyRole([User::ROLE_SENIOR, User::ROLE_MENTOR, User::ROLE_MANAGER]), 403);

        $q = trim((string) $request->query('q', ''));

        $threads = DmThread::query()
            ->where('advisor_user_id', (int) $user->id)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('student_id', 'like', '%'.$q.'%')
                        ->orWhere('thread_type', 'like', '%'.$q.'%')
                        ->orWhere('guest_application_id', 'like', '%'.$q.'%');
                });
            })
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $selectedId = (int) ($request->query('thread_id') ?: 0);
        $selected = $selectedId > 0
            ? $threads->firstWhere('id', $selectedId)
            : $threads->first();
        $messages = collect();
        if ($selected) {
            $messages = DmMessage::query()
                ->where('thread_id', (int) $selected->id)
                ->when($q !== '', fn ($query) => $query->where('message', 'like', '%'.$q.'%'))
                ->orderBy('id')
                ->limit(200)
                ->get();
            DmMessage::query()
                ->where('thread_id', (int) $selected->id)
                ->where('is_read_by_advisor', false)
                ->whereNotIn('sender_role', [User::ROLE_SENIOR, User::ROLE_MENTOR, User::ROLE_MANAGER])
                ->update(['is_read_by_advisor' => true]);
        }

        $guestMap = GuestApplication::query()
            ->whereIn('id', $threads->pluck('guest_application_id')->filter()->all())
            ->get(['id', 'first_name', 'last_name', 'email', 'converted_student_id'])
            ->keyBy('id');

        return view('senior.messages', [
            'threads' => $threads,
            'selectedThread' => $selected,
            'messages' => $messages,
            'guestMap' => $guestMap,
            'search' => $q,
        ]);
    }

    public function markAdvisorTyping(Request $request, DmThread $thread): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        abort_if(!$user, 401);
        abort_if((int) ($thread->advisor_user_id ?? 0) !== (int) $user->id, 403);

        Cache::put("dm_typing_{$thread->id}_advisor", true, 8);

        return response()->json(['ok' => true]);
    }

    public function seniorSend(Request $request, DmThread $thread)
    {
        $user = $request->user();
        abort_if(!$user, 401);
        abort_if((int) ($thread->advisor_user_id ?? 0) !== (int) $user->id, 403);

        $senderRole = in_array((string) $user->role, [User::ROLE_SENIOR, User::ROLE_MENTOR, User::ROLE_MANAGER], true)
            ? (string) $user->role
            : User::ROLE_SENIOR;

        $this->storeMessage($request, $thread, $senderRole, (int) $user->id, false);

        return redirect()->route('senior.messages', ['thread_id' => (int) $thread->id])->with('status', 'Mesaj gonderildi.');
    }

    public function download(Request $request, DmMessage $message)
    {
        $user = $request->user();
        abort_if(!$user, 401);

        $thread = DmThread::query()->findOrFail((int) $message->thread_id);
        $allowed = false;

        if ((int) ($thread->advisor_user_id ?? 0) === (int) $user->id) {
            $allowed = true;
        }

        if (!$allowed && (string) $user->role === User::ROLE_GUEST && (string) $thread->thread_type === 'guest') {
            $guest = $this->resolveGuest((string) ($user->email ?? ''));
            $allowed = $guest && (int) $guest->id === (int) ($thread->guest_application_id ?? 0);
        }

        if (!$allowed && (string) $user->role === User::ROLE_STUDENT && (string) $thread->thread_type === 'student') {
            $allowed = trim((string) ($user->student_id ?? '')) !== ''
                && trim((string) ($user->student_id ?? '')) === trim((string) ($thread->student_id ?? ''));
        }

        abort_if(!$allowed, 403);
        abort_if(trim((string) $message->attachment_storage_path) === '', 404, 'Dosya bulunamadi.');

        return Storage::disk('public')->download(
            (string) $message->attachment_storage_path,
            (string) ($message->attachment_original_name ?: ('dm-'.$message->id))
        );
    }

    private function storeMessage(Request $request, DmThread $thread, string $senderRole, int $senderUserId, bool $isParticipant): void
    {
        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:5000'],
            'quick_request' => ['nullable', 'boolean'],
            'attachment' => FileUploadRules::attachment(),
            'department' => ['nullable', 'in:operations,finance,advisory,marketing,system'],
            'sla_hours' => ['nullable', 'integer', 'min:1', 'max:168'],
        ]);

        $text = trim((string) ($data['message'] ?? ''));
        $file = $request->file('attachment');
        abort_if($text === '' && !$file, 422, 'Mesaj veya dosya gerekli.');

        // ── Çift gönderim koruması: aynı kullanıcı + aynı thread + 5sn içinde aynı metin ──
        if ($text !== '' && $senderUserId > 0) {
            $dedupKey = 'msg_dedup_'.$senderUserId.'_'.$thread->id.'_'.md5($text);
            if (\Illuminate\Support\Facades\Cache::has($dedupKey)) {
                return; // Aynı mesaj az önce gönderildi, sessizce yoksay
            }
            \Illuminate\Support\Facades\Cache::put($dedupKey, 1, 5);
        }

        $attachmentPath = null;
        $attachmentOriginalName = null;
        $attachmentMime = null;
        $attachmentSizeKb = null;

        if ($file) {
            $attachmentOriginalName = (string) $file->getClientOriginalName();
            $attachmentMime = (string) ($file->getMimeType() ?: '');
            $attachmentSizeKb = (int) ceil(((int) $file->getSize()) / 1024);
            $ext = strtolower((string) ($file->getClientOriginalExtension() ?: 'bin'));
            $safe = Str::slug(pathinfo($attachmentOriginalName, PATHINFO_FILENAME)) ?: 'file';
            $fileName = now()->format('Ymd_His').'_'.Str::limit($safe, 40, '').'.'.$ext;
            $attachmentPath = $file->storeAs('dm/'.$thread->id, $fileName, 'public');
        }

        DmMessage::query()->create([
            'thread_id' => (int) $thread->id,
            'sender_user_id' => $senderUserId > 0 ? $senderUserId : null,
            'sender_role' => $senderRole,
            'message' => $text !== '' ? $text : null,
            'is_quick_request' => (bool) ($data['quick_request'] ?? false),
            'attachment_original_name' => $attachmentOriginalName,
            'attachment_storage_path' => $attachmentPath,
            'attachment_mime' => $attachmentMime,
            'attachment_size_kb' => $attachmentSizeKb,
            'is_read_by_advisor' => in_array($senderRole, [User::ROLE_SENIOR, User::ROLE_MENTOR, User::ROLE_MANAGER], true),
            'is_read_by_participant' => in_array($senderRole, [User::ROLE_GUEST, User::ROLE_STUDENT], true),
        ]);

        $preview = $text !== '' ? $text : ('[dosya] '.(string) $attachmentOriginalName);
        $dept = (string) ($data['department'] ?? $thread->department ?? '');
        if (!in_array($dept, ['operations', 'finance', 'advisory', 'marketing', 'system'], true)) {
            $dept = $thread->department ?: $this->defaultDepartmentByThreadType((string) $thread->thread_type);
        }
        $sla = (int) ($data['sla_hours'] ?? $thread->sla_hours ?? $this->defaultSlaHoursByDepartment($dept));
        if ($sla < 1 || $sla > 168) {
            $sla = $this->defaultSlaHoursByDepartment($dept);
        }

        $update = [
            'last_message_preview' => Str::limit($preview, 220, '...'),
            'last_message_at' => now(),
            'status' => 'open',
            'department' => $dept,
            'sla_hours' => $sla,
        ];
        if ($isParticipant) {
            $update['last_participant_message_at'] = now();
            $update['next_response_due_at'] = now()->addHours($sla);
        } else {
            $update['last_advisor_reply_at'] = now();
            $update['next_response_due_at'] = null;
        }
        $thread->forceFill($update)->save();

        if (!empty($data['quick_request'])) {
            $this->taskAutomationService->ensureConversationQuickRequestTask($thread);
        }
        if ($isParticipant) {
            $this->taskAutomationService->ensureConversationResponseTask($thread, $sla);
            $this->queueAdvisorNotification($thread, $preview, (string) ($request->user()?->email ?? 'system'));
        } else {
            $this->taskAutomationService->markConversationResponseDone($thread);
            $this->queueParticipantNotification($thread, $preview, (string) ($request->user()?->email ?? 'system'));
        }
    }

    private function resolveOrCreateGuestThread(GuestApplication $guest, int $initiatorUserId): DmThread
    {
        $companyId = (int) ($guest->company_id ?: 0);
        $thread = DmThread::query()
            ->where('company_id', $companyId > 0 ? $companyId : 0)
            ->where('thread_type', 'guest')
            ->where('guest_application_id', (int) $guest->id)
            ->first();

        if ($thread) {
            if ((int) ($thread->advisor_user_id ?? 0) <= 0) {
                $advisorId = $this->resolveAdvisorUserIdForGuest($guest);
                if ($advisorId > 0) {
                    $thread->forceFill(['advisor_user_id' => $advisorId])->save();
                }
            }
            return $thread;
        }

        return DmThread::query()->create([
            'company_id' => $companyId > 0 ? $companyId : 0,
            'thread_type' => 'guest',
            'guest_application_id' => (int) $guest->id,
            'advisor_user_id' => $this->resolveAdvisorUserIdForGuest($guest),
            'initiated_by_user_id' => $initiatorUserId > 0 ? $initiatorUserId : null,
            'status' => 'open',
            'department' => 'advisory',
            'sla_hours' => 24,
        ]);
    }

    private function resolveOrCreateStudentThread(string $studentId, int $initiatorUserId): DmThread
    {
        $assignment = StudentAssignment::query()->where('student_id', $studentId)->first();
        $companyId = (int) ($assignment->company_id ?? 0);

        $thread = DmThread::query()
            ->where('company_id', $companyId > 0 ? $companyId : 0)
            ->where('thread_type', 'student')
            ->where('student_id', $studentId)
            ->first();

        if ($thread) {
            if ((int) ($thread->advisor_user_id ?? 0) <= 0) {
                $advisorId = $this->resolveAdvisorUserIdForStudent($studentId, $companyId);
                if ($advisorId > 0) {
                    $thread->forceFill(['advisor_user_id' => $advisorId])->save();
                }
            }
            return $thread;
        }

        return DmThread::query()->create([
            'company_id' => $companyId > 0 ? $companyId : 0,
            'thread_type' => 'student',
            'student_id' => $studentId,
            'advisor_user_id' => $this->resolveAdvisorUserIdForStudent($studentId, $companyId),
            'initiated_by_user_id' => $initiatorUserId > 0 ? $initiatorUserId : null,
            'status' => 'open',
            'department' => 'advisory',
            'sla_hours' => 24,
        ]);
    }

    private function resolveGuest(string $email): ?GuestApplication
    {
        return $this->guestResolver->resolveByEmail($email);
    }

    private function resolveAdvisorUserIdForGuest(GuestApplication $guest): ?int
    {
        $companyId = (int) ($guest->company_id ?: 0);
        $assignedSeniorEmail = strtolower(trim((string) ($guest->assigned_senior_email ?? '')));
        if ($assignedSeniorEmail !== '') {
            $seniorId = User::query()
                ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
                ->where('email', strtolower($assignedSeniorEmail))
                ->whereIn('role', [User::ROLE_SENIOR, User::ROLE_MENTOR])
                ->where('is_active', true)
                ->value('id');
            if ($seniorId) {
                return (int) $seniorId;
            }
        }

        $fallback = User::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('role', [User::ROLE_OPERATIONS_ADMIN, User::ROLE_OPERATIONS_STAFF])
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');

        return $fallback ? (int) $fallback : null;
    }

    private function resolveAdvisorUserIdForStudent(string $studentId, int $companyId = 0): ?int
    {
        $assignment = StudentAssignment::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->where('student_id', $studentId)
            ->first();
        $seniorEmail = strtolower(trim((string) ($assignment->senior_email ?? '')));
        if ($seniorEmail !== '') {
            $seniorId = User::query()
                ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
                ->where('email', strtolower($seniorEmail))
                ->whereIn('role', [User::ROLE_SENIOR, User::ROLE_MENTOR])
                ->where('is_active', true)
                ->value('id');
            if ($seniorId) {
                return (int) $seniorId;
            }
        }

        $fallback = User::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('role', [User::ROLE_OPERATIONS_ADMIN, User::ROLE_OPERATIONS_STAFF])
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');

        return $fallback ? (int) $fallback : null;
    }

    private function defaultDepartmentByThreadType(string $threadType): string
    {
        return $threadType === 'guest' ? 'operations' : 'advisory';
    }

    private function defaultSlaHoursByDepartment(string $department): int
    {
        return match ($department) {
            'finance', 'system' => 24,
            'operations', 'advisory' => 12,
            'marketing' => 24,
            default => 24,
        };
    }

    private function queueParticipantNotification(DmThread $thread, string $preview, string $triggeredBy): void
    {
        // Danışman cevap verdiğinde guest/student'a e-posta bildir
        $recipientEmail = '';
        $recipientName  = '';

        if ($thread->thread_type === 'guest' && $thread->guest_application_id) {
            $guest = \App\Models\GuestApplication::query()->find($thread->guest_application_id);
            if ($guest) {
                $user = \App\Models\User::query()->where('email', $guest->email)->first();
                $recipientEmail = $guest->email ?? '';
                $recipientName  = trim(($guest->first_name ?? '') . ' ' . ($guest->last_name ?? ''));
            }
        } elseif ($thread->thread_type === 'student' && $thread->student_id) {
            $assignment = \App\Models\StudentAssignment::query()->find($thread->student_id);
            if ($assignment) {
                $user = \App\Models\User::query()->where('student_id', $thread->student_id)->first();
                $recipientEmail = $user?->email ?? '';
                $recipientName  = $user?->name ?? '';
            }
        }

        if ($recipientEmail === '') {
            return;
        }

        $this->notificationService->send([
            'channel'         => 'email',
            'category'        => 'conversation',
            'guest_id'        => $thread->thread_type === 'guest' ? (string) ($thread->guest_application_id ?? '') : null,
            'student_id'      => $thread->thread_type === 'student' ? (string) ($thread->student_id ?? '') : null,
            'recipient_email' => $recipientEmail,
            'recipient_name'  => $recipientName,
            'subject'         => 'MentorDE — Danışmanınızdan Yeni Mesaj',
            'body'            => Str::limit($preview, 220, '...'),
            'variables'       => [
                'thread_id'   => (int) $thread->id,
                'thread_type' => (string) $thread->thread_type,
            ],
            'source_type'  => 'conversation_reply',
            'source_id'    => (string) $thread->id,
            'triggered_by' => $triggeredBy,
        ]);
    }

    private function queueAdvisorNotification(DmThread $thread, string $preview, string $triggeredBy): void
    {
        $advisorId = (int) ($thread->advisor_user_id ?? 0);
        if ($advisorId <= 0) {
            return;
        }
        $advisor = User::query()->where('id', $advisorId)->where('is_active', true)->first();
        if (!$advisor || trim((string) $advisor->email) === '') {
            return;
        }

        $this->notificationService->send([
            'channel'         => 'email',
            'category'        => 'conversation',
            'student_id'      => $thread->thread_type === 'student' ? (string) ($thread->student_id ?? '') : null,
            'recipient_email' => (string) $advisor->email,
            'recipient_name'  => (string) ($advisor->name ?? ''),
            'subject'         => 'MentorDE Mesaj Merkezi - Yeni Mesaj',
            'body'            => Str::limit($preview, 220, '...'),
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
