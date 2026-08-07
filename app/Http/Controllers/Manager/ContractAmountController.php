<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\GuestApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Sözleşme tutarı — finansın saydığı TEK rakam.
 *
 * ── NEDEN GEREKLİ ───────────────────────────────────────────────────────
 * `contract_amount_eur` kolonu vardı ve finans ekranı onu topluyordu, ama
 * uygulamada onu YAZAN hiçbir kod yoktu — finans rakamları boş geliyordu.
 * Paket fiyatı ayrı bir alanda metin olarak duruyor ("2.000 EUR"); pazarlıkla
 * değişen gerçek tutarın yeri yoktu.
 *
 * Akış: paket fiyatı başlangıç değeri → sözleşme aşamasında elle düzeltilir
 * → SABİTLENİR → finans yalnızca sabitlenmiş tutarı sayar.
 *
 * ── NEDEN SABİTLEME AYRI ADIM ───────────────────────────────────────────
 * Tutar bir PARA kararı. Sabitlenmemiş bir rakamın finansa girmesi, henüz
 * anlaşılmamış bir fiyatın ciroya yazılması demek olurdu. Sabitleme kimin ne
 * zaman yaptığını da kaydediyor; sonradan mutlaka sorulur.
 *
 * Sabitlenmiş tutarı değiştirmek AYRI bir işlem (çözme) — yanlışlıkla
 * değiştirilmesi zor olsun diye.
 */
class ContractAmountController extends Controller
{
    private function companyId(): int
    {
        return app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
    }

    private function guest(int $guestId): GuestApplication
    {
        $cid = $this->companyId();

        return GuestApplication::query()
            ->when($cid > 0, fn ($b) => $b->where('company_id', $cid))
            ->whereKey($guestId)
            ->firstOrFail();
    }

    /** Tutarı kaydet ve sabitle. */
    public function store(Request $request, int $guest): RedirectResponse
    {
        $application = $this->guest($guest);

        if ($application->contract_amount_locked_at !== null) {
            return back()->withErrors([
                'contract_amount' => 'Tutar sabitlenmiş. Değiştirmek için önce sabitlemeyi kaldırın.',
            ]);
        }

        $data = $request->validate([
            'contract_amount_eur' => ['required', 'numeric', 'min:0', 'max:999999'],
            'contract_amount_note' => ['nullable', 'string', 'max:500'],
        ], [
            'contract_amount_eur.required' => 'Tutar zorunlu.',
            'contract_amount_eur.numeric'  => 'Tutar sayı olmalı (örn. 2000 veya 2000.50).',
        ]);

        $application->forceFill([
            'contract_amount_eur'       => (float) $data['contract_amount_eur'],
            'contract_amount_note'      => $data['contract_amount_note'] ?? null,
            'contract_amount_locked_at' => now(),
            'contract_amount_set_by'    => (string) ($request->user()?->email ?? ''),
        ])->save();

        return back()->with('status', sprintf(
            'Sözleşme tutarı %s EUR olarak sabitlendi. Finans bu rakamı kullanacak.',
            number_format((float) $data['contract_amount_eur'], 2, ',', '.')
        ));
    }

    /**
     * Sabitlemeyi kaldır — tutar yeniden düzenlenebilir olur.
     *
     * Tutarın KENDİSİ silinmiyor: yanlışlıkla çözülürse rakam kaybolmasın.
     * Yalnızca kilit kalkıyor ve finans onu saymayı bırakıyor.
     */
    public function unlock(Request $request, int $guest): RedirectResponse
    {
        $application = $this->guest($guest);

        $application->forceFill([
            'contract_amount_locked_at' => null,
            'contract_amount_set_by'    => (string) ($request->user()?->email ?? ''),
        ])->save();

        return back()->with('status',
            'Sabitleme kaldırıldı — tutar yeniden düzenlenebilir. Finans bu kaydı şu an saymıyor.'
        );
    }
}
