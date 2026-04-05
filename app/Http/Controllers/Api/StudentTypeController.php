<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentType;
use App\Support\SystematicInput;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StudentTypeController extends Controller
{
    public function index()
    {
        return StudentType::orderBy('sort_order')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name_tr' => ['required', 'string', 'max:255'],
            'name_de' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:64', 'unique:student_types,code'],
            'id_prefix' => ['required', 'string', 'size:3', 'unique:student_types,id_prefix'],
            'description_tr' => ['nullable', 'string'],
            'description_de' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'applicable_processes' => ['nullable', 'array'],
            'required_document_categories' => ['nullable', 'array'],
            'default_checklist_template_id' => ['nullable', 'string', 'max:255'],
            'field_rules' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'created_by' => ['nullable', 'string', 'max:255'],
        ]);

        $data['code'] = SystematicInput::codeLower((string) $data['code'], 'code');
        $data['id_prefix'] = SystematicInput::idPrefix((string) $data['id_prefix'], 'id_prefix');

        $studentType = StudentType::create($data);

        return response()->json($studentType, Response::HTTP_CREATED);
    }

    public function update(Request $request, StudentType $studentType)
    {
        $data = $request->validate([
            'name_tr' => ['sometimes', 'required', 'string', 'max:255'],
            'name_de' => ['sometimes', 'required', 'string', 'max:255'],
            'name_en' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['sometimes', 'required', 'string', 'max:64', 'unique:student_types,code,' . $studentType->id],
            'id_prefix' => ['sometimes', 'required', 'string', 'size:3', 'unique:student_types,id_prefix,' . $studentType->id],
            'description_tr' => ['nullable', 'string'],
            'description_de' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'applicable_processes' => ['nullable', 'array'],
            'required_document_categories' => ['nullable', 'array'],
            'default_checklist_template_id' => ['nullable', 'string', 'max:255'],
            'field_rules' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'created_by' => ['nullable', 'string', 'max:255'],
        ]);

        if (array_key_exists('code', $data)) {
            $data['code'] = SystematicInput::codeLower((string) $data['code'], 'code');
        }
        if (array_key_exists('id_prefix', $data)) {
            $data['id_prefix'] = SystematicInput::idPrefix((string) $data['id_prefix'], 'id_prefix');
        }

        $studentType->update($data);

        return $studentType->refresh();
    }
}
