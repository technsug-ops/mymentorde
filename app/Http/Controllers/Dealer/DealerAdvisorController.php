<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dealer\Concerns\DealerPortalTrait;
use App\Models\GuestApplication;
use App\Models\GuestTicket;
use App\Models\GuestTicketReply;
use App\Models\StudentAssignment;
use App\Models\User;
use App\Services\EventLogService;
use App\Services\NotificationService;
use App\Services\TaskAutomationService;
use Illuminate\Http\Request;

class DealerAdvisorController extends Controller
{
    use DealerPortalTrait;

    public function __construct(
        private readonly TaskAutomationService $taskAutomationService,
        private readonly EventLogService $eventLogService,
        private readonly NotificationService $notificationService,
    ) {}

    public function advisor(Request $request)
    {
        $data = $this->baseData($request);
        /** @var \App\Support\DealerTierPermissions $tierPerms */
        $tierPerms = $data['tierPerms'];
        $seniors   = collect();
        $tickets   = collect();

        if (!empty($data['dealerCode'])) {
            if ($tierPerms->isStandard()) {
                $seniors = StudentAssignment::query()
                    ->where('dealer_id', $data['dealerCode'])
                    ->whereNotNull('senior_email')
                    ->selectRaw('senior_email, COUNT(*) as total_students')
                    ->groupBy('senior_email')
                    ->orderByDesc('total_students')
                    ->get();
            }

            $guestIds = GuestApplication::query()
                ->where('dealer_code', $data['dealerCode'])
                ->limit(500)->pluck('id');

            if ($guestIds->isNotEmpty()) {
                $tickets = GuestTicket::query()
                    ->whereIn('guest_application_id', $guestIds->all())
                    ->latest()->limit(20)
                    ->get(['id', 'guest_application_id', 'subject', 'status', 'department', 'priority', 'updated_at']);
            }
        }

        $opsAdmin = $tierPerms->isBasic()
            ? User::where('role', 'operations_admin')->first(['id', 'email', 'name'])
            : null;

        return view('dealer.advisor', $data + compact('seniors', 'tickets', 'opsAdmin'));
    }

    public function createAdvisorTicket(Request $request)
    {
        $data = $this->baseData($request);
        abort_if(empty($data['dealerCode']), 403, 'Dealer code missing');

        $validated = $request->validate([
            'subject'  => ['required', 'string', 'max:255'],
            'message'  => ['required', 'string', 'max:3000'],
            'priority' => ['nullable', 'in:low,normal,high'],
        ]);

        $latestGuest = GuestApplication::query()
            ->where('dealer_code', $data['dealerCode'])->latest()->first(['id']);

        /** @var \App\Support\DealerTierPermissions $tierPerms */
        $tierPerms      = $data['tierPerms'];
        $assignedUserId = null;
        $department     = 'dealer_support';
        if ($tierPerms->isBasic()) {
            $opsAdmin       = User::where('role', 'operations_admin')->first(['id']);
            $assignedUserId = $opsAdmin?->id;
            $department     = 'operations';
        }

        $ticket = GuestTicket::query()->create([
            'guest_application_id' => $latestGuest?->id,
            'subject'              => trim((string) $validated['subject']),
            'message'              => trim((string) $validated['message']),
            'status'               => 'open',
            'priority'             => $validated['priority'] ?? 'normal',
            'department'           => $department,
            'assigned_user_id'     => $assignedUserId,
            'created_by_email'     => (string) ($request->user()?->email ?? ''),
            'routed_at'            => now(),
        ]);

        GuestTicketReply::query()->create([
            'guest_ticket_id' => (int) $ticket->id,
            'author_role'     => 'dealer',
            'author_email'    => (string) ($request->user()?->email ?? ''),
            'message'         => trim((string) $validated['message']),
        ]);

        $this->eventLogService->log('dealer_ticket_created', [
            'ticket_id'   => (int) $ticket->id,
            'dealer_code' => $data['dealerCode'],
            'dealer_user' => (string) ($request->user()?->email ?? ''),
        ], 'dealer', (string) ($request->user()?->email ?? 'dealer'));

        return redirect('/dealer/advisor')->with('status', 'Destek talebiniz oluşturuldu. #'.$ticket->id);
    }

    public function ticketDetail(Request $request, GuestTicket $ticket)
    {
        $data = $this->baseData($request);
        abort_if(empty($data['dealerCode']), 403, 'Dealer code missing');

        $guestApp = GuestApplication::query()->find($ticket->guest_application_id);
        abort_if(
            !$guestApp || (string) ($guestApp->dealer_code ?? '') !== (string) $data['dealerCode'],
            403,
            'Bu ticket bu dealer hesabına ait değil.'
        );

        $replies = $ticket->replies()->orderBy('id')->get();

        return view('dealer.advisor.ticket-detail', $data + compact('ticket', 'guestApp', 'replies'));
    }

    public function replyTicket(Request $request, GuestTicket $ticket)
    {
        $data = $this->baseData($request);
        abort_if(empty($data['dealerCode']), 403, 'Dealer code missing');

        $guestApp = GuestApplication::query()->find($ticket->guest_application_id);
        abort_if(
            !$guestApp || (string) ($guestApp->dealer_code ?? '') !== (string) $data['dealerCode'],
            403,
            'Bu ticket bu dealer hesabına ait değil.'
        );
        abort_if($ticket->status === 'closed', 422, 'Kapalı ticket yanıtlanamaz.');

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:3000'],
        ]);

        GuestTicketReply::query()->create([
            'guest_ticket_id' => (int) $ticket->id,
            'author_role'     => 'dealer',
            'author_email'    => (string) ($request->user()?->email ?? ''),
            'message'         => trim((string) $validated['message']),
        ]);

        $ticket->update(['last_replied_at' => now()]);

        return redirect()->route('dealer.advisor.tickets.show', $ticket->id)
            ->with('status', 'Yanıtınız gönderildi.');
    }
}
