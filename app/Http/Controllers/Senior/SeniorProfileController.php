<?php

namespace App\Http\Controllers\Senior;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Senior\Concerns\SeniorPortalTrait;
use App\Models\BusinessContract;
use App\Models\Hr\HrLeaveRequest;
use App\Models\Hr\HrPersonProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class SeniorProfileController extends Controller
{
    use SeniorPortalTrait;

    public function materials(): \Illuminate\Http\RedirectResponse
    {
        return redirect('/senior/knowledge-base');
    }

    public function profile(Request $request)
    {
        $prefs   = $this->seniorPortalPreferences($request);
        $user    = $request->user();
        $year    = (int) $request->query('year', now()->year);
        $profile = HrPersonProfile::firstOrNew(['user_id' => $user->id]);
        $quota   = $profile->annual_leave_quota ?? 14;
        $used    = $profile->exists ? $profile->usedLeaveDays($year) : 0;
        $leaves  = HrLeaveRequest::where('user_id', $user->id)
            ->with('attachments')
            ->orderByDesc('start_date')
            ->get();

        $contracts = BusinessContract::where('user_id', $user->id)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->orderByDesc('issued_at')
            ->get();

        return view('senior.profile', [
            'portalPrefs'    => $prefs,
            'weeklySchedule' => $this->normalizeWeeklySchedule((array) data_get($prefs, 'profile.weekly_schedule', [])),
            'sidebarStats'   => $this->sidebarStats($request),
            'leaves'         => $leaves,
            'quota'          => $quota,
            'used'           => $used,
            'remaining'      => max(0, $quota - $used),
            'leaveYear'      => $year,
            'contracts'      => $contracts,
        ]);
    }

    public function settings(Request $request)
    {
        $prefs = $this->seniorPortalPreferences($request);

        return view('senior.settings', [
            'portalPrefs'  => $prefs,
            'sidebarStats' => $this->sidebarStats($request),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'name'                         => ['required', 'string', 'max:120'],
            'phone'                        => ['nullable', 'string', 'max:40'],
            'title'                        => ['nullable', 'string', 'max:120'],
            'bio'                          => ['nullable', 'string', 'max:2000'],
            'expertise'                    => ['nullable', 'string', 'max:500'],
            'languages'                    => ['nullable', 'string', 'max:300'],
            'appointment_note'             => ['nullable', 'string', 'max:1000'],
            'weekly_schedule'              => ['nullable', 'array'],
            'weekly_schedule.*.enabled'    => ['nullable'],
            'weekly_schedule.*.start'      => ['nullable', 'date_format:H:i'],
            'weekly_schedule.*.end'        => ['nullable', 'date_format:H:i'],
            'weekly_schedule.*.note'       => ['nullable', 'string', 'max:80'],
        ]);

        $user       = $request->user();
        $user->name = (string) $data['name'];
        $user->save();

        $prefs = $this->seniorPortalPreferences($request);
        data_set($prefs, 'profile.phone', trim((string) ($data['phone'] ?? '')));
        data_set($prefs, 'profile.title', trim((string) ($data['title'] ?? '')));
        data_set($prefs, 'profile.bio', trim((string) ($data['bio'] ?? '')));
        data_set($prefs, 'profile.expertise', trim((string) ($data['expertise'] ?? '')));
        data_set($prefs, 'profile.languages', trim((string) ($data['languages'] ?? '')));
        data_set($prefs, 'profile.appointment_note', trim((string) ($data['appointment_note'] ?? '')));
        $weeklySchedule = $this->normalizeWeeklySchedule((array) ($data['weekly_schedule'] ?? []));
        data_set($prefs, 'profile.weekly_schedule', $weeklySchedule);
        data_set($prefs, 'profile.working_hours_weekdays', $this->scheduleSummary($weeklySchedule, ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']));
        data_set($prefs, 'profile.working_hours_weekend', $this->scheduleSummary($weeklySchedule, ['saturday', 'sunday']));
        $this->saveSeniorPortalPreferences($request, $prefs);

        return back()->with('status', 'Profil guncellendi.');
    }

    public function updateSettings(Request $request)
    {
        $settings = [
            'notify_email'               => (bool) $request->boolean('notify_email'),
            'notify_ticket'              => (bool) $request->boolean('notify_ticket'),
            'notify_appointment'         => (bool) $request->boolean('notify_appointment'),
            'notify_dm'                  => (bool) $request->boolean('notify_dm'),
            'appointment_auto_confirm'   => (bool) $request->boolean('appointment_auto_confirm'),
            'appointment_buffer_minutes' => max(0, min(180, (int) $request->input('appointment_buffer_minutes', 15))),
            'appointment_slot_minutes'   => max(15, min(180, (int) $request->input('appointment_slot_minutes', 30))),
        ];

        $prefs = $this->seniorPortalPreferences($request);
        data_set($prefs, 'settings', $settings);
        $this->saveSeniorPortalPreferences($request, $prefs);

        return back()->with('status', 'Ayarlar kaydedildi.');
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password'     => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()->max(128)],
        ]);

        $user = $request->user();
        if (!$user || !Hash::check((string) $data['current_password'], (string) $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Mevcut sifre hatali.',
            ]);
        }

        $user->password = Hash::make((string) $data['new_password']);
        $user->save();

        return back()->with('status', 'Sifre guncellendi.');
    }
}
