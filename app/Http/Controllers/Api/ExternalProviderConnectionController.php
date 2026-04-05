<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExternalProviderConnection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ExternalProviderConnectionController extends Controller
{
    public function index(Request $request)
    {
        $provider = trim((string) $request->query('provider', ''));
        $status = trim((string) $request->query('status', ''));

        return ExternalProviderConnection::query()
            ->when($provider !== '', fn ($q) => $q->where('provider', $provider))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest()
            ->limit(200)
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'provider' => ['required', 'string', 'in:meta_ads,ga4,google_ads,calendly,mailchimp,clickup'],
            'account_label' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'in:draft,connected,error,paused'],
            'oauth_client_id' => ['nullable', 'string', 'max:255'],
            'scopes' => ['nullable', 'string', 'max:5000'],
            'last_error' => ['nullable', 'string', 'max:400'],
            'meta' => ['nullable', 'array'],
        ]);

        $row = ExternalProviderConnection::query()->create([
            'provider' => (string) $data['provider'],
            'account_label' => trim((string) ($data['account_label'] ?? '')) ?: null,
            'status' => (string) ($data['status'] ?? 'draft'),
            'oauth_client_id' => trim((string) ($data['oauth_client_id'] ?? '')) ?: null,
            'scopes' => trim((string) ($data['scopes'] ?? '')) ?: null,
            'last_error' => trim((string) ($data['last_error'] ?? '')) ?: null,
            'meta' => $data['meta'] ?? null,
        ]);

        return response()->json($row, Response::HTTP_CREATED);
    }

    public function update(Request $request, ExternalProviderConnection $externalProviderConnection)
    {
        $data = $request->validate([
            'account_label' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'in:draft,connected,error,paused'],
            'oauth_client_id' => ['nullable', 'string', 'max:255'],
            'scopes' => ['nullable', 'string', 'max:5000'],
            'last_error' => ['nullable', 'string', 'max:400'],
            'meta' => ['nullable', 'array'],
            'touch_sync' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'account_label' => array_key_exists('account_label', $data) ? (trim((string) ($data['account_label'] ?? '')) ?: null) : $externalProviderConnection->account_label,
            'status' => array_key_exists('status', $data) ? (string) $data['status'] : $externalProviderConnection->status,
            'oauth_client_id' => array_key_exists('oauth_client_id', $data) ? (trim((string) ($data['oauth_client_id'] ?? '')) ?: null) : $externalProviderConnection->oauth_client_id,
            'scopes' => array_key_exists('scopes', $data) ? (trim((string) ($data['scopes'] ?? '')) ?: null) : $externalProviderConnection->scopes,
            'last_error' => array_key_exists('last_error', $data) ? (trim((string) ($data['last_error'] ?? '')) ?: null) : $externalProviderConnection->last_error,
            'meta' => array_key_exists('meta', $data) ? $data['meta'] : $externalProviderConnection->meta,
        ];
        if (!empty($data['touch_sync'])) {
            $payload['last_sync_at'] = now();
        }

        $externalProviderConnection->update($payload);
        return response()->json($externalProviderConnection->fresh());
    }
}

