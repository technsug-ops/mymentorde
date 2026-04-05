<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\DocumentBuilderTemplate;
use Illuminate\Http\Request;

class DocTemplateController extends Controller
{
    private function cid(): ?int
    {
        $v = auth()->user()?->company_id;
        return $v ? (int) $v : null;
    }

    public function index(Request $request)
    {
        $docType = $request->query('doc_type', '');
        $cid     = $this->cid();

        $templates = DocumentBuilderTemplate::where(fn($q) => $q->whereNull('company_id')->orWhere('company_id', $cid))
            ->when($docType !== '', fn($q) => $q->where('doc_type', $docType))
            ->orderBy('doc_type')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('manager.doc-templates.index', compact('templates', 'docType'));
    }

    public function create()
    {
        return view('manager.doc-templates.form', ['tpl' => null]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['company_id']  = $this->cid();
        $data['created_by']  = auth()->user()?->email;
        $data['version']     = 1;
        $data['is_active']   = true;

        $tpl = DocumentBuilderTemplate::create($data);

        if ($data['is_default']) {
            $this->clearOtherDefaults($tpl->id, $data['doc_type'], $data['language']);
        }

        return redirect('/manager/doc-templates')->with('status', 'Şablon oluşturuldu.');
    }

    public function edit(DocumentBuilderTemplate $tpl)
    {
        return view('manager.doc-templates.form', compact('tpl'));
    }

    public function update(Request $request, DocumentBuilderTemplate $tpl)
    {
        $data = $this->validated($request);
        $data['version'] = $tpl->version + 1;

        $tpl->update($data);

        if ($data['is_default']) {
            $this->clearOtherDefaults($tpl->id, $tpl->doc_type, $tpl->language);
        }

        return redirect('/manager/doc-templates')->with('status', 'Şablon güncellendi.');
    }

    public function destroy(DocumentBuilderTemplate $tpl)
    {
        $tpl->delete();
        return back()->with('status', 'Şablon silindi.');
    }

    public function setDefault(DocumentBuilderTemplate $tpl)
    {
        $this->clearOtherDefaults($tpl->id, $tpl->doc_type, $tpl->language);
        $tpl->update(['is_default' => true]);

        return back()->with('status', '"'.$tpl->name.'" varsayılan şablon olarak ayarlandı.');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name'              => 'required|string|max:150',
            'doc_type'          => 'required|in:cv,motivation,reference,cover_letter,sperrkonto,housing',
            'language'          => 'required|in:de,tr,en',
            'section_order'     => 'required|string',   // JSON string from textarea
            'section_templates' => 'required|string',   // JSON string from textarea
            'variables'         => 'nullable|string',   // JSON string from textarea
            'is_active'         => 'boolean',
            'is_default'        => 'boolean',
        ]);

        // JSON alanlarını decode et
        $data['section_order']     = json_decode($data['section_order'], true) ?? [];
        $data['section_templates'] = json_decode($data['section_templates'], true) ?? [];
        $data['variables']         = isset($data['variables']) && $data['variables'] !== ''
            ? (json_decode($data['variables'], true) ?? null)
            : null;
        $data['is_active']  = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');

        return $data;
    }

    /** Aynı tip/dil için diğer varsayılanları kaldır */
    private function clearOtherDefaults(int $excludeId, string $docType, string $lang): void
    {
        $cid = $this->cid();
        DocumentBuilderTemplate::where('doc_type', $docType)
            ->where('language', $lang)
            ->where(fn($q) => $q->whereNull('company_id')->orWhere('company_id', $cid))
            ->where('id', '!=', $excludeId)
            ->update(['is_default' => false]);
    }
}
