<?php

namespace App\Services\Platform;

use App\Models\Company;
use App\Models\PlatformBroadcast;
use App\Models\PlatformBroadcastRecipient;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Throwable;

/**
 * Platform Owner Broadcast Service.
 *
 * Mentorde'nin TUM customer company'lerine cross-tenant duyuru gonderim akisi.
 *
 *  compose()           : istek datasini PlatformBroadcast'a doker (draft).
 *  resolveRecipients() : target_segment + tiers + company_ids'e gore user listesi
 *                        (manager + senior rolleri — duyuru icin uygun seyirci).
 *  send()              : email/in_app/both kanalindan gerçek gonderim, sent_count
 *                        ve recipient kayitlarini günceller.
 *  sendImmediate()     : controller'in "Simdi Gonder" butonu icin wrapper.
 *  cancel()            : scheduled/draft broadcast iptal.
 *  trackOpen()         : email pikseli + in_app banner gosterimi tetiklenince.
 *  trackClick()        : CTA tiklamasi — clicked_at + clicked_count++.
 *
 * Tum mail/queue calismalari isolated: gonderim sirasinda istisna olursa
 * recipient 'failed' isaretlenir ama akış devam eder.
 */
class BroadcastService
{
    /**
     * Composer — request data'sini draft broadcast'a cevirir.
     */
    public function compose(array $data): PlatformBroadcast
    {
        return PlatformBroadcast::create([
            'title'              => (string) ($data['title'] ?? ''),
            'body'               => (string) ($data['body'] ?? ''),
            'channel'            => (string) ($data['channel'] ?? PlatformBroadcast::CHANNEL_BOTH),
            'target_segment'     => (string) ($data['target_segment'] ?? PlatformBroadcast::SEGMENT_ALL),
            'target_tiers'       => $this->normalizeArray($data['target_tiers']       ?? null),
            'target_company_ids' => $this->normalizeArray($data['target_company_ids'] ?? null),
            'scheduled_for'      => !empty($data['scheduled_for']) ? $data['scheduled_for'] : null,
            'cta_label'          => isset($data['cta_label']) && $data['cta_label'] !== '' ? $data['cta_label'] : null,
            'cta_url'            => isset($data['cta_url']) && $data['cta_url'] !== '' ? $data['cta_url'] : null,
            'status'             => !empty($data['scheduled_for'])
                ? PlatformBroadcast::STATUS_SCHEDULED
                : PlatformBroadcast::STATUS_DRAFT,
            'created_by_user_id' => $data['created_by_user_id'] ?? auth()->id(),
        ]);
    }

    /**
     * Target_segment + tier + company_ids'e gore alici user listesi.
     *
     * Donus: User collection — yalnizca aktif, in-app/email icin kullanilir.
     * Sadece manager + senior rolleri hedeflenir (customer panel kullanicilari).
     */
    public function resolveRecipients(PlatformBroadcast $b): Collection
    {
        // 1) Hedef company set'i
        $companyQuery = Company::query()->where('is_active', true);

        switch ($b->target_segment) {
            case PlatformBroadcast::SEGMENT_TRIAL:
                $companyQuery->where('subscription_tier', Company::TIER_TRIAL);
                break;
            case PlatformBroadcast::SEGMENT_PAID:
                $companyQuery->whereIn('subscription_tier', [
                    Company::TIER_BASIC, Company::TIER_GOLD, Company::TIER_PREMIUM,
                ]);
                break;
            case PlatformBroadcast::SEGMENT_SPECIFIC:
                $ids = $b->target_company_ids ?: [];
                if (empty($ids)) {
                    return collect();
                }
                $companyQuery->whereIn('id', $ids);
                break;
            case PlatformBroadcast::SEGMENT_ALL:
            default:
                // hicbir ek filtre yok
                break;
        }

        // 2) Ekstra tier filtresi (specific haric, hepsinin uzerine binebilir)
        if ($b->target_segment !== PlatformBroadcast::SEGMENT_SPECIFIC
            && is_array($b->target_tiers) && !empty($b->target_tiers)) {
            $companyQuery->whereIn('subscription_tier', $b->target_tiers);
        }

        $companyIds = $companyQuery->pluck('id')->all();
        if (empty($companyIds)) {
            return collect();
        }

        // 3) Bu company'lerdeki yetkili user'lar (manager + senior)
        return User::query()
            ->whereIn('company_id', $companyIds)
            ->whereIn('role', ['manager', 'senior'])
            ->whereNotNull('email')
            ->get();
    }

