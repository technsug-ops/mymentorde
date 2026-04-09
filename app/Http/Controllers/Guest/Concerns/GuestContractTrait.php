<?php

namespace App\Http\Controllers\Guest\Concerns;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\GuestApplication;
use App\Rules\ValidFileMagicBytes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

trait GuestContractTrait
{
    public function requestContract(Request $request)
    {
        $guest = $this->resolveGuest($request);
        abort_if(!$guest, 404, 'Guest kaydi bulunamadi.');

        $currentStatus = $this->normalizeContractStatus((string) ($guest->contract_status ?? 'not_requested'));
        if ($currentStatus !== 'not_requested') {
            return redirect()
                ->route('guest.contract')
                ->withErrors(['contract' => "Sözleşme talebi için mevcut durum uygun değil: {$currentStatus}."]);
        }
        if (trim((string) ($guest->selected_package_code ?? '')) === '') {
            return redirect()
                ->route('guest.contract')
                ->withErrors(['contract' => 'Sözleşme talebinden önce hizmet paketi seçimi gereklidir.']);
        }
        $formSubmitted = (bool) $guest->registration_form_submitted_at;
        if (!$formSubmitted && $this->isRegistrationDraftComplete($guest)) {
            $guest->forceFill([
                'registration_form_submitted_at' => now(),
                'status_message' => 'On kayit formu tam bulundu, sozlesme adiminda otomatik gonderildi olarak isaretlendi.',
            ])->save();
            $formSubmitted = true;
        }
        if (!$formSubmitted) {
            return redirect()
                ->route('guest.contract')
                ->withErrors(['contract' => 'Sözleşme talebinden önce ön kayıt formu gönderilmelidir.']);
        }
        $docsReady = (bool) $guest->docs_ready;
        if (!$docsReady) {
            $ownerId = $this->resolveDocumentOwnerId($guest);
            $docsReadyComputed = $this->computeDocsReady($guest, $ownerId);
            if ($docsReadyComputed) {
                $guest->forceFill(['docs_ready' => true])->save();
                $docsReady = true;
            }
        }
        if (!$docsReady) {
            return redirect()
                ->route('guest.contract')
                ->withErrors(['contract' => 'Sözleşme talebinden önce zorunlu belgeler tamamlanmalıdır.']);
        }

        $guest->forceFill([
            'contract_status'                => 'pending_manager',
            'contract_requested_at'          => now(),
            'contract_signed_at'             => null,
            'contract_signed_file_path'      => null,
            'contract_approved_at'           => null,
            'contract_template_id'           => null,
            'contract_template_code'         => null,
            'contract_snapshot_text'         => null,
            'contract_annex_kvkk_text'       => null,
            'contract_annex_commitment_text' => null,
            'contract_generated_at'          => null,
            'status_message'                 => 'Sözleşme talebi alındı. Danışman hazırlayıp gönderecek.',
        ])->save();
        $this->taskAutomationService->ensureContractReviewTask($guest);
        $this->eventLogService->log(
            eventType: 'guest_contract_requested',
            entityType: 'guest_application',
            entityId: (string) $guest->id,
            message: "Guest #{$guest->id} sozlesme talebi gonderdi. Manager incelemesi bekleniyor.",
            meta: null,
            actorEmail: (string) optional($request->user())->email,
            companyId: (int) ($guest->company_id ?: 0)
        );
        $this->queueTemplateNotification(
            guest: $guest,
            category: 'guest_contract_update',
            sourceType: 'guest_contract_requested',
            sourceId: (string) $guest->id,
            vars: ['guest_id' => (string) $guest->id]
        );

        return redirect()->route('guest.contract')->with('status', 'Sözleşme talebi gönderildi. Danışman hazırlayıp gönderecek.');
    }

