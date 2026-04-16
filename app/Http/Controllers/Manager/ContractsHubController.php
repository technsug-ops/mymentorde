<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\BusinessContract;
use App\Models\Dealer;
use App\Models\GuestApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Manager Contracts Hub
 *
 * Tek bir sayfada tüm "bitmiş" sözleşmeleri kategorize şekilde gösterir.
 * Sadece Manager görür (+ ileride permission ile ops_admin / finance_admin).
 *
 * Kategoriler (2-level):
 *   1. Personel       → admin | senior | staff | other
 *   2. Partnerler     → lead_generation | freelance | operational | referrer
 *   3. Danışanlar     → bachelor | master | phd | ausbildung | language | visa
 *   4. Kurumlar       → (placeholder — ilerde üniversite / firma entegrasyon sözleşmeleri)
 */
class ContractsHubController extends Controller
{
    /** Personel rol → alt kategori map'i */
    private const ADMIN_ROLES  = ['manager', 'marketing_admin', 'operations_admin', 'finance_admin', 'system_admin'];
    private const SENIOR_ROLES = ['senior', 'mentor'];
    private const STAFF_ROLES  = ['marketing_staff', 'operations_staff', 'sales_admin', 'sales_staff'];

    /** Dealer türü → alt kategori (değer olarak direkt kullanıyoruz) */
    private const DEALER_SUBCATS = [
        'lead_generation'   => ['label' => '🎯 Lead Generation',   'order' => 1],
        'freelance_danisman'=> ['label' => '🧑‍💼 Freelance Danışman', 'order' => 2],
        'operational'       => ['label' => '⚙️ Operasyon Dealer', 'order' => 3],
        'referrer'          => ['label' => '🔗 Referans',          'order' => 4],
    ];

    /** Student başvuru tipi → alt kategori (kısmi servisler ileride ayrı flag ile işaretlenebilir) */
    private const APP_TYPE_SUBCATS = [
        'bachelor'    => ['label' => '🎓 Üniversite (Bachelor)',  'order' => 1],
        'master'      => ['label' => '📘 Master',                'order' => 2],
        'phd'         => ['label' => '🔬 Doktora',               'order' => 3],
        'ausbildung'  => ['label' => '🛠️ Ausbildung',            'order' => 4],
        'language'    => ['label' => '🗣️ Dil Okulu',             'order' => 5],
        'prep'        => ['label' => '📚 Hazırlık (Prep)',       'order' => 6],
        'visa'        => ['label' => '🛂 Vize Servisi',          'order' => 7],
    ];

