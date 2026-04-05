<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\HrCertification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HrCertificationController extends Controller
{
    private const ALL_EMPLOYEE_ROLES = [
        'manager', 'senior',
        'system_admin', 'system_staff',
        'operations_admin', 'operations_staff',
        'finance_admin', 'finance_staff',
        'marketing_admin', 'marketing_staff',
        'sales_admin', 'sales_staff',
    ];

    private function companyId(): int
    {
        return (int) (auth()->user()?->company_id ?? 0);
    }

    public function index(Request $request)
    {
        $cid        = $this->companyId();
        $userFilter = $request->query('user_id', '');
        $statusFilter = $request->query('status', '');

        $employeeIds = User::whereIn('role', self::ALL_EMPLOYEE_ROLES)
            ->when($cid > 0, fn($q) => $q->where('company_id', $cid))
            ->pluck('id')
            ->all();

        $query = HrCertification::whereIn('user_id', $employeeIds)
            ->with('user:id,name,role')
            ->when($userFilter !== '', fn($q) => $q->where('user_id', $userFilter))
            ->orderBy('expiry_date')
            ->orderByDesc('issue_date');

        $allCerts = $query->get();

        // Filtrele status (isExpired/isExpiringSoon computed'da olduğu için PHP'de filtrele)
        $certs = match ($statusFilter) {
            'expired'  => $allCerts->filter(fn($c) => $c->isExpired()),
            'soon'     => $allCerts->filter(fn($c) => $c->isExpiringSoon()),
            'active'   => $allCerts->filter(fn($c) => !$c->isExpired() && !$c->isExpiringSoon()),
            default    => $allCerts,
        };

        $employees = User::whereIn('role', self::ALL_EMPLOYEE_ROLES)
            ->when($cid > 0, fn($q) => $q->where('company_id', $cid))
            ->orderBy('name')
            ->get(['id', 'name']);

        $expiredCount  = $allCerts->filter(fn($c) => $c->isExpired())->count();
        $soonCount     = $allCerts->filter(fn($c) => $c->isExpiringSoon())->count();

        return view('manager.hr.certifications.index', compact(
            'certs', 'employees', 'userFilter', 'statusFilter', 'expiredCount', 'soonCount'
        ));
    }

    public function store(Request $request)
    {
        $cid  = $this->companyId();
        $data = $request->validate([
            'user_id'     => 'required|exists:users,id',
            'cert_name'   => 'required|string|max:200',
            'issuer'      => 'nullable|string|max:200',
            'issue_date'  => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'notes'       => 'nullable|string|max:500',
        ]);

        $data['company_id'] = $cid ?: null;
        HrCertification::create($data);

        return back()->with('status', 'Sertifika eklendi.');
    }

    public function update(Request $request, HrCertification $hrCertification)
    {
        $data = $request->validate([
            'cert_name'   => 'required|string|max:200',
            'issuer'      => 'nullable|string|max:200',
            'issue_date'  => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'notes'       => 'nullable|string|max:500',
        ]);

        $hrCertification->update($data);
        return back()->with('status', 'Sertifika güncellendi.');
    }

    public function destroy(HrCertification $hrCertification)
    {
        $hrCertification->delete();
        return back()->with('status', 'Sertifika silindi.');
    }

    // ── Self-servis: çalışan kendi sertifikalarını yönetir ────────────────────

    public function myCertifications()
    {
        $user  = auth()->user();
        $certs = HrCertification::where('user_id', $user->id)
            ->orderBy('expiry_date')
            ->orderByDesc('issue_date')
            ->get();

        return view('hr.my.certifications', compact('certs'));
    }

    public function myStore(Request $request)
    {
        $user = auth()->user();
        $cid  = (int) ($user->company_id ?? 0);

        $data = $request->validate([
            'cert_name'   => 'required|string|max:200',
            'issuer'      => 'nullable|string|max:200',
            'issue_date'  => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'notes'       => 'nullable|string|max:500',
        ]);

        HrCertification::create(array_merge($data, [
            'user_id'    => $user->id,
            'company_id' => $cid ?: null,
        ]));

        return back()->with('status', 'Sertifika eklendi.');
    }

    public function myDestroy(HrCertification $cert)
    {
        abort_if($cert->user_id !== auth()->id(), 403);
        $cert->delete();
        return back()->with('status', 'Sertifika silindi.');
    }

    public function myUpdate(Request $request, HrCertification $cert)
    {
        abort_if($cert->user_id !== auth()->id(), 403);

        $data = $request->validate([
            'cert_name'   => 'required|string|max:200',
            'issuer'      => 'nullable|string|max:200',
            'issue_date'  => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'notes'       => 'nullable|string|max:500',
        ]);

        $cert->update($data);
        return back()->with('status', 'Sertifika güncellendi.');
    }
}
