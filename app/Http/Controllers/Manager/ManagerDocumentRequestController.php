<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\DocumentCategory;
use App\Models\DocumentUploadToken;
use App\Models\BusinessContract;
use App\Models\Dealer;
use App\Models\GuestApplication;
use App\Models\StudentAssignment;
use App\Models\User;
use App\Support\ModuleAccess;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Belge Talep Linki — Premium özellik (doc_request modülü).
 *
 * Manager aday öğrenciden belirli bir belgeyi tek-kullanımlık imzalı link ile
 * talep eder. Aday linki açar, kameradan fotoğraf çeker veya dosya yükler;
 * belge sisteme düşer. Login gerektirmez.
 */
class ManagerDocumentRequestController extends Controller
{
    // ── Guest (aday öğrenci) endpoint'leri ─────────────────────────────────
    public function index(Request $request, GuestApplication $guest)
    {
        return $this->indexFor(
            $request,
            DocumentUploadToken::TARGET_GUEST,
            (string) $guest->id,
            (int) ($guest->company_id ?? 1),
            trim((string) ($guest->first_name ?? '') . ' ' . (string) ($guest->last_name ?? ''))
        );
    }

    public function store(Request $request, GuestApplication $guest)
    {
        return $this->storeFor(
            $request,
            DocumentUploadToken::TARGET_GUEST,
            (string) $guest->id,
            (int) ($guest->company_id ?? 1),
            trim((string) ($guest->first_name ?? '') . ' ' . (string) ($guest->last_name ?? ''))
        );
    }

    public function destroy(Request $request, GuestApplication $guest, DocumentUploadToken $token)
    {
        return $this->destroyFor(
            $request,
            DocumentUploadToken::TARGET_GUEST,
            (string) $guest->id,
            $token
        );
    }

    // ── Student (öğrenci) endpoint'leri ────────────────────────────────────
    public function indexForStudent(Request $request, string $studentId)
    {
        $assignment = $this->resolveStudent($studentId);
        return $this->indexFor(
            $request,
            DocumentUploadToken::TARGET_STUDENT,
            $studentId,
            (int) ($assignment->company_id ?? 1),
            trim((string) ($assignment->display_name ?? ''))
        );
    }

    public function storeForStudent(Request $request, string $studentId)
    {
        $assignment = $this->resolveStudent($studentId);
        return $this->storeFor(
            $request,
            DocumentUploadToken::TARGET_STUDENT,
            $studentId,
            (int) ($assignment->company_id ?? 1),
            trim((string) ($assignment->display_name ?? ''))
        );
    }

    public function destroyForStudent(Request $request, string $studentId, DocumentUploadToken $token)
    {
        return $this->destroyFor(
            $request,
            DocumentUploadToken::TARGET_STUDENT,
            $studentId,
            $token
        );
    }

    // ── User (HR onboarding — çalışan/personel) endpoint'leri ──────────────
    public function indexForUser(Request $request, User $user)
    {
        return $this->indexFor(
            $request,
            DocumentUploadToken::TARGET_USER,
            (string) $user->id,
            (int) ($user->company_id ?? 1),
            (string) ($user->name ?? '')
        );
    }

    public function storeForUser(Request $request, User $user)
    {
        return $this->storeFor(
            $request,
            DocumentUploadToken::TARGET_USER,
            (string) $user->id,
            (int) ($user->company_id ?? 1),
            (string) ($user->name ?? '')
        );
    }

    public function destroyForUser(Request $request, User $user, DocumentUploadToken $token)
    {
        return $this->destroyFor(
            $request,
            DocumentUploadToken::TARGET_USER,
            (string) $user->id,
            $token
        );
    }

    // ── Dealer (KYC) endpoint'leri ─────────────────────────────────────────
    public function indexForDealer(Request $request, string $code)
    {
        $dealer = $this->resolveDealer($code);
        return $this->indexFor(
            $request,
            DocumentUploadToken::TARGET_DEALER,
            (string) $dealer->code,
            (int) ($dealer->company_id ?? 1),
            (string) ($dealer->name ?? $dealer->code)
        );
    }

    public function storeForDealer(Request $request, string $code)
    {
        $dealer = $this->resolveDealer($code);
        return $this->storeFor(
            $request,
            DocumentUploadToken::TARGET_DEALER,
            (string) $dealer->code,
            (int) ($dealer->company_id ?? 1),
            (string) ($dealer->name ?? $dealer->code)
        );
    }

    public function destroyForDealer(Request $request, string $code, DocumentUploadToken $token)
    {
        $this->resolveDealer($code);
        return $this->destroyFor(
            $request,
            DocumentUploadToken::TARGET_DEALER,
            $code,
            $token
        );
    }

    private function resolveDealer(string $code): Dealer
    {
        $dealer = Dealer::where('code', $code)->first();
        if (!$dealer) {
            abort(404, 'Bayi bulunamadı.');
        }
        return $dealer;
    }

    // ── Contract (imzalı PDF geri yükleme) endpoint'leri ───────────────────
    public function indexForContract(Request $request, BusinessContract $businessContract)
    {
        return $this->indexFor(
            $request,
            DocumentUploadToken::TARGET_CONTRACT,
            (string) $businessContract->id,
            (int) ($businessContract->company_id ?? 1),
            (string) ($businessContract->title ?? ('Sözleşme #' . $businessContract->id))
        );
    }

    public function storeForContract(Request $request, BusinessContract $businessContract)
    {
        return $this->storeFor(
            $request,
            DocumentUploadToken::TARGET_CONTRACT,
            (string) $businessContract->id,
            (int) ($businessContract->company_id ?? 1),
            (string) ($businessContract->title ?? ('Sözleşme #' . $businessContract->id))
        );
    }

