<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class ApiRegressionSmokeCommand extends Command
{
    protected $signature = 'api:regression-smoke';
    protected $description = 'Validate critical API error-code contract (ERR_*)';

    public function handle(): int
    {
        $this->line('API regression smoke basladi...');

        $fails = [];

        $check = function (string $label, string $method, string $uri, array $payload = [], ?int $authUserId = null) {
            auth()->logout();
            if ($authUserId) {
                auth()->loginUsingId($authUserId);
            }
            $request  = Request::create($uri, strtoupper($method), $payload);
            $response = app()->handle($request);
            $status   = $response->getStatusCode();
            $json     = json_decode((string) $response->getContent(), true);
            return ['label' => $label, 'status' => $status, 'json' => is_array($json) ? $json : []];
        };

        $results = [];
        $results[] = $check('unauthorized_config', 'GET', '/api/v1/config/suggestions');
        $results[] = $check('not_found_route', 'GET', '/api/v1/not-found-smoke');

        $manager = User::query()->where('role', 'manager')->where('is_active', true)->orderBy('id')->first();
        if (!$manager) {
            $fails[] = 'aktif manager bulunamadi';
        } else {
            $results[] = $check('method_not_allowed_auto_assign', 'GET', '/api/v1/config/student-assignments/auto-assign', [], (int) $manager->id);
        }

        foreach ($results as $r) {
            $code = $r['json']['error_code'] ?? null;
            $this->line(sprintf('%s => status:%s | code:%s', $r['label'], $r['status'], $code ?: '-'));
        }

        $r1 = $results[0] ?? null;
        if (!$r1 || (int) $r1['status'] !== 401 || (($r1['json']['error_code'] ?? '') !== 'ERR_UNAUTHORIZED')) {
            $fails[] = 'unauthorized response beklenen formatta degil';
        }

        $r2 = $results[1] ?? null;
        if (!$r2 || (int) $r2['status'] !== 404 || (($r2['json']['error_code'] ?? '') !== 'ERR_NOT_FOUND')) {
            $fails[] = 'not-found response beklenen formatta degil';
        }

        if ($manager) {
            $r3 = collect($results)->firstWhere('label', 'method_not_allowed_auto_assign');
            if (!$r3 || (int) $r3['status'] !== 405 || (($r3['json']['error_code'] ?? '') !== 'ERR_METHOD_NOT_ALLOWED')) {
                $fails[] = 'method-not-allowed response beklenen formatta degil';
            }
        }

        foreach ($fails as $f) { $this->error('FAIL: ' . $f); }

        if (!empty($fails)) {
            $this->error('API regression smoke SONUC: FAIL');
            return 1;
        }

        $this->info('API regression smoke SONUC: PASS');
        return 0;
    }
}