    public function withdrawContractRequest(Request $request)
    {
        $guest = $this->resolveGuest($request);
        abort_if(!$guest, 404, 'Guest kaydi bulunamadi.');

        $currentStatus = $this->normalizeContractStatus((string) ($guest->contract_status ?? 'not_requested'));
        if ($currentStatus !== 'pending_manager') {
            return redirect()
                ->route('guest.contract')
                ->withErrors(['contract' => "Talebi geri çekmek için durum 'pending_manager' olmalıdır. Mevcut durum: {$currentStatus}."]);
        }

        $guest->forceFill([
            'contract_status'                => 'not_requested',
            'contract_requested_at'          => null,
            'contract_template_id'           => null,
            'contract_template_code'         => null,
            'contract_snapshot_text'         => null,
            'contract_annex_kvkk_text'       => null,
            'contract_annex_commitment_text' => null,
            'contract_generated_at'          => null,
            'status_message'                 => 'Sözleşme talebi misafir tarafından geri çekildi.',
        ])->save();

        $this->taskAutomationService->markTasksDoneBySource('guest_contract_requested', (string) $guest->id);
        $this->taskAutomationService->markTasksDoneBySource('guest_contract_sales_followup', (string) $guest->id);
        $this->eventLogService->log(
            eventType: 'guest_contract_withdrawn',
            entityType: 'guest_application',
            entityId: (string) $guest->id,
            message: "Guest #{$guest->id} sozlesme talebini geri cekti.",
            meta: null,
            actorEmail: (string) optional($request->user())->email,
            companyId: (int) ($guest->company_id ?: 0)
        );

        return redirect()->route('guest.contract')->with('status', 'Sözleşme talebiniz geri çekildi.');
    }

    public function requestReopen(Request $request)
    {
        $guest = $this->resolveGuest($request);
        abort_if(!$guest, 404, 'Guest kaydi bulunamadi.');

        $currentStatus = $this->normalizeContractStatus((string) ($guest->contract_status ?? 'not_requested'));
        if ($currentStatus !== 'cancelled') {
            return redirect()
                ->route('guest.contract')
                ->withErrors(['contract' => "Yeniden değerlendirme talebi için sözleşme 'cancelled' durumunda olmalıdır. Mevcut durum: {$currentStatus}."]);
        }

        $data = $request->validate([
            'reopen_reason' => ['required', 'string', 'max:1000'],
        ]);

        $guest->forceFill([
            'contract_status'     => 'reopen_requested',
            'reopen_reason'       => strip_tags(trim((string) $data['reopen_reason'])),
            'reopen_requested_at' => now(),
            'reopen_decided_by'   => null,
            'reopen_decided_at'   => null,
            'status_message'      => 'Misafir tarafından yeniden değerlendirme talep edildi.',
        ])->save();

        $this->eventLogService->log(
            eventType: 'guest_contract_reopen_requested',
            entityType: 'guest_application',
            entityId: (string) $guest->id,
            message: "Guest #{$guest->id} iptal edilen sözleşme için yeniden değerlendirme talep etti.",
            meta: ['reopen_reason' => trim((string) $data['reopen_reason'])],
            actorEmail: (string) optional($request->user())->email,
            companyId: (int) ($guest->company_id ?: 0)
        );

        $studentId = trim((string) ($guest->converted_student_id ?? ''));
        if ($studentId === '') {
            $studentId = 'GST-' . str_pad((string) $guest->id, 8, '0', STR_PAD_LEFT);
        }
        $this->notificationService->send([
            'channel'     => 'in_app',
            'category'    => 'guest_contract_update',
            'student_id'  => $studentId,
            'company_id'  => (int) ($guest->company_id ?: 0),
            'body'        => 'Sözleşme sürecinde güncelleme: yeniden değerlendirme talep edildi.',
            'source_type' => 'guest_application',
            'source_id'   => (string) $guest->id,
        ]);

        return redirect()->route('guest.contract')->with('status', 'Yeniden değerlendirme talebiniz iletildi. Danışman ekibimiz en kısa sürede değerlendirecek.');
    }

