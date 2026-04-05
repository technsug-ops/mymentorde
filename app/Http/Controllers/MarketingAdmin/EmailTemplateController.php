<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\Marketing\EmailCampaign;
use App\Models\Marketing\EmailSendLog;
use App\Models\Marketing\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class EmailTemplateController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'type' => (string) $request->query('type', 'all'),
            'status' => (string) $request->query('status', 'all'),
        ];

        $query = EmailTemplate::query()->orderByDesc('id');
        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->where(function ($w) use ($q): void {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('category', 'like', "%{$q}%")
                    ->orWhere('trigger_event', 'like', "%{$q}%");
            });
        }
        if (in_array($filters['type'], ['automated', 'manual'], true)) {
            $query->where('type', $filters['type']);
        }
        if ($filters['status'] === 'active') {
            $query->where('is_active', true);
        } elseif ($filters['status'] === 'passive') {
            $query->where('is_active', false);
        }

        $rows = $query->paginate(15)->withQueryString();
        $editId = (int) $request->query('edit_id', 0);
        $editing = $editId > 0 ? EmailTemplate::query()->find($editId) : null;

        return view('marketing-admin.email.templates.index', [
            'pageTitle' => 'E-posta Sablonlari',
            'title' => 'Template Listesi',
            'rows' => $rows,
            'filters' => $filters,
            'editing' => $editing,
            'stats' => $this->stats(),
            'templateTypes' => ['automated', 'manual'],
            'triggerEvents' => $this->triggerEvents(),
        ]);
    }

    public function create()
    {
        return redirect('/mktg-admin/email/templates');
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request, true);
        $data['created_by'] = (int) $request->user()->id;

        EmailTemplate::query()->create($data);

        return $this->responseFor($request, ['ok' => true], 'Template eklendi.', Response::HTTP_CREATED);
    }

    public function show(string $id)
    {
        return redirect('/mktg-admin/email/templates?edit_id='.$id);
    }

    public function edit(string $id)
    {
        return redirect('/mktg-admin/email/templates?edit_id='.$id);
    }

    public function update(Request $request, string $id)
    {
        $row = EmailTemplate::query()->findOrFail($id);
        $data = $this->validatePayload($request, false);
        $row->update($data);

        return $this->responseFor($request, ['ok' => true, 'id' => $row->id], 'Template guncellendi.');
    }

    public function destroy(Request $request, string $id)
    {
        $row = EmailTemplate::query()->findOrFail($id);
        $hasCampaign = EmailCampaign::query()->where('template_id', $row->id)->exists();
        if ($hasCampaign) {
            $row->update(['is_active' => false]);
            return $this->responseFor(
                $request,
                ['ok' => false, 'id' => $id, 'archived' => true],
                'Template kampanyada kullanildigi icin silinemedi; pasif yapildi.',
                Response::HTTP_CONFLICT
            );
        }

        EmailSendLog::query()->where('template_id', $row->id)->delete();
        $row->delete();
        return $this->responseFor($request, ['ok' => true, 'id' => $id], 'Template silindi.');
    }

    public function testSend(Request $request, string $id)
    {
        $row = EmailTemplate::query()->findOrFail($id);
        $recipientEmail = trim((string) ($request->input('recipient_email', $request->user()->email ?? '')));

        if ($recipientEmail === '') {
            return $this->responseFor(
                $request,
                ['ok' => false, 'id' => $id, 'sent' => false, 'reason' => 'recipient_email_required'],
                'Test gonderim icin e-posta gerekli.',
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        EmailSendLog::query()->create([
            'email_campaign_id' => null,
            'template_id' => (int) $row->id,
            'recipient_user_id' => $request->user()?->id,
            'recipient_email' => $recipientEmail,
            'subject' => (string) ($row->subject_tr ?: $row->name),
            'language' => 'tr',
            'trigger_event' => $row->trigger_event,
            'status' => 'sent',
            'opened_at' => null,
            'clicked_at' => null,
            'clicked_links' => [],
            'bounce_reason' => null,
            'sent_at' => now(),
            'created_at' => now(),
        ]);

        $row->forceFill([
            'stat_total_sent' => (int) $row->stat_total_sent + 1,
            'stat_last_sent_at' => now(),
        ])->save();

        return $this->responseFor($request, ['ok' => true, 'id' => $id, 'sent' => true], 'Test gonderim kuyruuga alindi.');
    }

    private function validatePayload(Request $request, bool $isCreate): array
    {
        $rules = [
            'name' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:255'],
            'type' => [$isCreate ? 'required' : 'sometimes', Rule::in(['automated', 'manual'])],
            'category' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:120'],
            'trigger_event' => ['nullable', 'string', 'max:120'],
            'trigger_delay_minutes' => ['nullable', 'integer', 'min:0', 'max:10080'],
            'subject_tr' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:255'],
            'subject_de' => ['nullable', 'string', 'max:255'],
            'subject_en' => ['nullable', 'string', 'max:255'],
            'body_tr' => [$isCreate ? 'required' : 'sometimes', 'string'],
            'body_de' => ['nullable', 'string'],
            'body_en' => ['nullable', 'string'],
            'from_name' => ['nullable', 'string', 'max:120'],
            'from_email' => ['nullable', 'email', 'max:190'],
            'reply_to' => ['nullable', 'email', 'max:190'],
            'is_active' => ['nullable'],
            'trigger_is_active' => ['nullable'],
            'placeholders' => ['nullable'],
        ];

        $data = $request->validate($rules);
        $placeholdersRaw = $request->input('placeholders', '');
        $placeholders = $this->normalizePlaceholders($placeholdersRaw);

        $payload = [
            'name' => Arr::get($data, 'name'),
            'type' => Arr::get($data, 'type'),
            'category' => Arr::get($data, 'category'),
            'trigger_event' => Arr::get($data, 'trigger_event'),
            'trigger_delay_minutes' => (int) Arr::get($data, 'trigger_delay_minutes', 0),
            'trigger_conditions' => null,
            'trigger_is_active' => $request->boolean('trigger_is_active', true),
            'subject_tr' => Arr::get($data, 'subject_tr'),
            'subject_de' => Arr::get($data, 'subject_de'),
            'subject_en' => Arr::get($data, 'subject_en'),
            'body_tr' => Arr::get($data, 'body_tr'),
            'body_de' => Arr::get($data, 'body_de'),
            'body_en' => Arr::get($data, 'body_en'),
            'available_placeholders' => $placeholders,
            'from_name' => trim((string) Arr::get($data, 'from_name', '')) ?: 'MentorDE',
            'from_email' => trim((string) Arr::get($data, 'from_email', '')) ?: 'noreply@mentorde.com',
            'reply_to' => trim((string) Arr::get($data, 'reply_to', '')) ?: null,
            'is_active' => $request->boolean('is_active', true),
        ];

        if (!$isCreate) {
            $payload = collect($payload)->only(array_keys($data + ['placeholders' => null]))->all();
            if (array_key_exists('placeholders', $data) || $request->has('placeholders')) {
                $payload['available_placeholders'] = $placeholders;
            }
            if ($request->has('is_active')) {
                $payload['is_active'] = $request->boolean('is_active');
            }
            if ($request->has('trigger_is_active')) {
                $payload['trigger_is_active'] = $request->boolean('trigger_is_active');
            }
            if ($request->has('trigger_delay_minutes')) {
                $payload['trigger_delay_minutes'] = (int) Arr::get($data, 'trigger_delay_minutes', 0);
            }
        }

        return $payload;
    }

    private function normalizePlaceholders(mixed $raw): array
    {
        if (is_array($raw)) {
            return collect($raw)->map(fn ($v) => trim((string) $v))->filter()->unique()->values()->all();
        }
        $txt = trim((string) $raw);
        if ($txt === '') {
            return [];
        }
        return collect(explode(',', $txt))->map(fn ($v) => trim((string) $v))->filter()->unique()->values()->all();
    }

    private function stats(): array
    {
        return [
            'total' => EmailTemplate::query()->count(),
            'active' => EmailTemplate::query()->where('is_active', true)->count(),
            'automated' => EmailTemplate::query()->where('type', 'automated')->count(),
            'manual' => EmailTemplate::query()->where('type', 'manual')->count(),
        ];
    }

    private function triggerEvents(): array
    {
        return [
            'guest_registered',
            'student_converted',
            'day_7_no_docs',
            'day_14_no_package',
            'day_30_inactive',
            'day_45_final',
            'milestone_reached',
            'birthday',
            'custom',
        ];
    }

    private function responseFor(Request $request, array $payload, string $statusMessage, int $statusCode = Response::HTTP_OK)
    {
        if ($request->expectsJson()) {
            return response()->json($payload, $statusCode);
        }

        return redirect('/mktg-admin/email/templates')->with('status', $statusMessage);
    }
}
