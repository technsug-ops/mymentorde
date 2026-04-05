<?php

namespace App\Services;

class DocumentNamingService
{
    public function buildStandardFileName(string $studentId, string $categoryCode, array $processTags = [], string $extension = 'pdf'): string
    {
        $cleanStudent = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $studentId) ?? 'STD');
        $cleanCategory = strtoupper(preg_replace('/[^A-Za-z0-9_]/', '', $categoryCode) ?? 'DOC');

        $tagPart = collect($processTags)
            ->map(fn ($tag) => strtolower(preg_replace('/[^A-Za-z0-9_]/', '', (string) $tag) ?? ''))
            ->filter()
            ->take(4)
            ->implode('-');

        $tagPart = $tagPart !== '' ? $tagPart : 'general';
        $ext = strtolower($extension !== '' ? $extension : 'pdf');

        return sprintf('%s_%s_%s_%s.%s', $cleanStudent, $cleanCategory, $tagPart, now()->format('Ymd_His'), $ext);
    }

    public function buildDocumentId(int $dbId): string
    {
        return sprintf('DOC-%s-%06d', now()->format('Y'), $dbId);
    }
}
