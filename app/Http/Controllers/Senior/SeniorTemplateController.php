<?php

namespace App\Http\Controllers\Senior;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SeniorTemplateController extends Controller
{
    // ── 2.3 Canned Response Templates ────────────────────────────────────────

    public function responseTemplates(Request $request)
    {
        $companyId = (int) optional($request->user())->company_id;
        $category  = trim((string) $request->query('category', ''));

        $query = \App\Models\SeniorResponseTemplate::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('company_id')->orWhere('company_id', $companyId))
            ->where(fn ($q) => $q->whereNull('owner_user_id')->orWhere('owner_user_id', (int) $request->user()?->id));

        if ($category) {
            $query->where('category', $category);
        }

        $templates = $query->orderBy('usage_count', 'desc')->orderBy('title')->get(['id', 'category', 'title', 'body', 'usage_count']);

        if ($request->expectsJson()) {
            return response()->json(['templates' => $templates->values()]);
        }

        $grouped = $templates->groupBy('category');
        $categories = ['document' => 'Belge', 'visa' => 'Vize', 'language' => 'Dil', 'housing' => 'Konut', 'payment' => 'Ödeme', 'general' => 'Genel'];
        return view('senior.response-templates', compact('grouped', 'categories'));
    }

    public function responseTemplateStore(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'category' => 'required|in:document,visa,language,housing,payment,general',
            'title'    => 'required|string|max:180',
            'body'     => 'required|string|max:2000',
        ]);

        $template = \App\Models\SeniorResponseTemplate::create([
            'company_id'    => (int) optional($request->user())->company_id,
            'owner_user_id' => (int) $request->user()?->id,
            'category'      => $data['category'],
            'title'         => $data['title'],
            'body'          => $data['body'],
            'is_active'     => true,
        ]);

        return response()->json(['ok' => true, 'id' => $template->id]);
    }

    public function responseTemplateUpdate(Request $request, \App\Models\SeniorResponseTemplate $template): \Illuminate\Http\JsonResponse
    {
        abort_if($template->owner_user_id && $template->owner_user_id !== (int) $request->user()?->id, 403);

        $data = $request->validate([
            'category'  => 'sometimes|in:document,visa,language,housing,payment,general',
            'title'     => 'sometimes|string|max:180',
            'body'      => 'sometimes|string|max:2000',
            'is_active' => 'sometimes|boolean',
        ]);

        $template->update($data);

        return response()->json(['ok' => true]);
    }

    public function responseTemplateDelete(Request $request, \App\Models\SeniorResponseTemplate $template): \Illuminate\Http\JsonResponse
    {
        abort_if($template->owner_user_id && $template->owner_user_id !== (int) $request->user()?->id, 403);
        $template->delete();
        return response()->json(['ok' => true]);
    }

    public function responseTemplateUse(\App\Models\SeniorResponseTemplate $template): \Illuminate\Http\JsonResponse
    {
        $template->increment('usage_count');
        return response()->json(['ok' => true, 'usage_count' => $template->usage_count]);
    }
}
