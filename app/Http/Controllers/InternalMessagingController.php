<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Support\FileUploadRules;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\User;
use App\Services\ConversationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InternalMessagingController extends Controller
{
    // Dahili mesajlaşmaya erişebilen roller
    public const ALLOWED_ROLES = [
        User::ROLE_MANAGER,
        User::ROLE_SENIOR,
        User::ROLE_MENTOR,
        User::ROLE_SYSTEM_ADMIN,
        User::ROLE_SYSTEM_STAFF,
        User::ROLE_MARKETING_ADMIN,
        User::ROLE_MARKETING_STAFF,
        User::ROLE_SALES_ADMIN,
        User::ROLE_SALES_STAFF,
        User::ROLE_OPERATIONS_ADMIN,
        User::ROLE_OPERATIONS_STAFF,
        User::ROLE_FINANCE_ADMIN,
        User::ROLE_FINANCE_STAFF,
    ];

    public function __construct(private readonly ConversationService $service) {}

    /** Ana sayfa — konuşma listesi + seçili thread */
    public function index(Request $request)
    {
        $user = $request->user();
        abort_if(!in_array((string) $user->role, self::ALLOWED_ROLES, true), 403);

        $companyId = $this->companyId();
        $selectedId = (int) ($request->query('conv') ?: 0);

        $conversations = Conversation::query()
            ->forUser((int) $user->id)
            ->notArchived()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->with(['participantUsers:id,name,role', 'participants' => fn ($q) => $q->where('user_id', $user->id)])
            ->orderByRaw('(SELECT is_pinned FROM conversation_participants WHERE conversation_id = conversations.id AND user_id = ? LIMIT 1) DESC', [$user->id])
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

            $this->service->markRead($selected, (int) $user->id);
        }

        // DM başlatılabilecek kullanıcı listesi
        $dmableUsers = User::query()
            ->where('id', '!=', $user->id)
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('role', self::ALLOWED_ROLES)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        $layout = match(true) {
            in_array($user->role, [User::ROLE_SENIOR, User::ROLE_MENTOR]) => 'senior.layouts.app',
            in_array($user->role, [User::ROLE_MARKETING_ADMIN, User::ROLE_MARKETING_STAFF, User::ROLE_SALES_ADMIN, User::ROLE_SALES_STAFF]) => 'marketing-admin.layouts.app',
            default => 'manager.layouts.app',
        };

        return view('im.index', [
            'conversations' => $conversations,
            'selected'      => $selected,
            'messages'      => $messages,
            'dmableUsers'   => $dmableUsers,
            'currentUser'   => $user,
            'unreadMap'     => $this->buildUnreadMap($conversations, (int) $user->id),
            'layout'        => $layout,
        ]);
    }

    /** DM başlat veya mevcut DM'i aç */
    public function dmStart(Request $request, int $targetUserId)
    {
        $user = $request->user();
        abort_if(!in_array((string) $user->role, self::ALLOWED_ROLES, true), 403);

        $target = User::query()->findOrFail($targetUserId);

        if (!$this->service->canStartDmWith($user, $target)) {
            return back()->withErrors(['dm' => 'Bu kullanıcıyla direkt mesaj başlatma yetkiniz yok.']);
        }

        $conv = $this->service->findOrCreateDm((int) $user->id, $targetUserId, $this->companyId());

        // tab=internal ekle: aksi halde hub.index default olarak customer tab'ını açıyor
        // ve kullanıcı "boş ekran" görüp tekrar Ekip sekmesine tıklamak zorunda kalıyor (B20).
        return redirect()->route('im.index', ['tab' => 'internal', 'conv' => $conv->id]);
    }

    /** Grup konuşması oluştur */
    public function groupCreate(Request $request)
    {
        $user = $request->user();
        abort_if(!in_array((string) $user->role, self::ALLOWED_ROLES, true), 403);

        $data = $request->validate([
            'title'            => ['required', 'string', 'max:190'],
            'participant_ids'   => ['nullable', 'array'],
            'participant_ids.*' => ['integer', 'exists:users,id'],
            'participants'      => ['nullable', 'array'],
            'participants.*'    => ['integer', 'exists:users,id'],
            'context_type'     => ['nullable', 'in:student,guest,task,contract'],
            'context_id'       => ['nullable', 'string', 'max:64'],
            'type'             => ['nullable', 'in:group,room,announcement'],
        ]);

        $type = $data['type'] ?? 'group';

        $participantIds = array_merge(
            (array) ($data['participant_ids'] ?? []),
            (array) ($data['participants'] ?? [])
        );

        $conv = $this->service->createGroup(
            $data['title'],
            $participantIds,
            (int) $user->id,
            $this->companyId(),
            $data['context_type'] ?? null,
            $data['context_id'] ?? null,
            $type
        );

        $label = match ($type) {
            'room'         => 'Konu odası oluşturuldu.',
            'announcement' => 'Duyuru kanalı oluşturuldu.',
            default        => 'Grup konuşması oluşturuldu.',
        };

        // tab=internal eklendi: B20 ile aynı sebep — aksi halde manager için
        // default tab=customer açılır ve oluşturulan grup/oda görünmez.
        return redirect()->route('im.index', ['tab' => 'internal', 'conv' => $conv->id])
            ->with('status', $label);
    }

    /** Mesaj gönder */
    public function send(Request $request, int $convId)
    {
        $user    = $request->user();
        $conv    = $this->resolveConversation($convId, (int) $user->id);

        // Arşivlenmiş konuşmada mesaj gönderilemez
        abort_if($conv->isArchived(), 403, 'Bu konuşma arşivlenmiş. Yeni mesaj gönderilemez.');

        // Duyurularda yalnızca admin mesaj gönderebilir
        if ($conv->type === 'announcement') {
            $part = $conv->participants()->where('user_id', $user->id)->first();
            abort_if(!$part || $part->role !== 'admin', 403, 'Duyuru kanalına yalnızca yöneticiler mesaj gönderebilir.');
        }

        $data = $request->validate([
            'body'            => ['required_without:attachment', 'nullable', 'string', 'max:5000'],
            'reply_to'        => ['nullable', 'integer'],
            'attachment'      => FileUploadRules::attachment(),
        ]);

        $body = trim((string) ($data['body'] ?? ''));
        $file = $request->file('attachment');

        $attachPath = null;
        $attachName = null;
        $attachSize = null;
        $attachMime = null;

        if ($file) {
            $attachName = $file->getClientOriginalName();
            $attachMime = $file->getMimeType() ?: '';
            $attachSize = (int) $file->getSize();
            $ext        = strtolower($file->getClientOriginalExtension() ?: 'bin');
            $safe       = Str::slug(pathinfo($attachName, PATHINFO_FILENAME)) ?: 'file';
            $fileName   = now()->format('Ymd_His').'_'.Str::limit($safe, 40, '').'.'.$ext;
            $attachPath = $file->storeAs(
                'internal-messages/'.(int) ($conv->company_id ?? 0).'/'.$convId,
                $fileName,
                'private'
            );

            if ($body === '') {
                $body = '[dosya] '.$attachName;
            }
        }

        abort_if($body === '', 422, 'Mesaj veya dosya gerekli.');

        $msg = $this->service->sendMessage($conv, (int) $user->id, $body, (int) ($data['reply_to'] ?? 0) ?: null);

        if ($attachPath) {
            $msg->forceFill([
                'attachment_path' => $attachPath,
                'attachment_name' => $attachName,
                'attachment_size' => $attachSize,
                'attachment_mime' => $attachMime,
            ])->save();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'id'         => $msg->id,
                'body'       => $msg->body,
                'sender'     => ['id' => $user->id, 'name' => $user->name],
                'created_at' => $msg->created_at?->toIso8601String(),
            ]);
        }

        return redirect()->route('im.index', ['tab' => 'internal', 'conv' => $convId]);
    }

    /** Konuşmayı okundu işaretle */
    public function read(Request $request, int $convId)
    {
        $user = $request->user();
        $conv = $this->resolveConversation($convId, (int) $user->id);
        $this->service->markRead($conv, (int) $user->id);

        return response()->json(['ok' => true]);
    }

    /** Sustur / susturma kaldır */
    public function mute(Request $request, int $convId)
    {
        $user = $request->user();
        $part = $this->resolveParticipant($convId, (int) $user->id);
        $part->forceFill(['is_muted' => !$part->is_muted])->save();

        return back()->with('status', $part->is_muted ? 'Konuşma susturuldu.' : 'Susturma kaldırıldı.');
    }

    /** Sabitle / sabitlemeyi kaldır */
    public function pin(Request $request, int $convId)
    {
        $user = $request->user();
        $part = $this->resolveParticipant($convId, (int) $user->id);
        $part->forceFill(['is_pinned' => !$part->is_pinned])->save();

        return back()->with('status', $part->is_pinned ? 'Konuşma sabitlendi.' : 'Sabitleme kaldırıldı.');
    }

    /** Mesaj sil (soft delete — kendi mesajı veya manager) */
    public function deleteMessage(Request $request, int $msgId)
    {
        $user = $request->user();
        $msg  = Message::query()->findOrFail($msgId);
        $conv = $this->resolveConversation((int) $msg->conversation_id, (int) $user->id);

        // Kendi mesajı herkes silebilir; başkasının mesajını sadece grup admin + manager
        $isOwn = (int) $msg->sender_id === (int) $user->id;
        $canDeleteAny = $this->service->canPerform($conv, $user, 'delete_any_message');
        abort_if(!$isOwn && !$canDeleteAny, 403, 'Başkasının mesajını silme yetkiniz yok.');

        $msg->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('im.index', ['tab' => 'internal', 'conv' => $conv->id]);
    }

    /** Gruba üye ekle — sadece grup admin + manager */
    public function groupAddMember(Request $request, int $convId)
    {
        $user = $request->user();
        $conv = $this->resolveConversation($convId, (int) $user->id);
        abort_if(!in_array($conv->type, ['group','room'], true), 403, 'Yalnızca grup veya odalara üye eklenebilir.');
        abort_if(!$this->service->canPerform($conv, $user, 'add_member'), 403, 'Üye ekleme yetkiniz yok.');

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $targetId = (int) $data['user_id'];

        // Zaten üye mi?
        if ($conv->isParticipant($targetId)) {
            return back()->withErrors(['member' => 'Bu kullanıcı zaten grupta.']);
        }

        ConversationParticipant::query()->create([
            'conversation_id' => $conv->id,
            'user_id'         => $targetId,
            'role'            => 'member',
        ]);

        // Sistem mesajı
        $added = User::query()->find($targetId);
        $this->service->sendMessage($conv, (int) $user->id, "📥 {$added?->name} gruba eklendi.");

        return redirect()->route('im.index', ['tab' => 'internal', 'conv' => $conv->id])
            ->with('status', 'Üye eklendi.');
    }

    /** Gruptan üye çıkar */
    public function groupRemoveMember(Request $request, int $convId, int $targetUserId)
    {
        $user = $request->user();
        $conv = $this->resolveConversation($convId, (int) $user->id);
        abort_if(!in_array($conv->type, ['group','room'], true), 403, 'Yalnızca grup veya odalardan üye çıkarılabilir.');

        // Kendi kendini çıkarabilir ya da admin/manager çıkarabilir
        $isAdmin   = in_array((string) $user->role, [User::ROLE_MANAGER, User::ROLE_SYSTEM_ADMIN], true);
        $isSelf    = (int) $user->id === $targetUserId;
        $myPart    = $conv->participants()->where('user_id', $user->id)->first();
        $isGroupAdmin = $myPart?->role === 'admin';

        abort_if(!$isSelf && !$isAdmin && !$isGroupAdmin, 403, 'Üye çıkarma yetkiniz yok.');

        // En az 1 admin kalsın (admin çıkarılıyorsa ve son admin ise engelle)
        if (!$isSelf) {
            $removePart = $conv->participants()->where('user_id', $targetUserId)->first();
            if ($removePart?->role === 'admin') {
                $adminCount = $conv->participants()->where('role', 'admin')->count();
                if ($adminCount <= 1) {
                    return back()->withErrors(['member' => 'Son admin çıkarılamaz.']);
                }
            }
        }

        $conv->participants()->where('user_id', $targetUserId)->delete();

        $removed = User::query()->find($targetUserId);
        $this->service->sendMessage($conv, (int) $user->id, "📤 {$removed?->name} gruptan çıkarıldı.");

        if ($isSelf) {
            return redirect()->route('im.index')->with('status', 'Gruptan ayrıldınız.');
        }

        return redirect()->route('im.index', ['tab' => 'internal', 'conv' => $conv->id])
            ->with('status', 'Üye çıkarıldı.');
    }

    /** Dosya indir */
    public function download(Request $request, int $msgId)
    {
        $user = $request->user();
        $msg  = Message::query()->withTrashed()->findOrFail($msgId);
        $conv = $this->resolveConversation((int) $msg->conversation_id, (int) $user->id);

        abort_if((string) ($msg->attachment_path ?? '') === '', 404, 'Ek bulunamadı.');

        return Storage::disk('private')->download(
            (string) $msg->attachment_path,
            (string) ($msg->attachment_name ?: 'dosya')
        );
    }

    /** Polling: belirli bir mesaj ID'sinden sonraki yeni mesajlar (JSON) */
    public function poll(Request $request, int $convId)
    {
        $user  = $request->user();
        $conv  = $this->resolveConversation($convId, (int) $user->id);
        $after = (int) ($request->query('after') ?: 0);

        $msgs = Message::query()
            ->where('conversation_id', $convId)
            ->where('id', '>', $after)
            ->with('sender:id,name,role')
            ->withTrashed()
            ->orderBy('created_at')
            ->limit(50)
            ->get()
            ->map(fn (Message $m) => [
                'id'         => $m->id,
                'body'       => $m->getDisplayBody(),
                'is_system'  => $m->is_system,
                'is_deleted' => $m->trashed(),
                'is_edited'  => $m->is_edited,
                'sender_id'  => $m->sender_id,
                'sender'     => $m->sender ? ['id' => $m->sender->id, 'name' => $m->sender->name] : null,
                'created_at' => $m->created_at?->toIso8601String(),
                'has_attachment' => $m->hasAttachment(),
                'attachment_name' => $m->attachment_name,
            ]);

        $this->service->markRead($conv, (int) $user->id);

        return response()->json(['messages' => $msgs]);
    }

    /** Okunmamış sayısı (navbar badge için) */
    public function unreadCount(Request $request)
    {
        $user  = $request->user();
        $count = $this->service->unreadCountForUser((int) $user->id, $this->companyId());

        return response()->json(['count' => $count]);
    }

    // ── Yardımcılar ──────────────────────────────────────────

    private function companyId(): int
    {
        return app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
    }

    private function resolveConversation(int $convId, int $userId): Conversation
    {
        $conv = Conversation::query()->findOrFail($convId);
        abort_if(!$conv->isParticipant($userId), 403, 'Bu konuşmaya erişim yetkiniz yok.');
        return $conv;
    }

    private function resolveParticipant(int $convId, int $userId): ConversationParticipant
    {
        $this->resolveConversation($convId, $userId);
        return ConversationParticipant::query()
            ->where('conversation_id', $convId)
            ->where('user_id', $userId)
            ->firstOrFail();
    }

    /**
     * Her konuşma için okunmamış mesaj sayısını map olarak döner.
     *
     * @return array<int,int>
     */
    private function buildUnreadMap($conversations, int $userId): array
    {
        $ids = $conversations->pluck('id')->all();
        if (empty($ids)) {
            return [];
        }

        $participants = ConversationParticipant::query()
            ->whereIn('conversation_id', $ids)
            ->where('user_id', $userId)
            ->get(['conversation_id', 'last_read_at'])
            ->keyBy('conversation_id');

        $counts = [];
        foreach ($ids as $cid) {
            $part = $participants->get($cid);
            $lastRead = $part?->last_read_at;

            $q = Message::query()->where('conversation_id', $cid)->whereNull('deleted_at');
            if ($lastRead) {
                $q->where('created_at', '>', $lastRead);
            }
            $counts[(int) $cid] = (int) $q->count();
        }

        return $counts;
    }

    /** Mesaj içinde arama (JSON) */
    public function search(Request $request)
    {
        $user = $request->user();
        abort_if(!in_array((string) $user->role, self::ALLOWED_ROLES, true), 403);

        $q = trim((string) $request->query('q', ''));
        abort_if(mb_strlen($q) < 2, 422, 'Arama terimi en az 2 karakter olmalı.');

        $convIds = ConversationParticipant::query()
            ->where('user_id', $user->id)
            ->pluck('conversation_id');

        $results = Message::query()
            ->whereIn('conversation_id', $convIds)
            ->where('body', 'like', '%' . $q . '%')
            ->whereNull('deleted_at')
            ->where('is_system', false)
            ->with('conversation:id,title,type', 'sender:id,name')
            ->latest('created_at')
            ->limit(30)
            ->get()
            ->map(fn (Message $m) => [
                'id'              => $m->id,
                'body_preview'    => Str::limit($m->body, 120),
                'conversation_id' => $m->conversation_id,
                'conv_title'      => $m->conversation?->title ?? 'DM',
                'conv_type'       => $m->conversation?->type,
                'sender_name'     => $m->sender?->name ?? 'Sistem',
                'created_at'      => $m->created_at?->toIso8601String(),
            ]);

        return response()->json(['results' => $results, 'query' => $q]);
    }

    /** Mesaj düzenle (15 dakika kuralı) */
    public function editMessage(Request $request, int $msgId)
    {
        $user = $request->user();
        $msg  = Message::query()->findOrFail($msgId);

        abort_if((int) $msg->sender_id !== (int) $user->id, 403, 'Yalnızca kendi mesajınızı düzenleyebilirsiniz.');
        abort_if($msg->is_system, 403, 'Sistem mesajları düzenlenemez.');
        abort_if($msg->created_at->diffInMinutes(now()) > 15, 422, 'Düzenleme süresi (15 dakika) dolmuştur.');

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $msg->forceFill([
            'body'      => trim($data['body']),
            'is_edited' => true,
            'edited_at' => now(),
        ])->save();

        // Son mesajsa konuşma önizlemesini güncelle
        $conv = Conversation::find($msg->conversation_id);
        if ($conv && $conv->last_message_at && $conv->last_message_at->eq($msg->created_at)) {
            $conv->forceFill(['last_message_preview' => Str::limit($data['body'], 100)])->save();
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'body' => $msg->body, 'edited_at' => $msg->edited_at]);
        }

        return redirect()->route('im.index', ['tab' => 'internal', 'conv' => $msg->conversation_id]);
    }

    /** Konuşma arşivle — sadece grup admin + manager */
    public function archive(Request $request, int $convId)
    {
        $user = $request->user();
        $conv = $this->resolveConversation($convId, (int) $user->id);

        abort_if(!$this->service->canPerform($conv, $user, 'archive'), 403, 'Arşivleme yetkiniz yok.');

        $this->service->archiveConversation($conv, (int) $user->id);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'is_archived' => true]);
        }

        return redirect()->route('im.index')->with('status', 'Konuşma arşivlendi.');
    }

    /** Arşivden çıkar — sadece grup admin + manager */
    public function unarchive(Request $request, int $convId)
    {
        $user = $request->user();
        $conv = $this->resolveConversation($convId, (int) $user->id);

        abort_if(!$this->service->canPerform($conv, $user, 'unarchive'), 403, 'Arşivden çıkarma yetkiniz yok.');

        $this->service->unarchiveConversation($conv, (int) $user->id);

        return redirect()->route('im.index', ['tab' => 'internal', 'conv' => $conv->id])
            ->with('status', 'Konuşma arşivden çıkarıldı.');
    }

    /** Konuşmayı kalıcı sil — sadece manager/system_admin */
    public function destroy(Request $request, int $convId)
    {
        $user = $request->user();
        $conv = $this->resolveConversation($convId, (int) $user->id);

        abort_if(!$this->service->canPerform($conv, $user, 'destroy'), 403, 'Sadece yöneticiler konuşma silebilir.');

        $this->service->destroyConversation($conv);

        return redirect()->route('im.index')->with('status', 'Konuşma kalıcı olarak silindi.');
    }

    /** Member'ı admin'e yükselt — mevcut grup admin + manager */
    public function promoteMember(Request $request, int $convId, int $targetUserId)
    {
        $user = $request->user();
        $conv = $this->resolveConversation($convId, (int) $user->id);

        abort_if(!$this->service->canPerform($conv, $user, 'promote_member'), 403, 'Yetki ataması yapamazsınız.');
        abort_if(!in_array($conv->type, ['group','room'], true), 403, 'Yalnızca grup/odada admin atanabilir.');
        abort_if(!$conv->isParticipant($targetUserId), 404, 'Kullanıcı bu grupta değil.');

        $this->service->promoteToAdmin($conv, $targetUserId, (int) $user->id);

        return redirect()->route('im.index', ['tab' => 'internal', 'conv' => $conv->id])
            ->with('status', 'Admin yetkisi verildi.');
    }

    /** Admin yetkisini kaldır — mevcut grup admin + manager */
    public function demoteMember(Request $request, int $convId, int $targetUserId)
    {
        $user = $request->user();
        $conv = $this->resolveConversation($convId, (int) $user->id);

        abort_if(!$this->service->canPerform($conv, $user, 'promote_member'), 403, 'Yetki değişikliği yapamazsınız.');

        $ok = $this->service->demoteFromAdmin($conv, $targetUserId, (int) $user->id);
        if (!$ok) {
            return back()->withErrors(['member' => 'Son admin demote edilemez veya kullanıcı admin değil.']);
        }

        return redirect()->route('im.index', ['tab' => 'internal', 'conv' => $conv->id])
            ->with('status', 'Admin yetkisi kaldırıldı.');
    }

    // ── Katman 2: Mesaj Tepkileri ──────────────────────────────────────────────

    /** Mesaja emoji tepkisi ekle */
    public function react(Request $request, int $msgId)
    {
        $data = $request->validate([
            'emoji' => ['required', 'string', 'max:10', 'in:' . implode(',', MessageReaction::ALLOWED)],
        ]);

        $msg  = Message::findOrFail($msgId);
        $user = $request->user();

        // Katılımcı kontrolü
        $isParticipant = ConversationParticipant::where('conversation_id', $msg->conversation_id)
            ->where('user_id', $user->id)->exists();
        abort_if(!$isParticipant, 403);

        MessageReaction::firstOrCreate([
            'message_id' => $msgId,
            'user_id'    => (int) $user->id,
            'emoji'      => $data['emoji'],
        ], ['created_at' => now()]);

        $counts = MessageReaction::where('message_id', $msgId)
            ->selectRaw('emoji, COUNT(*) as cnt')
            ->groupBy('emoji')
            ->pluck('cnt', 'emoji');

        return response()->json(['ok' => true, 'reactions' => $counts]);
    }

    /** Mesajdan emoji tepkisi kaldır */
    public function removeReaction(Request $request, int $msgId, string $emoji)
    {
        $msg  = Message::findOrFail($msgId);
        $user = $request->user();

        $isParticipant = ConversationParticipant::where('conversation_id', $msg->conversation_id)
            ->where('user_id', $user->id)->exists();
        abort_if(!$isParticipant, 403);

        MessageReaction::where('message_id', $msgId)
            ->where('user_id', (int) $user->id)
            ->where('emoji', urldecode($emoji))
            ->delete();

        $counts = MessageReaction::where('message_id', $msgId)
            ->selectRaw('emoji, COUNT(*) as cnt')
            ->groupBy('emoji')
            ->pluck('cnt', 'emoji');

        return response()->json(['ok' => true, 'reactions' => $counts]);
    }

    // ── Katman 2: Mesaj İletme ─────────────────────────────────────────────────

    /** Bir mesajı başka konuşmaya ilet */
    public function forwardMessage(Request $request, int $msgId)
    {
        $data = $request->validate([
            'target_conversation_id' => 'required|integer|exists:conversations,id',
        ]);

        $msg  = Message::findOrFail($msgId);
        $user = $request->user();

        // Kaynak konuşmada katılımcı mı?
        $isSourceParticipant = ConversationParticipant::where('conversation_id', $msg->conversation_id)
            ->where('user_id', $user->id)->exists();
        abort_if(!$isSourceParticipant, 403, 'Kaynak konuşmaya erişiminiz yok.');

        // Hedef konuşmada katılımcı mı?
        $isTargetParticipant = ConversationParticipant::where('conversation_id', $data['target_conversation_id'])
            ->where('user_id', $user->id)->exists();
        abort_if(!$isTargetParticipant, 403, 'Hedef konuşmaya erişiminiz yok.');

        $forwarded = Message::create([
            'conversation_id' => (int) $data['target_conversation_id'],
            'sender_id'       => (int) $user->id,
            'body'            => "📨 İletilen mesaj:\n\n" . $msg->body,
            'forwarded_from'  => $msg->id,
            'attachment_path' => $msg->attachment_path,
            'attachment_name' => $msg->attachment_name,
            'attachment_size' => $msg->attachment_size,
            'attachment_mime' => $msg->attachment_mime,
        ]);

        // Hedef konuşmanın son mesaj zamanını güncelle
        Conversation::where('id', $data['target_conversation_id'])
            ->update(['last_message_at' => now()]);

        return response()->json(['ok' => true, 'message_id' => $forwarded->id]);
    }

    // ── K3.2 — AI Konuşma Özeti ───────────────────────────────────────────────
    public function summarize(Request $request, int $convId): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $part = ConversationParticipant::where('conversation_id', $convId)
            ->where('user_id', $user->id)
            ->first();
        abort_if(!$part, 403, 'Bu konuşmaya erişiminiz yok.');

        $messages = Message::where('conversation_id', $convId)
            ->with('sender:id,name')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        if ($messages->isEmpty()) {
            return response()->json(['ok' => true, 'summary' => 'Konuşmada henüz mesaj yok.']);
        }

        // Kısa özet: son 50 mesajı satır formatına getir
        $lines = $messages->map(function ($m) {
            $name = $m->sender?->name ?? 'Bilinmiyor';
            $body = Str::limit((string) ($m->body ?? ''), 120);
            $at   = $m->created_at?->format('d.m H:i') ?? '';
            return "[{$at}] {$name}: {$body}";
        })->implode("\n");

        // AiWritingService varsa kullan, yoksa basit özet
        try {
            $ai     = app(\App\Services\AiWritingService::class);
            $result = $ai->improveGermanDocument('summary', $lines, ['instruction' => 'Konuşmayı Türkçe 3-5 cümleyle özetle.']);
            $summary = $result['improved'] ?? $result['content'] ?? '';
            if (empty(trim($summary))) {
                throw new \RuntimeException('Boş yanıt');
            }
        } catch (\Throwable) {
            $total = $messages->count();
            $participants = $messages->pluck('sender.name')->unique()->filter()->values()->implode(', ');
            $summary = "Son {$total} mesaj, katılımcılar: {$participants}. Konuşma özeti AI servisi şu an aktif değil.";
        }

        return response()->json(['ok' => true, 'summary' => trim((string) $summary)]);
    }
}
