<?php

namespace App\Services;

use App\Models\FieldRule;
use App\Models\FieldRuleApproval;

class FieldRuleEngine
{
    public function evaluate(string $targetForm, array $formData, ?string $studentType = null, ?string $studentId = null, ?string $guestId = null, ?string $actor = null): array
    {
        $rules = FieldRule::query()
            ->where('target_form', $targetForm)
            ->where('is_active', true)
            ->orderBy('priority')
            ->get();

        $results = [];

        foreach ($rules as $rule) {
            if ($studentType && is_array($rule->applicable_student_types) && count($rule->applicable_student_types) > 0) {
                if (!in_array($studentType, $rule->applicable_student_types, true)) {
                    continue;
                }
            }

            if ($this->hasException($rule->exceptions, $formData)) {
                continue;
            }

            $value = $this->valueByPath($formData, $rule->target_field);
            if (!$this->matches($rule->condition ?? [], $value)) {
                continue;
            }

            $message = $rule->severity === 'block'
                ? ($rule->block_message_tr ?: 'Bu alan yetkili onayi gerektirir.')
                : ($rule->warning_message_tr ?: 'Kural uyarisi.');

            $entry = [
                'rule_id' => $rule->id,
                'name_tr' => $rule->name_tr,
                'severity' => $rule->severity,
                'message' => $message,
                'target_field' => $rule->target_field,
                'triggered_value' => $value,
                'requires_approval' => (bool) $rule->requires_approval,
            ];

            if ($rule->severity === 'block' && $rule->requires_approval) {
                $approval = FieldRuleApproval::create([
                    'rule_id' => $rule->id,
                    'student_id' => $studentId,
                    'guest_id' => $guestId,
                    'triggered_field' => $rule->target_field,
                    'triggered_value' => is_array($value) ? $value : ['value' => $value],
                    'severity' => $rule->severity,
                    'status' => 'pending',
                ]);
                $entry['approval_id'] = $approval->id;
            }

            $results[] = $entry;
        }

        return $results;
    }

    private function hasException(?array $exceptions, array $formData): bool
    {
        if (!is_array($exceptions) || count($exceptions) === 0) {
            return false;
        }

        foreach ($exceptions as $exception) {
            $value = $this->valueByPath($formData, (string) ($exception['field'] ?? ''));
            if ($this->matches($exception, $value)) {
                return true;
            }
        }

        return false;
    }

    private function matches(array $condition, mixed $value): bool
    {
        $op = (string) ($condition['operator'] ?? 'equals');
        $target = $condition['value'] ?? null;

        return match ($op) {
            'equals' => $value == $target,
            'not_equals' => $value != $target,
            'in' => is_array($target) && in_array($value, $target, true),
            'not_in' => is_array($target) && !in_array($value, $target, true),
            'greater_than' => is_numeric($value) && is_numeric($target) && (float) $value > (float) $target,
            'less_than' => is_numeric($value) && is_numeric($target) && (float) $value < (float) $target,
            'year_diff_greater' => is_numeric($value) && is_numeric($target) && ((int) date('Y') - (int) $value) > (int) $target,
            default => false,
        };
    }

    private function valueByPath(array $data, string $path): mixed
    {
        if ($path === '') {
            return null;
        }

        $segments = explode('.', $path);
        $value = $data;

        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return null;
            }
        }

        return $value;
    }
}
