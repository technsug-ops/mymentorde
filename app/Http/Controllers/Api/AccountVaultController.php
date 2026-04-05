<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountAccessLog;
use App\Models\AccountVault;
use App\Services\AccountVaultService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AccountVaultController extends Controller
{
    public function index(Request $request)
    {
        $studentId = (string) $request->query('student_id', '');

        return AccountVault::query()
            ->when($studentId !== '', fn ($q) => $q->where('student_id', $studentId))
            ->latest()
            ->limit(100)
            ->get()
            ->map(function (AccountVault $row): array {
                return [
                    'id' => $row->id,
                    'student_id' => $row->student_id,
                    'service_name' => $row->service_name,
                    'service_label' => $row->service_label,
                    'account_url' => $row->account_url,
                    'account_email' => $row->account_email,
                    'account_username' => $row->account_username,
                    'application_id' => $row->application_id,
                    'notes' => $row->notes,
                    'status' => $row->status,
                    'created_by' => $row->created_by,
                    'created_at' => optional($row->created_at)?->toDateTimeString(),
                ];
            });
    }

    public function store(Request $request, AccountVaultService $service)
    {
        $data = $request->validate([
            'student_id' => ['required', 'string', 'max:64'],
            'service_name' => ['required', 'string', 'max:64'],
            'service_label' => ['required', 'string', 'max:255'],
            'account_url' => ['nullable', 'string', 'max:500'],
            'account_email' => ['required', 'email', 'max:255'],
            'account_username' => ['nullable', 'string', 'max:255'],
            'account_password' => ['required', 'string', 'min:4'],
            'application_id' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'in:active,inactive,expired'],
        ]);

        $row = $service->create($data, $request);

        return response()->json(['id' => $row->id], Response::HTTP_CREATED);
    }

    public function reveal(AccountVault $accountVault, Request $request, AccountVaultService $service)
    {
        return response()->json([
            'id' => $accountVault->id,
            'password' => $service->revealPassword($accountVault, $request),
        ]);
    }

    public function update(AccountVault $accountVault, Request $request, AccountVaultService $service)
    {
        $data = $request->validate([
            'service_name' => ['sometimes', 'required', 'string', 'max:64'],
            'service_label' => ['sometimes', 'required', 'string', 'max:255'],
            'account_url' => ['nullable', 'string', 'max:500'],
            'account_email' => ['sometimes', 'required', 'email', 'max:255'],
            'account_username' => ['nullable', 'string', 'max:255'],
            'account_password' => ['nullable', 'string', 'min:4'],
            'application_id' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'in:active,inactive,expired'],
        ]);

        return response()->json($service->update($accountVault, $data, $request));
    }

    public function destroy(AccountVault $accountVault, Request $request, AccountVaultService $service)
    {
        $service->delete($accountVault, $request);
        return response()->json(['ok' => true]);
    }

    public function logs(Request $request)
    {
        $studentId = (string) $request->query('student_id', '');

        return AccountAccessLog::query()
            ->when($studentId !== '', fn ($q) => $q->where('student_id', $studentId))
            ->latest('accessed_at')
            ->limit(100)
            ->get();
    }
}
