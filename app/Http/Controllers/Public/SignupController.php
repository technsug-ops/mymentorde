<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeNewCompanyMail;
use App\Models\Company;
use App\Models\User;
use App\Support\ModuleAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Public self-service signup wizard.
 *
 * Trial planında 14 gün ücretsiz başlangıç; tüm planlar başlangıçta TRIAL olarak
 * provisioned edilir (kullanıcı 14 gün denemeden sonra plan seçer/onaylar).
 *
 * Flow:
 *   GET  /kayit?tier=gold   → form göster (tier preview)
 *   POST /kayit              → Company + Manager User oluştur, auto-login,
 *                              /manager/dashboard'a yönlendir
 *
 * Throttle: IP başına saatte 5 deneme.
 */
class SignupController extends Controller
{
    public function show(Request $request): View
    {
        $tiers = config('subscription_tiers');
        $tierKey = (string) $request->query('tier', 'gold');
        if (!array_key_exists($tierKey, $tiers)) {
            $tierKey = 'gold';
        }

        return view('public.signup', [
            'tiers'          => $tiers,
            'selectedTier'   => $tierKey,
            'brandName'      => config('brand.name', 'MentorDE'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Honeypot — bot tuzağı (hidden input, dolduran bot)
        if (!empty($request->input('website'))) {
            return redirect()->route('public.signup.show')->with('error', 'Doğrulama başarısız.');
        }

        $validator = Validator::make($request->all(), [
            'company_name'  => ['required', 'string', 'max:120', 'min:3'],
            'admin_name'    => ['required', 'string', 'max:120', 'min:2'],
            'admin_email'   => ['required', 'email', 'max:190', 'unique:users,email'],
            'admin_phone'   => ['nullable', 'string', 'max:30'],
            'password'      => ['required', 'string', 'min:8', 'max:120', 'confirmed'],
            'tier'          => ['required', 'string', 'in:trial,basic,gold,premium'],
            'kvkk_accept'   => ['accepted'],
            'terms_accept'  => ['accepted'],
        ], [
            'admin_email.unique'    => 'Bu e-posta adresi zaten kayıtlı. Giriş yapmayı dene veya farklı bir e-posta kullan.',
            'password.confirmed'    => 'Şifreler birbirinin aynısı olmalı.',
            'password.min'          => 'Şifre en az 8 karakter olmalı.',
            'kvkk_accept.accepted'  => 'KVKK aydınlatma metnini onaylaman gerek.',
            'terms_accept.accepted' => 'Kullanım koşullarını onaylaman gerek.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput($request->except(['password', 'password_confirmation']));
        }

        $data = $validator->validated();

        // ── Trial başlangıç tier ──
        // Tüm planlar başlangıçta 14 gün trial; kullanıcı seçtiği tier sadece
        // ileriki konfigürasyon için tutulur. Trial bitince ödeme yapınca gerçek
        // tier'e geçer. Şimdilik tier seçimine göre direkt provision ediyoruz.
        $tier = $data['tier'];
        $tierConfig = config("subscription_tiers.{$tier}") ?? config('subscription_tiers.trial');

        // Code (slug) — şirket adı + random suffix (unique)
        $baseCode = Str::slug($data['company_name'], '_');
        if ($baseCode === '') {
            $baseCode = 'org';
        }
        $code = $baseCode;
        $attempts = 0;
        while (Company::query()->where('code', $code)->exists() && $attempts < 20) {
            $code = $baseCode . '_' . strtolower(Str::random(4));
            $attempts++;
        }

        // Modüller — tier'dan al
        $tierModules = $tierConfig['modules'] ?? [];
        if ($tierModules === '*') {
            $modules = ModuleAccess::allModules();
        } elseif (is_array($tierModules) && !empty($tierModules)) {
            $modules = array_values(array_unique($tierModules));
        } else {
            $modules = ['core'];
        }

        // Trial 14 gün
        $trialEnds = now()->addDays(14)->toDateString();

        $company = null;
        $user = null;

        try {
            DB::transaction(function () use ($data, $tier, $tierConfig, $code, $trialEnds, $modules, &$company, &$user): void {
                $company = Company::query()->create([
                    'name'              => $data['company_name'],
                    'code'              => $code,
                    'is_active'         => true,
                    'enabled_modules'   => $modules,
                    'subscription_tier' => $tier,
                    'trial_ends_at'     => $trialEnds,
                    'billing_email'     => strtolower(trim($data['admin_email'])),
                    'mrr_eur'           => 0, // Trial boyunca 0; ödeme sonrası tier'in mrr'i set edilir
                ]);

                $user = User::query()->create([
                    'company_id'        => $company->id,
                    'name'              => $data['admin_name'],
                    'email'             => strtolower(trim($data['admin_email'])),
                    'phone'             => $data['admin_phone'] ?? null,
                    'password'          => Hash::make($data['password']),
                    'role'              => User::ROLE_MANAGER,
                    'is_active'         => true,
                    'email_verified_at' => now(),
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('public.signup: provision failed', [
                'error'   => $e->getMessage(),
                'company' => $data['company_name'],
                'email'   => $data['admin_email'],
            ]);
            return back()->withErrors([
                'general' => 'Kayıt sırasında beklenmeyen bir hata oluştu. Lütfen tekrar dene veya destek@mentorde.com adresine yaz.',
            ])->withInput($request->except(['password', 'password_confirmation']));
        }

        if (!$company || !$user) {
            return back()->withErrors(['general' => 'Provision başarısız. Lütfen destek ile iletişime geç.'])
                ->withInput($request->except(['password', 'password_confirmation']));
        }

        ModuleAccess::flushCache((int) $company->id);

        // Audit log — platform sahibi gözlemleyebilsin
        try {
            \App\Models\PlatformAuditLog::record(
                'platform.signup.self_service',
                [
                    'target_type'   => 'company',
                    'target_id'     => $company->id,
                    'company'       => $company->name,
                    'code'          => $company->code,
                    'tier'          => $tier,
                    'admin_email'   => $user->email,
                    'admin_name'    => $user->name,
                    'trial_ends_at' => $trialEnds,
                    'ip'            => $request->ip(),
                ],
                \App\Models\PlatformAuditLog::SEVERITY_INFO
            );
        } catch (\Throwable $e) {
            // Audit fail kayıt akışını bozmasın
            Log::warning('public.signup: audit log failed (non-fatal)', ['error' => $e->getMessage()]);
        }

        Log::info('public.signup: success', [
            'company_id' => $company->id,
            'company'    => $company->name,
            'tier'       => $tier,
            'email'      => $user->email,
            'ip'         => $request->ip(),
        ]);

        // Welcome email — queue'ya düşer, response'u yavaşlatmaz
        try {
            Mail::to($user->email, $user->name)
                ->send(new WelcomeNewCompanyMail($company, $user));
        } catch (\Throwable $e) {
            // Mail fail signup'ı bozmasın — log + devam
            Log::warning('public.signup: welcome mail failed (non-fatal)', [
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }

        // Auto-login + redirect
        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/manager/dashboard?welcome=1');
    }
}