    public function uploadSignedContract(Request $request)
    {
        $guest = $this->resolveGuest($request);
        abort_if(!$guest, 404, 'Guest kaydi bulunamadi.');

        $currentStatus = $this->normalizeContractStatus((string) ($guest->contract_status ?? 'not_requested'));
        if (!in_array($currentStatus, ['requested', 'rejected'], true)) {
            return redirect()
                ->route('guest.contract')
                ->withErrors(['contract' => "Imzali sozlesme yukleme icin mevcut durum uygun degil: {$currentStatus}."]);
        }
        if ($this->contractStateHasInconsistency($guest, $currentStatus)) {
            return redirect()
                ->route('guest.contract')
                ->withErrors(['contract' => 'Sözleşme kaydı tutarsız görünüyor (eksik template/snapshot/talep zamanı). Lütfen önce danışman ile kontrol edin.']);
        }

        $data = $request->validate([
            'signed_contract' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240', new ValidFileMagicBytes()],
        ]);

        $file = $request->file('signed_contract');
        $allowedSignedExts = ['pdf', 'jpg', 'jpeg', 'png'];
        $rawExt = strtolower((string) $file->getClientOriginalExtension());
        $ext = in_array($rawExt, $allowedSignedExts, true) ? $rawExt : 'pdf';
        $ownerId = trim((string) ($guest->converted_student_id ?? '')) !== ''
            ? (string) $guest->converted_student_id
            : 'GST-' . str_pad((string) $guest->id, 8, '0', STR_PAD_LEFT);
        $name = app(\App\Services\DocumentNamingService::class)->buildStandardFileName(
            $ownerId, 'SOZLESME-IMZALI',
            (string) ($guest->first_name ?? ''), (string) ($guest->last_name ?? ''), $ext,
        );
        $path = $file->storeAs("guest-contracts/{$guest->id}", $name, 'local');

        $guest->forceFill([
            'contract_status'           => 'signed_uploaded',
            'contract_signed_at'        => now(),
            'contract_signed_file_path' => $path,
            'status_message'            => 'Imzali sozlesme yuklendi. Onay bekleniyor.',
        ])->save();

        $ownerId = trim((string) ($guest->converted_student_id ?? '')) !== ''
            ? (string) $guest->converted_student_id
            : 'GST-' . str_pad((string) $guest->id, 8, '0', STR_PAD_LEFT);
        $contractCategory = DocumentCategory::query()->firstOrCreate(
            ['code' => 'SOZLESME_IMZALI'],
            ['name_tr' => 'İmzalı Sözleşme', 'is_active' => true, 'sort_order' => 0]
        );
        $contractDoc = Document::query()->create([
            'student_id'         => $ownerId,
            'category_id'        => (int) $contractCategory->id,
            'process_tags'       => ['contract', 'signed_contract'],
            'original_file_name' => $name,
            'standard_file_name' => $name,
            'storage_path'       => $path,
            'mime_type'          => (string) ($file->getMimeType() ?: 'application/pdf'),
            'status'             => 'uploaded',
            'uploaded_by'        => (string) optional($request->user())->email,
        ]);
        $contractDoc->forceFill([
            'document_id' => 'DOC-CONTRACT-' . str_pad((string) $contractDoc->id, 6, '0', STR_PAD_LEFT),
        ])->save();

        $this->taskAutomationService->ensureSignedContractTask($guest);
        $this->eventLogService->log(
            eventType: 'guest_contract_signed_uploaded',
            entityType: 'guest_application',
            entityId: (string) $guest->id,
            message: "Guest #{$guest->id} imzali sozlesme yukledi.",
            meta: ['path' => $path, 'document_id' => (string) $contractDoc->document_id],
            actorEmail: (string) optional($request->user())->email,
            companyId: (int) ($guest->company_id ?: 0)
        );
        $this->queueTemplateNotification(
            guest: $guest,
            category: 'guest_contract_update',
            sourceType: 'guest_contract_signed_uploaded',
            sourceId: (string) $guest->id,
            vars: ['guest_id' => (string) $guest->id]
        );

        return redirect()->route('guest.contract.signed-thanks');
    }

