<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\DmMessage;
use App\Models\DmThread;
use App\Models\GuestApplication;
use App\Models\Message;
use App\Models\User;
use App\Services\ConversationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Unified Messaging Hub — /im
 *
 * İki sekme:
 *  - Danışan : DmThread/DmMessage — role göre filtrelenmiş
 *  - Ekip    : Conversation/Message — staff içi
 */
class UnifiedMessagingHubController extends Controller
{
    // Danışan sekmesine erişebilen roller
    // Marketing/Sales danışan mesajlarını görmez — sadece Ekip sekmesi
    private const CUSTOMER_ROLES = [
        User::ROLE_MANAGER,
        User::ROLE_SENIOR,
        User::ROLE_MENTOR,
        User::ROLE_SYSTEM_ADMIN,
        User::ROLE_SYSTEM_STAFF,
        User::ROLE_OPERATIONS_ADMIN,
        User::ROLE_OPERATIONS_STAFF,
    ];

    public function __construct(private readonly ConversationService $convService) {}

    public function index(Request $request)
    {
        $user      = $request->user();
        $role      = (string) ($user->role ?? '');
        $companyId = $this->companyId();

        $canCustomer = in_array($role, self::CUSTOMER_ROLES, true);
        $canInternal = in_array($role, InternalMessagingController::ALLOWED_ROLES, true);

        abort_if(!$canCustomer && !$canInternal, 403);

        // Aktif sekme: URL'den al, yoksa role göre varsayılan
        $tab = $request->query('tab');
        if (!in_array($tab, ['customer', 'internal'], true)) {
            $tab = $canCustomer ? 'customer' : 'internal';
        }

        // Danışan sekmesi verisi — her zaman tam yükle (sekme switch'i için)
        $customerData   = null;
        $customerUnread = 0;
        if ($canCustomer) {
            $customerData   = $this->loadCustomerData($request, $user, $role, $companyId, true);
            $customerUnread = (int) ($customerData['unread_total'] ?? 0);
        }

        // Ekip sekmesi verisi — her zaman tam yükle (sekme switch'i için)
        $internalData   = null;
        $internalUnread = 0;
        if ($canInternal) {
            $internalData   = $this->loadInternalData($request, $user, $companyId, true);
            $internalUnread = $this->convService->unreadCountForUser((int) $user->id, $companyId);
        }

        $layout = match(true) {
            in_array($role, [User::ROLE_SENIOR, User::ROLE_MENTOR]) => 'senior.layouts.app',
            in_array($role, [User::ROLE_MARKETING_ADMIN, User::ROLE_MARKETING_STAFF, User::ROLE_SALES_ADMIN, User::ROLE_SALES_STAFF]) => 'marketing-admin.layouts.app',
            default => 'manager.layouts.app',
        };

        return view('hub.index', [
            'tab'            => $tab,
            'canCustomer'    => $canCustomer,
            'canInternal'    => $canInternal,
            'customerData'   => $customerData,
            'customerUnread' => $customerUnread,
            'internalData'   => $internalData,
            'internalUnread' => $internalUnread,
            'currentUser'    => $user,
            'layout'         => $layout,
        ]);
    }

    // ── Private loaders ───────────────────────────────────────────────────

    private function loadCustomerData(Request $request, User $user, string $role, int $companyId, bool $full): array
    {
        // Okunmamış sayısı — her zaman hesapla (badge için)
        $unreadQuery = DmMessage::query()->where('is_read_by_advisor', false);
        if ($companyId > 0) {
            $unreadQuery->whereIn('thread_id', fn ($s) => $s->select('id')->from('dm_threads')->where('company_id', $companyId));
        }
        if (in_array($role, [User::ROLE_SENIOR, User::ROLE_MENTOR], true)) {
            // Senior sadece kendi thread'leri
            $unreadQuery->whereIn('thread_id', fn ($s) => $s->select('id')->from('dm_threads')->where('advisor_user_id', (int) $user->id));
        } else {
            // Manager ve diğerleri: guest/student thread'leri görmez
            $unreadQuery->whereIn('thread_id', fn ($s) => $s->select('id')->from('dm_threads')->whereNotIn('thread_type', ['guest', 'student']));
        }
        $unreadTotal = (int) $unreadQuery->count();

        if (!$full) {
            return ['unread_total' => $unreadTotal, 'threads' => collect(), 'selectedThread' => null, 'messages' => collect(), 'advisors' => collect(), 'quickReplies' => [], 'unreadAdvisorMap' => [], 'guestMap' => collect()];
        }

        $threadId = (int) ($request->query('thread_id') ?: 0);

        $threads = DmThread::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            // Senior/Mentor: sadece atandıkları thread'ler
            // Manager ve diğerleri: guest/student thread'leri görmez
            ->when(
                in_array($role, [User::ROLE_SENIOR, User::ROLE_MENTOR], true),
                fn ($q) => $q->where('advisor_user_id', (int) $user->id),
                fn ($q) => $q->whereNotIn('thread_type', ['guest', 'student'])
            )
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->limit(150)
            ->get();

        $selected = $threadId > 0 ? $threads->firstWhere('id', $threadId) : $threads->first();
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
                ->update(['is_read_by_advisor' => true]);
        }

        $advisors = User::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('role', [User::ROLE_SENIOR, User::ROLE_MENTOR, User::ROLE_MANAGER, User::ROLE_OPERATIONS_ADMIN, User::ROLE_OPERATIONS_STAFF])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        $unreadAdvisorMap = DmMessage::query()
            ->where('is_read_by_advisor', false)
            ->whereIn('thread_id', $threads->pluck('id')->all())
            ->selectRaw('thread_id, COUNT(*) as total')
            ->groupBy('thread_id')
            ->pluck('total', 'thread_id');