    public function index(Request $request): View
    {
        $user = Auth::user();
        abort_if($user?->role !== 'manager', 403, 'Contracts Hub sadece Manager için.');

        $rows = collect();

        // ─── 1) Business Contracts (staff + dealer) ─────────────────────────
        $bizContracts = BusinessContract::query()
            ->whereIn('status', ['signed_uploaded', 'approved']) // "bitmiş" sayılanlar
            ->with(['staffUser:id,name,email,role', 'dealer:id,code,name,dealer_type_code'])
            ->latest('issued_at')
            ->get();

        foreach ($bizContracts as $bc) {
            $hasFile = !empty($bc->signed_file_path);
            $downloadUrl = $hasFile
                ? route('manager.business-contracts.download-signed', $bc)
                : null;
            $previewUrl = $hasFile
                ? route('manager.contracts-hub.preview-biz', $bc)
                : null;

            if ($bc->contract_type === 'staff' && $bc->staffUser) {
                $rows->push([
                    'id'          => 'biz-' . $bc->id,
                    'category'    => 'personel',
                    'subcategory' => $this->staffSubcategory($bc->staffUser->role),
                    'title'       => $bc->title ?: ('Sözleşme #' . $bc->contract_no),
                    'contract_no' => (string) $bc->contract_no,
                    'owner_name'  => (string) $bc->staffUser->name,
                    'owner_email' => (string) $bc->staffUser->email,
                    'owner_code'  => (string) $bc->staffUser->id,
                    'role'        => (string) $bc->staffUser->role,
                    'status'      => $bc->status,
                    'status_label'=> $this->statusLabel($bc->status),
                    'issued_at'   => optional($bc->issued_at)->format('d.m.Y'),
                    'view_url'    => route('manager.business-contracts.show', $bc),
                    'has_file'    => $hasFile,
                    'download_url'=> $downloadUrl,
                    'preview_url' => $previewUrl,
                ]);
            } elseif ($bc->contract_type === 'dealer' && $bc->dealer) {
                $sub = $bc->dealer->dealer_type_code ?: 'referrer';
                $rows->push([
                    'id'          => 'biz-' . $bc->id,
                    'category'    => 'partner',
                    'subcategory' => $sub,
                    'title'       => $bc->title ?: ('Sözleşme #' . $bc->contract_no),
                    'contract_no' => (string) $bc->contract_no,
                    'owner_name'  => (string) $bc->dealer->name,
                    'owner_email' => '',
                    'owner_code'  => (string) $bc->dealer->code,
                    'role'        => 'dealer',
                    'status'      => $bc->status,
                    'status_label'=> $this->statusLabel($bc->status),
                    'issued_at'   => optional($bc->issued_at)->format('d.m.Y'),
                    'view_url'    => route('manager.business-contracts.show', $bc),
                    'has_file'    => $hasFile,
                    'download_url'=> $downloadUrl,
                    'preview_url' => $previewUrl,
                ]);
            }
        }

        // ─── 2) Danışan (Guest/Student) Hizmet Sözleşmeleri ─────────────────
        // Sadece onaylanmış veya imzalanmış olanlar
        // "Bitmiş" danışan sözleşmeleri = imzalanmış veya onaylanmış
        $guestContracts = GuestApplication::query()
            ->where(function ($q) {
                $q->whereIn('contract_status', ['signed_uploaded', 'approved'])
                  ->orWhereNotNull('contract_signed_at')
                  ->orWhereNotNull('contract_approved_at');
            })
            ->latest('contract_signed_at')
            ->get([
                'id', 'first_name', 'last_name', 'email',
                'application_type', 'contract_status', 'contract_signed_at', 'contract_approved_at',
                'contract_template_code', 'converted_student_id', 'contract_signed_file_path',
            ]);

        foreach ($guestContracts as $g) {
            $appType = (string) ($g->application_type ?: 'bachelor');
            $hasFile = !empty($g->contract_signed_file_path);
            $status  = $g->contract_status === 'approved' ? 'approved' : 'signed_uploaded';
            $rows->push([
                'id'          => 'guest-' . $g->id,
                'category'    => 'danisan',
                'subcategory' => $appType,
                'title'       => 'Hizmet Sözleşmesi',
                'contract_no' => 'G-' . $g->id,
                'owner_name'  => trim(($g->first_name ?? '') . ' ' . ($g->last_name ?? '')),
                'owner_email' => (string) ($g->email ?? ''),
                'owner_code'  => (string) ($g->converted_student_id ?? ('GST-' . $g->id)),
                'role'        => 'guest',
                'status'      => $status,
                'status_label'=> $this->statusLabel($status),
                'issued_at'   => optional($g->contract_approved_at ?? $g->contract_signed_at)->format('d.m.Y'),
                'view_url'    => '/manager/guests/' . $g->id,
                'has_file'    => $hasFile,
                'download_url'=> $hasFile ? route('manager.contracts-hub.download-guest', $g->id) : null,
                'preview_url' => $hasFile ? route('manager.contracts-hub.preview-guest', $g->id) : null,
            ]);
        }

        // ─── Kategori ağacı + sayımlar ──────────────────────────────────────
        $tree = [
            'personel' => [
                'label' => '👥 Personel',
                'subs'  => [
                    'admin'  => ['label' => '👔 Admin & Manager', 'count' => 0],
                    'senior' => ['label' => '🎓 Eğitim Danışmanı', 'count' => 0],
                    'staff'  => ['label' => '💼 Staff (Ops / Finans / Pazarlama)', 'count' => 0],
                    'other'  => ['label' => '📋 Diğer Çalışanlar', 'count' => 0],
                ],
                'count' => 0,
            ],
            'partner'  => [
                'label' => '🤝 Partnerler',
                'subs'  => collect(self::DEALER_SUBCATS)->map(fn ($s) => ['label' => $s['label'], 'count' => 0])->toArray(),
                'count' => 0,
            ],
            'danisan'  => [
                'label' => '🎒 Danışanlar',
                'subs'  => collect(self::APP_TYPE_SUBCATS)->map(fn ($s) => ['label' => $s['label'], 'count' => 0])->toArray(),
                'count' => 0,
            ],
            'kurum'    => [
                'label' => '🏛️ Kurumlar',
                'subs'  => [
                    'university' => ['label' => '🔜 Üniversite Entegrasyonları', 'count' => 0],
                    'company'    => ['label' => '🔜 Firma Entegrasyonları',      'count' => 0],
                ],
                'count' => 0,
            ],
        ];

        foreach ($rows as $r) {
            $cat = $r['category'];
            $sub = $r['subcategory'];
            if (isset($tree[$cat])) {
                $tree[$cat]['count']++;
                if (isset($tree[$cat]['subs'][$sub])) {
                    $tree[$cat]['subs'][$sub]['count']++;
                } elseif (isset($tree[$cat]['subs']['other'])) {
                    $tree[$cat]['subs']['other']['count']++;
                }
            }
        }

        return view('manager.contracts-hub', [
            'rows' => $rows->values(),
            'tree' => $tree,
            'totalCount' => $rows->count(),
        ]);
    }