    public function digitalSign(Request $request): \Illuminate\Http\JsonResponse
    {
        $guest = $this->resolveGuest($request);
        if (!$guest) {
            return response()->json(['error' => 'Guest kaydı bulunamadı.'], 404);
        }

        $currentStatus = $this->normalizeContractStatus((string) ($guest->contract_status ?? 'not_requested'));
        if (!in_array($currentStatus, ['requested', 'rejected'], true)) {
            return response()->json(['error' => "Dijital imza için mevcut durum uygun değil: {$currentStatus}."], 422);
        }

        $data = $request->validate([
            'signature_data' => ['required', 'string', 'min:100'],
        ]);

        $raw = trim((string) $data['signature_data']);
        if (str_starts_with($raw, 'data:')) {
            $raw = substr($raw, strpos($raw, ',') + 1);
        }
        if (!base64_decode($raw, strict: true)) {
            return response()->json(['error' => 'Geçersiz imza verisi.'], 422);
        }

        $guest->forceFill([
            'contract_digital_signature_data' => $raw,
            'contract_digital_signed_at'      => now(),
            'contract_digital_sign_ip'        => $request->ip(),
            'contract_status'                 => 'signed_uploaded',
            'contract_signed_at'              => now(),
            'status_message'                  => 'Dijital imza ile sözleşme imzalandı.',
        ])->save();

        $this->eventLogService->log(
            eventType: 'guest_contract_digital_signed',
            entityType: 'guest_application',
            entityId: (string) $guest->id,
            message: "Guest #{$guest->id} dijital imza ile sözleşmeyi imzaladı.",
            meta: ['ip' => $request->ip()],
            actorEmail: (string) optional($request->user())->email,
            companyId: (int) ($guest->company_id ?: 0)
        );
        $this->taskAutomationService->ensureSignedContractTask($guest);

        return response()->json(['success' => true, 'message' => 'Sözleşme başarıyla imzalandı.']);
    }

