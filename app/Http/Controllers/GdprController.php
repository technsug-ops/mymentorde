<?php

namespace App\Http\Controllers;

use App\Models\GuestApplication;
use App\Models\ManagerRequest;
use App\Services\EventLogService;
use App\Services\PersonalDataExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GdprController extends Controller
{
    public function __construct(
        private readonly PersonalDataExportService $exportService,
        private readonly EventLogService $eventLog,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Student
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /student/gdpr/export
     * GDPR Madde 20 — Student kişisel verilerini JSON olarak indir.
     */
    public function exportStudentData(Request $request)
    {
        $user = Auth::user();
        $data = $this->exportService->exportForStudent($user);

        $this->eventLog->log(
            'gdpr.data_export',
            'user',
            (string) $user->id,
            'Öğrenci kişisel veri dışa aktarması gerçekleştirildi.',
            ['ip' => $request->ip()],
            $user->email,
        );

        $filename = 'mentorde-kisisel-verilerim-' . now()->format('Ymd') . '.json';

        return response()->streamDownload(
            fn () => print(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)),
            $filename,
            ['Content-Type' => 'application/json'],
        );
    }

    /**
     * POST /student/gdpr/erasure
     * GDPR Madde 17 — Veri silme talebi oluşturur (manager onayına gider).
     */
    public function requestStudentErasure(Request $request)
    {
        $user = Auth::user();

        // Zaten açık bir talep varsa ikinci talebe izin verme
        $existing = ManagerRequest::query()
            ->where('requester_user_id', $user->id)
            ->where('request_type', 'gdpr_erasure')
            ->whereIn('status', ['pending', 'in_review'])
            ->first();

        if ($existing) {
            return back()->with('error', 'Zaten bekleyen bir silme talebiniz var. Talebin işlenmesini bekleyin.');
        }

        ManagerRequest::query()->create([
            'company_id'         => app()->bound('current_company_id') ? (int) app('current_company_id') : null,
            'requester_user_id'  => $user->id,
            'request_type'       => 'gdpr_erasure',
            'subject'            => 'GDPR Veri Silme Talebi — ' . $user->email,
            'description'        => 'Kullanıcı GDPR Madde 17 kapsamında kişisel verilerinin anonimleştirilmesini talep etmektedir.',
            'status'             => 'pending',
            'priority'           => 'high',
            'requested_at'       => now(),
            'source_type'        => 'user',
            'source_id'          => (string) $user->id,
        ]);

        $this->eventLog->log(
            'gdpr.erasure_request',
            'user',
            (string) $user->id,
            'GDPR veri silme talebi oluşturuldu.',
            ['ip' => $request->ip()],
            $user->email,
        );

        return back()->with('success', 'Veri silme talebiniz alındı. Yöneticiniz tarafından işleme alınacaktır.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Guest
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /guest/gdpr/export
     * GDPR Madde 20 — Guest kişisel verilerini JSON olarak indir.
     */
    public function exportGuestData(Request $request)
    {
        $user = Auth::user();
        $cid  = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;

        $app = GuestApplication::query()
            ->where('guest_user_id', $user->id)
            ->when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->latest()
            ->firstOrFail();

        $data = $this->exportService->exportForGuest($user, $app);

        $this->eventLog->log(
            'gdpr.data_export',
            'guest_application',
            (string) $app->id,
            'Guest kişisel veri dışa aktarması gerçekleştirildi.',
            ['ip' => $request->ip()],
            $user->email,
        );

        $filename = 'mentorde-kisisel-verilerim-' . now()->format('Ymd') . '.json';

        return response()->streamDownload(
            fn () => print(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)),
            $filename,
            ['Content-Type' => 'application/json'],
        );
    }

    /**
     * POST /guest/gdpr/erasure
     * GDPR Madde 17 — Guest başvurusu için veri silme talebi.
     */
    public function requestGuestErasure(Request $request)
    {
        $user = Auth::user();
        $cid  = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;

        $app = GuestApplication::query()
            ->where('guest_user_id', $user->id)
            ->when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->latest()
            ->firstOrFail();

        $existing = ManagerRequest::query()
            ->where('requester_user_id', $user->id)
            ->where('request_type', 'gdpr_erasure')
            ->whereIn('status', ['pending', 'in_review'])
            ->first();

        if ($existing) {
            return back()->with('error', 'Zaten bekleyen bir silme talebiniz var.');
        }

        ManagerRequest::query()->create([
            'company_id'         => $cid ?: null,
            'requester_user_id'  => $user->id,
            'request_type'       => 'gdpr_erasure',
            'subject'            => 'GDPR Veri Silme Talebi (Guest) — ' . $app->email,
            'description'        => 'Başvuru sahibi GDPR Madde 17 kapsamında kişisel verilerinin anonimleştirilmesini talep etmektedir.',
            'status'             => 'pending',
            'priority'           => 'high',
            'requested_at'       => now(),
            'source_type'        => 'guest_application',
            'source_id'          => (string) $app->id,
        ]);

        $this->eventLog->log(
            'gdpr.erasure_request',
            'guest_application',
            (string) $app->id,
            'GDPR veri silme talebi (guest) oluşturuldu.',
            ['ip' => $request->ip()],
            $user->email,
        );

        return back()->with('success', 'Veri silme talebiniz alındı. Yöneticiniz tarafından işleme alınacaktır.');
    }
}
