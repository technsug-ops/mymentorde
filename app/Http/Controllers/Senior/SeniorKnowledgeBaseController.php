<?php

namespace App\Http\Controllers\Senior;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBaseArticle;
use App\Models\StudentAssignment;
use App\Support\FileUploadRules;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class SeniorKnowledgeBaseController extends Controller
{
    private function seniorEmail(Request $request): string
    {
        return strtolower((string) ($request->user()?->email ?? ''));
    }

    private function assignedStudentIds(Request $request): Collection
    {
        $email     = $this->seniorEmail($request);
        $companyId = (int) ($request->user()?->company_id ?? 0);
        return StudentAssignment::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereRaw('lower(senior_email) = ?', [$email])
            ->pluck('student_id')
            ->filter()
            ->unique()
            ->values();
    }

    private function sidebarStats(Request $request): array
    {
        $email     = $this->seniorEmail($request);
        $companyId = (int) ($request->user()?->company_id ?? 0);
        $base = StudentAssignment::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereRaw('lower(senior_email) = ?', [$email]);
        $studentIds = (clone $base)->pluck('student_id')->filter()->unique();
        $today = now()->toDateString();

        return [
            'active_students' => (int) (clone $base)->where('is_archived', false)->count(),
            'pending_guests' => (int) \App\Models\GuestApplication::query()
                ->whereIn('converted_student_id', $studentIds->all())
                ->where('converted_to_student', false)
                ->count(),
            'today_tasks' => (int) \App\Models\MarketingTask::query()
                ->where('assigned_user_id', (int) optional($request->user())->id)
                ->whereNotIn('status', ['done', 'cancelled'])
                ->whereDate('due_date', $today)
                ->count(),
            'today_appointments' => (int) \App\Models\StudentAppointment::query()
                ->whereRaw('lower(senior_email) = ?', [$email])
                ->whereDate('scheduled_at', $today)
                ->count(),
        ];
    }

    public function knowledgeBase(Request $request)
    {
        $q         = trim((string) $request->query('q', ''));
        $published = trim((string) $request->query('published', 'all'));
        $category  = trim((string) $request->query('category', 'all'));
        $role      = trim((string) $request->query('role', 'all'));
        $sort      = trim((string) $request->query('sort', 'latest'));
        $tag       = trim((string) $request->query('tag', ''));

        $articles = KnowledgeBaseArticle::query()
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($x) use ($q) {
                    $x->where('title_tr', 'like', "%{$q}%")
                        ->orWhere('body_tr', 'like', "%{$q}%")
                        ->orWhere('category', 'like', "%{$q}%");
                });
            })
            ->when($published === 'yes', fn ($w) => $w->where('is_published', true))
            ->when($published === 'no',  fn ($w) => $w->where('is_published', false))
            ->when($category !== '' && $category !== 'all', fn ($w) => $w->where('category', $category))
            ->when($tag !== '', fn ($w) => $w->whereJsonContains('tags', $tag))
            ->when($role === 'student', fn ($w) => $w->whereJsonContains('target_roles', 'student'))
            ->when($role === 'guest',   fn ($w) => $w->whereJsonContains('target_roles', 'guest'))
            ->when($sort === 'popular', fn ($w) => $w->orderByDesc('view_count'))
            ->when($sort === 'helpful', fn ($w) => $w->orderByDesc('helpful_count'))
            ->when(!in_array($sort, ['popular', 'helpful']), fn ($w) => $w->latest())
            ->paginate(20, ['id', 'title_tr', 'category', 'tags', 'target_roles', 'is_published', 'view_count', 'helpful_count', 'media_type', 'source_url', 'file_path', 'original_filename'])
            ->withQueryString();

        $categories = KnowledgeBaseArticle::query()
            ->selectRaw('category')
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $allTags = KnowledgeBaseArticle::whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $kTotal     = KnowledgeBaseArticle::count();
        $kPublished = KnowledgeBaseArticle::where('is_published', true)->count();
        $kStudent   = KnowledgeBaseArticle::whereJsonContains('target_roles', 'student')->count();
        $kGuest     = KnowledgeBaseArticle::whereJsonContains('target_roles', 'guest')->count();

        return view('senior.knowledge-base', [
            'articles'     => $articles,
            'categories'   => $categories,
            'allTags'      => $allTags,
            'filters'      => compact('q', 'published', 'category', 'role', 'sort', 'tag'),
            'kTotal'       => $kTotal,
            'kPublished'   => $kPublished,
            'kStudent'     => $kStudent,
            'kGuest'       => $kGuest,
            'sidebarStats' => $this->sidebarStats($request),
        ]);
    }

    public function knowledgeBaseHelpful(KnowledgeBaseArticle $article): \Illuminate\Http\JsonResponse
    {
        $article->increment('helpful_count');
        return response()->json(['helpful_count' => $article->helpful_count]);
    }

    public function knowledgeBaseStore(Request $request)
    {
        $data = $request->validate([
            'title_tr'      => 'required|string|max:255',
            'category'      => 'required|string|max:64',
            'body_tr'       => 'nullable|string',
            'source_url'    => 'nullable|url|max:500',
            'media_type'    => 'nullable|in:video,pdf,text,article,link',
            'is_published'  => 'nullable|boolean',
            'target_roles'  => 'nullable|array',
            'target_roles.*'=> 'in:student,guest,senior',
            'file'          => FileUploadRules::documentOptional(),
            'tags'          => 'nullable|string',
        ]);

        $filePath = null;
        $origName = null;
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $file     = $request->file('file');
            $origName = $file->getClientOriginalName();
            $filePath = $file->store('materials', 'local');
            $data['media_type'] = 'pdf';
        }

        $tags = array_values(array_filter(array_map('trim', explode(',', $data['tags'] ?? ''))));

        KnowledgeBaseArticle::create([
            'title_tr'          => $data['title_tr'],
            'category'          => $data['category'],
            'body_tr'           => $data['body_tr'] ?? null,
            'source_url'        => $data['source_url'] ?? null,
            'media_type'        => $data['media_type'] ?? null,
            'file_path'         => $filePath,
            'original_filename' => $origName,
            'is_published'      => (bool) ($data['is_published'] ?? false),
            'target_roles'      => $data['target_roles'] ?? [],
            'tags'              => $tags,
        ]);

        return redirect('/senior/knowledge-base')->with('kb_success', 'Materyal eklendi.');
    }

    public function knowledgeBaseUpdate(Request $request, KnowledgeBaseArticle $article)
    {
        $data = $request->validate([
            'title_tr'      => 'required|string|max:255',
            'category'      => 'required|string|max:64',
            'body_tr'       => 'nullable|string',
            'source_url'    => 'nullable|url|max:500',
            'media_type'    => 'nullable|in:video,pdf,text,article,link',
            'is_published'  => 'nullable|boolean',
            'target_roles'  => 'nullable|array',
            'target_roles.*'=> 'in:student,guest,senior',
            'file'          => FileUploadRules::documentOptional(),
            'tags'          => 'nullable|string',
        ]);

        $tags = array_values(array_filter(array_map('trim', explode(',', $data['tags'] ?? ''))));

        $updates = [
            'title_tr'     => $data['title_tr'],
            'category'     => $data['category'],
            'body_tr'      => $data['body_tr'] ?? $article->body_tr,
            'source_url'   => $data['source_url'] ?? null,
            'media_type'   => $data['media_type'] ?? $article->media_type,
            'is_published' => (bool) ($data['is_published'] ?? $article->is_published),
            'target_roles' => $data['target_roles'] ?? [],
            'tags'         => $tags,
        ];

        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            if ($article->file_path) {
                \Storage::disk('local')->delete($article->file_path);
            }
            $file = $request->file('file');
            $updates['original_filename'] = $file->getClientOriginalName();
            $updates['file_path'] = $file->store('materials', 'local');
            $updates['media_type'] = 'pdf';
        }

        $article->update($updates);
        return redirect('/senior/knowledge-base')->with('kb_success', 'Materyal güncellendi.');
    }

    public function knowledgeBaseDelete(KnowledgeBaseArticle $article)
    {
        if ($article->file_path) {
            \Storage::disk('local')->delete($article->file_path);
        }
        $article->delete();
        return redirect('/senior/knowledge-base')->with('kb_success', 'Materyal silindi.');
    }

    public function knowledgeBaseToggle(KnowledgeBaseArticle $article)
    {
        $article->update(['is_published' => !$article->is_published]);
        return back()->with('kb_success', $article->is_published ? 'Yayına alındı.' : 'Yayından kaldırıldı.');
    }

    public function knowledgeBaseToggleRole(Request $request, KnowledgeBaseArticle $article): \Illuminate\Http\RedirectResponse
    {
        $role = $request->input('role');
        if (!in_array($role, ['student', 'guest', 'senior'])) {
            return back();
        }
        $roles = (array) ($article->target_roles ?? []);
        if (in_array($role, $roles)) {
            $roles = array_values(array_filter($roles, fn ($r) => $r !== $role));
        } else {
            $roles[] = $role;
        }
        $article->update(['target_roles' => array_values(array_unique($roles))]);
        return back()->with('kb_success', ucfirst($role) . ' görünürlüğü güncellendi.');
    }

    public function knowledgeBaseServeFile(KnowledgeBaseArticle $article)
    {
        abort_unless($article->file_path && \Storage::disk('local')->exists($article->file_path), 404);
        $inline = request()->query('download') !== '1';
        $disposition = $inline ? 'inline' : 'attachment';
        $filename = $article->original_filename ?? basename($article->file_path);
        return response()->file(
            storage_path('app/' . $article->file_path),
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
            ]
        );
    }
}