        $guestMap = GuestApplication::query()
            ->whereIn('id', $threads->pluck('guest_application_id')->filter()->all())
            ->get(['id', 'first_name', 'last_name', 'email', 'converted_student_id'])
            ->keyBy('id');

        return [
            'unread_total'     => $unreadTotal,
            'threads'          => $threads,
            'selectedThread'   => $selected,
            'messages'         => $messages,
            'advisors'         => $advisors,
            'quickReplies'     => $this->quickReplies(),
            'unreadAdvisorMap' => $unreadAdvisorMap,
            'guestMap'         => $guestMap,
        ];
    }

    private function loadInternalData(Request $request, User $user, int $companyId, bool $full): array
    {
        $selectedId = (int) ($request->query('conv') ?: 0);
        $showArchived = (bool) $request->query('archived');

        if (!$full) {
            return ['conversations' => collect(), 'selected' => null, 'messages' => collect(), 'dmableUsers' => collect(), 'unreadMap' => [], 'archivedCount' => 0, 'showArchived' => false];
        }

        $baseQuery = Conversation::query()
            ->forUser((int) $user->id)
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId));

        // Archived count — UI'de "Arşivli Göster (N)" linki için
        $archivedCount = (clone $baseQuery)->where('is_archived', true)->count();

        // Ana liste: archived flag'e göre filtrele
        $conversations = $baseQuery
            ->when($showArchived, fn ($q) => $q->where('is_archived', true))
            ->when(!$showArchived, fn ($q) => $q->where('is_archived', false))
            ->with(['participantUsers:id,name,role', 'participants' => fn ($q) => $q->where('user_id', $user->id)])
            ->orderByRaw('(SELECT is_pinned FROM conversation_participants WHERE conversation_id = conversations.id AND user_id = ? LIMIT 1) DESC', [(int) $user->id])
            ->orderByDesc('last_message_at')
            ->limit(100)
            ->get();

        $selected = $selectedId > 0
            ? $conversations->firstWhere('id', $selectedId)
            : $conversations->first();

        $messages = collect();
        if ($selected) {
            $messages = Message::query()
                ->where('conversation_id', (int) $selected->id)
                ->with('sender:id,name,role', 'replyTo:id,body,sender_id')
                ->withTrashed()
                ->orderBy('created_at')
                ->limit(100)
                ->get();

            $this->convService->markRead($selected, (int) $user->id);
        }

        // Rehber: aynı company'deki tüm IM-access'li ekip
        $dmableQuery = User::query()
            ->where('id', '!=', $user->id)
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->where('is_active', true);

        // Senior istisnası: kendi aday/öğrencilerini de (guest rolü) rehbere dahil et.
        // guest_applications.assigned_senior_email senior email'ine eşit olan guest_user_id'ler
        if ($user->role === User::ROLE_SENIOR) {
            $ownGuestIds = \App\Models\GuestApplication::query()
                ->where('assigned_senior_email', $user->email)
                ->whereNotNull('guest_user_id')
                ->pluck('guest_user_id')
                ->filter()
                ->map(fn ($v) => (int) $v)
                ->unique()
                ->values()
                ->all();

            $dmableQuery->where(function ($q) use ($ownGuestIds) {
                $q->whereIn('role', InternalMessagingController::ALLOWED_ROLES)
                  ->orWhere(function ($qq) use ($ownGuestIds) {
                      if (!empty($ownGuestIds)) {
                          $qq->whereIn('id', $ownGuestIds);
                      } else {
                          $qq->whereRaw('1 = 0');
                      }
                  });
            });
        } else {
            $dmableQuery->whereIn('role', InternalMessagingController::ALLOWED_ROLES);
        }

        $dmableUsers = $dmableQuery
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        return [
            'conversations' => $conversations,
            'selected'      => $selected,
            'messages'      => $messages,
            'dmableUsers'   => $dmableUsers,
            'unreadMap'     => $this->buildUnreadMap($conversations, (int) $user->id),
            'archivedCount' => $archivedCount,
            'showArchived'  => $showArchived,
        ];
    }

    /** @return array<int,int> */
    private function buildUnreadMap($conversations, int $userId): array
    {
        $ids = $conversations->pluck('id')->all();
        if (empty($ids)) return [];

        $parts = ConversationParticipant::query()
            ->whereIn('conversation_id', $ids)
            ->where('user_id', $userId)
            ->get(['conversation_id', 'last_read_at'])
            ->keyBy('conversation_id');

        $counts = [];
        foreach ($ids as $cid) {
            $lastRead = $parts->get($cid)?->last_read_at;
            $q = Message::query()
                ->where('conversation_id', $cid)
                ->whereNull('deleted_at')
                ->where('is_system', false)
                ->where('sender_id', '!=', $userId); // kendi mesajın unread sayılmaz
            if ($lastRead) $q->where('created_at', '>', $lastRead);
            $counts[(int) $cid] = (int) $q->count();
        }
        return $counts;
    }

    private function companyId(): int
    {
        return app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
    }

    /** @return array<int,string> */
    private function quickReplies(): array
    {
        return [
            'Merhaba, mesajınızı aldık. En kısa sürede dönüş yapacağız.',
            'Belgeniz inceleniyor. Eksik olursa bu kanaldan bilgi vereceğiz.',
            'Talebiniz ilgili departmana yönlendirildi.',
            'Ek bilgi gerekiyor. Lütfen detayları bu mesaja cevap olarak paylaşın.',
            'İşlem tamamlandı. Farklı bir konuda yardım gerekirse yazabilirsiniz.',
        ];
    }
}
