<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaskTemplate;
use App\Models\TaskTemplateItem;
use App\Services\TaskTemplateService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskTemplateController extends Controller
{
    public function __construct(private readonly TaskTemplateService $service) {}

    public function index(Request $request): JsonResponse
    {
        $companyId  = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $department = trim((string) $request->query('department', ''));

        $templates = TaskTemplate::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->when($department !== '', fn ($q) => $q->where('department', $department))
            ->where('is_active', true)
            ->withCount('items')
            ->orderBy('department')
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'department', 'category', 'is_chain', 'is_active', 'created_at']);

        return response()->json($templates);
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : null;

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:2000'],
            'department'  => ['required', 'string', 'in:operations,finance,advisory,marketing,system'],
            'category'    => ['required', 'string', 'in:' . implode(',', array_keys(TaskTemplate::CATEGORIES))],
            'is_chain'    => ['nullable', 'boolean'],
        ]);

        $template = TaskTemplate::query()->create(array_merge($data, [
            'company_id'         => $companyId,
            'created_by_user_id' => (int) optional($request->user())->id ?: null,
        ]));

        return response()->json($template->fresh(), 201);
    }

    public function show(TaskTemplate $taskTemplate): JsonResponse
    {
        return response()->json($taskTemplate->load('items'));
    }

    public function update(Request $request, TaskTemplate $taskTemplate): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['sometimes', 'required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:2000'],
            'department'  => ['sometimes', 'required', 'string', 'in:operations,finance,advisory,marketing,system'],
            'category'    => ['sometimes', 'required', 'string', 'in:' . implode(',', array_keys(TaskTemplate::CATEGORIES))],
            'is_chain'    => ['nullable', 'boolean'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $taskTemplate->update($data);
        return response()->json($taskTemplate->fresh());
    }

    public function destroy(TaskTemplate $taskTemplate): JsonResponse
    {
        // Soft deactivate — oluşturulmuş task'lar referansı korur
        $taskTemplate->update(['is_active' => false]);
        return ApiResponse::ok(['deactivated' => true]);
    }

    // ── Items ────────────────────────────────────────────────────────────────

    public function itemStore(Request $request, TaskTemplate $taskTemplate): JsonResponse
    {
        $data = $request->validate([
            'title'             => ['required', 'string', 'max:190'],
            'description'       => ['nullable', 'string', 'max:2000'],
            'priority'          => ['required', 'string', 'in:low,normal,high,urgent'],
            'due_offset_days'   => ['nullable', 'integer', 'min:0', 'max:365'],
            'assign_to_role'    => ['nullable', 'string', 'max:64'],
            'assign_to_source'  => ['nullable', 'string', 'in:senior_of_student,creator,specific_role'],
            'sort_order'        => ['nullable', 'integer', 'min:0'],
            'depends_on_order'  => ['nullable', 'integer', 'min:0'],
            'checklist_items'   => ['nullable', 'array'],
            'checklist_items.*' => ['string', 'max:255'],
            'estimated_hours'   => ['nullable', 'numeric', 'min:0', 'max:999'],
        ]);

        if (! array_key_exists('sort_order', $data) || $data['sort_order'] === null) {
            $data['sort_order'] = (int) ($taskTemplate->items()->max('sort_order') ?? -1) + 1;
        }

        $item = TaskTemplateItem::query()->create(array_merge($data, ['template_id' => $taskTemplate->id]));
        return response()->json($item->fresh(), 201);
    }

    public function itemUpdate(Request $request, TaskTemplate $taskTemplate, TaskTemplateItem $item): JsonResponse
    {
        if ((int) $item->template_id !== (int) $taskTemplate->id) {
            return ApiResponse::error('ERR_NOT_FOUND', 'Item bu template\'e ait değil.', 404);
        }

        $data = $request->validate([
            'title'             => ['sometimes', 'required', 'string', 'max:190'],
            'description'       => ['nullable', 'string', 'max:2000'],
            'priority'          => ['sometimes', 'required', 'string', 'in:low,normal,high,urgent'],
            'due_offset_days'   => ['nullable', 'integer', 'min:0', 'max:365'],
            'assign_to_role'    => ['nullable', 'string', 'max:64'],
            'assign_to_source'  => ['nullable', 'string', 'in:senior_of_student,creator,specific_role'],
            'sort_order'        => ['nullable', 'integer', 'min:0'],
            'depends_on_order'  => ['nullable', 'integer', 'min:0'],
            'checklist_items'   => ['nullable', 'array'],
            'checklist_items.*' => ['string', 'max:255'],
            'estimated_hours'   => ['nullable', 'numeric', 'min:0', 'max:999'],
        ]);

        $item->update($data);
        return response()->json($item->fresh());
    }

    public function itemDestroy(TaskTemplate $taskTemplate, TaskTemplateItem $item): JsonResponse
    {
        if ((int) $item->template_id !== (int) $taskTemplate->id) {
            return ApiResponse::error('ERR_NOT_FOUND', 'Item bu template\'e ait değil.', 404);
        }
        $item->delete();
        return ApiResponse::ok();
    }

    // ── Apply ────────────────────────────────────────────────────────────────

    public function apply(Request $request, int $templateId): JsonResponse
    {
        $data = $request->validate([
            'student_id'   => ['nullable', 'string', 'max:64'],
            'senior_email' => ['nullable', 'email', 'max:190'],
        ]);

        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : null;
        $userId    = (int) optional($request->user())->id;

        $context = array_merge($data, [
            'company_id' => $companyId,
            'created_by' => $userId,
        ]);

        $tasks = $this->service->applyTemplate($templateId, $context);

        return response()->json([
            'created' => $tasks->count(),
            'task_ids' => $tasks->pluck('id')->all(),
        ], 201);
    }
}
