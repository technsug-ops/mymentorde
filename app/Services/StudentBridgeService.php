<?php

namespace App\Services;

use App\Models\GuestApplication;
use App\Models\StudentAssignment;
use App\Models\StudentType;
use Illuminate\Support\Str;

/**
 * Aday öğrenci (GuestApplication) → öğrenci süreç takibi köprüsü.
 *
 * "Evrak Bekliyor" (docs_pending) aşamasına gelen ve bir senior'a (takipçi)
 * atanmış aday öğrenci için, sözleşme/tam dönüşümü BEKLEMEDEN bir takip kaydı
 * (StudentAssignment) oluşturur. Böylece öğrenci Süreç Takibi + Student Pipeline
 * kanban'ında "Başvuru Hazırlık" (application_prep) aşamasında görünür.
 *
 * KRİTİK: converted_to_student'a DOKUNMAZ — tam dönüşüm (student rolü, sözleşme)
 * ayrı kalır. Sadece converted_student_id bağını kurar. convert() bu kaydı
 * yeniden kullanır (mükerrer öğrenci oluşmaz).
 *
 * Idempotent: converted_student_id zaten doluysa mevcut kaydı döndürür.
 */
class StudentBridgeService
{
    /**
     * Aday öğrenciyi süreç takibine köprüle. Idempotent.
     * @return StudentAssignment|null  Köprü kurulamadıysa (senior yoksa) null.
     */
    public function bridgeFromGuest(GuestApplication $guest, ?string $seniorEmail = null): ?StudentAssignment
    {
        // Zaten köprülenmiş / dönüşmüş → mevcut takip kaydını döndür
        $existingSid = trim((string) ($guest->converted_student_id ?? ''));
        if ($existingSid !== '') {
            return StudentAssignment::query()->withoutGlobalScopes()
                ->where('student_id', $existingSid)->first();
        }

        // Takipçi senior yoksa köprü kurma (süreç takibi senior'a bağlı)
        $seniorEmail = strtolower(trim((string) ($seniorEmail ?: $guest->assigned_senior_email ?? '')));
        if ($seniorEmail === '') {
            return null;
        }

        $companyId = (int) ($guest->company_id ?: 0);
        $typeCode  = $this->mapApplicationTypeToStudentTypeCode((string) $guest->application_type);
        $identity  = $this->generateStudentIdentity($typeCode);

        $assignment = StudentAssignment::query()->create([
            'company_id'        => $companyId > 0 ? $companyId : null,
            'student_id'        => $identity['student_id'],
            'internal_sequence' => $identity['internal_sequence'],
            'senior_email'      => $seniorEmail,
            'display_name'      => trim((string) (($guest->first_name ?? '') . ' ' . ($guest->last_name ?? ''))) ?: null,
            'branch'            => trim((string) ($guest->branch ?? '')) ?: null,
            'risk_level'        => 'normal',
            'payment_status'    => 'ok',
            'dealer_id'         => trim((string) ($guest->dealer_code ?? '')) ?: null,
            'student_type'      => $typeCode,
            'is_archived'       => false,
        ]);

        // Aday ↔ takip kaydı bağı. converted_to_student FALSE kalır (henüz tam dönüşmedi).
        $guest->forceFill(['converted_student_id' => $assignment->student_id])->save();

        // "Başvuru hazırlığını başlat" tetikleyici task'ı (senior'a). Addon — fail olursa köprü bozulmaz.
        try {
            app(\App\Services\TaskAutomationService::class)->ensureApplicationPrepTask(
                $assignment,
                trim((string) (($guest->first_name ?? '') . ' ' . ($guest->last_name ?? ''))) ?: null
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('application_prep_task_failed', [
                'student_id' => $assignment->student_id,
                'error'      => $e->getMessage(),
            ]);
        }

        return $assignment;
    }

    /** application_type → student type code (convert() ile aynı eşleme). */
    public function mapApplicationTypeToStudentTypeCode(string $applicationType): string
    {
        return match (strtolower(trim($applicationType))) {
            'master'     => 'master',
            'ausbildung' => 'ausbildung',
            default      => 'bachelor',
        };
    }

    /** Benzersiz student_id üret (convert() ile aynı mantık). */
    public function generateStudentIdentity(string $studentTypeCode): array
    {
        $input = strtoupper(trim($studentTypeCode));
        $studentType = StudentType::query()
            ->where('code', strtolower($input))
            ->orWhere('code', $input)
            ->orWhere('id_prefix', $input)
            ->first();
        if (!$studentType) {
            abort(422, 'Student type bulunamadi.');
        }

        $prefix = strtoupper((string) $studentType->id_prefix);
        $base   = "{$prefix}-" . now()->format('y') . '-' . now()->format('m');
        $nextSequence = ((int) StudentAssignment::query()->max('internal_sequence')) + 1;

        do {
            $suffix    = preg_replace('/[^A-Z0-9]/', 'X', strtoupper(Str::random(4))) ?: 'X' . strtoupper(Str::random(3));
            $candidate = "{$base}-{$suffix}";
        } while (StudentAssignment::query()->where('student_id', $candidate)->exists());

        return ['student_id' => $candidate, 'internal_sequence' => $nextSequence];
    }
}
