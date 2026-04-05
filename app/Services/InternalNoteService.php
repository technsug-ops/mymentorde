<?php

namespace App\Services;

use App\Models\InternalNote;
use Illuminate\Support\Collection;

class InternalNoteService
{
    public function listByStudent(?string $studentId = null, int $limit = 100): Collection
    {
        $id = trim((string) $studentId);

        return InternalNote::query()
            ->when($id !== '', fn ($q) => $q->where('student_id', $id))
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->limit(max(1, $limit))
            ->get();
    }

    public function create(array $payload, ?string $actorEmail = null, ?string $actorRole = null): InternalNote
    {
        $data = $payload;
        $data['created_by'] = $actorEmail;
        $data['created_by_role'] = $actorRole;

        return InternalNote::query()->create($data);
    }

    public function createSystemNote(string $studentId, string $content, ?string $actorEmail = null, string $actorRole = 'system'): InternalNote
    {
        return $this->create([
            'student_id' => $studentId,
            'content' => $content,
            'category' => 'system',
            'priority' => 'normal',
            'is_pinned' => false,
            'attachments' => [],
        ], $actorEmail, $actorRole);
    }

    public function pin(InternalNote $note): InternalNote
    {
        $note->update(['is_pinned' => true]);
        return $note->fresh();
    }

    public function unpin(InternalNote $note): InternalNote
    {
        $note->update(['is_pinned' => false]);
        return $note->fresh();
    }

    public function delete(InternalNote $note): void
    {
        $note->delete();
    }
}

