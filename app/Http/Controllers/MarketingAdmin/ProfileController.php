<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\BusinessContract;
use App\Models\Hr\HrLeaveAttachment;
use App\Models\Hr\HrLeaveRequest;
use App\Models\Hr\HrPersonProfile;
use App\Models\MarketingTeam;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user  = $request->user();
        $team  = MarketingTeam::query()->where('user_id', $user?->id)->first();
        $year  = (int) $request->query('year', now()->year);

        $profile   = HrPersonProfile::firstOrNew(['user_id' => $user->id]);
        $quota     = $profile->annual_leave_quota ?? 14;
        $used      = $profile->exists ? $profile->usedLeaveDays($year) : 0;
        $leaves    = HrLeaveRequest::where('user_id', $user->id)->with('attachments')->orderByDesc('start_date')->get();
        $contracts = BusinessContract::where('user_id', $user->id)->whereNotIn('status', ['draft', 'cancelled'])->orderByDesc('issued_at')->get();

        return view('marketing-admin.profile.index', [
            'pageTitle'            => 'Profil',
            'title'                => 'Profil Bilgileri',
            'user'                 => $user,
            'team'                 => $team,
            'effectivePermissions' => method_exists($user, 'effectivePermissionCodes') ? $user->effectivePermissionCodes() : [],
            'leaves'               => $leaves,
            'quota'                => $quota,
            'used'                 => $used,
            'remaining'            => max(0, $quota - $used),
            'leaveYear'            => $year,
            'contracts'            => $contracts,
        ]);
    }

    public function downloadAttachment(HrLeaveAttachment $attachment)
    {
        $user  = auth()->user();
        $leave = $attachment->leaveRequest;
        abort_unless($leave->user_id === $user->id, 403);
        abort_unless($attachment->type === 'file' && $attachment->path, 404);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();
        $payload = ['name' => $data['name']];
        if (!empty($data['password'])) {
            $payload['password'] = $data['password'];
        }
        $user->update($payload);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'name' => $user->name], Response::HTTP_OK);
        }

        return redirect('/mktg-admin/profile')->with('status', 'Profil guncellendi.');
    }
}
