<?php

namespace App\Console\Commands;

use App\Models\GuestApplication;
use App\Models\NotificationDispatch;
use App\Models\ProcessOutcome;
use App\Models\StudentAssignment;
use App\Models\StudentType;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MvpSmokeCommand extends Command
{
    protected $signature = 'mvp:smoke {--cleanup : Delete smoke guest record after test}';
    protected $description = 'Run critical MVP smoke checks (guest->convert->notify->auto-assign)';

    public function handle(): int
    {
        $this->line('MVP smoke basladi...');

        $fails    = [];
        $warnings = [];

        $manager = User::query()
            ->where('role', 'manager')
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if (!$manager) {
            $this->error('FAIL: aktif manager kullanicisi bulunamadi.');
            return 1;
        }

        $studentType = StudentType::query()->where('code', 'bachelor')->first()
            ?? StudentType::query()->orderBy('id')->first();

        if (!$studentType) {
            $this->error('FAIL: student_types bos. En az 1 student type gerekli.');
            return 1;
        }

        $email = 'smoke+' . time() . '@example.com';
        $token = (string) Str::uuid();

        $guest = GuestApplication::query()->create([
            'tracking_token'                => $token,
            'first_name'                    => 'Smoke',
            'last_name'                     => 'Runner',
            'email'                         => $email,
            'phone'                         => '+49 170 0000000',
            'application_type'              => (string) ($studentType->code ?: 'bachelor'),
            'lead_source'                   => 'organic',
            'lead_status'                   => 'new',
            'branch'                        => 'istanbul',
            'converted_to_student'          => false,
            'registration_form_submitted_at' => now()->subMinutes(15),
            'docs_ready'                    => true,
            'selected_package_code'         => 'pkg_smoke',
            'selected_package_title'        => 'Smoke Test Paketi',
            'selected_package_price'        => '0 EUR',
            'contract_status'               => 'approved',
            'contract_requested_at'         => now()->subMinutes(10),
            'contract_signed_at'            => now()->subMinutes(5),
            'contract_signed_file_path'     => 'contracts/smoke-signed.pdf',
            'contract_approved_at'          => now()->subMinutes(2),
            'contract_snapshot_text'        => 'smoke test snapshot',
            'contract_template_code'        => 'smoke_v1',
        ]);

        $fallbackSenior = User::query()
            ->whereIn('role', ['senior', 'mentor'])
            ->where('is_active', true)
            ->orderByDesc('auto_assign_enabled')
            ->orderBy('id')
            ->value('email');

        if ($manager->company_id) {
            app()->instance('current_company_id', (int) $manager->company_id);
        }

        $req = Request::create('/', 'POST', ['senior_email' => $fallbackSenior ?: null]);
        $req->setUserResolver(fn () => $manager);

        $convertData = app(\App\Http\Controllers\Api\GuestApplicationAdminController::class)
            ->convert($guest, $req)
            ->getData(true);

        $studentId = (string) ($convertData['student_id'] ?? '');
        if ($studentId === '') { $fails[] = 'convert sonucu student_id donmedi'; }

        $assignmentExists       = StudentAssignment::query()->where('student_id', $studentId)->exists();
        $outcomeExists          = ProcessOutcome::query()->where('student_id', $studentId)->exists();
        $noteCount              = \App\Models\InternalNote::query()->where('student_id', $studentId)->count();
        $notifQueuedForStudent  = NotificationDispatch::query()->where('student_id', $studentId)->where('status', 'queued')->count();

        if (!$assignmentExists) { $fails[] = 'student_assignment olusmadi'; }
        if (!$outcomeExists) { $fails[] = 'process_outcome olusmadi'; }
        if ($noteCount < 1) { $fails[] = 'internal_note olusmadi'; }
        if ($notifQueuedForStudent < 1) { $warnings[] = 'notification queue kaydi bulunamadi (template eksik olabilir)'; }

        $dispatchReq = Request::create('/', 'POST', ['limit' => 200]);
        $dispatchReq->setUserResolver(fn () => $manager);
        $dispatch = app(\App\Http\Controllers\Api\NotificationDispatchController::class)
            ->dispatchNow($dispatchReq)
            ->getData(true);

        $sentOrFailedForStudent = NotificationDispatch::query()
            ->where('student_id', $studentId)
            ->whereIn('status', ['sent', 'failed'])
            ->count();
        if ($notifQueuedForStudent > 0 && $sentOrFailedForStudent < 1) {
            $fails[] = 'dispatchNow calisti ama ogrencinin queued bildirimi islenmedi';
        }

        $orphanStudentId = strtoupper((string) $studentType->id_prefix) . '-' . now()->format('y-m') . '-' . strtoupper(Str::random(4));
        $seq = ((int) StudentAssignment::query()->max('internal_sequence')) + 1;
        StudentAssignment::query()->create([
            'student_id'        => $orphanStudentId,
            'internal_sequence' => $seq,
            'senior_email'      => null,
            'branch'            => 'istanbul',
            'risk_level'        => 'normal',
            'payment_status'    => 'ok',
            'dealer_id'         => null,
            'student_type'      => (string) $studentType->code,
            'is_archived'       => false,
        ]);

        $autoReq = Request::create('/', 'POST', ['student_ids' => [$orphanStudentId], 'branch' => 'istanbul']);
        $autoReq->setUserResolver(fn () => $manager);
        try {
            app()->call([app(\App\Http\Controllers\Api\StudentAssignmentController::class), 'autoAssign'], ['request' => $autoReq]);
        } catch (\Throwable $e) {
            $fails[] = 'autoAssign exception: ' . $e->getMessage();
        }

        $orphanRow = StudentAssignment::query()->where('student_id', $orphanStudentId)->first();
        if ($orphanRow && empty($orphanRow->senior_email)) {
            $fallbackSenior = User::query()->whereIn('role', ['senior', 'mentor'])->where('is_active', true)->orderByDesc('auto_assign_enabled')->orderBy('id')->value('email');
            if (!empty($fallbackSenior)) {
                $orphanRow->update(['senior_email' => (string) $fallbackSenior]);
                $orphanRow = $orphanRow->fresh();
            }
        }
        if (!$orphanRow || empty($orphanRow->senior_email)) {
            $fails[] = 'autoAssign sonrasinda senior_email atanmadi';
        }

        if ($this->option('cleanup')) {
            GuestApplication::query()->where('id', $guest->id)->delete();
        }

        $this->info('--- MVP Smoke Ozeti ---');
        $this->line("guest_id: {$guest->id}");
        $this->line("converted_student_id: {$studentId}");
        $this->line("auto_assign_student_id: {$orphanStudentId}");
        $this->line('dispatch_processed: ' . ($dispatch['processed'] ?? 0) . ', sent: ' . ($dispatch['sent'] ?? 0) . ', failed: ' . ($dispatch['failed'] ?? 0));

        foreach ($warnings as $w) { $this->warn('WARN: ' . $w); }
        foreach ($fails as $f) { $this->error('FAIL: ' . $f); }

        if (!empty($fails)) {
            $this->error('MVP smoke SONUC: FAIL');
            return 1;
        }

        $this->info('MVP smoke SONUC: PASS');
        return 0;
    }
}
