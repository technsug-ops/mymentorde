<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\GuestApplication;
use Illuminate\Contracts\View\View;

/**
 * Generic Application Guide controller — config-driven.
 *
 * Her guide config/application_guides.php içinde tanımlı:
 *   - aps        (Akademische Prüfstelle)
 *   - anmeldung  (Şehir kaydı)
 *   - sperrkonto (Bloke hesap)
 *   - vpd        (Vorprüfungsdokumentation)
 *
 * URL pattern:
 *   GET /manager/guests/{guest}/rehber/{slug}
 *   GET /manager/students/{studentId}/rehber/{slug}
 *
 * Uni-Assist + Vize guide pattern'inden hafifletilmiş —
 * field mapping basit ($guest model'inden çıkarım), checklist tabanlı.
 */
class ApplicationGuideController extends Controller
{
    public function showForGuest(GuestApplication $guest, string $slug): View
    {
        return $this->renderShow($guest, null, $slug);
    }

    public function showForStudent(string $studentId, string $slug): View
    {
        $guest = GuestApplication::query()
            ->where('converted_student_id', $studentId)
            ->firstOrFail();
        return $this->renderShow($guest, $studentId, $slug);
    }

    private function renderShow(GuestApplication $guest, ?string $studentId, string $slug): View
    {
        $guides = config('application_guides', []);
        abort_unless(isset($guides[$slug]), 404, "Rehber bulunamadı: {$slug}");

        $guide = $guides[$slug];

        // Field values: $guest->{key} → display value
        $fieldValues = [];
        foreach (($guide['fields'] ?? []) as $key => $meta) {
            $raw = $guest->{$key} ?? null;
            if ($raw instanceof \Carbon\CarbonInterface) {
                $raw = $raw->format('d.m.Y');
            }
            $fieldValues[$key] = [
                'label' => $meta['label'] ?? $key,
                'desc'  => $meta['desc']  ?? '',
                'value' => $raw !== null && $raw !== '' ? (string) $raw : '—',
                'has'   => $raw !== null && $raw !== '',
            ];
        }

        return view('manager.application-guide.show', [
            'guest'       => $guest,
            'studentId'   => $studentId,
            'slug'        => $slug,
            'guide'       => $guide,
            'fieldValues' => $fieldValues,
            'allSlugs'    => array_keys($guides),
        ]);
    }
}
