<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\University;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

/**
 * Manager — Üniversite görsel/profil yönetimi.
 * 547 üniversitenin image_path'ini doldurmak için. UniMatch program detay
 * filigranı bu görseli (varsa) ilk tier olarak kullanır.
 */
class UniversityController extends Controller
{
    public function index(Request $request): View
    {
        $q       = trim((string) $request->query('q', ''));
        $hasImg  = $request->query('img'); // 'yes' / 'no' / null

        $query = University::query()->orderBy('name');

        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('name', 'LIKE', "%{$q}%")
                   ->orWhere('city', 'LIKE', "%{$q}%");
            });
        }
        if ($hasImg === 'yes') {
            $query->whereNotNull('image_path')->where('image_path', '!=', '');
        } elseif ($hasImg === 'no') {
            $query->where(function ($qq) {
                $qq->whereNull('image_path')->orWhere('image_path', '');
            });
        }

        $universities = $query->paginate(40)->withQueryString();

        // Tepe sayaçlar
        $stats = [
            'total'    => University::count(),
            'with_img' => University::whereNotNull('image_path')->where('image_path', '!=', '')->count(),
        ];
        $stats['without_img'] = $stats['total'] - $stats['with_img'];

        return view('manager.universities.index', compact('universities', 'q', 'hasImg', 'stats'));
    }

    public function uploadImage(Request $request, University $university): RedirectResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'], // 4 MB
        ]);

        $file = $request->file('image');
        $ext  = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        // jpeg → jpg normalize
        if ($ext === 'jpeg') $ext = 'jpg';

        $dir = public_path('img/uni-logos');
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        // Eski dosyayı sil (extension değişmiş olabilir)
        if (! empty($university->image_path)) {
            $oldPath = public_path(ltrim($university->image_path, '/'));
            if (str_starts_with($university->image_path, '/img/uni-logos/') && File::exists($oldPath)) {
                File::delete($oldPath);
            }
        }

        $filename = $university->id . '.' . $ext;
        $file->move($dir, $filename);

        $university->image_path = '/img/uni-logos/' . $filename;
        $university->save();

        return back()->with('success', "✓ {$university->name} görseli yüklendi");
    }

    public function deleteImage(University $university): RedirectResponse
    {
        if (empty($university->image_path)) {
            return back()->with('info', 'Bu üniversitenin görseli zaten yok.');
        }

        // Lokal upload ise dosyayı sil (external URL ise sadece path'i temizle)
        if (str_starts_with($university->image_path, '/img/uni-logos/')) {
            $path = public_path(ltrim($university->image_path, '/'));
            if (File::exists($path)) {
                File::delete($path);
            }
        }

        $name = $university->name;
        $university->image_path = null;
        $university->save();

        return back()->with('success', "✓ {$name} görseli kaldırıldı");
    }

    /**
     * Tanitim videosu URL'sini guncelle. YouTube/Vimeo/diger embed URL.
     * Bos string gonderilirse silinir (deleteVideo ile aynisi).
     */
    public function updateVideo(Request $request, University $university): RedirectResponse
    {
        $data = $request->validate([
            'video_url'     => ['nullable', 'url', 'max:500'],
            'video_caption' => ['nullable', 'string', 'max:200'],
        ]);

        $url = trim((string) ($data['video_url'] ?? ''));
        $cap = trim((string) ($data['video_caption'] ?? ''));

        $university->video_url     = $url !== '' ? $url : null;
        $university->video_caption = $cap !== '' ? $cap : null;
        $university->save();

        $msg = $url === ''
            ? "✓ {$university->name} videosu kaldırıldı"
            : "✓ {$university->name} videosu güncellendi";
        return back()->with('success', $msg);
    }

    public function deleteVideo(University $university): RedirectResponse
    {
        if (empty($university->video_url)) {
            return back()->with('info', 'Bu üniversitenin videosu zaten yok.');
        }
        $name = $university->name;
        $university->video_url     = null;
        $university->video_caption = null;
        $university->save();
        return back()->with('success', "✓ {$name} videosu kaldırıldı");
    }
}
