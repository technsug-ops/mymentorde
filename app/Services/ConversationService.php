<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConversationService
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * İki kullanıcı arasında mevcut DM konuşmasını döner ya da yeni oluşturur.
     */
    public function findOrCreateDm(int $actorId, int $targetId, int $companyId): Conversation
    {
        // Mevcut DM var mı?
        $existing = Conversation::query()
            ->where('type', 'direct')
            ->where(fn ($q) => $companyId > 0 ? $q->where('company_id', $companyId) : $q)
            ->whereHas('participants', fn ($q) => $q->where('user_id', $actorId))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $targetId))
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($actorId, $targetId, $companyId): Conversation {
            $conv = Conversation::query()->create([
                'company_id'          => $companyId > 0 ? $companyId : null,
                'type'                => 'direct',
                'created_by_user_id'  => $actorId,
            ]);

            $this->addParticipant($conv, $actorId, 'admin');
            $this->addParticipant($conv, $targetId, 'member');

            return $conv;
        });
    }

    /**
     * Yeni grup konuşması oluşturur.
     * @param int[] $participantIds
     */
    public function createGroup(
        string $title,
        array $participantIds,
        int $creatorId,
        int $companyId,
        ?string $contextType = null,
        ?string $contextId = null,
        string $type = 'group'
    ): Conversation {
        return DB::transaction(function () use ($title, $participantIds, $creatorId, $companyId, $contextType, $contextId, $type): Conversation {
            $conv = Conversation::query()->create([
                'company_id'         => $companyId > 0 ? $companyId : null,
                'type'               => in_array($type, ['group', 'room'], true) ? $type : 'group',
                'title'              => $title,
                'created_by_user_id' => $creatorId,
                'context_type'       => $contextType,
                'context_id'         => $contextId,
            ]);

            $this->addParticipant($conv, $creatorId, 'admin');

            foreach (array_unique($participantIds) as $uid) {
                if ((int) $uid !== $creatorId) {
                    $this->addParticipant($conv, (int) $uid, 'member');
                }
            }

            // Sistem mesajı
            $creator = User::query()->find($creatorId, ['name']);
            $label = $type === 'room' ? 'odayı oluşturdu' : 'bu grubu oluşturdu';
            $this->addSystemMessage($conv, ($creator?->name ?? 'Bilinmeyen').' '.$label.'.');

            return $conv;
        });
    }

    /**
     * Mesaj gönderir, konuşma önizlemesini günceller, bildirim tetikler.
     */
    public function sendMessage(
        Conversation $conv,
        int $senderId,
        string $body,
        ?int $replyToId = null
    ): Message {
        $msg = Message::query()->create([
            'conversation_id'    => $conv->id,
            'sender_id'          => $senderId,
            'body'               => $body,
            'reply_to_message_id' => $replyToId,
            'created_at'         => now(),
        ]);

        $preview = Str::limit($body, 100);
        $conv->forceFill([
            'last_message_at'      => now(),
            'last_message_preview' => $preview,
        ])->save();

        // Gönderen haricindeki katılımcılara bildirim (muted değilse)
        $sender = User::query()->find($senderId, ['name']);
        $senderName = (string) ($sender?->name ?? 'Biri');
        $displayTitle = $conv->type === 'direct' ? $senderName : (string) ($conv->title ?? 'Grup');

        $participantIds = $conv->participants()
            ->where('user_id', '!=', $senderId)
            ->where('is_muted', false)
            ->pluck('user_id')
            ->all();

        foreach ($participantIds as $recipientId) {
            $this->notificationService->send([
                'channel'      => 'in_app',
                'category'     => 'internal_message',
                'user_id'      => (int) $recipientId,
                'company_id'   => $conv->company_id,
                'subject'      => $senderName.': '.Str::limit($body, 60),
                'body'         => 'Yeni mesaj — '.$displayTitle,
                'source_type'  => 'internal_message',
                'source_id'    => (string) $conv->id,
                'triggered_by' => (string) $senderId,
            ]);

            // Alıcının unread cache'ini sıfırla
            self::invalidateUnreadCache((int) $recipientId);
        }

        // Away period kontrolü — sistem mesajı değilse auto-reply tetikle
        \App\Services\AutoReplyService::checkImConversation($conv, $senderId);

        return $msg;
    }

    /**
     * Kullanıcının konuşmadaki last_read_at değerini günceller.
     * Cache invalidate eder.
     */
    public function markRead(Conversation $conv, int $userId): void
    {
        ConversationParticipant::query()
            ->where('conversation_id', $conv->id)
            ->where('user_id', $userId)
            ->update(['last_read_at' => now()]);

        \Illuminate\Support\Facades\Cache::forget("im_unread_{$userId}");
    }

    /**
     * Kullanıcının toplam okunmamış mesaj sayısı (navbar badge için).
     * 60 saniyelik cache — yeni mesaj geldikten veya okunduktan sonra sıfırlanır.
     */
    public function unreadCountForUser(int $userId, int $companyId = 0): int
    {
        $cacheKey = "im_unread_{$userId}";

        return (int) \Illuminate\Support\Facades\Cache::remember($cacheKey, 60, function () use ($userId, $companyId) {
            return (int) DB::table('conversation_participants as cp')
                ->join('messages as m', 'm.conversation_id', '=', 'cp.conversation_id')
                ->join('conversations as c', 'c.id', '=', 'cp.conversation_id')
                ->where('cp.user_id', $userId)
                ->where('cp.is_muted', false)
                ->when($companyId > 0, fn ($q) => $q->where('c.company_id', $companyId))
                ->where(function ($q): void {
                    $q->whereNull('cp.last_read_at')
                      ->orWhereColumn('m.created_at', '>', 'cp.last_read_at');
                })
                ->whereNull('m.deleted_at')
                ->count();
        });
    }

    /**
     * Unread count cache'ini manuel invalidate et (mesaj gönderiminde çağrılır).
     */
    public static function invalidateUnreadCache(int $userId): void
    {
        \Illuminate\Support\Facades\Cache::forget("im_unread_{$userId}");
    }

    /**
     * Konuşmaya katılımcı ekler (zaten ekliyse atlar).
     */
    public function addParticipant(Conversation $conv, int $userId, string $role = 'member'): void
    {
        ConversationParticipant::query()->firstOrCreate(
            ['conversation_id' => $conv->id, 'user_id' => $userId],
            ['role' => $role, 'joined_at' => now()]
        );
    }

    /**
     * Kullanıcının bir başkasıyla DM başlatıp başlatamayacağını kontrol eder.
     * Kural: aynı şirket içindeki herkes herkesle DM başlatabilir.
     */
    public function canStartDmWith(User $from, User $to): bool
    {
        // Farklı şirket
        if ((int) ($from->company_id ?? 0) !== (int) ($to->company_id ?? 0)) {
            return false;
        }

        return true;
    }

    private function departmentOf(string $role): string
    {
        return match ($role) {
            User::ROLE_MARKETING_ADMIN, User::ROLE_MARKETING_STAFF, User::ROLE_SALES_ADMIN, User::ROLE_SALES_STAFF => 'marketing',
            User::ROLE_OPERATIONS_ADMIN, User::ROLE_OPERATIONS_STAFF => 'operations',
            User::ROLE_FINANCE_ADMIN, User::ROLE_FINANCE_STAFF => 'finance',
            User::ROLE_SENIOR, User::ROLE_MENTOR => 'advisory',
            User::ROLE_SYSTEM_ADMIN, User::ROLE_SYSTEM_STAFF => 'system',
            default => 'general',
        };
    }

    private function addSystemMessage(Conversation $conv, string $text): void
    {
        Message::query()->create([
            'conversation_id' => $conv->id,
            'sender_id'       => null,
            'body'            => $text,
            'is_system'       => true,
            'created_at'      => now(),
        ]);
    }
}
