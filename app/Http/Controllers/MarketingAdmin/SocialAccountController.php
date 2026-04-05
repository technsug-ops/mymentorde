<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\Marketing\SocialMediaAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class SocialAccountController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'platform' => (string) $request->query('platform', 'all'),
            'active' => (string) $request->query('active', 'all'),
        ];

        $query = SocialMediaAccount::query()->orderByDesc('id');
        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->where(function ($w) use ($q): void {
                $w->where('account_name', 'like', "%{$q}%")
                    ->orWhere('account_url', 'like', "%{$q}%");
            });
        }
        if ($filters['platform'] !== 'all') {
            $query->where('platform', $filters['platform']);
        }
        if ($filters['active'] === 'active') {
            $query->where('is_active', true);
        } elseif ($filters['active'] === 'inactive') {
            $query->where('is_active', false);
        }

        $rows = $query->paginate(20)->withQueryString();
        $editId = (int) $request->query('edit_id', 0);
        $editing = $editId > 0 ? SocialMediaAccount::query()->find($editId) : null;

        return view('marketing-admin.social.accounts', [
            'pageTitle' => 'Sosyal Hesaplar',
            'title' => 'Platform Hesaplari',
            'rows' => $rows,
            'editing' => $editing,
            'filters' => $filters,
            'platformOptions' => $this->platformOptions(),
            'stats' => [
                'total' => SocialMediaAccount::query()->count(),
                'active' => SocialMediaAccount::query()->where('is_active', true)->count(),
                'connected' => SocialMediaAccount::query()->where('api_connected', true)->count(),
                'followers' => (int) SocialMediaAccount::query()->sum('followers'),
            ],
        ]);
    }

    public function create()
    {
        return redirect('/mktg-admin/social/accounts');
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request, true);
        $row = SocialMediaAccount::query()->create([
            'platform' => strtolower((string) $data['platform']),
            'account_name' => $data['account_name'],
            'account_url' => $data['account_url'],
            'profile_image_url' => $data['profile_image_url'] ?? null,
            'followers' => (int) ($data['followers'] ?? 0),
            'followers_growth_this_month' => (int) ($data['followers_growth_this_month'] ?? 0),
            'total_posts' => (int) ($data['total_posts'] ?? 0),
            'metrics_last_updated_at' => $data['metrics_last_updated_at'] ?? null,
            'api_connected' => $request->boolean('api_connected', false),
            'api_access_token' => $data['api_access_token'] ?? null,
            'api_token_expires_at' => $data['api_token_expires_at'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->responseFor($request, ['ok' => true, 'id' => $row->id], 'Sosyal hesap eklendi.', Response::HTTP_CREATED);
    }

    public function show(string $id)
    {
        return redirect('/mktg-admin/social/accounts?edit_id='.$id);
    }

    public function edit(string $id)
    {
        return redirect('/mktg-admin/social/accounts?edit_id='.$id);
    }

    public function update(Request $request, string $id)
    {
        $row = SocialMediaAccount::query()->findOrFail($id);
        $data = $this->validatePayload($request, false, $row->id);

        $payload = array_filter([
            'platform' => isset($data['platform']) ? strtolower((string) $data['platform']) : null,
            'account_name' => $data['account_name'] ?? null,
            'account_url' => $data['account_url'] ?? null,
            'profile_image_url' => $data['profile_image_url'] ?? null,
            'followers' => $data['followers'] ?? null,
            'followers_growth_this_month' => $data['followers_growth_this_month'] ?? null,
            'total_posts' => $data['total_posts'] ?? null,
            'metrics_last_updated_at' => $data['metrics_last_updated_at'] ?? null,
            'api_access_token' => $data['api_access_token'] ?? null,
            'api_token_expires_at' => $data['api_token_expires_at'] ?? null,
        ], fn ($v) => $v !== null);

        if ($request->has('api_connected')) {
            $payload['api_connected'] = $request->boolean('api_connected');
        }
        if ($request->has('is_active')) {
            $payload['is_active'] = $request->boolean('is_active');
        }
        if ($payload !== []) {
            $row->update($payload);
        }

        return $this->responseFor($request, ['ok' => true, 'id' => $id], 'Sosyal hesap guncellendi.');
    }

    public function destroy(Request $request, string $id)
    {
        $row = SocialMediaAccount::query()->findOrFail($id);
        $row->delete();

        return $this->responseFor($request, ['ok' => true, 'id' => $id], 'Sosyal hesap silindi.');
    }

    private function validatePayload(Request $request, bool $isCreate, ?int $currentId = null): array
    {
        return $request->validate([
            'platform' => [$isCreate ? 'required' : 'sometimes', Rule::in($this->platformOptions())],
            'account_name' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:190'],
            'account_url' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:500'],
            'profile_image_url' => ['nullable', 'string', 'max:500'],
            'followers' => ['nullable', 'integer', 'min:0'],
            'followers_growth_this_month' => ['nullable', 'integer'],
            'total_posts' => ['nullable', 'integer', 'min:0'],
            'metrics_last_updated_at' => ['nullable', 'date'],
            'api_connected' => ['nullable'],
            'api_access_token' => ['nullable', 'string'],
            'api_token_expires_at' => ['nullable', 'date'],
            'is_active' => ['nullable'],
        ]);
    }

    private function platformOptions(): array
    {
        return ['instagram', 'youtube', 'tiktok', 'facebook', 'linkedin', 'x', 'telegram'];
    }

    private function responseFor(Request $request, array $payload, string $statusMessage, int $statusCode = Response::HTTP_OK)
    {
        if ($request->expectsJson()) {
            return response()->json($payload, $statusCode);
        }
        return redirect('/mktg-admin/social/accounts')->with('status', $statusMessage);
    }
}
