<?php

namespace App\Http\Controllers\Concerns;

trait UsesServicePackages
{
    private function servicePackages(): array
    {
        return collect(config('service_packages.packages', []))
            ->where('is_active', true)
            ->sortBy('sort_order')
            ->values()
            ->all();
    }

    private function extraServiceOptions(): array
    {
        return collect(config('service_packages.extra_services', []))
            ->where('is_active', true)
            ->sortBy('sort_order')
            ->values()
            ->all();
    }

    private function findPackageByCode(string $code): ?array
    {
        return collect(config('service_packages.packages', []))
            ->where('is_active', true)
            ->firstWhere('code', $code);
    }

    private function findExtraServiceByCode(string $code): ?array
    {
        return collect(config('service_packages.extra_services', []))
            ->where('is_active', true)
            ->firstWhere('code', $code);
    }
}
