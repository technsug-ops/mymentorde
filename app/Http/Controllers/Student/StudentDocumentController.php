<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\StudentWorkflowTrait;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Rules\ValidFileMagicBytes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StudentDocumentController extends Controller
{
    use StudentWorkflowTrait;

    public function upload(Request $request)
    {
        $guest = $this->resolveStudentGuest($request);
        abort_if(! $guest, 404, 'Student icin bagli basvuru kaydi bulunamadi.');

        $data = $request->validate([
            'category_code' => ['required', 'string', 'max:64'],
            'file'          => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,webp', 'max:10240', new ValidFileMagicBytes()],
        ]);

        $category = DocumentCategory::query()->where('code', $data['category_code'])->firstOrFail();
        $file     = $request->file('file');
        $ownerId  = $this->resolveDocumentOwnerId($guest);
        $ext      = strtolower((string) ($file->getClientOriginalExtension() ?: 'bin'));
        $stdName  = app(\App\Services\DocumentNamingService::class)->buildStandardFileName(
            $ownerId,
            $category->code,
            (string) ($guest->first_name ?? ''),
            (string) ($guest->last_name ?? ''),
            $ext,
        );
        $stored   = $file->storeAs("student-documents/{$guest->id}", $stdName, 'local');

        $row = Document::query()->create([
            'student_id'         => $ownerId,
            'category_id'        => $category->id,
            'process_tags'       => ['student_registration'],
            'original_file_name' => (string) $file->getClientOriginalName(),
            'standard_file_name' => $stdName,
            'storage_path'       => $stored,
            'mime_type'          => (string) ($file->getMimeType() ?: ''),
            'status'             => 'uploaded',
            'uploaded_by'        => (string) optional($request->user())->email,
        ]);
        $row->forceFill(['document_id' => 'DOC-STD-' . str_pad((string) $row->id, 6, '0', STR_PAD_LEFT)])->save();

        $docsReady = $this->computeDocsReady($guest, $ownerId);
        $guest->forceFill([
            'docs_ready'     => $docsReady,
            'status_message' => $docsReady ? 'Belgeler tamamlandi.' : 'Belge yuklendi. Eksikler var.',
        ])->save();

        if ($docsReady) {
            return redirect()->route('student.registration.documents')->with('docs_complete', true);
        }

        return redirect()->route('student.registration.documents')->with('status', 'Belge yuklendi.');
    }

    public function delete(Request $request, Document $document)
    {
        $guest    = $this->resolveStudentGuest($request);
        abort_if(! $guest, 404, 'Student icin bagli basvuru kaydi bulunamadi.');
        $ownerIds = $this->resolveDocumentOwnerIds($guest);
        abort_if(! $ownerIds->contains((string) $document->student_id), 403, 'Bu belge size ait degil.');

        $path = trim((string) ($document->storage_path ?? ''));
        if ($path !== '' && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
        $document->delete();

        $docsReady = $this->computeDocsReady($guest, $this->resolveDocumentOwnerId($guest));
        $guest->forceFill([
            'docs_ready'     => $docsReady,
            'status_message' => $docsReady ? 'Belge silindi, checklist tamam.' : 'Belge silindi, checklist eksik.',
        ])->save();

        return redirect()->route('student.registration.documents')->with('status', 'Belge silindi.');
    }

    public function download(Request $request, Document $document)
    {
        $guest    = $this->resolveStudentGuest($request);
        abort_if(! $guest, 404, 'Student icin bagli basvuru kaydi bulunamadi.');
        $ownerIds = $this->resolveDocumentOwnerIds($guest);
        abort_if(! $ownerIds->contains((string) $document->student_id), 403, 'Bu belge size ait degil.');

        $path = trim((string) ($document->storage_path ?? ''));
        abort_if($path === '', 404, 'Belge dosya yolu bulunamadi.');
        abort_unless(Storage::disk('local')->exists($path), 404, 'Belge dosyasi bulunamadi.');

        $downloadName = trim((string) ($document->standard_file_name ?: $document->original_file_name ?: basename($path)));
        if ($downloadName === '') {
            $downloadName = basename($path);
        }

        return Storage::disk('local')->download($path, $downloadName);
    }

    public function preview(Request $request, Document $document): \Illuminate\Http\JsonResponse
    {
        $guest    = $this->resolveStudentGuest($request);
        $ownerIds = $guest ? $this->resolveDocumentOwnerIds($guest) : collect();
        abort_if(! $ownerIds->contains((string) $document->student_id), 403);

        $mime        = strtolower((string) ($document->mime_type ?? ''));
        $previewable = str_starts_with($mime, 'image/') || $mime === 'application/pdf';
        abort_if(! $previewable, 422, 'Bu dosya tipi önizlenemez.');

        $path = (string) ($document->storage_path ?? '');
        abort_if($path === '', 404);
        abort_if(! Storage::disk('local')->exists($path), 404, 'Dosya bulunamadı.');

        return response()->json([
            'url'         => route('student.registration.documents.serve', $document->id),
            'mime'        => $mime,
            'filename'    => $document->original_file_name,
            'status'      => $document->status,
            'review_note' => $document->review_note,
        ]);
    }

    public function serve(Request $request, Document $document): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $guest    = $this->resolveStudentGuest($request);
        $ownerIds = $guest ? $this->resolveDocumentOwnerIds($guest) : collect();
        abort_if(! $ownerIds->contains((string) $document->student_id), 403, 'Bu belge size ait değil.');

        $path = trim((string) ($document->storage_path ?? ''));
        abort_if($path === '', 404);
        abort_if(! Storage::disk('local')->exists($path), 404, 'Dosya bulunamadı.');

        return Storage::disk('local')->response($path, $document->standard_file_name ?? basename($path));
    }
}