    /**
     * Gercek gonderim — email/in_app/both kanalindan.
     *
     * Recipient kayitlari atomic olusturulur, sonra her birine kanal mantigi
     * uygulanir. sent_count = basariyla gonderilen recipient sayisi.
     */
    public function send(PlatformBroadcast $b): int
    {
        // Idempotent: zaten gonderildiyse atla
        if ($b->status === PlatformBroadcast::STATUS_SENT) {
            return (int) $b->sent_count;
        }
        if ($b->status === PlatformBroadcast::STATUS_CANCELLED) {
            return 0;
        }

        $b->update(['status' => PlatformBroadcast::STATUS_SENDING]);

        $users = $this->resolveRecipients($b);
        if ($users->isEmpty()) {
            $b->update([
                'status'     => PlatformBroadcast::STATUS_SENT,
                'sent_at'    => now(),
                'sent_count' => 0,
            ]);
            return 0;
        }

        // Recipient kayitlarini olustur (varsa atla)
        DB::transaction(function () use ($b, $users): void {
            foreach ($users as $u) {
                PlatformBroadcastRecipient::firstOrCreate(
                    ['broadcast_id' => $b->id, 'user_id' => $u->id],
                    [
                        'company_id' => $u->company_id,
                        'status'     => PlatformBroadcastRecipient::STATUS_PENDING,
                    ],
                );
            }
        });

        $sentCount = 0;
        $channel   = $b->channel;

        $recipients = PlatformBroadcastRecipient::query()
            ->where('broadcast_id', $b->id)
            ->where('status', PlatformBroadcastRecipient::STATUS_PENDING)
            ->with('user')
            ->get();

        foreach ($recipients as $rec) {
            try {
                if ($channel === PlatformBroadcast::CHANNEL_EMAIL || $channel === PlatformBroadcast::CHANNEL_BOTH) {
                    $this->sendEmailFor($b, $rec);
                }
                // in_app: banner partial DB sorgusu ile listeden cekecek; ayrica
                // bir is yapilmasina gerek yok. Sadece kaydi 'sent' yapiyoruz.

                $rec->update([
                    'status'       => PlatformBroadcastRecipient::STATUS_SENT,
                    'delivered_at' => now(),
                ]);
                $sentCount++;
            } catch (Throwable $e) {
                Log::warning('broadcast.send_failed', [
                    'broadcast_id' => $b->id,
                    'recipient_id' => $rec->id,
                    'error'        => $e->getMessage(),
                ]);
                $rec->update([
                    'status' => PlatformBroadcastRecipient::STATUS_FAILED,
                    'error'  => mb_substr($e->getMessage(), 0, 1000),
                ]);
            }
        }

        $b->update([
            'status'     => PlatformBroadcast::STATUS_SENT,
            'sent_at'    => now(),
            'sent_count' => $sentCount,
        ]);

        return $sentCount;
    }

    /**
     * Controller "Simdi Gonder" tetigi.
     */
    public function sendImmediate(int $id): int
    {
        $b = PlatformBroadcast::findOrFail($id);
        return $this->send($b);
    }

    /**
     * Scheduled veya draft iptal.
     */
    public function cancel(int $id): bool
    {
        $b = PlatformBroadcast::find($id);
        if (!$b) return false;
        if (in_array($b->status, [PlatformBroadcast::STATUS_SENT, PlatformBroadcast::STATUS_CANCELLED], true)) {
            return false;
        }
        $b->update(['status' => PlatformBroadcast::STATUS_CANCELLED]);
        return true;
    }

    /**
     * Email open pixel + in_app banner gosterimi tetikledi.
     */
    public function trackOpen(int $recipientId): void
    {
        $rec = PlatformBroadcastRecipient::find($recipientId);
        if (!$rec || $rec->opened_at) return;

        DB::transaction(function () use ($rec): void {
            $rec->update([
                'opened_at' => now(),
                'status'    => $rec->status === PlatformBroadcastRecipient::STATUS_CLICKED
                    ? PlatformBroadcastRecipient::STATUS_CLICKED
                    : PlatformBroadcastRecipient::STATUS_OPENED,
            ]);
            PlatformBroadcast::where('id', $rec->broadcast_id)
                ->increment('opened_count');
        });
    }

