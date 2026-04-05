<?php

namespace App\Services;

use App\Models\MarketingTask;
use App\Models\TaskActivityLog;
use App\Models\TaskChecklist;
use App\Models\TaskTemplate;
use App\Models\User;
use Illuminate\Support\Collection;

class TaskTemplateService
{
    /**
     * Template'den görev(ler) oluşturur.
     *
     * $context örnek:
     *   ['student_id' => 'STU-001', 'senior_email' => 'x@y.com', 'company_id' => 1, 'created_by' => 42]
     *
     * @return Collection<int, MarketingTask>
     */
    public function applyTemplate(int $templateId, array $context = []): Collection
    {
        $template = TaskTemplate::with('items')->where('is_active', true)->findOrFail($templateId);
        $tasks = collect();
        $companyId = (int) ($context['company_id'] ?? 0) ?: null;
        $createdBy = (int) ($context['created_by'] ?? 0) ?: null;

        foreach ($template->items->sortBy('sort_order') as $item) {
            $assignedUserId = $this->resolveAssignee($item, $context);

            $task = MarketingTask::query()->create([
                'company_id'        => $companyId,
                'title'             => $this->interpolate($item->title, $context),
                'description'       => $item->description
                    ? $this->interpolate($item->description, $context)
                    : null,
                'department'        => $template->department,
                'priority'          => $item->priority,
                'due_date'          => $item->due_offset_days > 0
                    ? now()->addDays((int) $item->due_offset_days)->toDateString()
                    : null,
                'assigned_user_id'  => $assignedUserId,
                'created_by_user_id'=> $createdBy,
                'template_id'       => $template->id,
                'estimated_hours'   => $item->estimated_hours,
                'status'            => 'todo',
                'is_auto_generated' => true,
                'source_type'       => 'template_applied',
                'source_id'         => (string) $template->id,
            ]);

            // Zincirleme bağımlılık (chain mode)
            if ($template->is_chain && $item->depends_on_order !== null) {
                $dependsOnTask = $tasks->get((int) $item->depends_on_order);
                if ($dependsOnTask) {
                    $task->update([
                        'depends_on_task_id' => $dependsOnTask->id,
                        'status'             => 'blocked',
                    ]);
                }
            }

            // Checklist maddelerini oluştur
            if (! empty($item->checklist_items)) {
                $total = 0;
                foreach ($item->checklist_items as $i => $clTitle) {
                    if (! is_string($clTitle) || trim($clTitle) === '') {
                        continue;
                    }
                    TaskChecklist::query()->create([
                        'task_id'    => $task->id,
                        'title'      => trim($clTitle),
                        'sort_order' => $i,
                    ]);
                    $total++;
                }
                if ($total > 0) {
                    $task->update(['checklist_total' => $total]);
                }
            }

            TaskActivityLog::record((int) $task->id, $createdBy ?? 0, 'template_applied', null, "template:{$template->id}");
            $tasks->put((int) $item->sort_order, $task);
        }

        return $tasks->values();
    }

    /** Template değişkenlerini ($student_id, $senior_email, vb.) yerine koy */
    private function interpolate(string $text, array $context): string
    {
        foreach ($context as $key => $value) {
            $text = str_replace('{' . $key . '}', (string) $value, $text);
        }
        return $text;
    }

    /** Template item'a göre atanan kullanıcı ID'sini çöz */
    private function resolveAssignee(mixed $item, array $context): ?int
    {
        $source = (string) ($item->assign_to_source ?? '');

        if ($source === 'creator') {
            return (int) ($context['created_by'] ?? 0) ?: null;
        }

        if ($source === 'senior_of_student') {
            $seniorEmail = (string) ($context['senior_email'] ?? '');
            if ($seniorEmail !== '') {
                $senior = User::query()->where('email', $seniorEmail)->first(['id']);
                return $senior ? (int) $senior->id : null;
            }
            return null;
        }

        if ($source === 'specific_role' && isset($item->assign_to_role)) {
            $companyId = (int) ($context['company_id'] ?? 0);
            $user = User::query()
                ->where('role', (string) $item->assign_to_role)
                ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
                ->where('is_active', true)
                ->first(['id']);
            return $user ? (int) $user->id : null;
        }

        return null;
    }
}
