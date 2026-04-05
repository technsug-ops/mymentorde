<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\StudentWorkflowTrait;
use App\Models\UserPortalPreference;
use App\Rules\ValidFileMagicBytes;
use App\Services\ImageOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class StudentProfileController extends Controller
{
    use StudentWorkflowTrait;

    public function update(Request $request)
    {
        $guest = $this->resolveStudentGuest($request);
        abort_if(! $guest, 404, 'Student icin bagli basvuru kaydi bulunamadi.');

        $data = $request->validate([
            'first_name'             => ['required', 'string', 'max:80'],
            'last_name'              => ['required', 'string', 'max:80'],
            'phone'                  => ['nullable', 'string', 'max:40'],
            'gender'                 => ['nullable', 'in:kadin,erkek,belirtmek_istemiyorum,not_specified'],
            'application_country'    => ['nullable', 'string', 'max:80'],
            'communication_language' => ['nullable', 'in:tr,de,en'],
            'target_city'            => ['nullable', 'string', 'max:100'],
            'target_term'            => ['nullable', 'string', 'max:60'],
            'language_level'         => ['nullable', 'string', 'max:32'],
            'notes'                  => ['nullable', 'string', 'max:5000'],
        ]);

        $guest->forceFill([
            'first_name'             => trim((string) $data['first_name']),
            'last_name'              => trim((string) $data['last_name']),
            'phone'                  => trim((string) ($data['phone'] ?? '')) ?: null,
            'gender'                 => trim((string) ($data['gender'] ?? '')) ?: null,
            'application_country'    => trim((string) ($data['application_country'] ?? '')) ?: null,
            'communication_language' => trim((string) ($data['communication_language'] ?? '')) ?: null,
            'target_city'            => trim((string) ($data['target_city'] ?? '')) ?: null,
            'target_term'            => trim((string) ($data['target_term'] ?? '')) ?: null,
            'language_level'         => trim((string) ($data['language_level'] ?? '')) ?: null,
            'notes'                  => trim((string) ($data['notes'] ?? '')) ?: null,
            'status_message'         => 'Profil bilgileri guncellendi.',
        ])->save();

        $user = $request->user();
        if ($user) {
            $user->name = trim((string) $data['first_name'] . ' ' . (string) $data['last_name']);
            $user->save();
        }

        return redirect('/student/profile')->with('status', 'Profil kaydedildi.');
    }

    public function updateSettings(Request $request)
    {
        $guest = $this->resolveStudentGuest($request);
        abort_if(! $guest, 404, 'Student icin bagli basvuru kaydi bulunamadi.');

        $data = $request->validate([
            'preferred_locale'      => ['required', 'in:tr,de,en'],
            'preferred_timezone'    => ['nullable', 'string', 'timezone'],
            'preferred_date_format' => ['nullable', 'in:DD.MM.YYYY,YYYY-MM-DD,MM/DD/YYYY'],
            'notifications_enabled' => ['nullable', 'boolean'],
            'notify_email'          => ['nullable', 'boolean'],
            'notify_whatsapp'       => ['nullable', 'boolean'],
            'notify_inapp'          => ['nullable', 'boolean'],
        ]);

        $guest->forceFill([
            'preferred_locale'      => (string) $data['preferred_locale'],
            'communication_language'=> (string) $data['preferred_locale'],
            'notifications_enabled' => (bool) ($data['notifications_enabled'] ?? false),
            'notify_email'          => (bool) ($data['notify_email'] ?? false),
            'notify_whatsapp'       => (bool) ($data['notify_whatsapp'] ?? false),
            'notify_inapp'          => (bool) ($data['notify_inapp'] ?? false),
            'status_message'        => 'Ayarlar guncellendi.',
        ])->save();

        $user = $request->user();
        if ($user && (! empty($data['preferred_timezone']) || ! empty($data['preferred_date_format']))) {
            $pref = UserPortalPreference::firstOrNew([
                'user_id'    => $user->id,
                'portal_key' => 'student',
            ]);
            $json = $pref->preferences_json ?? [];
            if (! empty($data['preferred_timezone']))    $json['timezone']    = $data['preferred_timezone'];
            if (! empty($data['preferred_date_format'])) $json['date_format'] = $data['preferred_date_format'];
            $pref->preferences_json = $json;
            $pref->save();
        }

        return redirect('/student/settings')->with('status', 'Ayarlar kaydedildi.');
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();
        abort_if(! $user, 401, 'Oturum bulunamadi.');

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password'     => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()->max(128)],
        ]);

        if (! Hash::check((string) $data['current_password'], (string) $user->password)) {
            return redirect('/student/settings')->withErrors(['current_password' => 'Mevcut sifre hatali.']);
        }

        $user->password = Hash::make((string) $data['new_password']);
        $user->save();

        return redirect('/student/settings')->with('status', 'Sifre guncellendi.');
    }

    public function uploadPhoto(Request $request)
    {
        $guest = $this->resolveStudentGuest($request);
        abort_if(! $guest, 404, 'Student icin bagli basvuru kaydi bulunamadi.');

        $data = $request->validate([
            'profile_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', new ValidFileMagicBytes()],
        ]);

        $file     = $data['profile_photo'];
        $baseName = 'profile_' . now()->format('Ymd_His');

        $oldPath = trim((string) ($guest->profile_photo_path ?? ''));
        if ($oldPath !== '' && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $path = app(ImageOptimizationService::class)
            ->optimizeProfilePhoto($file, "student-profile/{$guest->id}", $baseName);

        $guest->forceFill([
            'profile_photo_path' => $path,
            'status_message'     => 'Profil fotografi guncellendi.',
        ])->save();

        return redirect()->route('student.profile')->with('status', 'Profil fotografi yuklendi.');
    }
}
