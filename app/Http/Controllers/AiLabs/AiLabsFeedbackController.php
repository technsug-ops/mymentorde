<?php

namespace App\Http\Controllers\AiLabs;

use App\Http\Controllers\Controller;
use App\Models\AiLabsFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AI Labs — kullanıcı feedback (👍 / 👎).
 * Tüm rolleri destekler: guest, student, senior, manager, admin_staff.
 *
 * Route: POST /ai-labs/feedback (public-authenticated endpoint)
 */
class AiLabsFeedbackController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'conversation_type' => 'required|in:guest,senior,staff',
            'conversation_id'   => 'required|integer|min:1',
            'rating'            => 'required|in:good,bad',
            'reason'            => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $companyId = (int) ($user?->company_id ?? app('current_company_id') ?? 0);
        if ($companyId === 0) {
            return response()->json(['ok' => false, 'error' => 'no_company'], 400);
        }

        $guestAppId = null;
        $role = (string) ($user?->role ?? '');

        // Guest rolü ise guest_application_id'yi session/route'tan al
        if ($data['conversation_type'] === 'guest') {
            $guest = app(\App\Services\GuestResolverService::class)->resolve($request);
            if ($guest) {
                $guestAppId = (int) $guest->id;
                if ($role === '') $role = 'guest';
            }
        }

        AiLabsFeedback::updateOrCreate(
            [
                'conversation_type'    => $data['conversation_type'],
                'conversation_id'      => $data['conversation_id'],
                'user_id'              => $user?->id,
                'guest_application_id' => $guestAppId,
            ],
            [
                'company_id' => $companyId,
                'rating'     => $data['rating'],
                'reason'     => $data['reason'] ?? null,
                'role'       => $role ?: null,
            ]
        );

        return response()->json(['ok' => true, 'message' => 'Geri bildirim kaydedildi.']);
    }
}
