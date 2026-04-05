<?php

namespace App\Services\Marketing\ExternalMetrics\Contracts;

use Illuminate\Support\Carbon;

interface ExternalMetricsProviderInterface
{
    /**
     * Fetch metrics from the provider for the given date range.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetch(Carbon $start, Carbon $end, int $companyId, array $cfg): array;
}
