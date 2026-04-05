<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dealer\Concerns\DealerPortalTrait;
use App\Models\BusinessContract;
use App\Services\EventLogService;
use App\Services\NotificationService;
use App\Services\TaskAutomationService;
use App\Support\FileUploadRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DealerContractController extends Controller
{
    use DealerPortalTrait;

    public function __construct(
        private readonly TaskAutomationService $taskAutomationService,
        private readonly EventLogService $eventLogService,
        private readonly NotificationService $notificationService,
    ) {}

    public function contracts(Request $request): \Illuminate\View\View
    {
        $base   = $this->baseData($request);
        $dealer = $base['dealer'];
        abort_if(!$dealer, 403, 'Dealer kaydı bulunamadı.');

        $contracts = BusinessContract::query()
            ->where('dealer_id', $dealer->id)
            ->whereIn('status', ['issued', 'signed_uploaded', 'approved'])
            ->orderByDesc('created_at')
            ->get();

        return view('dealer.contracts', compact('contracts', 'dealer'));
    }

    public function contractShow(Request $request, BusinessContract $contract): \Illuminate\View\View
    {
        $base   = $this->baseData($request);
        $dealer = $base['dealer'];
        abort_if(!$dealer, 403, 'Dealer kaydı bulunamadı.');
        abort_if((int) $contract->dealer_id !== (int) $dealer->id, 403, 'Bu sözleşme bu dealer hesabına ait değil.');

        return view('dealer.contract-show', compact('contract', 'dealer'));
    }

    public function contractUploadSigned(Request $request, BusinessContract $contract): \Illuminate\Http\RedirectResponse
    {
        $base   = $this->baseData($request);
        $dealer = $base['dealer'];
        abort_if(!$dealer, 403, 'Dealer kaydı bulunamadı.');
        abort_if((int) $contract->dealer_id !== (int) $dealer->id, 403, 'Bu sözleşme bu dealer hesabına ait değil.');
        abort_if(!in_array($contract->status, ['issued', 'rejected'], true), 422, 'Bu sözleşme imzalı yüklemeye uygun değil.');

        $request->validate([
            'signed_file' => FileUploadRules::signedContract(),
        ]);

        $file = $request->file('signed_file');
        $ext  = strtolower((string) $file->getClientOriginalExtension()) ?: 'pdf';
        $name = 'dealer_contract_'.$contract->id.'_signed_'.now()->format('Ymd_His').'.'.$ext;
        $path = $file->storeAs('dealer-contracts/'.$dealer->id, $name, 'local');

        $contract->update([
            'status'               => 'signed_uploaded',
            'signed_file_path'     => $path,
            'signed_uploaded_at'   => now(),
            'signed_uploaded_by'   => (string) ($request->user()?->email ?? ''),
        ]);

        $this->eventLogService->log('dealer_contract_signed_uploaded', [
            'contract_id' => $contract->id,
            'dealer_id'   => $dealer->id,
            'dealer_user' => (string) ($request->user()?->email ?? ''),
        ], 'dealer', (string) ($request->user()?->email ?? 'dealer'));

        return redirect()->route('dealer.contracts.show', $contract->id)
            ->with('status', 'İmzalı sözleşme yüklendi. Onay bekleniyor.');
    }
}
