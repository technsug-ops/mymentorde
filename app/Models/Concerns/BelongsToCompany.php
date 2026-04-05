<?php

namespace App\Models\Concerns;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $builder): void {
            if (app()->runningInConsole()) {
                return;
            }

            $companyId = self::resolveCompanyIdForScope();
            if ($companyId <= 0) {
                return;
            }

            $builder->where($builder->getModel()->qualifyColumn('company_id'), $companyId);
        });

        static::creating(function ($model): void {
            if (!empty($model->company_id)) {
                return;
            }
            $companyId = self::resolveCompanyIdForScope();
            if ($companyId > 0) {
                $model->company_id = $companyId;
            }
        });
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query
            ->withoutGlobalScope('company')
            ->where($query->getModel()->qualifyColumn('company_id'), $companyId);
    }

    private static function resolveCompanyIdForScope(): int
    {
        if (app()->bound('current_company_id')) {
            return (int) app('current_company_id');
        }

        return (int) Company::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');
    }
}

