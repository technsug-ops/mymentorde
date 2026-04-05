<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InternalNote;
use App\Services\InternalNoteService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InternalNoteController extends Controller
{
    public function __construct(private readonly InternalNoteService $service)
    {
    }

    public function index(Request $request)
    {
        $studentId = (string) $request->query('student_id', '');

        return $this->service->listByStudent($studentId, 100);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id' => ['required', 'string', 'max:64'],
            'content' => ['required', 'string'],
            'category' => ['nullable', 'in:general,risk,behavior,academic,financial,family,other'],
            'priority' => ['nullable', 'in:low,normal,high,critical'],
            'is_pinned' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array'],
        ]);

        $row = $this->service->create(
            $data,
            (string) optional($request->user())->email,
            (string) optional($request->user())->role
        );

        return response()->json($row, Response::HTTP_CREATED);
    }

    public function pin(InternalNote $internalNote)
    {
        return response()->json($this->service->pin($internalNote));
    }

    public function unpin(InternalNote $internalNote)
    {
        return response()->json($this->service->unpin($internalNote));
    }

    public function destroy(InternalNote $internalNote)
    {
        $this->service->delete($internalNote);
        return response()->json(['ok' => true]);
    }
}
