<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public landing /promo/{code} — kuponun paylaşıma uygun güzel kart sayfası.
 * Auth gerekmez; manager bu URL'i WhatsApp/Insta/mail'a yapıştırır.
 *
 * Davranış:
 *  - Kod bulunamaz/expired/inactive → expired view (404 değil — "kaçırdın" hissi
 *    daha iyi, bu sayfa sosyal medyada paylaşıldığı için broken link gibi görünmesin)
 *  - Bulundu ve aktif → seçili template ile render
 */
class PromoController extends Controller
{
    public function show(string $code, Request $request): View
    {
        $codeUpper = strtoupper(trim($code));

        $discount = DiscountCode::query()
            ->whereRaw('UPPER(code) = ?', [$codeUpper])
            ->first();

        if (! $discount || ! $discount->isCurrentlyActive()) {
            // PostHog: süresi geçmiş veya geçersiz kod görüntüleme — pazarlama
            // ekibi hangi expired kodun hala paylaşıldığını bilsin
            $this->safeCapture('discount_code_landing_expired_viewed', [
                'code'    => $codeUpper,
                'found'   => (bool) $discount,
                'active'  => $discount?->isCurrentlyActive() ?? false,
                'referer' => $request->headers->get('referer'),
            ], $request);

            return view('promo.expired', [
                'code' => $discount?->code ?? $codeUpper,
            ]);
        }

        $tplId = $discount->effectiveTemplateId();

        // PostHog: başarılı landing görüntüleme — share funnel metrik
        $this->safeCapture('discount_code_landing_viewed', [
            'code'        => $discount->code,
            'discount_id' => $discount->id,
            'template_id' => $tplId,
            'discount_type'  => $discount->discount_type ?? null,
            'discount_value' => $discount->discount_value ?? null,
            'referer'     => $request->headers->get('referer'),
            'utm_source'  => $request->query('utm_source'),
            'utm_medium'  => $request->query('utm_medium'),
            'utm_campaign'=> $request->query('utm_campaign'),
        ], $request);

        // Default metinler (boşsa template-default kullan)
        $title       = $discount->landing_title ?: $this->defaultTitle($tplId, $discount);
        $subtitle    = $discount->landing_subtitle ?: $this->defaultSubtitle($tplId, $discount);
        $ctaText     = $discount->landing_cta_text ?: 'Hemen Başvur';
        $disclaimer  = $discount->landing_disclaimer ?: 'Kupon kullanım koşulları geçerlidir. Tek kullanım, son kullanma tarihiyle sınırlıdır.';

        return view('promo.show', [
            'code'        => $discount,
            'templateId'  => $tplId,
            'title'       => $title,
            'subtitle'    => $subtitle,
            'ctaText'     => $ctaText,
            'disclaimer'  => $disclaimer,
            'discountText'=> $discount->discountText(),
            'applyUrl'    => url('/apply'),
        ]);
    }

    private function defaultTitle(int $tplId, DiscountCode $code): string
    {
        return match ($tplId) {
            2 => 'Sana Özel Sürpriz! 🎉',
            3 => 'Ayrıcalıklı Davetiye',
            4 => 'Almanya\'ya uçuşa hazır mısın? ✈️',
            5 => 'Kaçırma — Sınırlı Süre!',
            default => 'Sana Özel İndirim',
        };
    }

    private function defaultSubtitle(int $tplId, DiscountCode $code): string
    {
        return match ($tplId) {
            2 => 'Aşağıdaki kuponu kullan, hizmet paketinde indirimden yararlan.',
            3 => 'Sınırlı sayıda öğrenciye özel hazırlandı. Kodunu kullan, sürecini başlat.',
            4 => 'Bu kuponla yolculuğun daha hesaplı başlasın. Hadi gel, birlikte planlayalım.',
            5 => 'Bu fırsat sınırlı sayıda — kaçırma! Aşağıdaki kupon kodu ile başvurunu yap.',
            default => 'Almanya\'da öğrencilik hayalini başlatmak için bu kuponu kullan.',
        };
    }

    /**
     * PostHog event yayını — analytics service hata verirse landing sayfası
     * etkilenmesin (try/catch + null distinctId fallback session-bazlı).
     */
    private function safeCapture(string $event, array $properties, Request $request): void
    {
        try {
            $distinctId = $request->user()?->id
                ?? ($request->cookie('ph_distinct_id')
                    ?: ('anon_' . substr(sha1($request->ip() . $request->userAgent()), 0, 12)));

            app(AnalyticsService::class)->capture($event, $properties, $distinctId);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Promo landing analytics capture failed', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
