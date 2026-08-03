<?php

namespace Tests\Feature\Tenancy\Concerns;

use App\Models\Company;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Before;

/**
 * Tenant testleri için iki şirketlik kurulum.
 */
trait MakesCompanies
{
    protected Company $companyA;
    protected Company $companyB;

    protected function setUpCompanies(): void
    {
        $this->companyA = Company::create(['name' => 'Firma A', 'code' => 'firma_a', 'is_active' => true]);
        $this->companyB = Company::create(['name' => 'Firma B', 'code' => 'firma_b', 'is_active' => true]);
    }

    #[Before]
    protected function bootMakesCompanies(): void
    {
        $this->afterApplicationCreated(function (): void {
            $this->setUpCompanies();
        });

        $this->beforeApplicationDestroyed(function (): void {
            TenantContext::forget();
        });
    }

    protected function userFor(Company $company, string $role = User::ROLE_MANAGER): User
    {
        return User::create([
            'name' => 'Test ' . $role,
            'email' => $role . '-' . $company->code . '-' . uniqid() . '@example.test',
            'password' => Hash::make('secret-password'),
            'role' => $role,
            'company_id' => $company->id,
        ]);
    }
}
