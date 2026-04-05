<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dealer\Concerns\DealerPortalTrait;
use App\Models\GuestApplication;
use App\Models\GuestTicket;
use App\Models\GuestTicketReply;
use App\Models\MarketingTask;
use App\Models\NotificationDispatch;
use App\Models\StudentInstitutionDocument;
use App\Models\StudentUniversityApplication;
use App\Models\SystemEventLog;
use App\Services\EventLogService;
use App\Services\NotificationService;
use App\Services\TaskAutomationService;
use App\Support\ApplicationCountryCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DealerLeadController extends Controller
{
    use DealerPortalTrait;

    public function __construct(
        private readonly TaskAutomationService $taskAutomationService,
        private readonly EventLogService $eventLogService,
        private readonly NotificationService $notificationService,
    ) {}

    public function leadForm(Request $request)
    {
        return view('dealer.lead-form', $this->baseData($request) + [
            'applicationCountries' => ApplicationCountryCatalog::options(),
        ]);
    }

    public function storeLead(Request $request)
    {
        $data = $request->validate([
            'first_name'         => ['required', 'string', 'max:120'],
            'last_name'          => ['required', 'string', 'max:120'],
            'phone'              => ['required', 'string', 'max:60'],
            'email'              => ['nullable', 'email', 'max:190'],
            'application_type'   => ['required', 'string', 'max:64'],
            'application_country'=> ['nullable', 'string', 'max:120'],
            'notes'              => ['nullable', 'string', 'max:2000'],
            'kvkk_consent'       => ['accepted'],
        ]);

        $base = $this->baseData($request);
        abort_if(empty($base['dealerCode']), 403, 'Dealer code missing');

        $guest = GuestApplication::query()->create([
            'tracking_token'      => $this->generateTrackingToken(),
            'first_name'          => trim((string) $data['first_name']),
            'last_name'           => trim((string) $data['last_name']),
            'email'               => strtolower(trim((string) ($data['email'] ?? ''))) ?: null,
            'phone'               => trim((string) $data['phone']),
            'application_type'    => trim((string) $data['application_type']),
            'application_country' => trim((string) ($data['application_country'] ?? 'de')) ?: 'de',
            'lead_source'         => 'dealer_form',
            'dealer_code'         => $base['dealerCode'],
            'branch'              => (string) ($base['dealer']?->dealer_type_code ?? ''),
            'priority'            => 'normal',
            'risk_level'          => 'normal',
            'lead_status'         => 'new',
            'notes'               => trim((string) ($data['notes'] ?? '')) ?: null,
            'kvkk_consent'        => true,
            'docs_ready'          => false,
            'converted_to_student'=> false,
            'status_message'      => 'Dealer panelinden yonlendirme olusturuldu.',
        ]);

        $ticket = GuestTicket::query()->create([
            'guest_application_id' => (int) $guest->id,
            'subject'              => 'Dealer yonlendirme inceleme',
            'message'              => 'Dealer panelinden yeni yonlendirme olusturuldu. Operasyon incelemesi gerekli.',
            'status'               => 'open',
            'priority'             => 'normal',
            'department'           => 'operations',
            'created_by_email'     => (string) ($request->user()?->email ?? 'dealer@mentorde.local'),
            'routed_at'            => now(),
        ]);

        GuestTicketReply::query()->create([
            'guest_ticket_id' => (int) $ticket->id,
            'author_role'     => 'dealer',
            'author_email'    => (string) ($request->user()?->email ?? 'dealer@mentorde.local'),
            'message'         => sprintf(
                'Dealer lead olustu. Dealer: %s | Tip: %s | Ulke: %s | Telefon: %s',
                (string) ($base['dealerCode'] ?? '-'),
                (string) ($guest->application_type ?? '-'),
                (string) ($guest->application_country ?? '-'),
                (string) ($guest->phone ?? '-'),
            ),
        ]);

        $this->taskAutomationService->ensureGuestRegistrationReviewTask($guest);
        $this->taskAutomationService->ensureGuestTicketTask($guest, $ticket);

        $this->eventLogService->log('dealer_lead_created', [
            'guest_id'             => (int) $guest->id,
            'guest_tracking_token' => (string) $guest->tracking_token,
            'dealer_code'          => (string) ($base['dealerCode'] ?? ''),
            'dealer_user'          => (string) ($request->user()?->email ?? ''),
            'ticket_id'            => (int) $ticket->id,
        ], 'dealer', (string) ($request->user()?->email ?? 'dealer'));

        $this->queueDealerLeadNotifications($guest, (string) ($request->user()?->email ?? ''));

        return redirect('/dealer/leads')->with('status', 'Yönlendirme kaydedildi. Guest kaydı oluşturuldu.');
    }

    public function leads(Request $request)
    {
        $data           = $this->baseData($request);
        $q              = trim((string) $request->query('q', ''));
        $status         = trim((string) $request->query('status', ''));
        $source         = trim((string) $request->query('source', ''));
        $from           = trim((string) $request->query('from', ''));
        $to             = trim((string) $request->query('to', ''));

        $rows           = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 25);
        $packetCount    = 0;
        $contractCount  = 0;
        $convertedCount = 0;
        $stagesData     = ['new' => 0, 'contacted' => 0, 'qualified' => 0, 'converted' => 0, 'lost' => 0];

        if (!empty($data['dealerCode'])) {
            $baseQuery = GuestApplication::query()
                ->where('dealer_code', $data['dealerCode'])
                ->when($q !== '', function ($qr) use ($q) {
                    $qr->where(function ($x) use ($q) {
                        $x->where('first_name', 'like', "%{$q}%")
                            ->orWhere('last_name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%")
                            ->orWhere('phone', 'like', "%{$q}%")
                            ->orWhere('tracking_token', 'like', "%{$q}%")
                            ->orWhere('converted_student_id', 'like', "%{$q}%");
                    });
                })
                ->when($status !== '', fn ($qr) => $qr->where('lead_status', $status))
                ->when($source !== '', function ($qr) use ($source) {
                    $qr->where(function ($x) use ($source) {
                        $x->where('lead_source', $source)->orWhere('utm_source', $source);
                    });
                })
                ->when($from !== '', fn ($qr) => $qr->whereDate('created_at', '>=', $from))
                ->when($to !== '', fn ($qr) => $qr->whereDate('created_at', '<=', $to));

            $packetCount    = (clone $baseQuery)->whereNotNull('selected_package_code')->where('selected_package_code', '!=', '')->count();
            $contractCount  = (clone $baseQuery)->whereNotNull('contract_status')->where('contract_status', '!=', '')->count();
            $convertedCount = (clone $baseQuery)->whereNotNull('converted_student_id')->where('converted_student_id', '!=', '')->count();
            $stagesData     = [
                'new'       => (clone $baseQuery)->where('lead_status', 'new')->count(),
                'contacted' => (clone $baseQuery)->where('lead_status', 'contacted')->count(),
                'qualified' => (clone $baseQuery)->where('lead_status', 'qualified')->count(),
                'converted' => $convertedCount,
                'lost'      => (clone $baseQuery)->where('lead_status', 'lost')->count(),
            ];

            $rows = $baseQuery->latest()->paginate(25, [
                'id', 'tracking_token', 'first_name', 'last_name', 'email', 'phone',
                'application_type', 'lead_status', 'lead_source', 'utm_source', 'converted_student_id',
                'assigned_senior_email', 'contract_status', 'selected_package_code', 'created_at',
            ]);
        }

        return view('dealer.leads', $data + [
            'rows'          => $rows,
            'packetCount'   => $packetCount,
            'contractCount' => $contractCount,
            'stagesData'    => $stagesData,
            'filterQ'       => $q,
            'filterStatus'  => $status,
            'filterSource'  => $source,
            'filterFrom'    => $from,
            'filterTo'      => $to,
        ]);
    }

    public function leadDetail(Request $request, GuestApplication $lead)
    {
        $data = $this->baseData($request);
        abort_if(empty($data['dealerCode']), 403, 'Dealer code missing');
        abort_if((string) ($lead->dealer_code ?? '') !== (string) $data['dealerCode'], 403, 'Bu lead bu dealer hesabina ait degil.');

        /** @var \App\Support\DealerTierPermissions $tierPerms */
        $tierPerms             = $data['tierPerms'];
        $canViewStudentDetails = $tierPerms->can('canViewStudentDetails');
        $canViewProcessDetails = $tierPerms->can('canViewProcessDetails');
        $canViewDocuments      = $tierPerms->can('canViewDocuments');

        $tickets   = GuestTicket::query()
            ->where('guest_application_id', (int) $lead->id)
            ->with(['replies' => fn ($q) => $q->orderBy('id')])
            ->orderByDesc('id')
            ->get();
        $ticketIds = $tickets->pluck('id')->map(fn ($id) => (string) $id)->all();

        $tasks = MarketingTask::query()
            ->withoutGlobalScope('company')
            ->where(function ($q) use ($lead, $ticketIds) {
                $q->where(function ($x) use ($lead) {
                    $x->whereIn('source_type', ['guest_registration_submit', 'guest_contract_requested', 'guest_contract_signed_uploaded'])
                      ->where('source_id', (string) $lead->id);
                });
                if (!empty($ticketIds)) {
                    $q->orWhere(function ($x) use ($ticketIds) {
                        $x->where('source_type', 'guest_ticket_opened')->whereIn('source_id', $ticketIds);
                    });
                }
            })
            ->latest('id')->limit(100)->get();

        $events = SystemEventLog::query()
            ->where(function ($q) use ($lead) {
                $q->where(function ($x) use ($lead) {
                    $x->where('entity_type', 'guest')->where('entity_id', (string) $lead->id);
                })->orWhere(function ($x) use ($lead) {
                    $x->where('event_type', 'dealer_lead_created')
                      ->where('entity_type', 'dealer')
                      ->where('message', 'like', '%'.$lead->id.'%');
                });
            })
            ->latest('id')->limit(50)->get();

        $notifications = NotificationDispatch::query()
            ->where('source_type', 'dealer_lead')
            ->where('source_id', (string) $lead->id)
            ->latest('id')->limit(50)
            ->get(['id', 'channel', 'category', 'recipient_email', 'status', 'queued_at', 'sent_at', 'failed_at']);

        $commissionRevenue = null;
        if (!empty($lead->converted_student_id)) {
            $commissionRevenue = \App\Models\DealerStudentRevenue::query()
                ->where('dealer_id', $data['dealerCode'])
                ->where('student_id', $lead->converted_student_id)
                ->first();
        }

        $timeline = collect();
        $timeline->push(['type' => 'lead', 'title' => 'Lead olusturuldu', 'meta' => 'Dealer paneli | lead_status: '.((string) ($lead->lead_status ?: 'new')), 'when' => $lead->created_at]);

        foreach ($tickets as $ticket) {
            $timeline->push(['type' => 'ticket', 'title' => 'Ticket #'.$ticket->id.' ('.((string) ($ticket->department ?: 'operations')).')', 'meta' => 'status:'.((string) ($ticket->status ?: '-')).' | konu: '.((string) ($ticket->subject ?: '-')), 'when' => $ticket->created_at]);
            foreach ($ticket->replies as $reply) {
                $timeline->push(['type' => 'ticket_reply', 'title' => 'Ticket yanitı #'.$ticket->id, 'meta' => ((string) ($reply->author_role ?: '-')).' | '.((string) ($reply->author_email ?: '-')).' | '.Str::limit((string) ($reply->message ?: ''), 120), 'when' => $reply->created_at]);
            }
        }
        foreach ($tasks as $task) {
            $timeline->push(['type' => 'task', 'title' => 'Task #'.$task->id.' - '.((string) ($task->title ?: '-')), 'meta' => 'dept:'.((string) ($task->department ?: '-')).' | status:'.((string) ($task->status ?: '-')), 'when' => $task->created_at]);
        }
        foreach ($events as $event) {
            $timeline->push(['type' => 'event', 'title' => 'Sistem Event: '.((string) ($event->event_type ?: '-')), 'meta' => Str::limit((string) ($event->message ?: '-'), 140), 'when' => $event->created_at]);
        }
        foreach ($notifications as $n) {
            $timeline->push(['type' => 'notification', 'title' => 'Bildirim Kuyrugu #'.$n->id.' ('.$n->channel.')', 'meta' => 'recipient:'.((string) ($n->recipient_email ?: '-')).' | status:'.((string) ($n->status ?: '-')), 'when' => $n->queued_at ?: $n->created_at]);
        }

        $timeline = $timeline->sortByDesc(fn ($row) => optional($row['when'] ?? null)?->timestamp ?? 0)->values();

        $institutionDocs    = collect();
        $convertedStudentId = (string) ($lead->converted_student_id ?? '');
        if ($convertedStudentId !== '' && $canViewDocuments) {
            $institutionDocs = StudentInstitutionDocument::query()
                ->forStudent($convertedStudentId)->visibleToDealer()->latest()
                ->get(['id', 'institution_category', 'document_type_label', 'institution_name', 'received_date', 'status', 'notes', 'file_id', 'created_at']);
        }

        $dealerUniApps = collect();
        if ($convertedStudentId !== '' && $canViewProcessDetails) {
            $dealerUniApps = StudentUniversityApplication::query()
                ->forStudent($convertedStudentId)->where('is_visible_to_dealer', true)->orderBy('priority')
                ->get(['id', 'university_name', 'city', 'department_name', 'degree_type', 'semester', 'application_portal', 'status', 'priority', 'deadline', 'submitted_at', 'result_at']);
        }

        return view('dealer.lead-detail', $data + [
            'lead'                  => $lead,
            'tickets'               => $tickets,
            'tasks'                 => $tasks,
            'events'                => $events,
            'notifications'         => $notifications,
            'timeline'              => $timeline,
            'commissionRevenue'     => $commissionRevenue,
            'institutionDocs'       => $institutionDocs,
            'institutionCatalog'    => config('institution_document_catalog.categories', []),
            'dealerUniApps'         => $dealerUniApps,
            'progress'              => $this->calculateLeadProgress($lead),
            'canViewStudentDetails' => $canViewStudentDetails,
            'canViewProcessDetails' => $canViewProcessDetails,
            'canViewDocuments'      => $canViewDocuments,
        ]);
    }

    public function updateLeadQualification(Request $request, GuestApplication $lead): \Illuminate\Http\RedirectResponse
    {
        $data = $this->baseData($request);
        abort_if(empty($data['dealerCode']), 403);
        abort_if((string) ($lead->dealer_code ?? '') !== (string) $data['dealerCode'], 403);

        $validated = $request->validate([
            'lead_status'          => ['nullable', 'in:new,contacted,qualified,converted,lost'],
            'qualification_status' => ['nullable', 'in:unqualified,warm,hot,qualified'],
            'lost_reason'          => ['nullable', 'in:no_response,chose_competitor,budget,not_interested,timing,other'],
            'lost_note'            => ['nullable', 'string', 'max:300'],
            'follow_up_date'       => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $updates = array_filter([
            'lead_status'          => $validated['lead_status'] ?? null,
            'qualification_status' => $validated['qualification_status'] ?? null,
            'lost_reason'          => ($validated['lead_status'] ?? '') === 'lost' ? ($validated['lost_reason'] ?? null) : null,
            'lost_note'            => ($validated['lead_status'] ?? '') === 'lost' ? ($validated['lost_note'] ?? null) : null,
            'follow_up_date'       => $validated['follow_up_date'] ?? null,
        ], fn ($v) => $v !== null);

        if (!empty($updates)) {
            $lead->forceFill($updates)->save();
        }

        return redirect("/dealer/leads/{$lead->id}")->with('status', 'Lead bilgileri güncellendi.');
    }

    public function leadTickets(Request $request, GuestApplication $lead): \Illuminate\Http\JsonResponse
    {
        $data = $this->baseData($request);
        abort_if((string) ($lead->dealer_code ?? '') !== (string) $data['dealerCode'], 403);

        $tickets = GuestTicket::where('guest_application_id', (int) $lead->id)
            ->with(['replies' => fn ($q) => $q->latest()->limit(5)])
            ->latest()->limit(10)->get();

        return response()->json(['tickets' => $tickets]);
    }

    public function leadTimeline(Request $request, GuestApplication $lead): \Illuminate\Http\JsonResponse
    {
        $data = $this->baseData($request);
        abort_if((string) ($lead->dealer_code ?? '') !== (string) $data['dealerCode'], 403);

        $events = SystemEventLog::where('entity_type', 'guest')
            ->where('entity_id', (string) $lead->id)
            ->latest()->limit(20)
            ->get(['id', 'event_type', 'message', 'actor_email', 'created_at']);

        return response()->json(['events' => $events]);
    }
}
