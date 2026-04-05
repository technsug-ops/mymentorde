<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuestApplication;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    /**
     * GET /api/v1/profile/notification-preferences
     * Giriş yapmış kullanıcının bildirim kanalı tercihlerini döner.
     * Şu an guest_applications üzerindeki notify_* alanları.
     */
    public function show(Request $request)
    {
        $guest = $this->resolveGuest($request);

        if ($guest === null) {
            return response()->json(['ok' => false, 'message' => 'Profil bulunamadı.'], 404);
        }

        return response()->json([
            'ok' => true,
            'preferences' => [
                'notify_email'    => (bool) $guest->notify_email,
                'notify_whatsapp' => (bool) $guest->notify_whatsapp,
                'notify_inapp'    => (bool) $guest->notify_inapp,
            ],
        ]);
    }

    /**
     * PUT /api/v1/profile/notification-preferences
     * Bildirim kanalı tercihlerini günceller.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'notify_email'    => ['sometimes', 'boolean'],
            'notify_whatsapp' => ['sometimes', 'boolean'],
            'notify_inapp'    => ['sometimes', 'boolean'],
        ]);

        if (empty($data)) {
            return response()->json(['ok' => false, 'message' => 'Güncellenecek alan gönderilmedi.'], 422);
        }

        $guest = $this->resolveGuest($request);

        if ($guest === null) {
            return response()->json(['ok' => false, 'message' => 'Profil bulunamadı.'], 404);
        }

        // At least one channel must remain enabled
        $afterEmail    = $data['notify_email']    ?? (bool) $guest->notify_email;
        $afterWhatsapp = $data['notify_whatsapp'] ?? (bool) $guest->notify_whatsapp;
        $afterInapp    = $data['notify_inapp']    ?? (bool) $guest->notify_inapp;

        if (!$afterEmail && !$afterWhatsapp && !$afterInapp) {
            return response()->json([
                'ok'      => false,
                'message' => 'En az bir bildirim kanalı aktif olmalı.',
            ], 422);
        }

        $guest->forceFill($data)->save();

        return response()->json([
            'ok' => true,
            'preferences' => [
                'notify_email'    => (bool) $guest->notify_email,
                'notify_whatsapp' => (bool) $guest->notify_whatsapp,
                'notify_inapp'    => (bool) $guest->notify_inapp,
            ],
        ]);
    }

    private function resolveGuest(Request $request): ?GuestApplication
    {
        $userId = (int) optional($request->user())->id;
        if ($userId <= 0) {
            return null;
        }

        // Guest: kendi başvurusu (user_id veya email eşleşmesi)
        $email = strtolower((string) ($request->user()?->email ?? ''));
        $cid   = (int) ($request->user()?->company_id ?? 0);

        return GuestApplication::query()
            ->when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->where(fn ($q) => $q->where('user_id', $userId)->orWhere('email', $email))
            ->first();
    }
}
