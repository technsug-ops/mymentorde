<?php

namespace App\Services;

use App\Jobs\SendNotificationJob;
use App\Models\MessageTemplate;
use App\Models\NotificationDispatch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function __construct(
        private readonly NotificationPreferenceService $preferenceService,
        private readonly TemplateRenderer              $renderer,
    ) {}

    /**
     * Ana gönderim metodu — tüm bildirimler buradan geçer.
     *
     * Params:
     *   channel         string   'email' | 'in_app' | 'whatsapp'
     *   category        string   §2.4 kataloğundan
     *   user_id         ?int
     *   guest_id        ?string
     *   student_id      ?string
     *   company_id      ?int
     *   recipient_email ?string
     *   recipient_phone ?string
     *   recipient_name  ?string
     *   subject         ?string  (template_id yoksa kullanılır)
     *   body            ?string  (template_id yoksa zorunlu)
     *   template_id     ?int     (varsa body+subject template'den render edilir)
     *   variables       ?array   template değişkenleri
     *   source_type     ?string
     *   source_id       ?string
     *   triggered_by    ?string
     */
    public function send(array $params): ?NotificationDispatch
    {
        $channel    = (string) ($params['channel']    ?? '');
        $category   = (string) ($params['category']   ?? '');
        $userId     = isset($params['user_id'])    ? (int) $params['user_id']    : null;
        $guestId    = isset($params['guest_id'])   ? (string) $params['guest_id']   : null;
        $studentId  = isset($params['student_id']) ? (string) $params['student_id'] : null;
        $companyId  = isset($params['company_id']) ? (int) $params['company_id'] : null;

        // --- 1. Validation ---
        if ($channel === '' || $category === '') {
            Log::warning('NotificationService: channel veya category eksik.', $params);
            return null;
        }

        if ($channel === 'in_app' && $userId === null && $guestId === null && $studentId === null) {
            Log::warning('NotificationService: in_app kanalı için hedef kimlik eksik.', $params);
            return null;
        }

        if ($channel === 'email' && empty($params['recipient_email'])) {
            Log::warning('NotificationService: email kanalı için recipient_email eksik.', $params);
            return null;
        }

        // --- 2. Deduplicate kontrolü ---
        $sourceType = (string) ($params['source_type'] ?? '');
        $sourceId   = (string) ($params['source_id']   ?? '');

        if ($sourceType !== '' && $sourceId !== '') {
            $deduplicateQuery = NotificationDispatch::query()
                ->where('category', $category)
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->whereIn('status', ['pending', 'queued', 'sent']);

            if ($userId !== null) {
                $deduplicateQuery->where('user_id', $userId);
            } elseif ($guestId !== null) {
                $deduplicateQuery->where('guest_id', $guestId);
            } elseif ($studentId !== null) {
                $deduplicateQuery->where('student_id', $studentId);
            }

            // task_due_reminder: aynı gün içinde dedup
            if ($category === 'task_due_reminder') {
                $deduplicateQuery->whereDate('created_at', today());
            } else {
                $deduplicateQuery->where('created_at', '>=', now()->subHours(24));
            }

            if ($deduplicateQuery->exists()) {
                return NotificationDispatch::create([
                    'user_id'     => $userId,
                    'guest_id'    => $guestId,
                    'student_id'  => $studentId,
                    'company_id'  => $companyId,
                    'channel'     => $channel,
                    'category'    => $category,
                    'body'        => '',
                    'status'      => 'skipped',
                    'skip_reason' => 'duplicate',
                    'source_type' => $sourceType,
                    'source_id'   => $sourceId,
                    'triggered_by' => $params['triggered_by'] ?? null,
                ]);
            }
        }

        // --- 3. Opt-out kontrolü ---
        $isEnabled = $this->preferenceService->isEnabled(
            userId:    $userId,
            guestId:   $guestId,
            studentId: $studentId,
            channel:   $channel,
            category:  $category,
        );

        if (!$isEnabled) {
            return NotificationDispatch::create([
                'user_id'     => $userId,
                'guest_id'    => $guestId,
                'student_id'  => $studentId,
                'company_id'  => $companyId,
                'channel'     => $channel,
                'category'    => $category,
                'body'        => '',
                'status'      => 'skipped',
                'skip_reason' => "opt_out:{$channel}",
                'source_type' => $sourceType ?: null,
                'source_id'   => $sourceId   ?: null,
                'triggered_by' => $params['triggered_by'] ?? null,
            ]);
        }

        // --- 4. Template render ---
        $subject   = (string) ($params['subject'] ?? '');
        $body      = (string) ($params['body']    ?? '');
        $variables = (array)  ($params['variables'] ?? []);
        $templateId = isset($params['template_id']) ? (int) $params['template_id'] : null;

        if ($templateId !== null) {
            $template = MessageTemplate::find($templateId);
            if ($template) {
                $lang = (string) ($params['lang'] ?? 'tr');
                $rendered = $this->renderer->renderTemplate($template, $variables, $lang);
                $subject  = $rendered['subject'];
                $body     = $rendered['body'];
            } else {
                Log::warning('NotificationService: template bulunamadı.', ['template_id' => $templateId]);
            }
        } elseif ($body !== '' && !empty($variables)) {
            $body = $this->renderer->render($body, $variables);
            if ($subject !== '') {
                $subject = $this->renderer->render($subject, $variables);
            }
        }

        // --- 5. Kayıt oluştur ---
        $dispatch = NotificationDispatch::create([
            'user_id'         => $userId,
            'guest_id'        => $guestId,
            'student_id'      => $studentId,
            'company_id'      => $companyId,
            'template_id'     => $templateId,
            'channel'         => $channel,
            'category'        => $category,
            'recipient_email' => $params['recipient_email'] ?? null,
            'recipient_phone' => $params['recipient_phone'] ?? null,
            'recipient_name'  => $params['recipient_name']  ?? null,
            'subject'         => $subject ?: null,
            'body'            => $body,
            'variables'       => !empty($variables) ? $variables : null,
            'status'          => 'pending',
            'source_type'     => $sourceType ?: null,
            'source_id'       => $sourceId   ?: null,
            'triggered_by'    => $params['triggered_by'] ?? null,
        ]);

        // --- 6. Job dispatch ---
        SendNotificationJob::dispatch($dispatch->id);

        $dispatch->update(['status' => 'queued', 'queued_at' => now()]);

        return $dispatch;
    }

    /**
     * Çoklu alıcıya gönderim (eskalasyon, toplu güncelleme).
     *
     * @param  int[]  $userIds
     * @param  array  $params  — send() ile aynı yapı (user_id olmadan)
     * @return Collection<int, ?NotificationDispatch>
     */
    public function sendToMany(array $userIds, array $params): Collection
    {
        $results = collect();

        foreach ($userIds as $uid) {
            $results->push($this->send(array_merge($params, ['user_id' => $uid])));
        }

        return $results;
    }
}
