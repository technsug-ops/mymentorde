<?php

namespace App\Http\Controllers\Senior;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SeniorTemplateController extends Controller
{
    // ── 2.3 Canned Response Templates ────────────────────────────────────────

    public function responseTemplates(Request $request)
    {
        $category = trim((string) $request->query('category', ''));

        // Firma filtresi artık global kapsamdan geliyor (BelongsToCompany):
        // kendi firmasının şablonları + fabrika şablonları (company_id = 0).
        // Buradaki elle yazılmış `whereNull('company_id')` koşulu kaldırıldı;
        // NULL "sahibi bilinmiyor" demek ve şablonu paylaşımlı saymak için
        // yanlış işaretti — fabrika işareti 0.
        $query = \App\Models\SeniorResponseTemplate::where('is_active', true)
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
        // Fabrika şablonu TÜM firmalarda görünüyor; düzenlenmesine izin
        // verilseydi bir firmanın danışmanı diğer firmaların da gördüğü metni
        // değiştirirdi. Eski `owner_user_id` kontrolü buna açıktı: fabrika
        // satırında owner_user_id boş olduğu için koşul hiç tetiklenmiyordu.
        abort_if($template->isFactoryRow(), 403, 'Fabrika şablonu düzenlenemez.');
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
        abort_if($template->isFactoryRow(), 403, 'Fabrika şablonu silinemez.');
        abort_if($template->owner_user_id && $template->owner_user_id !== (int) $request->user()?->id, 403);
        $template->delete();
        return response()->json(['ok' => true]);
    }

    public function responseTemplateUse(\App\Models\SeniorResponseTemplate $template): \Illuminate\Http\JsonResponse
    {
        // Fabrika satırında sayaç paylaşımlı; firmalar arası bir yazma
        // olmasın diye artırılmıyor. Kullanım engellenmiyor, yalnızca
        // istatistik tutulmuyor.
        if ($template->isFactoryRow()) {
            return response()->json(['ok' => true, 'usage_count' => (int) $template->usage_count]);
        }

        $template->increment('usage_count');
        return response()->json(['ok' => true, 'usage_count' => $template->usage_count]);
    }
}
