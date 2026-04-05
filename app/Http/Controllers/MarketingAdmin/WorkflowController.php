<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\AutomationEnrollment;
use App\Models\AutomationWorkflow;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        $workflows = AutomationWorkflow::withoutTrashed()
            ->withCount(['enrollments', 'enrollments as active_enrollments_count' => fn ($q) => $q->whereIn('status', ['active', 'waiting'])])
            ->latest()
            ->paginate(20);

        $statusLabels = [
            'draft'            => 'Taslak',
            'pending_approval' => 'Onay Bekliyor',
            'active'           => 'Aktif',
            'paused'           => 'Duraklatıldı',
            'archived'         => 'Arşivlendi',
        ];
        $statusColors = [
            'draft'            => 'info',
            'pending_approval' => 'warn',
            'active'           => 'ok',
            'paused'           => 'pending',
            'archived'         => 'danger',
        ];

        return view('marketing-admin.workflows.index', compact('workflows', 'statusLabels', 'statusColors'));
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string|max:1000',
            'trigger_type'   => 'required|string|max:64',
            'is_recurring'   => 'boolean',
        ]);

        AutomationWorkflow::create([
            ...$data,
            'status'         => 'draft',
            'trigger_config' => [],
            'created_by'     => auth()->id(),
        ]);

        return back()->with('success', 'Workflow oluşturuldu.');
    }

    public function builder(AutomationWorkflow $workflow): \Illuminate\View\View
    {
        $nodes = $workflow->nodes()->orderBy('sort_order')->get();

        $nodeTypes = [
            'send_email'       => 'Email Gönder',
            'send_notification' => 'Bildirim Gönder',
            'wait'             => 'Bekle',
            'wait_until'       => 'Koşul Bekle',
            'condition'        => 'Koşul (If/Else)',
            'add_score'        => 'Puan Ekle',
            'create_task'      => 'Task Oluştur',
            'update_field'     => 'Alan Güncelle',
            'move_to_segment'  => 'Segmente Taşı',
            'ab_split'         => 'A/B Bölünme',
            'goal_check'       => 'Hedef Kontrol',
            'exit'             => 'Çıkış',
        ];

        return view('marketing-admin.workflows.builder', compact('workflow', 'nodes', 'nodeTypes'));
    }

    public function activate(AutomationWorkflow $workflow): \Illuminate\Http\RedirectResponse
    {
        if (! in_array($workflow->status, ['draft', 'pending_approval', 'paused'], true)) {
            return back()->with('error', 'Bu durumdaki workflow aktifleştirilemez.');
        }

        $workflow->update([
            'status'      => 'active',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Workflow aktifleştirildi.');
    }

    public function pause(AutomationWorkflow $workflow): \Illuminate\Http\RedirectResponse
    {
        $workflow->update(['status' => 'paused']);
        return back()->with('success', 'Workflow duraklatıldı.');
    }

    public function enrollments(Request $request, AutomationWorkflow $workflow): \Illuminate\View\View
    {
        $query = AutomationEnrollment::where('workflow_id', $workflow->id)
            ->with('guestApplication')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $enrollments = $query->paginate(30)->withQueryString();

        $statusLabels = [
            'active'    => 'Aktif',
            'waiting'   => 'Bekliyor',
            'completed' => 'Tamamlandı',
            'exited'    => 'Çıkıldı',
            'errored'   => 'Hata',
        ];

        return view('marketing-admin.workflows.enrollments', compact('workflow', 'enrollments', 'statusLabels'));
    }

    public function analytics(AutomationWorkflow $workflow): \Illuminate\View\View
    {
        $totalEnrollments = AutomationEnrollment::where('workflow_id', $workflow->id)->count();
        $completed        = AutomationEnrollment::where('workflow_id', $workflow->id)->where('status', 'completed')->count();
        $active           = AutomationEnrollment::where('workflow_id', $workflow->id)->whereIn('status', ['active', 'waiting'])->count();
        $errored          = AutomationEnrollment::where('workflow_id', $workflow->id)->where('status', 'errored')->count();

        $completionRate = $totalEnrollments > 0 ? round($completed / $totalEnrollments * 100, 1) : 0;

        return view('marketing-admin.workflows.analytics', compact(
            'workflow', 'totalEnrollments', 'completed', 'active', 'errored', 'completionRate'
        ));
    }

    public function destroy(AutomationWorkflow $workflow): \Illuminate\Http\RedirectResponse
    {
        if ($workflow->status === 'active') {
            return back()->with('error', 'Aktif workflow silinemez. Önce durdurun.');
        }
        $workflow->delete();
        return redirect('/mktg-admin/workflows')->with('success', 'Workflow silindi.');
    }

    // ─── 3.2 Visual Workflow Builder ────────────────────────────────────────

    /**
     * GET /mktg-admin/workflows/{workflow}/builder-data
     * JSON: nodes + edges + positions
     */
    public function builderData(AutomationWorkflow $workflow): \Illuminate\Http\JsonResponse
    {
        $nodes = $workflow->nodes()->orderBy('sort_order')->get()->map(fn ($n) => [
            'id'         => $n->id,
            'type'       => $n->node_type,
            'config'     => $n->node_config ?? [],
            'sort_order' => $n->sort_order,
            'position'   => ['x' => 120, 'y' => 60 + ($n->sort_order * 100)],
        ]);

        // Build linear edges from sorted node list
        $edges = [];
        for ($i = 0; $i < $nodes->count() - 1; $i++) {
            $edges[] = ['from' => $nodes[$i]['id'], 'to' => $nodes[$i + 1]['id']];
        }

        return response()->json([
            'ok'     => true,
            'nodes'  => $nodes,
            'edges'  => $edges,
            'status' => $workflow->status,
        ]);
    }

    /**
     * PUT /mktg-admin/workflows/{workflow}/builder-data
     * Drag-drop sonrası node sırasını + config kaydet.
     */
    public function builderDataSave(Request $request, AutomationWorkflow $workflow): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'nodes'          => 'required|array',
            'nodes.*.id'     => 'required|integer',
            'nodes.*.config' => 'nullable|array',
            'nodes.*.position' => 'nullable|array',
        ]);

        foreach ($data['nodes'] as $index => $nodeData) {
            $node = $workflow->nodes()->find($nodeData['id']);
            if ($node) {
                $node->update([
                    'node_config' => $nodeData['config'] ?? $node->node_config,
                    'sort_order'  => $index,
                ]);
            }
        }

        return response()->json(['ok' => true, 'message' => 'Builder verisi kaydedildi.']);
    }

    /**
     * POST /mktg-admin/workflows/{workflow}/simulate
     * Test enrollment ile workflow simülasyonu — adımları kuru çalıştır.
     */
    public function simulate(Request $request, AutomationWorkflow $workflow): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'guest_id' => 'nullable|integer|exists:guest_applications,id',
        ]);

        $nodes = $workflow->nodes()->orderBy('sort_order')->get();

        $steps = $nodes->map(fn ($n) => [
            'node_id'   => $n->id,
            'node_type' => $n->node_type,
            'config'    => $n->node_config ?? [],
            'result'    => match ($n->node_type) {
                'wait'             => "Bekle: {$n->node_config['hours']} saat",
                'send_email'       => "Email gönderilir — template_id: " . ($n->node_config['template_id'] ?? '?'),
                'send_notification' => "Bildirim gönderilir",
                'condition'        => "Koşul kontrol: {$n->node_config['field']} {$n->node_config['operator']} {$n->node_config['value']}",
                'add_score'        => "Puan eklenir: +" . ($n->node_config['score'] ?? 0),
                'create_task'      => "Task oluşturulur: " . ($n->node_config['title'] ?? 'Görev'),
                'update_field'     => "Alan güncellenir: {$n->node_config['field']}",
                'ab_split'         => "A/B bölünme: " . ($n->node_config['split']['A'] ?? 50) . "% A / " . ($n->node_config['split']['B'] ?? 50) . "% B",
                'goal_check'       => "Hedef kontrol",
                'exit'             => "Workflow tamamlandı",
                default            => "İşlenir: {$n->node_type}",
            },
        ]);

        return response()->json([
            'ok'       => true,
            'steps'    => $steps,
            'summary'  => "Workflow '{$workflow->name}' — {$nodes->count()} adım — simülasyon tamamlandı.",
        ]);
    }
}
