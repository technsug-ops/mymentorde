<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BatchOperationRun;
use App\Models\GuestApplication;
use App\Models\StudentAssignment;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BatchOperationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function index()
    {
        return BatchOperationRun::query()->latest()->limit(100)->get();
    }

    public function broadcastNotification(Request $request)
    {
        $data = $request->validate([
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['string', 'max:64'],
            'branch' => ['nullable', 'string', 'max:64'],
            'senior_email' => ['nullable', 'email'],
            'dealer_id' => ['nullable', 'string', 'max:64'],
            'channel' => ['required', 'in:email,whatsapp,inApp'],
            'category' => ['nullable', 'string', 'max:64'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:3000'],
        ]);

        $query = StudentAssignment::query()->where('is_archived', false);

        $studentIds = collect($data['student_ids'] ?? [])->map(fn ($v) => trim((string) $v))->filter()->values();
        if ($studentIds->isNotEmpty()) {
            $query->whereIn('student_id', $studentIds);
        }

        $branch = trim((string) ($data['branch'] ?? ''));
        if ($branch !== '') {
            $query->where('branch', 'like', "%{$branch}%");
        }

        $seniorEmail = trim((string) ($data['senior_email'] ?? ''));
        if ($seniorEmail !== '') {
            $query->where('senior_email', $seniorEmail);
        }

        $dealerId = trim((string) ($data['dealer_id'] ?? ''));
        if ($dealerId !== '') {
            $query->where('dealer_id', 'like', "%{$dealerId}%");
        }

        $targets = $query->limit(500)->get(['student_id', 'senior_email']);

        $processed = 0;
        $failed = 0;
        foreach ($targets as $target) {
            $studentId = (string) $target->student_id;
            $guest = GuestApplication::query()
                ->where('converted_student_id', $studentId)
                ->latest('id')
                ->first(['email', 'phone', 'first_name', 'last_name']);

            $recipientEmail = (string) ($guest->email ?? $target->senior_email ?? '');
            $recipientPhone = (string) ($guest->phone ?? '');
            $recipientName = trim((string) (($guest->first_name ?? '').' '.($guest->last_name ?? '')));
            if ($recipientName === '') {
                $recipientName = $studentId;
            }

            if ($recipientEmail === '' && $recipientPhone === '') {
                $failed++;
                continue;
            }

            $this->notificationService->send([
                'channel'         => (string) $data['channel'],
                'category'        => (string) ($data['category'] ?? 'batch_broadcast'),
                'student_id'      => $studentId,
                'recipient_email' => $recipientEmail !== '' ? $recipientEmail : null,
                'recipient_phone' => $recipientPhone !== '' ? $recipientPhone : null,
                'recipient_name'  => $recipientName,
                'subject'         => (string) ($data['subject'] ?? '') ?: null,
                'body'            => (string) $data['body'],
                'variables'       => [
                    'student_id' => $studentId,
                    'batch'      => true,
                ],
                'triggered_by' => (string) optional($request->user())->email,
            ]);
            $processed++;
        }

        $run = BatchOperationRun::query()->create([
            'operation_type' => 'notification_broadcast',
            'filters' => [
                'student_ids' => $studentIds->all(),
                'branch' => $branch !== '' ? $branch : null,
                'senior_email' => $seniorEmail !== '' ? $seniorEmail : null,
                'dealer_id' => $dealerId !== '' ? $dealerId : null,
            ],
            'payload' => [
                'channel' => (string) $data['channel'],
                'category' => (string) ($data['category'] ?? 'batch_broadcast'),
                'subject' => (string) ($data['subject'] ?? ''),
                'body' => (string) $data['body'],
            ],
            'target_count' => $targets->count(),
            'processed_count' => $processed,
            'failed_count' => $failed,
            'status' => 'done',
            'created_by' => (string) optional($request->user())->email,
        ]);

        return response()->json([
            'run_id' => $run->id,
            'target_count' => $targets->count(),
            'processed_count' => $processed,
            'failed_count' => $failed,
        ], Response::HTTP_CREATED);
    }
}

