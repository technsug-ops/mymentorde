<?php

namespace App\Models;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements CanResetPasswordContract, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, CanResetPassword;

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            // Bu sistemde tüm kullanıcılar admin tarafından veya şifre e-posta
            // akışıyla oluşturulur — dolayısıyla varsayılan olarak doğrulanmış sayılır.
            if ($user->email_verified_at === null) {
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

    public const ADMIN_PANEL_ROLES = [
        self::ROLE_MANAGER,
        self::ROLE_SYSTEM_ADMIN,
        self::ROLE_OPERATIONS_ADMIN,
        self::ROLE_FINANCE_ADMIN,
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
        'role',
        'student_id',
        'dealer_code',
        'senior_code',
        'senior_internal_sequence',
        'senior_type',
        'max_capacity',
        'auto_assign_enabled',
        'can_view_guest_pool',
        'is_active',
        'password',
        'bio',
        'expertise_tags',
        'photo_url',
        'failed_login_attempts',
        'locked_until',
        'last_failed_login_at',
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
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'company_id' => 'integer',
            'max_capacity' => 'integer',
            'auto_assign_enabled' => 'boolean',
            'can_view_guest_pool' => 'boolean',
            'is_active'              => 'boolean',
            'failed_login_attempts'  => 'integer',
            'locked_until'           => 'datetime',
            'last_failed_login_at'   => 'datetime',
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
