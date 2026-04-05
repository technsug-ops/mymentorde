<?php

namespace App\Services;

use App\Models\BusinessContract;
use App\Models\BusinessContractTemplate;
use App\Models\Dealer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class BusinessContractService
{
    public function __construct(
        private readonly ContractTemplateService $renderer
    ) {}

    /**
     * @param  array<string,string> $meta  Placeholder key-value pairs
     */
    public function create(
        string $contractType,
        int $templateId,
        int $dealerId = 0,
        int $userId = 0,
        array $meta = [],
        int $companyId = 0,
        int $issuedBy = 0,
        string $notes = '',
        string $bodyTextOverride = ''
    ): BusinessContract {
        $template = BusinessContractTemplate::findOrFail($templateId);
        $bodyText = $bodyTextOverride !== ''
            ? $bodyTextOverride
            : $this->renderer->renderText($template->body_text, $meta);
        $no       = $this->generateNo($contractType);

        $titleMap = [
            'dealer_referral_v1'  => 'Dealer Referans Ortaklığı Sözleşmesi',
            'dealer_operations_v1'=> 'Dealer Operasyon Sözleşmesi',
        ];
        $title = $titleMap[$template->template_code] ?? $template->name;
        if ($dealerId > 0) {
            $dealer = Dealer::find($dealerId, ['name']);
            if ($dealer) {
                $title .= ' — ' . $dealer->name;
            }
        }

        return BusinessContract::create([
            'company_id'    => $companyId,
            'contract_type' => $contractType,
            'dealer_id'     => $dealerId ?: null,
            'user_id'       => $userId ?: null,
            'template_id'   => $templateId,
            'contract_no'   => $no,
            'title'         => $title,
            'body_text'     => $bodyText,
            'meta'          => $meta,
            'status'        => 'draft',
            'issued_by'     => $issuedBy ?: null,
            'notes'         => $notes,
        ]);
    }

    public function issue(BusinessContract $contract): void
    {
        $contract->update([
            'status'    => 'issued',
            'issued_at' => now(),
        ]);
    }

    public function uploadSigned(BusinessContract $contract, UploadedFile $file): void
    {
        $path = $file->store('business-contracts/signed', 'local');

        $contract->update([
            'status'           => 'signed_uploaded',
            'signed_at'        => now(),
            'signed_file_path' => $path,
        ]);
    }

    public function approve(BusinessContract $contract, int $approvedBy): void
    {
        $contract->update([
            'status'      => 'approved',
            'approved_at' => now(),
            'approved_by' => $approvedBy,
        ]);
    }

    public function cancel(BusinessContract $contract): void
    {
        $contract->update(['status' => 'cancelled']);
    }

    /** @return array<string,string> Default placeholder map for dealer contracts */
    public function dealerPlaceholders(Dealer $dealer): array
    {
        return [
            'dealer_firma_adi'  => (string) ($dealer->name ?? ''),
            'dealer_yetkili_adi'=> (string) ($dealer->contact_name ?? ''),
            'dealer_adres'      => (string) ($dealer->address ?? ''),
            'dealer_vergi_no'   => (string) ($dealer->tax_no ?? ''),
            'dealer_telefon'    => (string) ($dealer->phone ?? ''),
            'dealer_eposta'     => (string) ($dealer->email ?? ''),
            'sozlesme_tarihi'   => now()->format('d.m.Y'),
            'yetkili_mahkeme'   => 'İstanbul',
        ];
    }

    private function generateNo(string $type): string
    {
        $prefix = match ($type) {
            'dealer' => 'DLR',
            'staff'  => 'STF',
            default  => 'BSC',
        };

        return $prefix . '-' . now()->format('Y') . '-' . strtoupper(Str::random(6));
    }
}
