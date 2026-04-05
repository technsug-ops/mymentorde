<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MessageTemplateController extends Controller
{
    public function index()
    {
        return MessageTemplate::query()->latest()->limit(200)->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'channel' => ['required', 'in:email,whatsapp,inApp'],
            'category' => ['required', 'string', 'max:64'],
            'subject_tr' => ['nullable', 'string', 'max:255'],
            'body_tr' => ['required', 'string'],
            'variables' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['created_by'] = (string) optional($request->user())->email;
        $row = MessageTemplate::create($data);

        return response()->json($row, Response::HTTP_CREATED);
    }

    public function update(Request $request, MessageTemplate $messageTemplate)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'channel' => ['sometimes', 'required', 'in:email,whatsapp,inApp'],
            'category' => ['sometimes', 'required', 'string', 'max:64'],
            'subject_tr' => ['nullable', 'string', 'max:255'],
            'body_tr' => ['sometimes', 'required', 'string'],
            'variables' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $messageTemplate->update($data);
        return response()->json($messageTemplate->fresh());
    }
}
