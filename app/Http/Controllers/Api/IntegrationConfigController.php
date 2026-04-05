<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IntegrationConfig;
use App\Services\Integrations\IntegrationFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class IntegrationConfigController extends Controller
{
    private const DEFAULTS = [
        'calendar' => ['providers' => ['calendly', 'google_calendar', 'cal_com'], 'active_provider' => 'calendly'],
        'email_marketing' => ['providers' => ['zoho', 'mailchimp', 'sendgrid'], 'active_provider' => 'zoho'],
        'crm' => ['providers' => ['clickup', 'monday', 'notion'], 'active_provider' => 'clickup'],
        'esign' => ['providers' => ['docusign', 'pandadoc', 'hellosign'], 'active_provider' => 'docusign'],
        'video' => ['providers' => ['zoom', 'google_meet', 'teams'], 'active_provider' => 'zoom'],
        'ai' => ['providers' => ['claude', 'gpt'], 'active_provider' => 'claude'],
    ];

    public function index()
    {
        $rows = collect(self::DEFAULTS)->map(function (array $meta, string $category) {
            $row = IntegrationConfig::firstOrCreate(
                ['category' => $category],
                [
                    'active_provider' => $meta['active_provider'],
                    'providers' => $meta['providers'],
                    'is_enabled' => false,
                    'status' => 'disconnected',
                ]
            );

            return [
                'category' => $row->category,
                'active_provider' => $row->active_provider,
                'providers' => $row->providers ?? $meta['providers'],
                'is_enabled' => (bool) $row->is_enabled,
                'status' => $row->status,
                'last_sync_at' => optional($row->last_sync_at)?->toIso8601String(),
            ];
        })->values();

        return response()->json($rows);
    }

    public function upsert(Request $request, string $category)
    {
        abort_unless(array_key_exists($category, self::DEFAULTS), Response::HTTP_NOT_FOUND, 'Bilinmeyen kategori');

        $data = $request->validate([
            'active_provider' => ['required', 'string', 'max:64'],
            'is_enabled' => ['nullable', 'boolean'],
            'status' => ['nullable', 'in:active,error,disconnected'],
        ]);

        $allowed = self::DEFAULTS[$category]['providers'];
        abort_unless(in_array($data['active_provider'], $allowed, true), Response::HTTP_UNPROCESSABLE_ENTITY, 'Gecersiz provider');

        $row = IntegrationConfig::updateOrCreate(
            ['category' => $category],
            [
                'active_provider' => $data['active_provider'],
                'providers' => $allowed,
                'is_enabled' => (bool) ($data['is_enabled'] ?? false),
                'status' => $data['status'] ?? ((bool) ($data['is_enabled'] ?? false) ? 'active' : 'disconnected'),
                'last_sync_at' => now(),
                'updated_by' => (string) optional($request->user())->email,
            ]
        );

        return response()->json($row->fresh());
    }

    public function testConnection(string $category, IntegrationFactory $factory)
    {
        abort_unless(array_key_exists($category, self::DEFAULTS), Response::HTTP_NOT_FOUND, 'Bilinmeyen kategori');

        try {
            $provider = IntegrationConfig::query()
                ->where('category', $category)
                ->value('active_provider');

            $meta = match ($category) {
                'calendar' => ['service' => get_class($factory->getCalendarService())],
                'email_marketing' => ['service' => get_class($factory->getEmailMarketingService())],
                'crm' => ['service' => get_class($factory->getProjectManagementService())],
                'esign' => ['service' => get_class($factory->getElectronicSignatureService())],
                'video' => ['service' => get_class($factory->getVideoConferenceService())],
                default => ['service' => 'config-only'],
            };

            return response()->json([
                'ok' => true,
                'category' => $category,
                'provider' => (string) ($provider ?? ''),
                'meta' => $meta,
                'tested_at' => now()->toIso8601String(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'ok' => false,
                'category' => $category,
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
