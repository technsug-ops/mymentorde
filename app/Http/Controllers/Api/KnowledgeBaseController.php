<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBaseArticle;
use App\Support\SystematicInput;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class KnowledgeBaseController extends Controller
{
    public function index(Request $request)
    {
        $category = trim((string) $request->query('category', ''));
        $published = $request->query('published');

        return KnowledgeBaseArticle::query()
            ->when($category !== '', fn ($q) => $q->where('category', $category))
            ->when($published !== null, fn ($q) => $q->where('is_published', filter_var($published, FILTER_VALIDATE_BOOLEAN)))
            ->latest()
            ->limit(200)
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title_tr' => ['required', 'string', 'max:255'],
            'body_tr' => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:64'],
            'tags' => ['nullable', 'array'],
            'target_roles' => ['nullable', 'array'],
            'is_published' => ['nullable', 'boolean'],
            'title_de' => ['nullable', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'body_de' => ['nullable', 'string'],
            'body_en' => ['nullable', 'string'],
        ]);

        $data['author_id'] = (string) optional($request->user())->email;
        $data['category'] = SystematicInput::category((string) ($data['category'] ?? 'faq'), 'category');
        $row = KnowledgeBaseArticle::query()->create($data);

        return response()->json($row, Response::HTTP_CREATED);
    }

    public function update(Request $request, KnowledgeBaseArticle $knowledgeBaseArticle)
    {
        $data = $request->validate([
            'title_tr' => ['sometimes', 'required', 'string', 'max:255'],
            'body_tr' => ['sometimes', 'required', 'string'],
            'category' => ['sometimes', 'required', 'string', 'max:64'],
            'tags' => ['nullable', 'array'],
            'target_roles' => ['nullable', 'array'],
            'is_published' => ['nullable', 'boolean'],
            'title_de' => ['nullable', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'body_de' => ['nullable', 'string'],
            'body_en' => ['nullable', 'string'],
        ]);

        if (array_key_exists('category', $data)) {
            $data['category'] = SystematicInput::category((string) $data['category'], 'category');
        }

        $knowledgeBaseArticle->update($data);
        return response()->json($knowledgeBaseArticle->fresh());
    }
}
