<?php

namespace App\Http\Controllers;

use App\Models\BusinessContract;
use App\Services\BusinessContractService;
use App\Support\FileUploadRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StaffContractController extends Controller
{
    private const EXCLUDED_ROLES = ['student', 'guest', 'dealer'];

    public function __construct(
        private readonly BusinessContractService $service
    ) {}

    public function index(): View
    {
        $user = Auth::user();
        abort_if(in_array($user->role, self::EXCLUDED_ROLES, true), 403);

        $contracts = BusinessContract::query()
            ->where('user_id', $user->id)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->orderByDesc('issued_at')
            ->get();

        return view('my-contracts.index', [
            'contracts' => $contracts,
            'layout'    => $this->resolveLayout($user->role),
        ]);
    }

    public function show(BusinessContract $contract): View
    {
        $user = Auth::user();
        abort_if(in_array($user->role, self::EXCLUDED_ROLES, true), 403);
        abort_if($contract->user_id !== $user->id, 403);

        return view('my-contracts.show', [
            'contract' => $contract,
            'layout'   => $this->resolveLayout($user->role),
        ]);
    }

    public function uploadSigned(Request $request, BusinessContract $contract): RedirectResponse
    {
        $user = Auth::user();
        abort_if(in_array($user->role, self::EXCLUDED_ROLES, true), 403);
        abort_if($contract->user_id !== $user->id, 403);

        if ($contract->status !== 'issued') {
            return back()->with('error', 'Yalnızca gönderilmiş sözleşmeler imzalanabilir.');
        }

        $request->validate([
            'signed_file' => FileUploadRules::signedContract(),
        ]);

        $this->service->uploadSigned($contract, $request->file('signed_file'));

        return back()->with('success', 'İmzalı sözleşmeniz alındı. Yönetici onayı bekleniyor.');
    }

    private function resolveLayout(string $role): string
    {
        return match (true) {
            $role === 'senior' => 'senior.layouts.app',
            in_array($role, ['marketing_admin', 'marketing_staff', 'sales_admin', 'sales_staff'], true) => 'marketing-admin.layouts.app',
            default => 'manager.layouts.app',
        };
    }
}
