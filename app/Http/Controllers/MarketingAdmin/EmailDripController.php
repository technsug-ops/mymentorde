<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\Marketing\EmailDripEnrollment;
use App\Models\Marketing\EmailDripSequence;
use App\Models\Marketing\EmailDripStep;
use Illuminate\Http\Request;

class EmailDripController extends Controller
{
    /**
     * GET /mktg-admin/email/drip-sequences
     */
    public function index(): \Illuminate\View\View
    {
        $sequences = EmailDripSequence::withCount(['steps', 'enrollments'])
            ->latest('created_at')
            ->paginate(20);

        return view('marketing-admin.email.drip.index', compact('sequences'));
    }

    /**
     * POST /mktg-admin/email/drip-sequences
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:180',
            'description'   => 'nullable|string|max:1000',
            'trigger_event' => 'required|in:guest_registered,contract_signed,package_selected',
            'segment_id'    => 'nullable|integer',
            'is_active'     => 'boolean',
        ]);

        EmailDripSequence::create([
            ...$data,
            'created_by' => auth()->user()->email ?? '',
            'created_at' => now(),
        ]);

        return back()->with('success', 'Drip serisı oluşturuldu.');
    }

    /**
     * GET /mktg-admin/email/drip-sequences/{id}
     */
    public function show(int $id): \Illuminate\View\View
    {
        $sequence = EmailDripSequence::with(['steps'])->findOrFail($id);
        $enrollmentCounts = [
            'active'      => EmailDripEnrollment::where('drip_sequence_id', $id)->where('status', 'active')->count(),
            'completed'   => EmailDripEnrollment::where('drip_sequence_id', $id)->where('status', 'completed')->count(),
            'unsubscribed'=> EmailDripEnrollment::where('drip_sequence_id', $id)->where('status', 'unsubscribed')->count(),
        ];

        return view('marketing-admin.email.drip.show', compact('sequence', 'enrollmentCounts'));
    }

    /**
     * PUT /mktg-admin/email/drip-sequences/{id}
     */
    public function update(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        $sequence = EmailDripSequence::findOrFail($id);
        $data = $request->validate([
            'name'          => 'required|string|max:180',
            'description'   => 'nullable|string|max:1000',
            'trigger_event' => 'required|in:guest_registered,contract_signed,package_selected',
            'segment_id'    => 'nullable|integer',
            'is_active'     => 'boolean',
        ]);
        $sequence->update($data);
        return back()->with('success', 'Drip serisi güncellendi.');
    }

    /**
     * DELETE /mktg-admin/email/drip-sequences/{id}
     */
    public function destroy(int $id): \Illuminate\Http\RedirectResponse
    {
        EmailDripSequence::findOrFail($id)->delete();
        return redirect('/mktg-admin/email/drip-sequences')->with('success', 'Drip serisi silindi.');
    }

    /**
     * POST /mktg-admin/email/drip-sequences/{id}/steps
     */
    public function stepStore(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        $sequence = EmailDripSequence::findOrFail($id);
        $data = $request->validate([
            'step_order'      => 'required|integer|min:1',
            'delay_hours'     => 'required|integer|min:0',
            'template_id'     => 'required|integer|exists:email_templates,id',
            'subject_override'=> 'nullable|string|max:191',
            'is_active'       => 'boolean',
        ]);

        EmailDripStep::create(['drip_sequence_id' => $sequence->id, ...$data]);
        return back()->with('success', 'Adım eklendi.');
    }

    /**
     * PUT /mktg-admin/email/drip-sequences/{id}/steps/{stepId}
     */
    public function stepUpdate(Request $request, int $id, int $stepId): \Illuminate\Http\JsonResponse
    {
        $step = EmailDripStep::where('drip_sequence_id', $id)->findOrFail($stepId);
        $data = $request->validate([
            'step_order'      => 'sometimes|integer|min:1',
            'delay_hours'     => 'sometimes|integer|min:0',
            'template_id'     => 'sometimes|integer|exists:email_templates,id',
            'subject_override'=> 'nullable|string|max:191',
            'is_active'       => 'sometimes|boolean',
        ]);
        $step->update($data);
        return response()->json(['ok' => true]);
    }

    /**
     * DELETE /mktg-admin/email/drip-sequences/{id}/steps/{stepId}
     */
    public function stepDelete(int $id, int $stepId): \Illuminate\Http\JsonResponse
    {
        EmailDripStep::where('drip_sequence_id', $id)->findOrFail($stepId)->delete();
        return response()->json(['ok' => true]);
    }

    /**
     * GET /mktg-admin/email/drip-sequences/{id}/enrollments
     */
    public function enrollments(int $id): \Illuminate\View\View
    {
        $sequence    = EmailDripSequence::findOrFail($id);
        $enrollments = EmailDripEnrollment::where('drip_sequence_id', $id)
            ->with('guest')
            ->latest('enrolled_at')
            ->paginate(30);

        return view('marketing-admin.email.drip.enrollments', compact('sequence', 'enrollments'));
    }
}
