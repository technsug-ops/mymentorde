<?php

namespace App\Services\Marketing;

use App\Models\Company;
use App\Models\MarketingIntegrationConnection;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class IntegrationHealthService
{
    /**
     * @return array{checked:int,updated:int,expiring:int,expired:int,errors:int}
     */
    public function run(int $limitPerCompany = 200): array
    {
        $checked = 0;
        $updated = 0;
        $expiring = 0;
        $expired = 0;
        $errors = 0;

        $companyIds = Company::query()->where('is_active', true)->pluck('id')->all();
        if (empty($companyIds)) {
            $companyIds = [1];
        }

        foreach ($companyIds as $companyId) {
            /** @var Collection<int, MarketingIntegrationConnection> $rows */
            $rows = MarketingIntegrationConnection::query()
                ->forCompany((int) $companyId)
                ->orderBy('provider')
                ->limit($limitPerCompany)
                ->get();

            foreach ($rows as $row) {
                $checked++;
                [$status, $error] = $this->resolveHealth($row);

                if ($status === 'expiring') {
                    $expiring++;
                } elseif ($status === 'expired') {
                    $expired++;
                }

                if ($status === 'error' || $status === 'expired') {
                    $errors++;
                }

                $dirty = false;
                if ((string) $row->status !== $status) {
                    $row->status = $status;
                    $dirty = true;
                }
                if ((string) ($row->last_error ?? '') !== (string) ($error ?? '')) {
                    $row->last_error = $error;
                    $dirty = true;
                }
                $row->last_checked_at = now();
                $dirty = true;

                if ($dirty) {
                    $row->save();
                    $updated++;
                }
            }
        }

        return [
            'checked' => $checked,
            'updated' => $updated,
            'expiring' => $expiring,
            'expired' => $expired,
            'errors' => $errors,
        ];
    }

    /**
     * @return array{0:string,1:?string}
     */
    private function resolveHealth(MarketingIntegrationConnection $row): array
    {
        if (!$row->is_enabled) {
            return ['disabled', null];
        }

        $token = trim((string) ($row->access_token ?? ''));
        if ($token === '') {
            return ['pending', 'token missing'];
        }

        $expiresAt = $row->token_expires_at;
        if (!$expiresAt instanceof Carbon) {
            return ['connected', null];
        }

        $diffHours = now()->diffInHours($expiresAt, false);
        if ($diffHours < 0) {
            return ['expired', 'token expired'];
        }

        if ($diffHours <= 72) {
            return ['expiring', 'token expires in <= 3 days'];
        }

        return ['connected', null];
    }
}

