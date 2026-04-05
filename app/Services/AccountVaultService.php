<?php

namespace App\Services;

use App\Models\AccountAccessLog;
use App\Models\AccountVault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class AccountVaultService
{
    public function create(array $data, Request $request): AccountVault
    {
        $row = AccountVault::create([
            'student_id' => $data['student_id'],
            'service_name' => $data['service_name'],
            'service_label' => $data['service_label'],
            'account_url' => $data['account_url'] ?? null,
            'account_email' => $data['account_email'],
            'account_username' => $data['account_username'] ?? null,
            'account_password_encrypted' => Crypt::encryptString($data['account_password']),
            'application_id' => $data['application_id'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? 'active',
            'is_visible_to_student' => (bool) ($data['is_visible_to_student'] ?? false),
            'created_by' => (string) optional($request->user())->email,
        ]);

        $this->log($row, 'create', $request);

        return $row;
    }

    public function revealPassword(AccountVault $row, Request $request): string
    {
        $this->log($row, 'view', $request);
        return Crypt::decryptString($row->account_password_encrypted);
    }

    public function update(AccountVault $row, array $data, Request $request): AccountVault
    {
        $payload = [
            'service_name' => $data['service_name'] ?? $row->service_name,
            'service_label' => $data['service_label'] ?? $row->service_label,
            'account_url' => $data['account_url'] ?? $row->account_url,
            'account_email' => $data['account_email'] ?? $row->account_email,
            'account_username' => $data['account_username'] ?? $row->account_username,
            'application_id' => $data['application_id'] ?? $row->application_id,
            'notes' => $data['notes'] ?? $row->notes,
            'status' => $data['status'] ?? $row->status,
        ];

        if (isset($data['account_password']) && $data['account_password'] !== '') {
            $payload['account_password_encrypted'] = Crypt::encryptString($data['account_password']);
        }

        $row->update($payload);
        $this->log($row, 'edit', $request);

        return $row->fresh();
    }

    public function delete(AccountVault $row, Request $request): void
    {
        $this->log($row, 'delete', $request);
        $row->delete();
    }

    private function log(AccountVault $row, string $type, Request $request): void
    {
        AccountAccessLog::create([
            'account_id' => $row->id,
            'student_id' => $row->student_id,
            'accessed_by' => (string) optional($request->user())->email,
            'access_type' => $type,
            'ip_address' => (string) $request->ip(),
            'accessed_at' => now(),
        ]);
    }
}