    public function destroyForContract(Request $request, BusinessContract $businessContract, DocumentUploadToken $token)
    {
        return $this->destroyFor(
            $request,
            DocumentUploadToken::TARGET_CONTRACT,
            (string) $businessContract->id,
            $token
        );
    }

    // ── Generic (her target_type için ortak) ──────────────────────────────
    /**
     * Polymorphic listele — herhangi bir target_type/target_id için tokenları döndür.
     * Bu method yeni modüllerde (HR, dealer, mentor) doğrudan kullanılabilir.
     */
    public function indexFor(Request $request, string $targetType, string $targetId, int $companyId, string $displayName = ''): \Illuminate\Http\JsonResponse
    {
        $this->authorizeUse($request);
        $this->assertTargetType($targetType);

        $tokens = DocumentUploadToken::query()
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $categories = DocumentCategory::query()
            ->where('is_active', true)
            ->orderBy('top_category_code')
            ->orderBy('sort_order')
            ->get(['code', 'name_tr', 'name_de', 'top_category_code']);

        return response()->json([
            'tokens'     => $tokens->map(fn ($t) => $this->serializeToken($t))->all(),
            'categories' => $categories,
            'target'     => [
                'type'         => $targetType,
                'type_label'   => DocumentUploadToken::TARGET_TYPES[$targetType] ?? $targetType,
                'id'           => $targetId,
                'display_name' => $displayName,
            ],
        ]);
    }

    /**
     * Polymorphic token üret — yeni modüller için doğrudan çağrılabilir.
     */
    public function storeFor(Request $request, string $targetType, string $targetId, int $companyId, string $displayName = ''): \Illuminate\Http\JsonResponse
    {
        $this->authorizeUse($request);
        $this->assertTargetType($targetType);

        $data = $request->validate([
            'category_code'  => 'required|string|max:64|regex:/^[A-Za-z0-9_-]+$/',
            'expires_hours'  => 'nullable|integer|min:1|max:168', // max 7 gün
            'custom_message' => 'nullable|string|max:500',
        ]);

        $category = DocumentCategory::where('code', $data['category_code'])->first();
        if (!$category) {
            return response()->json(['error' => 'Belge kategorisi bulunamadı.'], 422);
        }

        $token = DocumentUploadToken::create([
            'company_id'          => $companyId,
            'token'               => DocumentUploadToken::generateToken(),
            'target_type'         => $targetType,
            'target_id'           => $targetId,
            'target_display_name' => $displayName,
            'category_code'       => $data['category_code'],
            'category_name'       => $category->name_tr ?? $data['category_code'],
            'document_name_de'    => $category->name_de ?? null,
            'custom_message'      => $data['custom_message'] ?? null,
            'created_by_user_id'  => $request->user()?->id,
            'max_uses'            => 1,
            'used_count'          => 0,
            'expires_at'          => CarbonImmutable::now()->addHours($data['expires_hours'] ?? 48),
        ]);

        return response()->json([
            'success' => true,
            'token'   => $this->serializeToken($token),
            'url'     => url('/u/' . $token->token),
        ]);
    }

    /**
     * Polymorphic token sil.
     */
    public function destroyFor(Request $request, string $targetType, string $targetId, DocumentUploadToken $token): \Illuminate\Http\JsonResponse
    {
        $this->authorizeUse($request);
        $this->assertTargetType($targetType);

        if ((string) $token->target_type !== $targetType || (string) $token->target_id !== $targetId) {
            abort(404);
        }
        $token->delete();
        return response()->json(['success' => true]);
    }

    private function assertTargetType(string $type): void
    {
        if (!array_key_exists($type, DocumentUploadToken::TARGET_TYPES)) {
            abort(422, "Geçersiz target tipi: {$type}");
        }
    }

    private function resolveStudent(string $studentId): StudentAssignment
    {
        $assignment = StudentAssignment::query()
            ->where('student_id', $studentId)
            ->first();
        if (!$assignment) {
            abort(404, 'Öğrenci kaydı bulunamadı.');
        }
        return $assignment;
    }

    /**
     * Modül + permission kontrolü.
     * - Modül kapalı = 404
     * - 'doc_request.use' yetkisi yok = 403
     * Manager rolü migration ile otomatik yetkili. Senior'lara manager
     * tek tek atayabilir (RBAC sayfasından veya senior detay'dan).
     */
    private function authorizeUse(Request $request): void
    {
        ModuleAccess::assertEnabled('doc_request');
        $user = $request->user();
        if (!$user || !$user->hasPermissionCode('doc_request.use')) {
            abort(403, 'Bu özellik için yetkiniz yok.');
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function serializeToken(DocumentUploadToken $t): array
    {
        return [
            'id'             => $t->id,
            'token'          => $t->token,
            'url'            => url('/u/' . $t->token),
            'category_code'  => $t->category_code,
            'category_name'  => $t->category_name,
            'document_name_de' => $t->document_name_de,
            'custom_message' => $t->custom_message,
            'expires_at'     => $t->expires_at?->toIso8601String(),
            'is_expired'     => $t->isExpired(),
            'is_used'        => $t->isExhausted(),
            'used_count'     => $t->used_count,
            'max_uses'       => $t->max_uses,
            'last_used_at'   => $t->last_used_at?->toIso8601String(),
            'document_id'    => $t->document_id,
            'created_at'     => $t->created_at?->toIso8601String(),
        ];
    }
}
