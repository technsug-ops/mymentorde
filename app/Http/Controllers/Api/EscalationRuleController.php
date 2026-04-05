<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EscalationRule;
use App\Services\EscalationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EscalationRuleController extends Controller
{
    public function index()
    {
        return EscalationRule::query()->latest()->limit(100)->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'entity_type' => ['required', 'in:field_rule_approval,process_outcome'],
            'duration_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
            'escalation_steps' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['duration_hours'] = (int) ($data['duration_hours'] ?? 24);
        $data['escalation_steps'] = $this->normalizeSteps($data['escalation_steps'] ?? []);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        $data['created_by'] = (string) optional($request->user())->email;

        $row = EscalationRule::query()->create($data);
        return response()->json($row, Response::HTTP_CREATED);
    }

    public function update(Request $request, EscalationRule $escalationRule)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'duration_hours' => ['sometimes', 'integer', 'min:1', 'max:720'],
            'escalation_steps' => ['sometimes', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('escalation_steps', $data)) {
            $data['escalation_steps'] = $this->normalizeSteps($data['escalation_steps'] ?? []);
        }

        $escalationRule->update($data);
        return response()->json($escalationRule->fresh());
    }

    public function processNow(Request $request, EscalationService $service)
    {
        $data = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        return response()->json($service->process((int) ($data['limit'] ?? 100)));
    }

    private function normalizeSteps(array $steps): array
    {
        if (empty($steps)) {
            return [
                ['step' => 1, 'after_hours' => 24, 'action' => 'remind', 'target_roles' => ['senior'], 'channels' => ['in_app']],
                ['step' => 2, 'after_hours' => 48, 'action' => 'remind', 'target_roles' => ['senior', 'manager'], 'channels' => ['email', 'in_app']],
                ['step' => 3, 'after_hours' => 72, 'action' => 'escalate', 'target_roles' => ['manager'], 'channels' => ['email', 'in_app']],
            ];
        }

        return collect($steps)->map(function ($row, int $index): array {
            return [
                'step' => (int) ($row['step'] ?? ($index + 1)),
                'after_hours' => max(1, (int) ($row['after_hours'] ?? 24)),
                'action' => (string) ($row['action'] ?? 'remind'),
                'target_roles' => array_values(array_filter((array) ($row['target_roles'] ?? ['senior']))),
                'channels' => array_values(array_filter((array) ($row['channels'] ?? ['in_app']))),
            ];
        })->values()->all();
    }
}

