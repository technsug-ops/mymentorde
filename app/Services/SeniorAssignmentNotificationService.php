<?php

namespace App\Services;

use App\Models\GuestApplication;
use App\Models\StudentAssignment;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Senior atama bildirimi servisi — ADDON / SEPARABLE.
 *
 * ManagerPortalController::guestAssignSenior + StudentAssignmentController::upsert
 * tarafından çağrılır. Atama yapıldıktan SONRA çağrılır — bu servis fail etse bile
 * atama korunur (try/catch zarfı çağıran tarafta).
 *
 * Bu servisin tamamı silinse:
 * - Bildirim gönderilmez (graceful degradation)
 * - Atama akışı KIRILMAZ
 * - DM thread auto-create (ayrı concern) etkilenmez
 *
 * Module: notifications (her zaman açık — core)
 */
class SeniorAssignmentNotificationService
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Aday öğrenciye senior atandığında 2 in-app bildirim dispatch eder.
     */
    public function notifyForGuest(GuestApplication $guest, string $seniorEmail, ?string $triggeredBy = null): void
    {
        $this->dispatch(
            personId: (string) $guest->id,
            seniorEmail: $seniorEmail,
            personName: trim($guest->first_name . ' ' . $guest->last_name),
            companyId: (int) ($guest->company_id ?? 0),
            triggeredBy: $triggeredBy,
            isGuest: true,
        );
    }

    /**
     * Öğrenciye senior atandığında 2 in-app bildirim dispatch eder.
     */
    public function notifyForStudent(StudentAssignment $assignment, string $seniorEmail, ?string $triggeredBy = null): void
    {
        // Öğrenci adı için converted_student_id üzerinden GuestApplication lookup
        $guestApp = GuestApplication::query()
            ->where('converted_student_id', $assignment->student_id)
            ->first();
        $studentName = $guestApp
            ? trim($guestApp->first_name . ' ' . $guestApp->last_name)
            : (string) $assignment->student_id;

        $this->dispatch(
            personId: (string) $assignment->student_id,
            seniorEmail: $seniorEmail,
            personName: $studentName,
            companyId: (int) ($assignment->company_id ?? 0),
            triggeredBy: $triggeredBy,
            isGuest: false,
        );
    }

    /**
     * Ortak dispatch logic. Tüm hata noktaları try/catch içinde —
     * bildirim fail etse atama controller'da etkilenmez.
     */
    private function dispatch(
        string $personId,
        string $seniorEmail,
        string $personName,
        int $companyId,
        ?string $triggeredBy,
        bool $isGuest,
    ): void {
        try {
            $senior = User::query()->where('email', $seniorEmail)->first();
            $seniorName = $senior?->name ?: $seniorEmail;

            // 1) Senior'a bildirim — kişi sana atandı
            if ($senior) {
                try {
                    $this->notificationService->send([
                        'channel'      => 'in_app',
                        'category'     => 'senior_assignment.received',
                        'user_id'      => (int) $senior->id,
                        'company_id'   => $companyId,
                        'subject'      => $isGuest ? 'Yeni aday öğrenci atandı' : 'Yeni öğrenci atandı',
                        'body'         => "{$personName} sana atandı. Mesajlardan iletişime geçebilirsin.",
                        'source_type'  => $isGuest ? 'guest_application' : 'student_assignment',
                        'source_id'    => $personId,
                        'triggered_by' => $triggeredBy,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('senior_assignment.notify_senior_failed', [
                        'senior_email' => $seniorEmail,
                        'error'        => $e->getMessage(),
                    ]);
                }
            }

            // 2) Aday öğrenci / öğrenciye bildirim — danışmanın atandı (email gizli, isim açık)
            try {
                $this->notificationService->send([
                    'channel'      => 'in_app',
                    'category'     => 'senior_assignment.assigned',
                    'guest_id'     => $isGuest ? $personId : null,
                    'student_id'   => $isGuest ? null : $personId,
                    'company_id'   => $companyId,
                    'subject'      => 'Eğitim danışmanın atandı',
                    'body'         => "Eğitim danışmanın artık {$seniorName}. Mesajlar sekmesinden iletişime geçebilirsin.",
                    'source_type'  => $isGuest ? 'guest_application' : 'student_assignment',
                    'source_id'    => $personId,
                    'triggered_by' => $triggeredBy,
                ]);
            } catch (\Throwable $e) {
                Log::warning('senior_assignment.notify_person_failed', [
                    'person_id' => $personId,
                    'is_guest'  => $isGuest,
                    'error'     => $e->getMessage(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('senior_assignment.dispatch_failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
