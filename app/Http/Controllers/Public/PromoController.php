<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
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
    public function show(string $code): View
    {
        $code = strtoupper(trim($code));

        $discount = DiscountCode::query()
            ->whereRaw('UPPER(code) = ?', [$code])
            ->first();

        if (! $discount || ! $discount->isCurrentlyActive()) {
            return view('promo.expired', [
                'code' => $discount?->code ?? $code,
            ]);
        }

        $tplId = $discount->effectiveTemplateId();

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
}