    /**
     * CTA tiklamasi.
     */
    public function trackClick(int $recipientId): void
    {
        $rec = PlatformBroadcastRecipient::find($recipientId);
        if (!$rec || $rec->clicked_at) return;

        DB::transaction(function () use ($rec): void {
            $updates = [
                'clicked_at' => now(),
                'status'     => PlatformBroadcastRecipient::STATUS_CLICKED,
            ];
            if (!$rec->opened_at) {
                $updates['opened_at'] = now();
            }
            $rec->update($updates);

            PlatformBroadcast::where('id', $rec->broadcast_id)
                ->increment('clicked_count');

            // Open eksikse opened_count'ı da artir (tıklayan görmüştür)
            if (!$rec->opened_at) {
                PlatformBroadcast::where('id', $rec->broadcast_id)
                    ->increment('opened_count');
            }
        });
    }

    // ────────────────────────────────────────────────────────────────────────
    // Internal helpers
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Mail::raw ile basit gonderim — Markdown body raw text olarak.
     * Open pixel ve click tracking URL'leri body sonuna eklenir.
     */
    private function sendEmailFor(PlatformBroadcast $b, PlatformBroadcastRecipient $rec): void
    {
        $user = $rec->user;
        if (!$user || empty($user->email)) {
            throw new \RuntimeException('recipient has no email');
        }

        $openUrl  = URL::signedRoute('broadcast.track.open',  ['recipient_id' => $rec->id]);
        $clickUrl = $b->cta_url
            ? URL::signedRoute('broadcast.track.click', ['recipient_id' => $rec->id, 'url' => $b->cta_url])
            : null;

        // Basit Markdown render — # / **bold** vs. tek satirlik
        $bodyHtml = $this->markdownToHtml($b->body);

        $ctaHtml = '';
        if ($b->cta_label && $clickUrl) {
            $ctaHtml = sprintf(
                '<p style="margin:24px 0;"><a href="%s" style="display:inline-block;background:#7e58bf;color:#fff;padding:10px 22px;border-radius:8px;text-decoration:none;font-weight:600;">%s</a></p>',
                htmlspecialchars($clickUrl, ENT_QUOTES),
                htmlspecialchars($b->cta_label, ENT_QUOTES),
            );
        }

        $pixel = sprintf('<img src="%s" width="1" height="1" alt="" style="border:0;width:1px;height:1px;" />', htmlspecialchars($openUrl, ENT_QUOTES));

        $html = "<div style=\"font-family:-apple-system,Segoe UI,Helvetica,Arial,sans-serif;font-size:14px;color:#1a1730;line-height:1.55;max-width:600px;margin:0 auto;padding:20px;\">"
            . "<h2 style=\"color:#7e58bf;margin:0 0 16px;\">" . htmlspecialchars($b->title) . "</h2>"
            . $bodyHtml
            . $ctaHtml
            . "<hr style=\"border:none;border-top:1px solid #e2e2e8;margin:24px 0 12px;\">"
            . "<p style=\"font-size:11px;color:#a09bb5;margin:0;\">MentorDE Platform &middot; Bu bildirim Platform Ekibi tarafindan gonderilmistir.</p>"
            . $pixel
            . "</div>";

        Mail::html($html, function ($message) use ($user, $b): void {
            $message->to($user->email, $user->name)
                ->subject('[MentorDE] ' . $b->title);
        });
    }

    /**
     * Cok minimal Markdown → HTML donusturucu. Karmasik durumlar icin Str::markdown
     * varsa onu kullaniyoruz, yoksa basit fallback.
     */
    private function markdownToHtml(string $md): string
    {
        if (class_exists(\League\CommonMark\CommonMarkConverter::class)) {
            try {
                $conv = new \League\CommonMark\CommonMarkConverter([
                    'html_input'         => 'escape',
                    'allow_unsafe_links' => false,
                ]);
                return (string) $conv->convert($md);
            } catch (Throwable $e) {
                // fallback'e dus
            }
        }

        // Basit fallback: paragraf ve link
        $safe = htmlspecialchars($md);
        $safe = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $safe);
        $safe = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $safe);
        $safe = preg_replace('#\[([^\]]+)\]\((https?://[^\)]+)\)#', '<a href="$2" style="color:#7e58bf;">$1</a>', $safe);
        $paragraphs = preg_split('/\n{2,}/', trim($safe));
        return implode("\n", array_map(fn ($p) => '<p>' . nl2br($p) . '</p>', $paragraphs));
    }

    /**
     * @return array<int, string>|null
     */
    private function normalizeArray(mixed $val): ?array
    {
        if ($val === null || $val === '') return null;
        if (is_string($val)) {
            $decoded = json_decode($val, true);
            if (is_array($decoded)) return $decoded;
            return [$val];
        }
        if (is_array($val)) {
            $clean = array_values(array_filter($val, fn ($v) => $v !== null && $v !== ''));
            return $clean === [] ? null : $clean;
        }
        return null;
    }
}
