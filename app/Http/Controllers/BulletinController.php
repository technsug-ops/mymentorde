<?php

namespace App\Http\Controllers;

use App\Models\BulletinRead;
use App\Models\BulletinReaction;
use App\Models\CompanyBulletin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BulletinController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $cid  = $user?->company_id;

        $role       = (string) ($user->role ?? '');
        $department = (string) ($user->department ?? '');

        $bulletins = CompanyBulletin::active()
            ->where(fn($q) => $q->whereNull('company_id')->orWhere('company_id', $cid))
            ->visibleToUser($role, $department ?: null)
            ->with(['author:id,name', 'reactions'])
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->get();

        $readIds    = BulletinRead::where('user_id', $user->id)->pluck('bulletin_id')->all();
        $myReactions = BulletinReaction::where('user_id', $user->id)
            ->whereIn('bulletin_id', $bulletins->pluck('id'))
            ->pluck('emoji', 'bulletin_id')
            ->all();

        return view('bulletins.index', compact('bulletins', 'readIds', 'myReactions'));
    }

    public function markRead(Request $request, CompanyBulletin $bulletin)
    {
        $userId = $request->user()->id;

        BulletinRead::firstOrCreate(
            ['bulletin_id' => $bulletin->id, 'user_id' => $userId],
            ['read_at' => now()]
        );

        Cache::forget('bulletin_unread_' . $userId);

        return response()->json(['ok' => true]);
    }

    // GET /bulletins/partial — dashboard tab için layout'suz board HTML
    public function partial(Request $request)
    {
        $user = $request->user();
        $cid  = $user?->company_id;

        $role       = (string) ($user->role ?? '');
        $department = (string) ($user->department ?? '');

        $bulletins = CompanyBulletin::active()
            ->where(fn($q) => $q->whereNull('company_id')->orWhere('company_id', $cid))
            ->visibleToUser($role, $department ?: null)
            ->with(['author:id,name', 'reactions'])
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->get();

        $readIds     = BulletinRead::where('user_id', $user->id)->pluck('bulletin_id')->all();
        $myReactions = BulletinReaction::where('user_id', $user->id)
            ->whereIn('bulletin_id', $bulletins->pluck('id'))
            ->pluck('emoji', 'bulletin_id')
            ->all();

        return view('bulletins._board', compact('bulletins', 'readIds', 'myReactions'));
    }

    // POST /bulletins/{bulletin}/react  {"emoji":"🎉"}
    public function react(Request $request, CompanyBulletin $bulletin)
    {
        $emoji  = $request->input('emoji');
        $userId = $request->user()->id;

        if (!in_array($emoji, CompanyBulletin::REACTIONS, true)) {
            return response()->json(['error' => 'invalid emoji'], 422);
        }

        $existing = BulletinReaction::where('bulletin_id', $bulletin->id)
            ->where('user_id', $userId)->first();

        if ($existing) {
            if ($existing->emoji === $emoji) {
                // Aynı emoji → toggle off
                $existing->delete();
                $myEmoji = null;
            } else {
                // Farklı emoji → değiştir
                $existing->update(['emoji' => $emoji]);
                $myEmoji = $emoji;
            }
        } else {
            BulletinReaction::create([
                'bulletin_id' => $bulletin->id,
                'user_id'     => $userId,
                'emoji'       => $emoji,
            ]);
            $myEmoji = $emoji;
        }

        // Güncel sayılar
        $counts = BulletinReaction::where('bulletin_id', $bulletin->id)
            ->selectRaw('emoji, count(*) as cnt')
            ->groupBy('emoji')
            ->pluck('cnt', 'emoji')
            ->all();

        return response()->json(['ok' => true, 'myEmoji' => $myEmoji, 'counts' => $counts]);
    }
}
