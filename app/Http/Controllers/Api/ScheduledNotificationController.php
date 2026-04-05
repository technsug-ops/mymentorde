<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScheduledNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * K3 Notification — Zamanlanmış bildirim yönetimi (Manager API).
 */
class ScheduledNotificationController extends Controller
{
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;

        $items = ScheduledNotification::query()
            ->when($companyId > 0, fn ($q) => $q->where(
                fn ($q2) => $q2->whereNull('company_id')->orWhere('company_id', $companyId)
            ))
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['ok' => true, 'data' => $items]);
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'name'              => 'required|string|max:191',
            'channel'           => 'required|in:email,in_app,whatsapp',
            'category'          => 'nullable|string|max:64',
            'subject'           => 'nullable|string|max:191',
            'body_template'     => 'required|string',
            'target_role'       => 'nullable|string|max:64',
            'target_email'      => 'nullable|email|max:191',
            'schedule_type'     => 'required|in:once,daily,weekly,monthly',
            'send_at'           => 'nullable|date',
            'recurrence_time'   => 'nullable|string|max:8',
            'recurrence_day'    => 'nullable|integer|min:1|max:31',
            'recurrence_until'  => 'nullable|date',
        ]);

        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : null;
        $user = Auth::user();

        $sn = ScheduledNotification::create(array_merge($data, [
            'company_id'        => $companyId,
            'is_active'         => true,
            'created_by_email'  => $user?->email,
        ]));

        return response()->json(['ok' => true, 'data' => $sn], 201);
    }

    public function update(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $sn   = ScheduledNotification::findOrFail($id);
        $data = $request->validate([
            'name'             => 'sometimes|string|max:191',
            'channel'          => 'sometimes|in:email,in_app,whatsapp',
            'subject'          => 'nullable|string|max:191',
            'body_template'    => 'sometimes|string',
            'target_role'      => 'nullable|string|max:64',
            'target_email'     => 'nullable|email|max:191',
            'schedule_type'    => 'sometimes|in:once,daily,weekly,monthly',
            'send_at'          => 'nullable|date',
            'recurrence_time'  => 'nullable|string|max:8',
            'recurrence_day'   => 'nullable|integer|min:1|max:31',
            'recurrence_until' => 'nullable|date',
            'is_active'        => 'boolean',
        ]);

        $sn->update($data);

        return response()->json(['ok' => true, 'data' => $sn->fresh()]);
    }

    public function destroy(int $id): \Illuminate\Http\JsonResponse
    {
        ScheduledNotification::findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }
}
