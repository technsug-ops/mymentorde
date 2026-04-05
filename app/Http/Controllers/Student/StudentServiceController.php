<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\UsesServicePackages;
use App\Http\Controllers\Student\Concerns\StudentWorkflowTrait;
use Illuminate\Http\Request;

class StudentServiceController extends Controller
{
    use UsesServicePackages;
    use StudentWorkflowTrait;

    public function selectPackage(Request $request)
    {
        $guest = $this->resolveStudentGuest($request);
        abort_if(! $guest, 404, 'Student icin bagli basvuru kaydi bulunamadi.');
        if ($this->isServiceSelectionLockedByContract($guest)) {
            return redirect('/student/contract')->withErrors([
                'contract' => 'Sozlesme talebi acildiktan sonra paket degisikligi dogrudan yapilamaz. "Sozlesmeyi Guncelle Talebi" alanini kullanin.',
            ]);
        }

        $data = $request->validate([
            'package_code' => ['required', 'string', 'in:pkg_basic,pkg_plus,pkg_premium'],
        ]);

        $packages = collect($this->servicePackages())->keyBy('code');
        $pkg      = (array) $packages->get(trim((string) $data['package_code']));

        $guest->forceFill([
            'selected_package_code'  => (string) ($pkg['code'] ?? ''),
            'selected_package_title' => (string) ($pkg['title'] ?? ''),
            'selected_package_price' => (string) ($pkg['price'] ?? ''),
            'package_selected_at'    => now(),
            'status_message'         => 'Paket secimi guncellendi.',
        ])->save();

        $this->createStudentServiceTask(
            guest: $guest,
            title: 'Student paket secimi',
            description: "Student {$guest->converted_student_id} paket secti: " . trim((string) ($pkg['title'] ?? '')) . " (" . trim((string) $data['package_code']) . ")",
            priority: 'normal'
        );

        return redirect('/student/services')->with('status', 'Paket secimi kaydedildi.');
    }

    public function addExtra(Request $request)
    {
        $guest = $this->resolveStudentGuest($request);
        abort_if(! $guest, 404, 'Student icin bagli basvuru kaydi bulunamadi.');
        if ($this->isServiceSelectionLockedByContract($guest)) {
            return redirect('/student/contract')->withErrors([
                'contract' => 'Sozlesme talebi acildiktan sonra ek servis degisikligi dogrudan yapilamaz. "Sozlesmeyi Guncelle Talebi" alanini kullanin.',
            ]);
        }

        $data = $request->validate([
            'extra_code' => ['required', 'string', 'in:vip_meeting,blocked_account_support,visa_file_review,airport_pickup,accommodation_support,uni_dept_selection,uni_assist_apply,uni_application_tracking,visa_consulate_appointment,visa_file_preparation,visa_intent_letter,visa_interview_orient,finance_blocked_account,finance_health_insurance,accom_arrangement,accom_dorm_apply,accom_info,abroad_deutschlandticket,abroad_phone_line,abroad_airport_pickup,abroad_bank_account,abroad_residence_reg,abroad_foreigners_office,abroad_health_activate,abroad_life_seminar'],
        ]);

        $extraOptions = collect($this->extraServiceOptions())->keyBy('code');
        $extras       = is_array($guest->selected_extra_services) ? $guest->selected_extra_services : [];
        $code         = trim((string) $data['extra_code']);
        $meta         = (array) $extraOptions->get($code);
        $exists       = collect($extras)->contains(fn ($x) => (string) ($x['code'] ?? '') === $code);
        if (! $exists) {
            $extras[] = [
                'code'     => (string) ($meta['code'] ?? $code),
                'title'    => (string) ($meta['title'] ?? $code),
                'added_at' => now()->toDateTimeString(),
            ];
        }

        $extras = collect($extras)->unique('code')->values()->all();

        $guest->forceFill([
            'selected_extra_services' => $extras,
            'status_message'          => 'Ek servisler guncellendi.',
        ])->save();

        $this->createStudentServiceTask(
            guest: $guest,
            title: 'Student ek servis eklendi',
            description: "Student {$guest->converted_student_id} ek servis ekledi: {$code}",
            priority: 'low'
        );

        return redirect('/student/services')->with('status', 'Ek servis eklendi.');
    }

    public function removeExtra(Request $request, string $extraCode)
    {
        $guest = $this->resolveStudentGuest($request);
        abort_if(! $guest, 404, 'Student icin bagli basvuru kaydi bulunamadi.');
        if ($this->isServiceSelectionLockedByContract($guest)) {
            return redirect('/student/contract')->withErrors([
                'contract' => 'Sozlesme talebi acildiktan sonra ek servis kaldirma dogrudan yapilamaz. "Sozlesmeyi Guncelle Talebi" alanini kullanin.',
            ]);
        }

        $code     = trim((string) $extraCode);
        $extras   = is_array($guest->selected_extra_services) ? $guest->selected_extra_services : [];
        $filtered = collect($extras)
            ->reject(fn ($x) => (string) ($x['code'] ?? '') === $code)
            ->values()
            ->all();

        $guest->forceFill([
            'selected_extra_services' => $filtered,
            'status_message'          => 'Ek servis listesi guncellendi.',
        ])->save();

        $this->createStudentServiceTask(
            guest: $guest,
            title: 'Student ek servis kaldirildi',
            description: "Student {$guest->converted_student_id} ek servis kaldirdi: {$code}",
            priority: 'low'
        );

        return redirect('/student/services')->with('status', 'Ek servis kaldirildi.');
    }
}
