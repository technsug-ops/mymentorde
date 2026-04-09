<?php

namespace App\Providers;

use App\Models\BulletinRead;
use App\Models\CompanyBulletin;
use App\Models\GuestApplication;
use App\Models\MarketingAdminSetting;
use App\Models\MarketingTask;
use App\Models\StudentPayment;
use App\Services\TaskFeedbackService;
use App\Support\PortalTheme;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Stateless, hesaplama ağırlıklı servisler — her request'te tek instance yeterli.
        // Bunlar constructor'da DB veya auth'a bağımlı değil; lazy singleton olarak güvenle bağlanabilir.
        $this->app->singleton(\App\Services\LeadScoreService::class);
        $this->app->singleton(\App\Services\RiskScoreService::class);
        $this->app->singleton(\App\Services\DocumentNamingService::class);
        $this->app->singleton(\App\Services\GuestListService::class);
        $this->app->singleton(\App\Services\StudentListService::class);
        $this->app->singleton(\App\Services\CurrencyRateService::class);
        $this->app->singleton(\App\Services\NotificationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Üretimde dosya yükleme güvenliği için zorunlu PHP eklentisi
        if (app()->isProduction() && !extension_loaded('fileinfo')) {
            throw new \RuntimeException(
                'PHP fileinfo extension is required for file upload validation. Enable it in php.ini.'
            );
        }

        // CSP nonce direktifi — Blade şablonlarında @cspNonce ile kullanılır.
        // SecurityHeaders middleware'i her request'te nonce üretip csp-nonce ve $cspNonce olarak paylaşır.
        // Kullanım: <script @cspNonce> veya <script nonce="{{ $cspNonce ?? '' }}">
        Blade::directive('cspNonce', fn () => '<?php echo \'nonce="\' . (app()->bound(\'csp-nonce\') ? app(\'csp-nonce\') : \'\') . \'"\'; ?>');

        View::composer('*', function ($view): void {
            $theme = PortalTheme::resolve();
            $view->with('uiTheme', $theme);
            $view->with('uiThemeCssVars', PortalTheme::toCssVars($theme));
        });

        // ── Global brand ayarları — tüm portallarda firma adı + logo ────────
        // White-label: önce DB'den (manager panelden) oku, yoksa config('brand.*')'a düş
        View::composer('*', function ($view): void {
            $cid = (int) (auth()->user()?->company_id ?? 0);
            $brand = Cache::remember("brand_settings_{$cid}", 300, function () use ($cid): array {
                $fallbackName = (string) config('brand.name', 'MentorDE');
                $fallbackLogo = (string) (config('brand.logo_url') ?: config('brand.logo_path') ?: '');
                try {
                    $name    = MarketingAdminSetting::where('company_id', $cid)
                                   ->where('setting_key', 'brand_name')
                                   ->value('setting_value') ?: $fallbackName;
                    $logoUrl = MarketingAdminSetting::where('company_id', $cid)
                                   ->where('setting_key', 'brand_logo_url')
                                   ->value('setting_value') ?: $fallbackLogo;
                    return ['name' => $name, 'logo_url' => $logoUrl];
                } catch (\Throwable) {
                    return ['name' => $fallbackName, 'logo_url' => $fallbackLogo];
                }
            });
            $view->with('brandName',    $brand['name']);
            $view->with('brandInitial', strtoupper(mb_substr($brand['name'], 0, 1)));
            $view->with('brandLogoUrl', $brand['logo_url']);
        });

        View::composer('manager.layouts.app', function ($view): void {
            if (!auth()->check()) {
                $view->with('pendingLeaveCount', 0);
                return;
            }
            $cid = (int) (auth()->user()?->company_id ?? 0);
            $count = Cache::remember(
                "mgr_pending_leaves_{$cid}",
                120,
                function () use ($cid): int {
                    $employeeIds = \App\Models\User::whereIn('role', [
                        'manager','senior',
                        'system_admin','system_staff','operations_admin','operations_staff',
                        'finance_admin','finance_staff','marketing_admin','marketing_staff',
                        'sales_admin','sales_staff',
                    ])->when($cid > 0, fn($q) => $q->where('company_id', $cid))
                      ->pluck('id')->all();
                    return \App\Models\Hr\HrLeaveRequest::whereIn('user_id', $employeeIds)
                        ->where('status', 'pending')
                        ->count();
                }
            );
            $view->with('pendingLeaveCount', $count);
        });

        View::composer('senior.layouts.app', function ($view): void {
            if (!auth()->check()) {
                $view->with('sidebarKpi', ['activeStudents' => 0, 'pendingGuests' => 0, 'todayTasks' => 0, 'todayAppointments' => 0]);
                return;
            }

            $user        = auth()->user();
            $userId      = (int) $user->id;
            $seniorEmail = strtolower($user->email ?? '');

            // Cache 60 saniye: 5 ayrı DB sorgusunu tek cache hit'e indirger
            [$sidebarKpi, $dmUnread] = Cache::remember(
                "senior_sidebar_{$userId}",
                60,
                function () use ($userId, $seniorEmail): array {
                    $assignedIds = \App\Models\StudentAssignment::whereRaw('lower(senior_email) = ?', [$seniorEmail])
                        ->where('is_archived', false)
                        ->pluck('student_id');

                    $activeStudents = $assignedIds->count();

                    $pendingGuests = \App\Models\GuestApplication::whereRaw('lower(assigned_senior_email) = ?', [$seniorEmail])
                        ->where('converted_to_student', false)
                        ->whereNull('converted_student_id')
                        ->count();

                    $todayTasks = \App\Models\MarketingTask::where('assigned_user_id', $userId)
                        ->whereDate('due_date', now()->toDateString())
                        ->whereNotIn('status', ['done', 'cancelled'])
                        ->count();

                    $todayAppointments = $assignedIds->isEmpty() ? 0
                        : \App\Models\StudentAppointment::whereIn('student_id', $assignedIds->all())
                            ->whereBetween('scheduled_at', [now()->startOfDay(), now()->endOfDay()])
                            ->count();

                    $threadIds = \App\Models\DmThread::where('advisor_user_id', $userId)->pluck('id');
                    $dmUnread  = $threadIds->isEmpty() ? 0
                        : \App\Models\DmMessage::whereIn('thread_id', $threadIds)
                            ->where('is_read_by_advisor', false)
                            ->where('sender_user_id', '!=', $userId)
                            ->count();

                    return [
                        compact('activeStudents', 'pendingGuests', 'todayTasks', 'todayAppointments'),
                        $dmUnread,
                    ];
                }
            );

            $view->with('sidebarKpi', $sidebarKpi);
            $view->with('dmUnread', $dmUnread);

            // Yaklaşan üniversite başvuru deadline sayısı (7 gün içinde)
            $deadlineIn7 = 0;
            try {
                $assignedStudentIds = \App\Models\StudentAssignment::whereRaw('lower(senior_email) = ?', [$seniorEmail])
                    ->where('is_archived', false)
                    ->pluck('student_id')
                    ->all();
                if (!empty($assignedStudentIds)) {
                    $deadlineIn7 = \App\Models\StudentUniversityApplication::whereIn('student_id', $assignedStudentIds)
                        ->whereNotNull('deadline')
                        ->whereBetween('deadline', [now()->toDateString(), now()->addDays(7)->toDateString()])
                        ->where('status', '!=', 'submitted')
                        ->count();
                }
            } catch (\Throwable) {
                $deadlineIn7 = 0;
            }
            $view->with('deadlineIn7', $deadlineIn7);
        });

        // ── Bulletin unread count + urgent — tüm portal layoutları için ────────
        $bulletinComposer = function ($view): void {
            if (!auth()->check()) {
                $view->with('bulletinUnread', 0)->with('urgentBulletins', collect());
                return;
            }
            $userId     = (int) auth()->id();
            $cid        = (int) (auth()->user()?->company_id ?? 0);
            $role       = (string) (auth()->user()?->role ?? '');
            $department = (string) (auth()->user()?->department ?? '');

            $bulletinUnread = Cache::remember("bulletin_unread_{$userId}", 120, function () use ($userId, $cid, $role, $department): int {
                $readIds = BulletinRead::where('user_id', $userId)->pluck('bulletin_id')->all();
                return CompanyBulletin::active()
                    ->where(fn($q) => $q->whereNull('company_id')->orWhere('company_id', $cid))
                    ->visibleToUser($role, $department ?: null)
                    ->when(!empty($readIds), fn($q) => $q->whereNotIn('id', $readIds))
                    ->count();
            });

            $urgentBulletins = Cache::remember("urgent_bulletins_{$cid}", 60, function () use ($cid): \Illuminate\Support\Collection {
                return CompanyBulletin::active()
                    ->where('category', 'acil')
                    ->where(fn($q) => $q->whereNull('company_id')->orWhere('company_id', $cid))
                    ->orderByDesc('published_at')
                    ->get(['id', 'title']);
            });

            $view->with('bulletinUnread', $bulletinUnread)->with('urgentBulletins', $urgentBulletins);
        };

        foreach (['layouts.staff', 'senior.layouts.app', 'marketing-admin.layouts.app', 'manager.layouts.app'] as $layout) {
            View::composer($layout, $bulletinComposer);
        }

        // ── layouts.staff için sidebar değişkenleri ──────────────────────────
        View::composer('layouts.staff', function ($view): void {
            if (!auth()->check()) {
                $view->with('staffInitials', 'ST')->with('dashboardUrl', '/');
                return;
            }
            $user = auth()->user();
            $role = $user?->role ?? '';
            $view->with('staffInitials', strtoupper(substr($user?->name ?? 'ST', 0, 2)));
            $view->with('dashboardUrl', match(true) {
                in_array($role, ['manager','system_admin','system_staff','operations_admin','operations_staff']) => '/manager/dashboard',
                in_array($role, ['senior','mentor'])                                                            => '/senior/dashboard',
                in_array($role, ['marketing_admin','marketing_staff','sales_admin','sales_staff'])               => '/mktg-admin/dashboard',
                in_array($role, ['finance_admin','finance_staff'])                                              => '/staff/dashboard',
                default => '/staff/dashboard',
            });
        });

        // Task done → senior bildirim döngüsü
        MarketingTask::updated(function (MarketingTask $task): void {
            if ($task->wasChanged('status') && $task->status === 'done') {
                app(TaskFeedbackService::class)->onTaskDone($task);
            }
        });

        // Sözleşme değişiklikleri → StudentPayment oluştur veya güncelle
        GuestApplication::updated(function (GuestApplication $guest): void {
            $studentId = $guest->converted_student_id;
            if (!$studentId) {
                return;
            }

            $isSigned = in_array($guest->contract_status, ['signed', 'approved']);

            // ── Durum değişti ve imzalandı → yeni kayıt oluştur ─────────────
            if ($guest->wasChanged('contract_status') && $isSigned) {
                $amount = (float) ($guest->contract_amount_eur ?? 0);
                if ($amount <= 0) {
                    return;
                }
                $exists = StudentPayment::where('student_id', $studentId)
                    ->where('notes', 'like', '%guest_id:' . $guest->id . '%')
                    ->exists();
                if ($exists) {
                    return;
                }
                $description = 'Danışmanlık Hizmeti'
                    . ($guest->selected_package_title ? ' — ' . $guest->selected_package_title : '');
                $dueDate = $guest->contract_signed_at
                    ? \Carbon\Carbon::parse($guest->contract_signed_at)->addDays(14)->toDateString()
                    : now()->addDays(14)->toDateString();

                StudentPayment::create([
                    'company_id'     => $guest->company_id ?? null,
                    'student_id'     => $studentId,
                    'invoice_number' => StudentPayment::nextInvoiceNumber(),
                    'description'    => $description,
                    'amount_eur'     => $amount,
                    'currency'       => 'EUR',
                    'due_date'       => $dueDate,
                    'status'         => 'pending',
                    'notes'          => 'Otomatik oluşturuldu — guest_id:' . $guest->id,
                    'created_by'     => null,
                ]);
                return;
            }

            // ── Sözleşme zaten imzalı, tutar veya paket değişti → güncelle ──
            if (!$isSigned) {
                return;
            }

            $watchedFields = ['contract_amount_eur', 'selected_package_title', 'selected_package_price'];
            if (!$guest->wasChanged($watchedFields)) {
                return;
            }

            $payment = StudentPayment::where('student_id', $studentId)
                ->where('notes', 'like', '%guest_id:' . $guest->id . '%')
                ->where('status', '!=', 'cancelled')
                ->first();

            if (!$payment) {
                return;
            }

            $changes = [];
            $updateData = [];

            if ($guest->wasChanged('contract_amount_eur')) {
                $oldAmount = (float) ($guest->getOriginal('contract_amount_eur') ?? 0);
                $newAmount = (float) ($guest->contract_amount_eur ?? 0);
                if ($newAmount > 0 && $oldAmount !== $newAmount) {
                    $changes['Tutar'] = [
                        'from' => '€' . number_format($oldAmount, 2),
                        'to'   => '€' . number_format($newAmount, 2),
                    ];
                    $updateData['amount_eur'] = $newAmount;
                }
            }

            if ($guest->wasChanged('selected_package_title')) {
                $oldTitle = $guest->getOriginal('selected_package_title') ?? '—';
                $newTitle = $guest->selected_package_title ?? '—';
                if ($oldTitle !== $newTitle) {
                    $changes['Paket'] = ['from' => $oldTitle, 'to' => $newTitle];
                    $updateData['description'] = 'Danışmanlık Hizmeti — ' . $newTitle;
                }
            }

            if ($guest->wasChanged('selected_package_price')) {
                $oldPrice = $guest->getOriginal('selected_package_price') ?? '—';
                $newPrice = $guest->selected_package_price ?? '—';
                if ($oldPrice !== $newPrice) {
                    $changes['Paket Fiyatı'] = ['from' => $oldPrice, 'to' => $newPrice];
                }
            }

            if (empty($changes)) {
                return;
            }

            // Önce tutar/açıklama güncelle
            if (!empty($updateData)) {
                $payment->update($updateData);
            }

            // Değişiklik logunu kaydet + contract_updated_at damgasını bas
            $payment->applyContractUpdate($changes);
        });
    }
}