    public function requestContractUpdate(Request $request)
    {
        $guest = $this->resolveGuest($request);
        abort_if(!$guest, 404, 'Guest kaydi bulunamadi.');

        $currentStatus = $this->normalizeContractStatus((string) ($guest->contract_status ?? 'not_requested'));
        if (!in_array($currentStatus, ['requested', 'signed_uploaded', 'rejected'], true)) {
            return redirect()
                ->route('guest.contract')
                ->withErrors(['contract' => "Sözleşme güncelleme talebi için mevcut durum uygun değil: {$currentStatus}."]);
        }
        if ($this->contractStateHasInconsistency($guest, $currentStatus)) {
            return redirect()
                ->route('guest.contract')
                ->withErrors(['contract' => 'Sözleşme kaydı tutarsız görünüyor (eksik snapshot/template/imza kaydı). Lütfen önce danışman ile kontrol edin.']);
        }

        $data = $request->validate([
            'package_code'         => ['nullable', 'string', 'max:64'],
            'extra_service_codes'  => ['nullable', 'array'],
            'extra_service_codes.*'=> ['string', 'max:64'],
            'update_note'          => ['required', 'string', 'max:2000'],
        ]);

        $packages = collect($this->servicePackages())->keyBy('code');
        $requestedPackageCode = trim((string) ($data['package_code'] ?? ''));
        if ($requestedPackageCode !== '' && !$packages->has($requestedPackageCode)) {
            return redirect()
                ->route('guest.contract')
                ->withErrors(['contract' => 'Gecersiz paket secimi.'])
                ->withInput();
        }

        $updateNote = strip_tags(trim((string) $data['update_note']));
        $extraOptions = collect($this->extraServiceOptions())->keyBy('code');
        $selectedCodes = collect((array) ($data['extra_service_codes'] ?? []))
            ->map(fn ($x) => trim((string) $x))
            ->filter()->unique()->values();
        $extras = [];
        foreach ($selectedCodes as $code) {
            if (!$extraOptions->has($code)) {
                continue;
            }
            $meta = (array) $extraOptions->get($code);
            $extras[] = [
                'code'     => (string) ($meta['code'] ?? $code),
                'title'    => (string) ($meta['title'] ?? $code),
                'added_at' => now()->toDateTimeString(),
            ];
        }

        if ($requestedPackageCode !== '') {
            $pkg = (array) $packages->get($requestedPackageCode);
            $guest->selected_package_code  = (string) ($pkg['code'] ?? '');
            $guest->selected_package_title = (string) ($pkg['title'] ?? '');
            $guest->selected_package_price = (string) ($pkg['price'] ?? '');
            $guest->package_selected_at    = now();
        }
        $guest->selected_extra_services = $extras;

        $snapshot = $this->contractTemplateService->buildSnapshot($guest, (int) ($guest->company_id ?: 0));
        $guest->forceFill([
            'contract_status'                => 'requested',
            'contract_requested_at'          => now(),
            'contract_signed_at'             => null,
            'contract_signed_file_path'      => null,
            'contract_approved_at'           => null,
            'contract_template_id'           => (int) ($snapshot['template_id'] ?? 0) ?: null,
            'contract_template_code'         => (string) ($snapshot['template_code'] ?? ''),
            'contract_snapshot_text'         => (string) ($snapshot['body_text'] ?? ''),
            'contract_annex_kvkk_text'       => (string) ($snapshot['annex_kvkk_text'] ?? ''),
            'contract_annex_commitment_text' => (string) ($snapshot['annex_commitment_text'] ?? ''),
            'contract_generated_at'          => now(),
            'status_message'                 => 'Sözleşme güncelleme talebi gönderildi: '.$updateNote,
            'notes'                          => trim(((string) ($guest->notes ?? ''))."\n[Contract Update ".now()->format('Y-m-d H:i')."] ".$updateNote),
        ])->save();

        $this->taskAutomationService->ensureContractReviewTask($guest);
        $this->eventLogService->log(
            eventType: 'guest_contract_update_requested',
            entityType: 'guest_application',
            entityId: (string) $guest->id,
            message: "Guest #{$guest->id} sozlesme guncelleme talebi gonderdi.",
            meta: [
                'package_code'  => (string) ($guest->selected_package_code ?? ''),
                'extra_services'=> implode(', ', collect($extras)->pluck('title')->all()),
                'note'          => $updateNote,
            ],
            actorEmail: (string) optional($request->user())->email,
            companyId: (int) ($guest->company_id ?: 0)
        );
        $this->queueTemplateNotification(
            guest: $guest,
            category: 'guest_contract_update',
            sourceType: 'guest_contract_update_requested',
            sourceId: (string) $guest->id,
            vars: ['guest_id' => (string) $guest->id]
        );

        return redirect()->route('guest.contract')->with('status', 'Sözleşme güncelleme talebi gönderildi.');
    }

    private function normalizeContractStatus(string $status): string
    {
        $normalized = strtolower(trim($status));
        return in_array($normalized, [
            'not_requested', 'pending_manager', 'requested',
            'signed_uploaded', 'approved', 'rejected', 'cancelled', 'reopen_requested',
        ], true) ? $normalized : 'not_requested';
    }

    private function contractStateHasInconsistency(GuestApplication $guest, string $normalizedStatus): bool
    {
        $hasSnapshot    = trim((string) ($guest->contract_snapshot_text ?? '')) !== '';
        $hasTemplate    = trim((string) ($guest->contract_template_code ?? '')) !== '' || (int) ($guest->contract_template_id ?? 0) > 0;
        $hasRequestedAt = !empty($guest->contract_requested_at);
        $hasSignedFile  = trim((string) ($guest->contract_signed_file_path ?? '')) !== '';
        $hasSignedAt    = !empty($guest->contract_signed_at);

        if (in_array($normalizedStatus, ['requested', 'signed_uploaded', 'approved', 'rejected'], true)
            && (!$hasSnapshot || !$hasTemplate || !$hasRequestedAt)) {
            return true;
        }
        if (in_array($normalizedStatus, ['signed_uploaded', 'approved'], true)
            && (!$hasSignedFile || !$hasSignedAt)) {
            return true;
        }
        return false;
    }
}
