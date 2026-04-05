<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\DmThread;
use App\Models\DmMessage;
use App\Models\Message;
use App\Models\User;
use App\Models\UserAwayPeriod;
use Illuminate\Support\Facades\DB;

/**
 * AutoReplyService
 *
 * Mesaj gönderildiğinde alıcı "away" durumdaysa otomatik yanıt gönderir.
 * Her away period + thread/conversation kombinasyonu için tek seferlik gönderim.
 */
class AutoReplyService
{
    /**
     * DM sistemi için: thread'deki advisor away ise otomatik yanıt gönder.
     * Çağrı: MessageCenterController::send() içinden, participant (guest/student) mesaj gönderince.
     */
    public static function checkDmThread(DmThread $thread, int $senderUserId): void
    {
        // Advisor kim?
        $advisorId = (int) ($thread->advisor_user_id ?? 0);
        if ($advisorId === 0 || $advisorId === $senderUserId) {
            return; // Advisor yoksa veya advisor mesaj gönderiyorsa → atla
        }

        $advisor = User::find($advisorId);
        if (!$advisor) {
            return;
        }

        $awayPeriod = UserAwayPeriod::where('user_id', $advisorId)->active()->first();
        if (!$awayPeriod || !$awayPeriod->auto_reply_enabled) {
            return;
        }

        $awayPeriodKey = (string) $awayPeriod->id;

        // Bu away period için zaten auto-reply gönderildi mi?
        if ((string) ($thread->auto_reply_away_period_id ?? '') === $awayPeriodKey) {
            return;
        }

        $replyText = $awayPeriod->auto_reply_message
            ?: 'Merhaba, şu an müsait değilim.'
               . ($awayPeriod->away_until
                  ? ' ' . $awayPeriod->away_until->format('d.m.Y H:i') . ' tarihine kadar dönüş yapamayacağım.'
                  : '')
               . ' En kısa sürede yanıt vereceğim.';

        // Otomatik yanıtı sistem mesajı olarak ekle
        DmMessage::query()->create([
            'thread_id'              => (int) $thread->id,
            'sender_user_id'         => $advisorId,
            'sender_role'            => 'system_auto_reply',
            'message'                => '🤖 ' . $replyText,
            'is_read_by_advisor'     => true,
            'is_read_by_participant' => false,
        ]);

        // Thread'e kaydet — tekrar gönderilmesin
        $thread->forceFill([
            'auto_reply_sent_at'        => now(),
            'auto_reply_away_period_id' => $awayPeriodKey,
        ])->save();
    }

    /**
     * IM sistemi için: konuşmadaki alıcılar away ise otomatik yanıt gönder.
     * Çağrı: ConversationService::sendMessage() sonrasında.
     */
    public static function checkImConversation(Conversation $conv, int $senderUserId): void
    {
        // Gönderici haricindeki katılımcıları bul
        $participants = ConversationParticipant::query()
            ->where('conversation_id', $conv->id)
            ->where('user_id', '!=', $senderUserId)
            ->where('is_muted', false)
            ->get(['id', 'user_id', 'auto_reply_away_period_id']);

        foreach ($participants as $participant) {
            $recipientId = (int) $participant->user_id;
            $recipient   = User::find($recipientId, ['id', 'name']);
            if (!$recipient) {
                continue;
            }

            $awayPeriod = UserAwayPeriod::where('user_id', $recipientId)->active()->first();
            if (!$awayPeriod || !$awayPeriod->auto_reply_enabled) {
                continue;
            }

            $awayPeriodKey = (string) $awayPeriod->id;

            // Bu away period için bu konuşmada zaten gönderildi mi?
            if ((string) ($participant->auto_reply_away_period_id ?? '') === $awayPeriodKey) {
                continue;
            }

            $replyText = $awayPeriod->auto_reply_message
                ?: ($recipient->name . ' şu an müsait değil.')
                   . ($awayPeriod->away_until
                      ? ' ' . $awayPeriod->away_until->format('d.m.Y H:i') . ' tarihine kadar geri dönüş yapamayacak.'
                      : '');

            // Sistem mesajı olarak konuşmaya ekle
            Message::query()->create([
                'conversation_id' => $conv->id,
                'sender_id'       => $recipientId,
                'body'            => '🤖 ' . $replyText,
                'is_system'       => true,
                'created_at'      => now(),
            ]);

            // ConversationParticipant'a kaydet
            $participant->forceFill([
                'auto_reply_sent_at'        => now(),
                'auto_reply_away_period_id' => $awayPeriodKey,
            ])->save();
        }
    }
}
