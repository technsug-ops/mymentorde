<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Manager\Concerns\ContractHelperTrait;
use App\Models\ContractTemplate;
use App\Models\GuestApplication;
use App\Services\ContractTemplateService;
use App\Services\EventLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractPrintController extends Controller
{
    use ContractHelperTrait;

    public function __construct(
        private readonly ContractTemplateService $contractTemplateService,
        private readonly EventLogService $eventLogService,
    ) {}

    public function saveStudentServices(Request $request): RedirectResponse
    {
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $data = $request->validate([
            'guest_id'                => ['required', 'integer', 'min:1'],
            'selected_package_title'  => ['required', 'string', 'max:180'],
            'selected_package_price'  => ['required', 'string', 'max:64'],
            'selected_extra_services' => ['nullable', 'string', 'max:1000'],
        ]);

        $guest = GuestApplication::query()
            ->when($companyId > 0, fn ($q) => $q->forCompany($companyId))
            ->where('id', (int) $data['guest_id'])
            ->firstOrFail();

        $title = trim((string) $data['selected_package_title']);
        $price = trim((string) $data['selected_package_price']);
        $code  = strtolower(preg_replace('/[^a-z0-9]+/', '_', $title) ?: 'custom_package');

        $extraInput = trim((string) ($data['selected_extra_services'] ?? ''));
        $extras = [];
        if ($extraInput !== '') {
            $parts = collect(explode(',', $extraInput))
                ->map(fn ($x) => trim((string) $x))
                ->filter()
                ->values();
            foreach ($parts as $part) {
                $extras[] = [
                    'code'     => strtolower(preg_replace('/[^a-z0-9]+/', '_', (string) $part) ?: 'extra'),
                    'title'    => (string) $part,
                    'added_at' => now()->toDateTimeString(),
                ];
            }
        }

        $guest->update([
            'selected_package_code'   => $code,
            'selected_package_title'  => $title,
            'selected_package_price'  => $price,
            'selected_extra_services' => $extras,
            'package_selected_at'     => now(),
            'status_message'          => 'Servis secimi manager tarafindan guncellendi.',
        ]);

        return redirect()->route('manager.contract-template.show', [
            'q'        => (string) ($guest->converted_student_id ?? $guest->email),
            'guest_id' => (int) $guest->id,
        ])->with('status', 'Ogrenci servis secimi guncellendi.');
    }

    public function refreshSnapshot(Request $request): RedirectResponse
    {
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $data = $request->validate([
            'guest_id' => ['required', 'integer', 'min:1'],
        ]);

        $guest = GuestApplication::query()
            ->when($companyId > 0, fn ($q) => $q->forCompany($companyId))
            ->where('id', (int) $data['guest_id'])
            ->firstOrFail();

        $currentStatus = $this->normalizeContractStatus((string) ($guest->contract_status ?? 'not_requested'));
        if (! in_array($currentStatus, ['requested', 'pending_manager', 'rejected', 'not_requested'], true)) {
            return redirect()->route('manager.contract-template.show', [
                'q'        => (string) ($guest->converted_student_id ?? $guest->email),
                'guest_id' => (int) $guest->id,
            ])->withErrors(['contract' => "Taslak güncellemesi yalnızca sözleşme imzalanmadan önce yapılabilir. Mevcut durum: {$currentStatus}."]);
        }

        $snapshot = $this->contractTemplateService->buildSnapshot($guest, (int) ($guest->company_id ?: 0));

        $guest->forceFill([
            'contract_template_id'           => (int) ($snapshot['template_id'] ?? 0) ?: null,
            'contract_template_code'         => (string) ($snapshot['template_code'] ?? ''),
            'contract_snapshot_text'         => (string) ($snapshot['body_text'] ?? ''),
            'contract_annex_kvkk_text'       => (string) ($snapshot['annex_kvkk_text'] ?? ''),
            'contract_annex_commitment_text' => (string) ($snapshot['annex_commitment_text'] ?? ''),
            'contract_annex_payment_text'    => (string) ($snapshot['annex_payment_text'] ?? ''),
            'contract_generated_at'          => now(),
        ])->save();

        $this->eventLogService->log(
            eventType: 'manager_contract_snapshot_refreshed',
            entityType: 'guest_application',
            entityId: (string) $guest->id,
            message: 'Sözleşme taslağı güncel şablondan yeniden oluşturuldu.',
            meta: ['template_code' => (string) ($snapshot['template_code'] ?? '')],
            actorEmail: (string) optional($request->user())->email,
            companyId: (int) ($guest->company_id ?: 0)
        );

        return redirect()->route('manager.contract-template.show', [
            'q'        => (string) ($guest->converted_student_id ?? $guest->email),
            'guest_id' => (int) $guest->id,
        ])->with('status', 'Sözleşme taslağı güncellendi.');
    }

    public function printContract(Request $request, int $guestId)
    {
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;

        $guest = GuestApplication::query()
            ->when($companyId > 0, fn ($q) => $q->forCompany($companyId))
            ->where('id', $guestId)
            ->first();

        if (! $guest) {
            $guest = GuestApplication::query()->where('id', $guestId)->firstOrFail();
        }

        $contractText     = trim((string) ($guest->contract_snapshot_text ?? ''));
        $annexKvkkText    = trim((string) ($guest->contract_annex_kvkk_text ?? ''));
        $annexCommitText  = trim((string) ($guest->contract_annex_commitment_text ?? ''));
        $annexPaymentText = trim((string) ($guest->contract_annex_payment_text ?? ''));

        if ($contractText === '') {
            $snapshot         = $this->contractTemplateService->buildSnapshotCached($guest, (int) ($guest->company_id ?: 0));
            $contractText     = (string) ($snapshot['body_text'] ?? '');
            $annexKvkkText    = (string) ($snapshot['annex_kvkk_text'] ?? '');
            $annexCommitText  = (string) ($snapshot['annex_commitment_text'] ?? '');
            $annexPaymentText = (string) ($snapshot['annex_payment_text'] ?? '');
        }

        $templateId  = (int) ($guest->contract_template_id ?? 0);
        $tplForPrint = $templateId > 0
            ? ContractTemplate::find($templateId, ['print_header_html', 'print_footer_html'])
            : null;
        if (! $tplForPrint) {
            $tplForPrint = ContractTemplate::query()
                ->when((int) ($guest->company_id ?: 0) > 0, fn ($q) => $q->forCompany((int) $guest->company_id))
                ->where('is_active', true)
                ->orderByDesc('version')
                ->first(['print_header_html', 'print_footer_html']);
        }

        $printVars       = $this->contractTemplateService->buildPreviewVariables($guest);
        $printHeaderHtml = $this->contractTemplateService->renderText(
            (string) ($tplForPrint?->print_header_html ?? ''), $printVars
        );
        $printFooterHtml = $this->contractTemplateService->renderText(
            (string) ($tplForPrint?->print_footer_html ?? ''), $printVars
        );

        return view('manager.contract-print', [
            'guest'           => $guest,
            'contractText'    => $contractText,
            'annexKvkkText'   => $annexKvkkText,
            'annexCommitText' => $annexCommitText,
            'annexPaymentText'=> $annexPaymentText,
            'printHeaderHtml' => $printHeaderHtml,
            'printFooterHtml' => $printFooterHtml,
            'templateCode'    => (string) ($guest->contract_template_code ?? ''),
            'generatedAt'     => $guest->contract_generated_at,
            'contractStatus'  => (string) ($guest->contract_status ?? 'not_requested'),
        ]);
    }

    public function downloadPdf(Request $request, int $guestId): StreamedResponse
    {
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;

        $guest = GuestApplication::query()
            ->when($companyId > 0, fn ($q) => $q->forCompany($companyId))
            ->where('id', $guestId)
            ->first();

        if (! $guest) {
            $guest = GuestApplication::query()->where('id', $guestId)->firstOrFail();
        }

        $pdfContent = $this->contractTemplateService->generatePdf($guest, (int) ($guest->company_id ?: 0));
        $filename = 'sozlesme_' . ($guest->converted_student_id ?? ('GST-' . $guestId)) . '_' . now()->format('Ymd') . '.pdf';

        return response()->streamDownload(
            fn () => print($pdfContent),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }
}
