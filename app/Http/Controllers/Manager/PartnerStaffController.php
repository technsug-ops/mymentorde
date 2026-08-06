<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetByManagerMail;
use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Firmanın kendi kullanıcılarını yönetmesi.
 *
 * ── NEDEN ───────────────────────────────────────────────────────────────
 * Bir firmada tek kişi olması gerçekçi değil; birden fazla kişiyle irtibat
 * gerekebiliyor. Ama tam `manager/staff` ekranı partnere ağır kalıyor —
 * orada performans panosu, KPI hedefleri, liderlik tablosu, toplu işlem var.
 * Burası aynı işin sade hali: listele, ekle, şifre sıfırla, pasife al.
 *
 * ⚠ TEK HESAP PAYLAŞMAK YERİNE AYRI HESAP. Sistem kimin ne yaptığını
 * e-posta bazında kaydediyor; paylaşılan hesapta atama, onay ve talep
 * geçmişi tek isme yığılır. `users.email` zaten GLOBAL tekil — aynı adres
 * ikinci kişiye verilemez.
 *
 * ── KOTA ────────────────────────────────────────────────────────────────
 * Kullanıcı sayısı pakete bağlı (`subscription_tiers.*.limits.users_max`).
 * Öğrenci ve aday bu sayıya girmez — onlar müşteri, kullanıcı değil.
 */
class PartnerStaffController extends Controller
{
    /**
     * Firmanın açabileceği roller.
     *
     * Kasıtlı olarak dar: danışman (senior/mentor) operasyonu yürüten
     * firmanın elemanıdır, partner kendi danışmanını buradan yaratamaz.
     * Finans, sistem ve pazarlama rolleri de platformun iç rolleri.
     *
     * @var array<string,string>
     */
    private const ASSIGNABLE_ROLES = [
        User::ROLE_MANAGER          => 'Yönetici — tüm partner ekranları',
        User::ROLE_OPERATIONS_STAFF => 'Operasyon — öğrenci takibi ve belgeler',
    ];

    private function company(): Company
    {
        $cid = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;

        abort_if($cid <= 0, 404);

        return Company::query()->withoutGlobalScope('company')->findOrFail($cid);
    }

    public function index(): View
    {
        $company = $this->company();

        $rows = User::query()
            ->withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->whereNotIn('role', [User::ROLE_STUDENT, User::ROLE_GUEST, User::ROLE_VIP])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'is_active', 'created_at']);

        return view('manager.partner-staff.index', [
            'rows'    => $rows,
            'roles'   => self::ASSIGNABLE_ROLES,
            'limit'   => $company->userLimit(),
            'used'    => $rows->count(),
            'canAdd'  => $company->canAddStaffUser(),
            'tier'    => (string) ($company->subscription_tier ?: Company::TIER_TRIAL),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $this->company();

        // Kota ÖNCE — doğrulamadan da önce, kullanıcı boşuna form doldurmasın.
        if (! $company->canAddStaffUser()) {
            return back()->withInput()->withErrors([
                'limit' => sprintf(
                    'Paketinizin kullanıcı sınırına ulaştınız (%d). Daha fazla kullanıcı için üst pakete geçin.',
                    (int) $company->userLimit()
                ),
            ]);
        }

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:120'],
            // GLOBAL tekil: aynı adres başka bir firmada olsa bile alınamaz.
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')],
            'role'  => ['required', Rule::in(array_keys(self::ASSIGNABLE_ROLES))],
        ], [
            'email.unique' => 'Bu e-posta adresi başka bir hesapta kullanılıyor.',
        ]);

        $tempPassword = Str::password(12, true, true, false);

        $user = User::create([
            'name'              => trim($data['name']),
            'email'             => strtolower(trim($data['email'])),
            'password'          => Hash::make($tempPassword),
            'role'              => $data['role'],
            'company_id'        => $company->id,
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        $mailed = $this->sendCredentials($user, $tempPassword);

        return back()->with('status', $mailed
            ? "{$user->email} eklendi ve giriş bilgileri e-posta ile gönderildi."
            // Mail gitmediyse bunu SÖYLEMEK zorundayız: yoksa yönetici hesabın
            // hazır olduğunu sanır, karşı taraf ise hiçbir şey almaz.
            : "{$user->email} eklendi ama giriş maili GÖNDERİLEMEDİ. Geçici şifre: {$tempPassword}");
    }

    public function resetPassword(Request $request, int $userId): RedirectResponse
    {
        $user = $this->ownUser($userId);

        $tempPassword = Str::password(12, true, true, false);

        $user->forceFill([
            'password'            => Hash::make($tempPassword),
            'password_must_change' => true,
        ])->save();

        $mailed = $this->sendCredentials($user, $tempPassword);

        return back()->with('status', $mailed
            ? "{$user->email} için şifre sıfırlandı ve e-posta gönderildi."
            : "Şifre sıfırlandı ama mail GÖNDERİLEMEDİ. Geçici şifre: {$tempPassword}");
    }

    public function toggle(Request $request, int $userId): RedirectResponse
    {
        $user = $this->ownUser($userId);

        // Kendini kilitlemeyi engelle: firmada erişimi olan kimse kalmazdı.
        abort_if((int) $user->id === (int) $request->user()->id, 422, 'Kendi hesabinizi kapatamazsiniz.');

        $user->forceFill(['is_active' => ! $user->is_active])->save();

        return back()->with('status', $user->is_active
            ? "{$user->email} yeniden etkinleştirildi."
            : "{$user->email} pasife alındı — artık giriş yapamaz.");
    }

    // ─── Yardımcılar ────────────────────────────────────────────────────────

    /**
     * Kullanıcı gerçekten bu firmaya mı ait?
     *
     * `User` firma kapsamlı ama burada kapsamsız okuyoruz: bağlam
     * değiştirmiş bir üst firma yöneticisi de bu ekranı kullanabilmeli.
     * Sınır bu kontrol.
     */
    private function ownUser(int $userId): User
    {
        $company = $this->company();

        $user = User::query()
            ->withoutGlobalScope('company')
            ->whereKey($userId)
            ->firstOrFail();

        abort_if((int) $user->company_id !== (int) $company->id, 404);

        // Müşteri hesapları bu ekrandan yönetilmez.
        abort_if(
            in_array((string) $user->role, [User::ROLE_STUDENT, User::ROLE_GUEST, User::ROLE_VIP], true),
            404
        );

        return $user;
    }

    private function sendCredentials(User $user, string $tempPassword): bool
    {
        try {
            Mail::to($user->email)->send(new PasswordResetByManagerMail(
                name: $user->name ?: $user->email,
                email: $user->email,
                tempPassword: $tempPassword,
                loginUrl: url('/login'),
            ));

            return true;
        } catch (\Throwable $e) {
            Log::error('Partner staff credentials mail failed', [
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}

