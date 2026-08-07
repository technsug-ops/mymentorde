<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable implements CanResetPasswordContract, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, CanResetPassword;

    /**
     * Şirket izolasyonu.
     *
     * ⚠ Kimlik doğrulama bu scope'tan MUAF: config/auth.php `tenant_eloquent`
     * provider'ı kullanır (App\Auth\TenantAwareUserProvider). Aksi halde login,
     * şifre sıfırlama ve "beni hatırla" kırılırdı — kullanıcı giriş yapmadan
     * şirketi bilinemez.
     *
     * Giriş sonrası TÜM User sorguları (personel listesi, atama, arama...)
     * kullanıcının görünür şirket kümesiyle sınırlanır.
     */
    use BelongsToCompany;

    /**
     * Email verification notification'ı Türkçe + public welcome.verify URL ile gönder.
     * Default Laravel VerifyEmail İngilizce ve auth gerektiren URL kullanır
     * (kullanıcı login değilse race condition oluşurdu).
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new \App\Notifications\VerifyEmailTr());
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            // Mail doğrulama davranışı (Mayıs 2026):
            // - Admin/system kullanıcı yaratırken email_verified_at attribute'unu
            //   explicit set ETMEZSE → default now() (otomatik verified).
            // - Guest self-registration (GuestApplicationController) explicit olarak
            //   email_verified_at => null verir → null kalır, kullanıcı welcome maildeki
            //   signed URL'i tıklamadan login akışı EnsureEmailIsVerified middleware
            //   tarafından /email/verify notice sayfasına yönlendirilir.
            //
            // array_key_exists kullanılır (isset null değeri için false döner — ayırt edemez).
            if (!array_key_exists('email_verified_at', $user->getAttributes())) {
                $user->email_verified_at = now();
            }

            if (!empty($user->company_id)) {
                return;
            }

            $companyId = app()->bound('current_company_id')
                ? (int) app('current_company_id')
                : (int) Company::query()->where('is_active', true)->orderBy('id')->value('id');

            if ($companyId > 0) {
                $user->company_id = $companyId;
            }
        });
    }

    /**
     * Kullanıcının ERİŞEBİLDİĞİ şirketler (aidiyetten farklı).
     *
     * `users.company_id` = aidiyet (tek). Bu pivot = erişim (çok).
     * MentorDE personeli partner firmalara buradan bağlanır; firma kullanıcılarının
     * pivotta satırı olmaz, yani yalnızca kendi şirketlerini görürler.
     */
    /** @var list<int>|null İstek içi memoization — bkz. visibleCompanyIds() */
    private ?array $visibleCompanyIdsMemo = null;

    public function accessibleCompanies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_user')
            ->withPivot(['role_in_company', 'is_primary'])
            ->withTimestamps();
    }

    /**
     * Görebileceği şirket id'leri: kendi şirketi + pivottakiler.
     *
     * Her istekte SetCompanyContext tarafından çağrılıyor; pivot sorgusu ve
     * Schema::hasTable() kontrolü sıcak yolda ekstra sorgu demek (poll endpoint'i
     * 5 saniyede bir çağrılıyor). Bu yüzden hem istek içinde memoize edilir hem
     * de kısa süreli önbelleğe alınır. Pivot değişimi nadirdir; değiştiğinde
     * forgetVisibleCompanyIds() çağrılmalı.
     *
     * @return list<int>
     */
    public function visibleCompanyIds(): array
    {
        if ($this->visibleCompanyIdsMemo !== null) {
            return $this->visibleCompanyIdsMemo;
        }

        $own = (int) ($this->company_id ?? 0);

        $extra = Cache::remember(
            self::visibleCompaniesCacheKey((int) $this->id),
            300,
            function (): array {
                if (!Schema::hasTable('company_user')) {
                    return [];
                }

                return DB::table('company_user')
                    ->where('user_id', $this->id)
                    ->pluck('company_id')
                    ->map(static fn ($id): int => (int) $id)
                    ->all();
            }
        );

        // Üst firmanın PERSONELİ alt firmaların verisini de görür.
        //
        // İş modeli: MentorDE, B2B partner firmaların öğrencilerinin sürecini
        // yürütüyor — lead'i göremezse işi yapamaz. İzolasyon YATAY: firma
        // firmayı, bayi bayiyi, öğrenci öğrenciyi görmez. Yukarı doğru değil.
        //
        // ⚠ Yalnızca DENETLEYİCİ roller. Öğrenci, aday, bayi ve VIP bu kümeye
        // ASLA girmez — MentorDE'ye kayıtlı bir öğrenci partner firmanın
        // verisini görürdü. Yeni bir rol eklendiğinde bilinçli olarak
        // SUPERVISING_ROLES'a eklenmeli; varsayılan erişim YOK.
        $descendants = [];

        if ($own > 0 && in_array((string) $this->role, self::SUPERVISING_ROLES, true)) {
            $descendants = Company::descendantIds($own);
        }

        $ids = array_values(array_unique(array_filter(
            array_merge($own > 0 ? [$own] : [], $extra, $descendants),
            static fn (int $id): bool => $id > 0
        )));

        return $this->visibleCompanyIdsMemo = $ids;
    }

    public static function visibleCompaniesCacheKey(int $userId): string
    {
        return "user:{$userId}:visible_companies";
    }

    /** Pivot değiştiğinde (şirket atama/çıkarma) çağır. */
    public function forgetVisibleCompanyIds(): void
    {
        $this->visibleCompanyIdsMemo = null;
        Cache::forget(self::visibleCompaniesCacheKey((int) $this->id));
    }

    public const ROLE_MANAGER = 'manager';
    public const ROLE_SENIOR = 'senior';
    public const ROLE_MENTOR = 'mentor';
    public const ROLE_GUEST = 'guest';
    public const ROLE_STUDENT = 'student';
    public const ROLE_DEALER = 'dealer';

    public const ROLE_FINANCE_ADMIN = 'finance_admin';
    public const ROLE_FINANCE_STAFF = 'finance_staff';
    public const ROLE_OPERATIONS_ADMIN = 'operations_admin';
    public const ROLE_OPERATIONS_STAFF = 'operations_staff';
    public const ROLE_SYSTEM_ADMIN = 'system_admin';
    public const ROLE_SYSTEM_STAFF = 'system_staff';
    public const ROLE_MARKETING_ADMIN = 'marketing_admin';
    public const ROLE_SALES_ADMIN = 'sales_admin';
    public const ROLE_SALES_STAFF = 'sales_staff';
    public const ROLE_MARKETING_STAFF = 'marketing_staff';

    /**
     * Read-only Auditor — denetim/uyum için tüm sayfaları görür ama yazma yok.
     * SOC 2 / ISO 27001 "minimum yetki ilkesi" + risk azaltma + compliance officer
     * + yatırımcı/avukat erişimi senaryolari için. EnsureReadOnly middleware
     * POST/PUT/DELETE/PATCH istekleri 403 ile bloklar (allowlist hariç:
     * logout, dismiss benzeri benign actionlar). UI'de "READ-ONLY" rozeti gösterir.
     */
    public const ROLE_AUDITOR = 'auditor';

    /**
     * Platform Owner — Mentorde SaaS sahibi. Cross-company yetki, modul toggle,
     * billing, sistem admin yetkileri. Customer Manager'larin uzerinde tek rol.
     * Customer'larin enabled_modules JSON'unu degistirebilir = premium feature
     * paywallini koruyan tek yetki.
     */
    public const ROLE_PLATFORM_OWNER = 'platform_owner';

    /**
     * Alt firmaların verisini de görebilen DENETLEYİCİ roller.
     *
     * Üst firma (MentorDE) partner firmaların süreçlerini yürüttüğü için
     * personeli onların lead ve öğrencilerini görmelidir. Ama bu yetki
     * şirkete değil ROLE bağlıdır: aynı şirkete kayıtlı bir öğrenci, aday
     * ya da bayi partner verisine ASLA erişemez.
     *
     * ALLOWLIST — yeni rol eklendiğinde buraya bilinçli eklenmeli.
     * Kasten dışarıda: student, guest, dealer, vip.
     *
     * @var list<string>
     */
    public const SUPERVISING_ROLES = [
        self::ROLE_MANAGER,
        self::ROLE_SENIOR,
        self::ROLE_MENTOR,
        self::ROLE_FINANCE_ADMIN,
        self::ROLE_FINANCE_STAFF,
        self::ROLE_OPERATIONS_ADMIN,
        self::ROLE_OPERATIONS_STAFF,
        self::ROLE_SYSTEM_ADMIN,
        self::ROLE_SYSTEM_STAFF,
        self::ROLE_MARKETING_ADMIN,
        self::ROLE_MARKETING_STAFF,
        self::ROLE_SALES_ADMIN,
        self::ROLE_SALES_STAFF,
        self::ROLE_AUDITOR,
    ];

    /**
     * VIP Ortak — owner ile premium (customer manager) arasında üst yetkili.
     * İş/ağ yetkisi yüksek: tüm bayi ağı + alt bayiler, oversight raporları,
     * başvuru onay/red, denetim kayıtları (read-only) — kendi şirketi/ağı kapsamında.
     * Platform-altyapısına (modül toggle, güvenlik/IP, GDPR, rol yönetimi) EREMEZ;
     * bunlar Platform Owner + System Admin'de kalır. [system.access middleware]
     */
    public const ROLE_VIP = 'vip';

    public const ADMIN_PANEL_ROLES = [
        self::ROLE_PLATFORM_OWNER,   // ← Mentorde sahibi (top-level), Customer Manager'larin uzerinde
        self::ROLE_VIP,              // ← üst yetkili ortak, owner ile manager arasında
        self::ROLE_MANAGER,
        self::ROLE_SYSTEM_ADMIN,
        self::ROLE_OPERATIONS_ADMIN,
        self::ROLE_FINANCE_ADMIN,
        self::ROLE_MARKETING_ADMIN,  // marketing admin de co-manager olarak /manager/* erişebilir
        self::ROLE_AUDITOR,          // read-only auditor — tüm manager sayfalarına erişir, yazma yok
    ];

    public const MARKETING_ACCESS_ROLES = [
        self::ROLE_MANAGER,
        self::ROLE_SYSTEM_ADMIN,
        self::ROLE_SYSTEM_STAFF,
        self::ROLE_MARKETING_ADMIN,
        self::ROLE_SALES_ADMIN,
        self::ROLE_SALES_STAFF,
        self::ROLE_MARKETING_STAFF,
    ];

    public const TASK_ACCESS_ROLES = [
        self::ROLE_MANAGER,
        self::ROLE_SENIOR,
        self::ROLE_MENTOR,
        self::ROLE_SYSTEM_ADMIN,
        self::ROLE_SYSTEM_STAFF,
        self::ROLE_OPERATIONS_ADMIN,
        self::ROLE_OPERATIONS_STAFF,
        self::ROLE_FINANCE_ADMIN,
        self::ROLE_FINANCE_STAFF,
        self::ROLE_MARKETING_ADMIN,
        self::ROLE_SALES_ADMIN,
        self::ROLE_SALES_STAFF,
        self::ROLE_MARKETING_STAFF,
    ];

    public const ROLE_DEFAULT_PERMISSION_CODES = [
        self::ROLE_MANAGER => [
            'config.view',
            'config.manage',
            'student.assignment.manage',
            'student.card.view',
            'revenue.manage',
            'approval.manage',
            'notification.manage',
            'role.template.manage',
            'ticket.center.view',
            'ticket.center.route',
            'dam.view', 'dam.download', 'dam.upload', 'dam.update', 'dam.delete', 'dam.folder.manage', 'dam.admin',
            'doc_request.use',
        ],
        self::ROLE_SYSTEM_ADMIN => [
            'config.view',
            'config.manage',
            'notification.manage',
            'role.template.manage',
            'ticket.center.view',
            'dam.view', 'dam.download',
        ],
        self::ROLE_OPERATIONS_ADMIN => [
            'config.view',
            'student.assignment.manage',
            'approval.manage',
            'notification.manage',
            'ticket.center.view',
            'dam.view', 'dam.download',
        ],
        self::ROLE_FINANCE_ADMIN => [
            'config.view',
            'revenue.manage',
            'notification.manage',
            'dam.view', 'dam.download',
        ],
        self::ROLE_MARKETING_ADMIN => [
            'marketing.dashboard.view',
            'marketing.campaign.manage',
            'dam.view', 'dam.download', 'dam.upload', 'dam.update', 'dam.delete', 'dam.folder.manage', 'dam.admin',
        ],
        self::ROLE_MARKETING_STAFF => [
            'marketing.dashboard.view',
            'dam.view', 'dam.download', 'dam.upload', 'dam.update', 'dam.folder.manage',
        ],
        self::ROLE_SALES_ADMIN => [
            'marketing.dashboard.view',
            'dam.view', 'dam.download',
        ],
        self::ROLE_SALES_STAFF => [
            'marketing.dashboard.view',
            'dam.view', 'dam.download',
        ],
        self::ROLE_SENIOR => [
            'student.assignment.manage', 'student.card.view',
            'dam.view', 'dam.download', 'dam.upload', 'dam.update', 'dam.folder.manage',
        ],
        self::ROLE_DEALER => [
            'dam.view', 'dam.download',
        ],
    ];

    public const ROLE_GROUPS = [
        [
            'key' => 'manager',
            'title' => 'Manager',
            'parent' => self::ROLE_MANAGER,
            'children' => [],
        ],
        [
            'key' => 'system',
            'title' => 'System',
            'parent' => self::ROLE_SYSTEM_ADMIN,
            'children' => [self::ROLE_SYSTEM_STAFF],
        ],
        [
            'key' => 'operations',
            'title' => 'Operations',
            'parent' => self::ROLE_OPERATIONS_ADMIN,
            'children' => [self::ROLE_OPERATIONS_STAFF],
        ],
        [
            'key' => 'finance',
            'title' => 'Finance',
            'parent' => self::ROLE_FINANCE_ADMIN,
            'children' => [self::ROLE_FINANCE_STAFF],
        ],
        [
            'key' => 'marketing',
            'title' => 'Marketing',
            'parent' => self::ROLE_MARKETING_ADMIN,
            'children' => [self::ROLE_MARKETING_STAFF],
        ],
        [
            'key' => 'sales',
            'title' => 'Sales',
            'parent' => self::ROLE_SALES_ADMIN,
            'children' => [self::ROLE_SALES_STAFF],
        ],
        [
            'key' => 'advisor',
            'title' => 'Advisory',
            'parent' => self::ROLE_SENIOR,
            'children' => [self::ROLE_MENTOR],
        ],
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'email',
        'phone',
        'google_id',
        'role',
        'student_id',
        'dealer_code',
        'senior_code',
        'senior_internal_sequence',
        'senior_type',
        'advisor_specialties',
        'max_capacity',
        'auto_assign_enabled',
        'can_view_guest_pool',
        'is_active',
        'can_request_documents',
        'password',
        'password_must_change',
        'bio',
        'expertise_tags',
        'photo_url',
        'failed_login_attempts',
        'locked_until',
        'last_failed_login_at',
        'email_verified_at', // System-only — request input'tan değil, sadece controller'lar set eder
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    /**
     * Danışman uzmanlık etiketleri — bir kişide birden fazla olabilir.
     *
     * Anahtarlar aday başvuru türleriyle (`application_type`) eşleşecek
     * şekilde seçildi; otomatik atama bu eşleşmeye bakıyor.
     *
     * ⚠ `vize` bir başvuru türü DEĞİL. Otomatik eşleşmede karşılığı yok;
     * elle atama ve listeleme için bir işaret. Yani vize etiketli bir
     * danışman, yalnızca bu etiketi varsa hiçbir otomatik atamaya girmez.
     *
     * @var array<string,string>
     */
    public const ADVISOR_SPECIALTIES = [
        'bachelor'   => 'Bachelor',
        'master'     => 'Master',
        'ausbildung' => 'Ausbildung',
        'vize'       => 'Vize',
    ];

    /**
     * Temizlenmiş uzmanlık listesi — yalnızca bilinen etiketler.
     *
     * @return list<string>
     */
    public function advisorSpecialties(): array
    {
        $raw = $this->advisor_specialties;

        if (!is_array($raw)) {
            return [];
        }

        return array_values(array_intersect(
            array_map(fn ($v) => strtolower(trim((string) $v)), $raw),
            array_keys(self::ADVISOR_SPECIALTIES)
        ));
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'company_id' => 'integer',
            'max_capacity' => 'integer',
            'advisor_specialties' => 'array',
            'auto_assign_enabled' => 'boolean',
            'can_view_guest_pool' => 'boolean',
            'is_active'              => 'boolean',
            'password_must_change'   => 'boolean',
            'failed_login_attempts'  => 'integer',
            'locked_until'           => 'datetime',
            'last_failed_login_at'   => 'datetime',

            // Sessizlik check-in (sadece role=student için kullanılır)
            'silence_checkin_paused_at' => 'datetime',
            'last_silence_checkin_at'   => 'datetime',
        ];
    }

    public function hasRole(string $role): bool
    {
        return (string) $this->role === $role;
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array((string) $this->role, $roles, true);
    }

    public function roleAssignments()
    {
        return $this->hasMany(UserRoleAssignment::class);
    }

    public function availabilitySchedules()
    {
        return $this->hasMany(\App\Models\UserAvailabilitySchedule::class)->orderBy('day_of_week');
    }

    public function awayPeriods()
    {
        return $this->hasMany(\App\Models\UserAwayPeriod::class)->orderBy('away_from');
    }

    public function activeAwayPeriod(): ?\App\Models\UserAwayPeriod
    {
        return $this->awayPeriods()->active()->first();
    }

    public function favoriteAssets(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\DigitalAsset::class,
            'digital_asset_favorites',
            'user_id',
            'asset_id'
        )->withTimestamps();
    }

    public function favoriteFolders(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\DigitalAssetFolder::class,
            'digital_asset_folder_favorites',
            'user_id',
            'folder_id'
        )->withTimestamps();
    }

    /** @var array<string>|null Request-scope memoization cache */
    private ?array $_permissionCodesCache = null;

    public function effectivePermissionCodes(): array
    {
        if ($this->_permissionCodesCache !== null) {
            return $this->_permissionCodesCache;
        }

        $codes = [];
        $activeAssignments = $this->roleAssignments()
            ->where('is_active', true)
            ->with('template.permissions')
            ->get();

        foreach ($activeAssignments as $assignment) {
            $perms = $assignment->template?->permissions ?? collect();
            foreach ($perms as $p) {
                $code = (string) ($p->code ?? '');
                if ($code !== '') {
                    $codes[$code] = true;
                }
            }
        }

        $fallback = self::ROLE_DEFAULT_PERMISSION_CODES[(string) $this->role] ?? [];
        foreach ($fallback as $code) {
            $codes[(string) $code] = true;
        }

        // Per-user "Belge Talep Et" yetkisi (manager senior'lara tek tek verir)
        if (!empty($this->can_request_documents)) {
            $codes['doc_request.use'] = true;
        }

        // ── ŞİRKET YETKİ TAVANI ─────────────────────────────────────────────
        //
        // Rol yetkiyi VERİR, şirket tavanı DARALTIR. Partner firmalar öğrenciyi
        // bize devredip operasyonu bize bırakıyor; hangi firmanın ne kadar
        // yetkisi olacağını ağacın üstündeki firma belirler.
        //
        // Kısıt en SONDA uygulanır: rol şablonu, rol varsayılanı ya da
        // kişiye özel bir yetki tavanı deleMEZ. Aksi halde kısıtlanan bir
        // yetki başka bir yoldan geri sızardı.
        //
        // Platform sahibi MUAF — kendi platformunu kilitleyemesin.
        if ($this->role !== self::ROLE_PLATFORM_OWNER) {
            foreach (Company::effectiveDeniedPermissions((int) ($this->company_id ?? 0)) as $denied) {
                unset($codes[$denied]);
            }
        }

        $this->_permissionCodesCache = array_keys($codes);
        return $this->_permissionCodesCache;
    }

    public function hasPermissionCode(string $permissionCode): bool
    {
        $code = trim($permissionCode);
        if ($code === '') {
            return false;
        }
        return in_array($code, $this->effectivePermissionCodes(), true);
    }

    /**
     * Danışmanın başarıyla Almanya'ya yerleştirdiği öğrenci sayısı.
     * Cache: 1 saat.
     */
    public function successfulStudentsCount(): int
    {
        $email = (string) $this->email;

        return (int) \Illuminate\Support\Facades\Cache::remember(
            "senior_success_count_{$this->id}",
            3600,
            function () use ($email) {
                $convertedIds = \App\Models\GuestApplication::whereNotNull('converted_student_id')
                    ->pluck('id');

                return \App\Models\StudentAssignment::where('senior_email', $email)
                    ->whereIn('student_id', $convertedIds)
                    ->count();
            }
        );
    }

    /** Uzmanlık etiketlerini dizi olarak döndürür. */
    public function expertiseTags(): array
    {
        if (empty($this->expertise_tags)) {
            return [];
        }
        return array_filter(array_map('trim', explode(',', $this->expertise_tags)));
    }

    public function riskScore(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\StudentRiskScore::class, 'student_id');
    }
}
