<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\StudentWorkflowTrait;
use App\Models\GuestTicket;
use App\Models\GuestTicketReply;
use App\Services\EventLogService;
use Illuminate\Http\Request;

class StudentTicketController extends Controller
{
    use StudentWorkflowTrait;

    public function __construct(private readonly EventLogService $eventLogService) {}

    public function store(Request $request)
    {
        $guest = $this->resolveStudentGuest($request);
        abort_if(! $guest, 404, 'Student icin bagli basvuru kaydi bulunamadi.');

        $data = $request->validate([
            'subject'    => ['required', 'string', 'max:180'],
            'message'    => ['required', 'string', 'max:5000'],
            'priority'   => ['nullable', 'in:low,normal,high,urgent'],
            'department' => ['nullable', 'in:auto,operations,finance,advisory,marketing,system'],
            'return_to'  => ['nullable', 'in:/student/tickets,/student/contract,/student/services'],
        ]);

        $department = trim((string) ($data['department'] ?? 'auto'));
        if ($department === '' || $department === 'auto') {
            $department = $this->suggestDepartment((string) $data['subject'], (string) $data['message']);
        }

        $ticket = GuestTicket::query()->create([
            'guest_application_id' => (int) $guest->id,
            'subject'              => trim((string) $data['subject']),
            'message'              => trim((string) $data['message']),
            'status'               => 'open',
            'priority'             => (string) ($data['priority'] ?? 'normal'),
            'department'           => $department,
            'created_by_email'     => (string) optional($request->user())->email,
            'last_replied_at'      => now(),
            'routed_at'            => now(),
        ]);

        GuestTicketReply::query()->create([
            'guest_ticket_id' => (int) $ticket->id,
            'author_role'     => 'student',
            'author_email'    => (string) optional($request->user())->email,
            'message'         => trim((string) $data['message']),
        ]);

        $this->eventLogService->log(
            eventType: 'student_ticket_opened',
            entityType: 'guest_ticket',
            entityId: (string) $ticket->id,
            message: "Student {$guest->converted_student_id} ticket acti.",
            meta: ['department' => $department, 'priority' => (string) ($data['priority'] ?? 'normal')],
            actorEmail: (string) optional($request->user())->email,
            companyId: (int) ($guest->company_id ?: 0)
        );

        $returnTo = trim((string) ($data['return_to'] ?? '/student/tickets'));
        if (! in_array($returnTo, ['/student/tickets', '/student/contract', '/student/services'], true)) {
            $returnTo = '/student/tickets';
        }

        return redirect($returnTo)->with('status', 'Ticket olusturuldu.');
    }

    public function reply(Request $request, GuestTicket $ticket)
    {
        $guest = $this->resolveStudentGuest($request);
        abort_if(! $guest, 404, 'Student icin bagli basvuru kaydi bulunamadi.');
        abort_if((int) $ticket->guest_application_id !== (int) $guest->id, 403, 'Bu ticket size ait degil.');

        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        GuestTicketReply::query()->create([
            'guest_ticket_id' => (int) $ticket->id,
            'author_role'     => 'student',
            'author_email'    => (string) optional($request->user())->email,
            'message'         => trim((string) $data['message']),
        ]);

        $ticket->forceFill([
            'status'          => 'waiting_response',
            'last_replied_at' => now(),
        ])->save();

        $this->eventLogService->log(
            eventType: 'student_ticket_reply',
            entityType: 'guest_ticket',
            entityId: (string) $ticket->id,
            message: "Student {$guest->converted_student_id} ticket yaniti yazdi.",
            meta: ['status' => 'waiting_response'],
            actorEmail: (string) optional($request->user())->email,
            companyId: (int) ($guest->company_id ?: 0)
        );

        return redirect('/student/tickets')->with('status', 'Yanit eklendi.');
    }

    public function close(Request $request, GuestTicket $ticket)
    {
        $guest = $this->resolveStudentGuest($request);
        abort_if(! $guest, 404, 'Student icin bagli basvuru kaydi bulunamadi.');
        abort_if((int) $ticket->guest_application_id !== (int) $guest->id, 403, 'Bu ticket size ait degil.');

        $ticket->forceFill([
            'status'          => 'closed',
            'closed_at'       => now(),
            'last_replied_at' => now(),
        ])->save();

        $user = $request->user();
        if ($user && $user->email) {
            try {
                \Mail::to($user->email)->queue(new \App\Mail\TicketClosedMail($ticket));
            } catch (\Throwable) {}
        }

        return redirect('/student/tickets')->with('status', 'Ticket kapatildi.');
    }

    public function reopen(Request $request, GuestTicket $ticket)
    {
        $guest = $this->resolveStudentGuest($request);
        abort_if(! $guest, 404, 'Student icin bagli basvuru kaydi bulunamadi.');
        abort_if((int) $ticket->guest_application_id !== (int) $guest->id, 403, 'Bu ticket size ait degil.');

        $ticket->forceFill([
            'status'          => 'open',
            'closed_at'       => null,
            'last_replied_at' => now(),
        ])->save();

        return redirect('/student/tickets')->with('status', 'Ticket tekrar acildi.');
    }
}
