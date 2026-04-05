<?php

namespace App\Services;

use App\Models\CurrencyRate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyRateService
{
    private const API_URL = 'https://open.er-api.com/v6/latest/EUR';

    /**
     * Fetch latest EUR rates and persist EUR→TRY and EUR→USD.
     */
    public function sync(): array
    {
        $response = Http::timeout(15)->get(self::API_URL);

        if (!$response->successful()) {
            Log::warning('CurrencyRateService: API request failed', ['status' => $response->status()]);
            return [];
        }

        $data  = $response->json();
        $rates = $data['rates'] ?? [];
        $today = now()->toDateString();

        $synced = [];

        foreach (['TRY', 'USD', 'GBP'] as $target) {
            if (!isset($rates[$target])) {
                continue;
            }

            CurrencyRate::updateOrCreate(
                [
                    'base_currency'   => 'EUR',
                    'target_currency' => $target,
                    'fetched_at'      => $today,
                ],
                [
                    'rate'   => (float) $rates[$target],
                    'source' => 'open.er-api.com',
                ]
            );

            // Invalidate cache
            Cache::forget("currency_rate_EUR_{$target}");

            $synced[$target] = (float) $rates[$target];
        }

        return $synced;
    }

    /**
     * Get today's (or most recent) rate from cache/DB.
     */
    public function getRate(string $from = 'EUR', string $to = 'TRY'): ?float
    {
        return Cache::remember("currency_rate_{$from}_{$to}", 3600, function () use ($from, $to) {
            return CurrencyRate::query()
                ->where('base_currency', $from)
                ->where('target_currency', $to)
                ->latest('fetched_at')
                ->value('rate');
        });
    }

    /**
     * Get fetch date of the most recent rate.
     */
    public function getRateDate(string $from = 'EUR', string $to = 'TRY'): ?string
    {
        $row = CurrencyRate::query()
            ->where('base_currency', $from)
            ->where('target_currency', $to)
            ->latest('fetched_at')
            ->first(['fetched_at']);

        return $row?->fetched_at?->format('d.m.Y');
    }
}
