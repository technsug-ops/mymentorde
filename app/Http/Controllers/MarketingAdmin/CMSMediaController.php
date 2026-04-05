<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\Marketing\CmsMedia;
use App\Rules\ValidFileMagicBytes;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CMSMediaController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'type' => (string) $request->query('type', 'all'),
        ];
        $query = CmsMedia::query()->orderByDesc('id');
        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->where(function ($w) use ($q): void {
                $w->where('file_name', 'like', "%{$q}%")
                    ->orWhere('alt_text', 'like', "%{$q}%")
                    ->orWhere('file_url', 'like', "%{$q}%");
            });
        }
        if ($filters['type'] !== 'all') {
            $query->where('file_type', $filters['type']);
        }

        return view('marketing-admin.content.media', [
            'pageTitle' => 'CMS Medya Kutuphanesi',
            'title' => 'Medya Listesi',
            'rows' => $query->paginate(20)->withQueryString(),
            'filters' => $filters,
            'typeOptions' => ['image', 'video', 'pdf', 'doc', 'other'],
        ]);
    }

    public function upload(Request $request)
    {
        $hasFile = $request->hasFile('upload_file') && $request->file('upload_file')->isValid();

        $request->validate([
            'upload_file'         => ['nullable', 'file', 'max:20480',
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,mp4,mov,avi,webm,zip,txt,csv',
                new ValidFileMagicBytes],
            'file_name'           => ['nullable', 'string', 'max:255'],
            'file_url'            => [$hasFile ? 'nullable' : 'required', 'nullable', 'string', 'max:500'],
            'thumbnail_url'       => ['nullable', 'string', 'max:500'],
            'file_type'           => ['nullable', Rule::in(['image', 'video', 'pdf', 'doc', 'other'])],
            'mime_type'           => ['nullable', 'string', 'max:120'],
            'file_size_bytes'     => ['nullable', 'integer', 'min:1'],
            'width'               => ['nullable', 'integer', 'min:1'],
            'height'              => ['nullable', 'integer', 'min:1'],
            'alt_text'            => ['nullable', 'string', 'max:255'],
            'tags'                => ['nullable', 'string'],
            'used_in_content_ids' => ['nullable', 'string'],
        ]);

        if ($hasFile) {
            $file     = $request->file('upload_file');
            $ext      = strtolower((string) $file->getClientOriginalExtension());
            $stored   = $file->store('cms-media', 'public');
            $fileUrl  = asset('storage/'.$stored);
            $imgExts  = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
            $thumbUrl = in_array($ext, $imgExts, true) ? $fileUrl : null;
            $mime     = $file->getMimeType() ?? 'application/octet-stream';
            $size     = (int) $file->getSize();
            $autoName = $file->getClientOriginalName();
            $width = $height = null;
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                try { [$width, $height] = @getimagesize($file->getRealPath()) ?: [null, null]; } catch (\Throwable) {}
            }
            if (in_array($ext, $imgExts, true))                                      $fileType = 'image';
            elseif (in_array($ext, ['mp4', 'mov', 'avi', 'webm'], true))             $fileType = 'video';
            elseif ($ext === 'pdf')                                                   $fileType = 'pdf';
            elseif (in_array($ext, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'], true)) $fileType = 'doc';
            else                                                                      $fileType = 'other';
        } else {
            $fileUrl  = (string) $request->input('file_url', '');
            if ($fileUrl !== '' && !preg_match('#^https?://#i', $fileUrl) && !str_starts_with($fileUrl, '/')) {
                return ApiResponse::error(ApiResponse::ERR_UNPROCESSABLE, 'Gecersiz URL formati. Yalnizca https:// veya gorace yollar (/...) kabul edilir.');
            }
            $thumbUrl = $request->input('thumbnail_url');
            $mime     = (string) $request->input('mime_type', 'application/octet-stream');
            $size     = (int) $request->input('file_size_bytes', 1024);
            $fileType = (string) ($request->input('file_type') ?: 'other');
            $autoName = null;
            $width    = $request->filled('width')  ? (int) $request->input('width')  : null;
            $height   = $request->filled('height') ? (int) $request->input('height') : null;
        }

        $row = CmsMedia::query()->create([
            'file_name'           => $request->input('file_name') ?: ($autoName ?? basename($fileUrl)),
            'file_url'            => $fileUrl,
            'thumbnail_url'       => $request->input('thumbnail_url') ?: ($thumbUrl ?? null),
            'file_type'           => $fileType,
            'mime_type'           => $mime,
            'file_size_bytes'     => $size,
            'width'               => $width,
            'height'              => $height,
            'alt_text'            => $request->input('alt_text'),
            'tags'                => $this->normalizeCsv($request->input('tags', '')),
            'used_in_content_ids' => $this->normalizeIdCsv($request->input('used_in_content_ids', '')),
            'uploaded_by'         => (int) $request->user()->id,
        ]);

        return $this->responseFor($request, ['ok' => true, 'uploaded' => true, 'id' => $row->id], 'Medya kaydi eklendi.', Response::HTTP_CREATED);
    }

    public function destroy(Request $request, string $id)
    {
        $row = CmsMedia::query()->findOrFail($id);

        // Storage'a yüklenen dosyayı temizle
        if ($row->file_url && str_contains((string) $row->file_url, '/storage/cms-media/')) {
            $appUrl = rtrim((string) config('app.url', ''), '/');
            $relativePath = str_replace($appUrl.'/storage/', '', (string) $row->file_url);
            if ($relativePath !== '' && str_starts_with($relativePath, 'cms-media/')) {
                Storage::disk('public')->delete($relativePath);
            }
        }

        $row->delete();

        return $this->responseFor($request, ['ok' => true, 'id' => $id], 'Medya kaydi silindi.');
    }

    private function normalizeCsv(mixed $raw): array
    {
        $txt = trim((string) $raw);
        if ($txt === '') {
            return [];
        }
        return collect(explode(',', $txt))
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeIdCsv(mixed $raw): array
    {
        return collect($this->normalizeCsv($raw))
            ->map(fn ($v) => (int) $v)
            ->filter(fn (int $v) => $v > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function responseFor(Request $request, array $payload, string $statusMessage, int $statusCode = Response::HTTP_OK)
    {
        if ($request->expectsJson()) {
            return response()->json($payload, $statusCode);
        }
        return redirect('/mktg-admin/media')->with('status', $statusMessage);
    }
}