    private function staffSubcategory(string $role): string
    {
        if (in_array($role, self::ADMIN_ROLES, true))  return 'admin';
        if (in_array($role, self::SENIOR_ROLES, true)) return 'senior';
        if (in_array($role, self::STAFF_ROLES, true))  return 'staff';
        return 'other';
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'draft'            => 'Taslak',
            'issued'           => 'Gönderildi',
            'signed_uploaded'  => 'İmzalandı (Yüklendi)',
            'approved'         => 'Onaylandı',
            'cancelled'        => 'İptal',
            default            => $status,
        };
    }

    /**
     * Guest'in imzalı sözleşme PDF'ini indirir (manager yetkisiyle).
     */
    public function downloadGuestContract(GuestApplication $guest): BinaryFileResponse
    {
        abort_if(Auth::user()?->role !== 'manager', 403);

        $path = trim((string) ($guest->contract_signed_file_path ?? ''));
        $abs  = storage_path('app/' . $path);
        abort_if($path === '' || !file_exists($abs), 404, 'İmzalı sözleşme dosyası bulunamadı.');

        return response()->download($abs, 'hizmet-sozlesme-G-' . $guest->id . '.' . pathinfo($path, PATHINFO_EXTENSION));
    }

    /**
     * Inline preview — modal iframe için dosyayı stream eder (Content-Disposition inline).
     */
    public function previewGuestContract(GuestApplication $guest)
    {
        abort_if(Auth::user()?->role !== 'manager', 403);

        $path = trim((string) ($guest->contract_signed_file_path ?? ''));
        $abs  = storage_path('app/' . $path);
        abort_if($path === '' || !file_exists($abs), 404);

        return response()->file($abs);
    }

    /**
     * Business contract inline preview.
     */
    public function previewBusinessContract(BusinessContract $contract)
    {
        abort_if(Auth::user()?->role !== 'manager', 403);

        $path = trim((string) ($contract->signed_file_path ?? ''));
        $abs  = storage_path('app/' . $path);
        abort_if($path === '' || !file_exists($abs), 404);

        return response()->file($abs);
    }
}
