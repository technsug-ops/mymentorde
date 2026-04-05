<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FieldRule;
use App\Services\FieldRuleEngine;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class FieldRuleController extends Controller
{
    public function index()
    {
        return FieldRule::query()->orderBy('priority')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name_tr' => ['required', 'string', 'max:255'],
            'target_field' => ['required', 'string', 'max:255'],
            'target_form' => ['required', 'string', 'max:64'],
            'condition' => ['required', 'array'],
            'severity' => ['required', 'in:warning,block'],
            'warning_message_tr' => ['nullable', 'string'],
            'block_message_tr' => ['nullable', 'string'],
            'requires_approval' => ['nullable', 'boolean'],
            'approval_roles' => ['nullable', 'array'],
            'applicable_student_types' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['created_by'] = (string) optional($request->user())->email;
        $data['rule_key'] = $this->buildRuleKey($data);

        if (FieldRule::query()->where('rule_key', $data['rule_key'])->exists()) {
            throw ValidationException::withMessages([
                'rule' => 'Ayni kural zaten mevcut.',
            ]);
        }

        $row = FieldRule::create($data);
        return response()->json($row, Response::HTTP_CREATED);
    }

    public function update(Request $request, FieldRule $fieldRule)
    {
        $data = $request->validate([
            'name_tr' => ['sometimes', 'required', 'string', 'max:255'],
            'target_field' => ['sometimes', 'required', 'string', 'max:255'],
            'target_form' => ['sometimes', 'required', 'string', 'max:64'],
            'condition' => ['sometimes', 'required', 'array'],
            'severity' => ['sometimes', 'required', 'in:warning,block'],
            'warning_message_tr' => ['nullable', 'string'],
            'block_message_tr' => ['nullable', 'string'],
            'requires_approval' => ['nullable', 'boolean'],
            'approval_roles' => ['nullable', 'array'],
            'applicable_student_types' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer', 'min:0'],
        ]);

        $merged = array_merge($fieldRule->toArray(), $data);
        $ruleKey = $this->buildRuleKey($merged);
        $duplicateExists = FieldRule::query()
            ->where('rule_key', $ruleKey)
            ->where('id', '!=', $fieldRule->id)
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'rule' => 'Ayni kural zaten mevcut.',
            ]);
        }

        $data['rule_key'] = $ruleKey;

        $fieldRule->update($data);
        return response()->json($fieldRule->fresh());
    }

    public function evaluate(Request $request, FieldRuleEngine $engine)
    {
        $data = $request->validate([
            'target_form' => ['required', 'string', 'max:64'],
            'form_data' => ['required', 'array'],
            'student_type' => ['nullable', 'string', 'max:64'],
            'student_id' => ['nullable', 'string', 'max:64'],
            'guest_id' => ['nullable', 'string', 'max:64'],
        ]);

        $items = $engine->evaluate(
            $data['target_form'],
            $data['form_data'],
            $data['student_type'] ?? null,
            $data['student_id'] ?? null,
            $data['guest_id'] ?? null,
            (string) optional($request->user())->email
        );

        return response()->json([
            'triggered' => count($items),
            'items' => $items,
        ]);
    }

    private function buildRuleKey(array $data): string
    {
        $condition = $data['condition'] ?? [];
        if (is_array($condition)) {
            ksort($condition);
        }

        return sha1(implode('|', [
            (string) ($data['target_form'] ?? ''),
            (string) ($data['target_field'] ?? ''),
            (string) ($data['severity'] ?? ''),
            json_encode($condition, JSON_UNESCAPED_UNICODE),
        ]));
    }
}
