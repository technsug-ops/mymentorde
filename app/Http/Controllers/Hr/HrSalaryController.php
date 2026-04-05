<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\HrSalaryProfile;
use App\Models\User;
use Illuminate\Http\Request;

class HrSalaryController extends Controller
{
    private function companyId(): int
    {
        return (int) (auth()->user()?->company_id ?? 0);
    }

    public function index(Request $request)
    {
        $cid = $this->companyId();

        $employees = User::whereIn('role', [
            'manager', 'senior', 'system_admin', 'system_staff',
            'operations_admin', 'operations_staff',
            'finance_admin', 'finance_staff',
            'marketing_admin', 'marketing_staff',
            'sales_admin', 'sales_staff',
        ])
            ->when($cid > 0, fn($q) => $q->where('company_id', $cid))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        // Load active salary profiles for all employees
        $profiles = HrSalaryProfile::where('is_active', true)
            ->when($cid > 0, fn($q) => $q->where('company_id', $cid))
            ->get()
            ->keyBy('user_id');

        return view('manager.hr.salary.index', compact('employees', 'profiles'));
    }

    public function store(Request $request, User $user)
    {
        $cid = $this->companyId();
        abort_if($cid > 0 && (int) $user->company_id !== $cid, 403);

        $data = $request->validate([
            'gross_salary' => 'required|numeric|min:0',
            'currency'     => 'required|string|size:3',
            'payment_day'  => 'required|integer|min:1|max:31',
            'bank_name'    => 'nullable|string|max:100',
            'iban'         => 'nullable|string|max:50',
            'valid_from'   => 'required|date',
            'notes'        => 'nullable|string|max:500',
        ]);

        // Deactivate previous active profile for this user
        HrSalaryProfile::where('user_id', $user->id)->where('is_active', true)->update(['is_active' => false]);

        HrSalaryProfile::create([
            'company_id'   => $cid ?: null,
            'user_id'      => $user->id,
            'gross_salary' => $data['gross_salary'],
            'currency'     => strtoupper($data['currency']),
            'payment_day'  => $data['payment_day'],
            'bank_name'    => $data['bank_name'] ?? null,
            'iban'         => $data['iban'] ?? null,
            'valid_from'   => $data['valid_from'],
            'notes'        => $data['notes'] ?? null,
            'is_active'    => true,
            'created_by'   => auth()->id(),
        ]);

        return back()->with('status', $user->name . ' bordro profili güncellendi.');
    }
}
