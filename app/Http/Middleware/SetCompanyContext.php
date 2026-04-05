<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetCompanyContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Schema::hasTable('companies')) {
            return $next($request);
        }

        $defaultCompany = $this->resolveDefaultCompany();
        if (!$defaultCompany) {
            return $next($request);
        }

        // ERP/CRM alanlarinda firma context her zaman varsayilan firmadir.
        // Coklu firma secimi sadece marketing alaninda aktif kalir.
        if (!$this->isMarketingArea($request)) {
            app()->instance('current_company_id', (int) $defaultCompany->id);
            app()->instance('current_company', $defaultCompany);
            View::share('currentCompany', $defaultCompany);
            return $next($request);
        }

        $user = $request->user();
        $hasSession = $request->hasSession();
        $sessionCompanyId = $hasSession
            ? (int) $request->session()->get('current_company_id', 0)
            : 0;
        $userCompanyId = (int) ($user->company_id ?? 0);

        $companyId = $sessionCompanyId > 0 ? $sessionCompanyId : $userCompanyId;
        $company = null;

        if ($companyId > 0) {
            $company = Cache::remember("company:{$companyId}:active", 600, fn () => Company::query()
                ->where('id', $companyId)
                ->where('is_active', true)
                ->first());
        }

        if (!$company) {
            $company = $defaultCompany;
        }

        if ($company) {
            if ($hasSession) {
                $request->session()->put('current_company_id', (int) $company->id);
            }
            app()->instance('current_company_id', (int) $company->id);
            app()->instance('current_company', $company);
            View::share('currentCompany', $company);
        }

        return $next($request);
    }

    private function isMarketingArea(Request $request): bool
    {
        return $request->is('mktg-admin')
            || $request->is('mktg-admin/*')
            || $request->is('marketing-admin')
            || $request->is('marketing-admin/*')
            || $request->is('api/v1/marketing*');
    }

    private function resolveDefaultCompany(): ?Company
    {
        $code = strtolower(trim((string) config('app.primary_company_code', 'mentorde')));

        return Cache::remember("default_company:{$code}", 3600, function () use ($code): ?Company {
            $byCode = Company::query()
                ->where('is_active', true)
                ->whereRaw('lower(code) = ?', [$code])
                ->first();

            if ($byCode) {
                return $byCode;
            }

            return Company::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->first();
        });
    }
}
