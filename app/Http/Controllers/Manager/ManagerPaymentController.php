<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\MarketingAdminSetting;
use App\Models\StudentPayment;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ManagerPaymentController extends Controller
{
    private function companyId(): int
    {
        return (int) (auth()->user()?->company_id ?? 0);
    }

    private function brandName(): string
    {
        $cid = $this->companyId();
        return (string) MarketingAdminSetting::where('company_id', $cid)
            ->where('setting_key', 'brand_name')
            ->value('setting_value') ?: config('brand.name', 'MentorDE');
    }

    // ── Liste ──────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $cid      = $this->companyId();
        $status   = $request->query('status', '');
        $search   = trim((string) $request->query('search', ''));
        $month    = $request->query('month', '');

        // Vadesi geçenleri otomatik işaretle
        StudentPayment::markOverdue();

        $query = StudentPayment::query()
            ->when($cid > 0, fn($q) => $q->where('company_id', $cid))
            ->when($status !== '', fn($q) => $q->where('status', $status))
            ->when($search !== '', fn($q) => $q->where(function ($q) use ($search) {
                $q->where('student_id', 'like', "%{$search}%")
                  ->orWhere('invoice_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            }))
            ->when($month !== '', function ($q) use ($month) {
                [$y, $m] = explode('-', $month);
                $q->whereYear('due_date', $y)->whereMonth('due_date', $m);
            })
            ->orderByDesc('id');

        $payments = $query->paginate(25)->withQueryString();

        // Sayfa üzerindeki student_id'lere ait isimleri tek sorguda çek (N+1 önlemi)
        $studentIds = $payments->pluck('student_id')
            ->filter(fn($sid) => $sid && !str_starts_with($sid, 'GUEST-'))
            ->unique()->values()->all();
        $studentNames = User::whereIn('student_id', $studentIds)
            ->pluck('name', 'student_id');   // ['STU-001' => 'Ahmet Yılmaz', ...]

        // KPI özeti
        $allQuery = StudentPayment::query()->when($cid > 0, fn($q) => $q->where('company_id', $cid));
        $kpi = [
            'total'   => (float)(clone $allQuery)->sum('amount_eur'),
            'paid'    => (float)(clone $allQuery)->paid()->sum('amount_eur'),
            'pending' => (float)(clone $allQuery)->pending()->sum('amount_eur'),
            'overdue' => (float)(clone $allQuery)->overdue()->sum('amount_eur'),
            'count_pending' => (clone $allQuery)->pending()->count(),
            'count_overdue' => (clone $allQuery)->overdue()->count(),
        ];

        // Öğrenci listesi (yeni kayıt formu için)
        $students = User::where('role', User::ROLE_STUDENT)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'student_id', 'email']);

        $months = collect(range(0, 11))->map(fn($i) => now()->subMonths($i)->format('Y-m'))->all();

        return view('manager.payments.index', compact(
            'payments', 'kpi', 'students', 'studentNames',
            'status', 'search', 'month', 'months'
        ));
    }

    // ── Yeni Ödeme Kaydı Oluştur ──────────────────────────────────────────

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id'  => 'required|string|max:64',
            'description' => 'required|string|max:255',
            'amount_eur'  => 'required|numeric|min:0.01',
            'currency'    => 'required|string|size:3',
            'due_date'    => 'required|date',
            'notes'       => 'nullable|string|max:1000',
        ]);

        StudentPayment::create(array_merge($data, [
            'company_id'     => $this->companyId() ?: null,
            'invoice_number' => StudentPayment::nextInvoiceNumber(),
            'status'         => 'pending',
            'created_by'     => auth()->id(),
        ]));

        return back()->with('status', 'Ödeme kaydı oluşturuldu.');
    }

    // ── Değişiklik Bildirimi Okundu ───────────────────────────────────────

    public function acknowledgeUpdate(StudentPayment $payment)
    {
        $this->authorizePayment($payment);
        $payment->acknowledgeContractUpdate();
        return back()->with('status', "{$payment->invoice_number} değişikliği okundu işaretlendi.");
    }

    // ── Ödendi Olarak İşaretle ────────────────────────────────────────────

    public function markPaid(Request $request, StudentPayment $payment)
    {
        $this->authorizePayment($payment);

        $data = $request->validate([
            'payment_method' => 'required|in:bank_transfer,credit_card,cash,other',
            'paid_at'        => 'nullable|date',
            'notes'          => 'nullable|string|max:500',
        ]);

        $payment->update([
            'status'         => 'paid',
            'paid_at'        => $data['paid_at'] ? \Carbon\Carbon::parse($data['paid_at']) : now(),
            'payment_method' => $data['payment_method'],
            'notes'          => $data['notes'] ?? $payment->notes,
        ]);

        return back()->with('status', "Ödeme {$payment->invoice_number} ödendi olarak işaretlendi.");
    }

    // ── İptal Et ─────────────────────────────────────────────────────────

    public function cancel(StudentPayment $payment)
    {
        $this->authorizePayment($payment);
        abort_if($payment->status === 'paid', 422, 'Ödenmiş kayıt iptal edilemez.');

        $payment->update(['status' => 'cancelled']);
        return back()->with('status', "Ödeme {$payment->invoice_number} iptal edildi.");
    }

    // ── Sil ──────────────────────────────────────────────────────────────

    public function destroy(StudentPayment $payment)
    {
        $this->authorizePayment($payment);
        abort_if($payment->status === 'paid', 422, 'Ödenmiş kayıt silinemez.');

        $payment->delete();
        return back()->with('status', 'Kayıt silindi.');
    }

    // ── Fatura Ön İzleme (HTML) ───────────────────────────────────────────

    public function preview(StudentPayment $payment)
    {
        $this->authorizePayment($payment);

        $student   = $this->resolveStudentForPayment($payment);
        $brandName = $this->brandName();

        // Aynı invoice şablonunu düz HTML olarak döndür
        return view('manager.payments.invoice', compact('payment', 'student', 'brandName'));
    }

    // ── PDF Fatura İndir ─────────────────────────────────────────────────

    public function invoice(StudentPayment $payment)
    {
        $this->authorizePayment($payment);

        $student   = $this->resolveStudentForPayment($payment);
        $brandName = $this->brandName();

        $pdf = Pdf::loadView('manager.payments.invoice', compact('payment', 'student', 'brandName'))
            ->setPaper('a4', 'portrait');

        // İndirme kaydını güncelle
        $payment->recordDownload();

        $filename = 'fatura-' . $payment->invoice_number . '.pdf';
        return $pdf->download($filename);
    }

    // ── Yetki Kontrolü ───────────────────────────────────────────────────

    private function authorizePayment(StudentPayment $payment): void
    {
        $cid = $this->companyId();
        if ($cid > 0 && (int) $payment->company_id !== $cid) {
            abort(403);
        }
    }

    /**
     * Öğrenci kaydını çöz.
     * GUEST-{token} ID'li kayıtlar için GuestApplication'dan ad/email alır.
     */
    private function resolveStudentForPayment(StudentPayment $payment): ?object
    {
        $sid = $payment->student_id;

        // Gerçek öğrenci
        if (!str_starts_with($sid, 'GUEST-')) {
            return User::where('student_id', $sid)
                ->first(['name', 'email', 'student_id']);
        }

        // GUEST-{tracking_token} → notes alanından ad soy ad çıkar
        // notes format: "Otomatik oluşturuldu — guest_id:20 | Ahmet Yılmaz"
        $name  = null;
        $email = null;
        if ($payment->notes && preg_match('/\|\s*(.+)$/', $payment->notes, $m)) {
            $name = trim($m[1]);
        }
        // GuestApplication'dan email çekmeye çalış
        if (preg_match('/guest_id:(\d+)/', $payment->notes ?? '', $m2)) {
            $guest = \App\Models\GuestApplication::find((int) $m2[1], ['first_name', 'last_name', 'email']);
            if ($guest) {
                $name  = $name ?: ($guest->first_name . ' ' . $guest->last_name);
                $email = $guest->email;
            }
        }

        return (object) [
            'name'       => $name ?: $sid,
            'email'      => $email,
            'student_id' => $sid,
        ];
    }
}
