<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\ModuleAccess;
use Illuminate\Http\Request;

/**
 * Customer Manager'larin kendi sirketinin SaaS planini gormesi icin
 * read-only "Planim" sayfasi + Platform Owner'a yukseltme talebi.
 *
 * Customer Manager modul toggle YAPAMAZ (Faz 1 yetki ayrimi) — sadece
 * mevcut planini gorur ve daha yuksek bir tier'a yukseltmek istediginde
 * Platform Owner'a in_app notification gonderir.
 */
class MyPlanController extends Controller
{
    public function index()
    {
        $user    = auth()->user();
        $company = $this->resolveCompany($user);

        $tierKey    = (string) ($company->subscription_tier ?? 'trial');
        $tierConfig = config("subscription_tiers.{$tierKey}", []);

        $allTiers      = config('subscription_tiers', []);
        $enabledModules = ModuleAccess::enabledModules($company->id);
        $allModules     = ModuleAccess::allModules();

        return view('manager.my-plan', [
            'company'        => $company,
            'tierKey'        => $tierKey,
            'tierConfig'     => $tierConfig,
            'allTiers'       => $allTiers,
            'enabledModules' => $enabledModules,
            'allModules'     => $allModules,
        ]);
    }

    public function requestUpgrade(Request $request)
    {
        $data = $request->validate([
            'desired_tier' => ['required', 'string', 'in:basic,gold,premium'],
            'message'      => ['nullable', 'string', 'max:500'],
        ]);

        $user    = auth()->user();
        $company = $this->resolveCompany($user);

        $currentTier = (string) ($company->subscription_tier ?? 'trial');
        $desiredTier = $data['desired_tier'];

        $platformOwners = User::query()
            ->where('role', User::ROLE_PLATFORM_OWNER)
            ->where('is_active', true)
            ->get();

        if ($platformOwners->isEmpty()) {
            return back()->with('status', 'Platform sahibi bulunamadi. Lutfen support@mentorde.com ile iletisime gec.');
        }

        $notif = app(NotificationService::class);
        $body  = "[{$company->name}] firmasi {$currentTier} → {$desiredTier} tier yukseltmesi talep ediyor."
            . ($data['message'] ? " Not: {$data['message']}" : '');

        foreach ($platformOwners as $owner) {
            $notif->send([
                'channel'      => 'in_app',
                'category'     => 'platform.upgrade_request',
                'user_id'      => $owner->id,
                'subject'      => "Tier yukseltme talebi: {$company->name}",
                'body'         => $body,
                'source_type'  => 'company_upgrade_request',
                'source_id'    => $company->id . ':' . $desiredTier,
                'triggered_by' => $user->email,
                'variables'    => [
                    'company_id'   => $company->id,
                    'company_name' => $company->name,
                    'current_tier' => $currentTier,
                    'desired_tier' => $desiredTier,
                    'requested_by' => $user->name . ' <' . $user->email . '>',
                ],
            ]);
        }

        return back()->with('status', "Yukseltme talebi gonderildi ({$desiredTier}). Platform sahibi en kisa zamanda donus yapacak.");
    }

    private function resolveCompany(User $user): Company
    {
        $companyId = (int) ($user->company_id ?? 0);
        $company   = Company::find($companyId);
        abort_if(!$company, 404, 'Sirket bulunamadi.');
        return $company;
    }
}
