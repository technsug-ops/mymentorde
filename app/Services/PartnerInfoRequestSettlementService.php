<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentUploadToken;
use App\Models\PartnerInfoRequestItem;
use Illuminate\Support\Facades\Log;

/**
 * Belge yüklendiğinde talep kalemini kapatır.
 *
 * ── KOPUK HALKA ─────────────────────────────────────────────────────────
 * Zincir üç adımdı: operasyon partnerden belge ister → partner kalemi
 * öğrencisine iletir (bir yükleme jetonu üretilir) → öğrenci yükler.
 *
 * Üçüncü adım ikinciyle konuşmuyordu. `forwarded_token_id` yazılıyordu ama
 * yükleme tarafında kimse geri bakmıyordu; belge geliyor, kalem "bekliyor"
 * kalıyor ve partnerin elle "sağlandı" işaretlemesi gerekiyordu. Unutulduğu
 * anda operasyon tarafı bekleyen bir talep görüyor, oysa belge gelmişti.
 *
 * ── NEDEN JETON ÜZERİNDEN ───────────────────────────────────────────────
 * Eşleşme jeton kimliğiyle kuruluyor: kesin. "Aynı öğrencinin aynı
 * kategoride bir belgesi var" gibi bir çıkarımla kapatılsaydı, öğrencinin
 * eskiden yüklediği alakasız bir belge kalemi yanlışlıkla kapatabilirdi —
 * ve bu sessiz olurdu.
 *
 * ⚠ ADDON: yüklemeyi ASLA bozmamalı. Öğrenci belgesini yüklemiş olur;
 * arka plandaki muhasebe patladı diye ona hata göstermek kabul edilemez.
 * Çağıran taraf da try/catch içinde çağırıyor, burada da yutuluyor.
 */
class PartnerInfoRequestSettlementService
{
    /**
     * Bu jetonla açılmış bekleyen kalemi "sağlandı" yap.
     *
     * @return bool  Kapatıldıysa true.
     */
    public function settleFromToken(DocumentUploadToken $token, Document $document): bool
    {
        try {
            // ⚠ Kapsamsız: kalem talebi AÇAN firmanın kutusunda olabilir,
            // yükleme ise jetonun firması bağlamında işleniyor. Sınır zaten
            // jeton kimliği — tahmin edilemez bir değer.
            $item = PartnerInfoRequestItem::query()
                ->where('forwarded_token_id', $token->id)
                ->where('status', PartnerInfoRequestItem::STATUS_PENDING)
                ->first();

            if (! $item) {
                return false;
            }

            $item->update([
                'status'      => PartnerInfoRequestItem::STATUS_PROVIDED,
                'document_id' => $document->id,
                'provided_by' => 'ogrenci_yuklemesi',
                'provided_at' => now(),
            ]);

            // Başlık durumunu elle bırakmak "hepsi geldi ama talep hâlâ açık"
            // tutarsızlığını üretirdi.
            $item->request?->refreshStatus();

            return true;
        } catch (\Throwable $e) {
            Log::warning('partner_info_request.settle_failed', [
                'token_id'    => $token->id,
                'document_id' => $document->id,
                'error'       => $e->getMessage(),
            ]);

            return false;
        }
    }
}
