<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProcessDefinition;
use App\Support\SystematicInput;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProcessDefinitionController extends Controller
{
    public function index()
    {
        return ProcessDefinition::orderBy('sort_order')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'external_id' => ['required', 'string', 'max:32', 'unique:process_definitions,external_id'],
            'code' => ['required', 'string', 'max:64', 'unique:process_definitions,code'],
            'name_tr' => ['required', 'string', 'max:255'],
            'name_de' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'description_tr' => ['nullable', 'string'],
            'description_de' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_mandatory' => ['nullable', 'boolean'],
            'applicable_student_types' => ['nullable', 'array'],
            'default_checklist' => ['nullable', 'array'],
            'revenue_milestone_id' => ['nullable', 'string', 'max:64'],
            'color' => ['nullable', 'string', 'max:32'],
            'icon' => ['nullable', 'string', 'max:64'],
            'updated_by' => ['nullable', 'string', 'max:255'],
        ]);

        $data['external_id'] = SystematicInput::externalId((string) $data['external_id'], 'external_id');
        $data['code'] = SystematicInput::codeLower((string) $data['code'], 'code');

        $process = ProcessDefinition::create($data);

        return response()->json($process, Response::HTTP_CREATED);
    }

    public function update(Request $request, ProcessDefinition $processDefinition)
    {
        $data = $request->validate([
            'external_id' => ['sometimes', 'required', 'string', 'max:32', 'unique:process_definitions,external_id,' . $processDefinition->id],
            'code' => ['sometimes', 'required', 'string', 'max:64', 'unique:process_definitions,code,' . $processDefinition->id],
            'name_tr' => ['sometimes', 'required', 'string', 'max:255'],
            'name_de' => ['sometimes', 'required', 'string', 'max:255'],
            'name_en' => ['sometimes', 'required', 'string', 'max:255'],
            'description_tr' => ['nullable', 'string'],
            'description_de' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_mandatory' => ['nullable', 'boolean'],
            'applicable_student_types' => ['nullable', 'array'],
            'default_checklist' => ['nullable', 'array'],
            'revenue_milestone_id' => ['nullable', 'string', 'max:64'],
            'color' => ['nullable', 'string', 'max:32'],
            'icon' => ['nullable', 'string', 'max:64'],
            'updated_by' => ['nullable', 'string', 'max:255'],
        ]);

        if (array_key_exists('external_id', $data)) {
            $data['external_id'] = SystematicInput::externalId((string) $data['external_id'], 'external_id');
        }
        if (array_key_exists('code', $data)) {
            $data['code'] = SystematicInput::codeLower((string) $data['code'], 'code');
        }

        $processDefinition->update($data);

        return $processDefinition->refresh();
    }
}
