<?php

namespace App\Services;

use App\Mail\ContractCompletedMail;
use App\Models\BusinessContract;
use App\Models\BusinessContractTemplate;
use App\Models\Dealer;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

        // Sözleşme onaylandığında karşı tarafa zengin mail (PDF + meta) gönder.
        // Panel notification ayrı yerde tetikleniyor; bu yalnızca e-posta tarafı.
        $this->sendCompletedMail($contract);
    }

    private function sendCompletedMail(BusinessContract $contract): void
    {
        // Kim alacak? Dealer (e-posta dealer.email'inden) veya staff (User.email)
        $recipientEmail = '';
        $recipientName  = '';

        if ($contract->dealer_id) {
            $dealer = Dealer::find($contract->dealer_id);
            if ($dealer) {
                $recipientEmail = trim((string) ($dealer->email ?? ''));
                $recipientName  = trim((string) ($dealer->contact_name ?? $dealer->name ?? ''));
            }
        } elseif ($contract->user_id) {
            $user = User::query()->withoutGlobalScope('company')->find($contract->user_id);
            if ($user) {
                $recipientEmail = trim((string) ($user->email ?? ''));
                $recipientName  = trim((string) ($user->name ?? ''));
            }
        }

        if ($recipientEmail === '') return;
        if ($recipientName === '') $recipientName = 'Sayın Yetkilimiz';

        $attachments = [];
        if (!empty($contract->signed_file_path)) {
            $attachments[] = (string) $contract->signed_file_path;
        }

        // contract.meta JSON içinde ek dosya yolları varsa
        $meta = is_array($contract->meta) ? $contract->meta : [];
        if (!empty($meta['annex_files']) && is_array($meta['annex_files'])) {
            foreach ($meta['annex_files'] as $f) {
                $path = is_array($f) ? (string) ($f['path'] ?? '') : (string) $f;
                if ($path !== '') $attachments[] = $path;
            }
        }
        $annexNotes = [];
        if (!empty($meta['annex_notes']) && is_array($meta['annex_notes'])) {
            foreach ($meta['annex_notes'] as $note) {
                $note = trim((string) (is_array($note) ? ($note['note'] ?? '') : $note));
                if ($note !== '') $annexNotes[] = $note;
            }
        }

        try {
            Mail::to($recipientEmail)->queue(new ContractCompletedMail(
                recipientName: $recipientName,
                contractTitle: (string) ($contract->title ?? 'İş Sözleşmesi'),
                contractNo: (string) ($contract->contract_no ?? null) ?: null,
                attachmentPaths: $attachments,
                annexNotes: $annexNotes,
                portalUrl: url('/manager/business-contracts/' . $contract->id),
            ));
        } catch (\Throwable $e) {
            Log::warning('business-contract.completed.mail.failed', [
                'contract_id' => $contract->id,
                'email'       => $recipientEmail,
                'error'       => $e->getMessage(),
            ]);
        }
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
