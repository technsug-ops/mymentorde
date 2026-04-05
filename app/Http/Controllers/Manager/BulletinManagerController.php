<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\BulletinRead;
use App\Models\BulletinReaction;
use App\Models\CompanyBulletin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BulletinManagerController extends Controller
{
    private function companyId(): ?int
    {
        $cid = auth()->user()?->company_id;
        return $cid ? (int) $cid : null;
    }

    public function index(Request $request)
    {
        $cid      = $this->companyId();
        $category = $request->query('category', '');

        $bulletins = CompanyBulletin::where(fn($q) => $q->whereNull('company_id')->orWhere('company_id', $cid))
            ->when($category !== '', fn($q) => $q->where('category', $category))
            ->with('author:id,name')
            ->withCount('reads')
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->paginate(20)
            ->withQueryString();

        return view('manager.bulletins.index', compact('bulletins', 'category'));
    }

    public function create()
    {
        return view('manager.bulletins.form', ['bulletin' => null]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'              => 'required|string|max:200',
            'body'               => 'required|string|max:5000',
            'category'           => 'required|in:genel,duyuru,acil,ik,kutlama,motivasyon',
            'is_pinned'          => 'boolean',
            'published_at'       => 'required|date',
            'expires_at'         => 'nullable|date|after:published_at',
            'target_roles'       => 'nullable|array',
            'target_roles.*'     => 'string|max:50',
            'target_departments' => 'nullable|array',
            'target_departments.*' => 'string|max:80',
        ]);

        $data['company_id']         = $this->companyId();
        $data['author_id']          = auth()->id();
        $data['is_pinned']          = $request->boolean('is_pinned');
        $data['target_roles']       = !empty($data['target_roles']) ? array_values($data['target_roles']) : null;
        $data['target_departments'] = !empty($data['target_departments']) ? array_values($data['target_departments']) : null;

        CompanyBulletin::create($data);

        // All users' unread caches will naturally expire (TTL 120s)
        // Urgent bulletins cache: clear immediately
        Cache::forget('urgent_bulletins_' . ($data['company_id'] ?? 0));

        return redirect('/manager/bulletins')->with('status', 'Duyuru yayınlandı.');
    }

    public function edit(CompanyBulletin $bulletin)
    {
        return view('manager.bulletins.form', compact('bulletin'));
    }

    public function update(Request $request, CompanyBulletin $bulletin)
    {
        $data = $request->validate([
            'title'              => 'required|string|max:200',
            'body'               => 'required|string|max:5000',
            'category'           => 'required|in:genel,duyuru,acil,ik,kutlama,motivasyon',
            'is_pinned'          => 'boolean',
            'published_at'       => 'required|date',
            'expires_at'         => 'nullable|date|after:published_at',
            'target_roles'       => 'nullable|array',
            'target_roles.*'     => 'string|max:50',
            'target_departments' => 'nullable|array',
            'target_departments.*' => 'string|max:80',
        ]);

        $data['is_pinned']          = $request->boolean('is_pinned');
        $data['target_roles']       = !empty($data['target_roles']) ? array_values($data['target_roles']) : null;
        $data['target_departments'] = !empty($data['target_departments']) ? array_values($data['target_departments']) : null;
        $bulletin->update($data);

        Cache::forget('urgent_bulletins_' . ($bulletin->company_id ?? 0));

        return redirect('/manager/bulletins')->with('status', 'Duyuru güncellendi.');
    }

    public function destroy(CompanyBulletin $bulletin)
    {
        $cid = $bulletin->company_id ?? 0;
        $bulletin->delete();
        Cache::forget('urgent_bulletins_' . $cid);

        return back()->with('status', 'Duyuru silindi.');
    }

    public function analytics(CompanyBulletin $bulletin)
    {
        // Okuma listesi: kim ne zaman okudu
        $reads = BulletinRead::where('bulletin_id', $bulletin->id)
            ->with('user:id,name,email,role')
            ->orderByDesc('read_at')
            ->get();

        // Reaksiyon listesi: her emoji → sayı ve kim kullandı
        $reactions = BulletinReaction::where('bulletin_id', $bulletin->id)
            ->with('user:id,name,email')
            ->get();

        $reactionGroups = $reactions->groupBy('emoji')->map(fn($g) => [
            'count' => $g->count(),
            'users' => $g->map(fn($r) => $r->user?->name ?? '—')->filter()->values(),
        ]);

        // Şirketteki toplam aktif çalışan sayısı (read rate için)
        $cid = $bulletin->company_id;
        $staffRoles = ['manager','senior','marketing_admin','marketing_staff','sales_admin','sales_staff',
                       'finance_admin','finance_staff','operations_admin','operations_staff','system_admin','system_staff'];
        $totalStaff = \App\Models\User::whereIn('role', $staffRoles)
            ->when($cid, fn($q) => $q->where('company_id', $cid))
            ->count();

        $readRate = $totalStaff > 0 ? round(($reads->count() / $totalStaff) * 100, 1) : 0;

        return view('manager.bulletins.analytics', compact(
            'bulletin', 'reads', 'reactionGroups', 'totalStaff', 'readRate'
        ));
    }
}
