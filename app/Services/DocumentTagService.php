<?php

namespace App\Services;

class DocumentTagService
{
    public function normalize(array $tags): array
    {
        return collect($tags)
            ->map(fn ($tag) => strtolower(trim((string) $tag)))
            ->map(fn ($tag) => preg_replace('/[^a-z0-9_]/', '', $tag) ?? '')
            ->filter()
            ->unique()
            ->values()
            ->take(8)
            ->all();
    }
}

