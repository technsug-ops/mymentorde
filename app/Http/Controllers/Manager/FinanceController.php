<?php
namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\CompanyFinanceEntry;
use App\Models\GuestApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinanceController extends Controller
{
    private function companyId(): int
    {
        return (int) (auth()->user()?->company_id ?? 0);
    }

    // ── Sözleşme Gelirlerini Çek (canlı) ─────────────────────────────────────
    /**
     * guest_applications'dan sözleşme bazlı gelir verileri.
     *
     * @return array{
     *   monthlyRevenue: \Illuminate\Support\Collection,   // period => amount
     *   confirmedTotal: float,
     *   pendingTotal:   float,
     *   cancelledTotal: float,
     *   collectionRate: float,                            // tahsilat oranı %
     *   statusBreakdown: \Illuminate\Support\Collection,
     *   recentContracts: \Illuminate\Support\Collection,
     * }
     */
    private function contractRevenueData(int $cid): array
    {
        $base = GuestApplication::query()
            ->whereNotNull('contract_amount_eur')
            ->where('contract_amount_eur', '>', 0)
            ->when($cid > 0, fn($q) => $q->where('company_id', $cid));

        // Onaylı / tahsil edilmiş (signed + approved = confirmed revenue)
        $confirmed = (clone $base)
            ->whereIn('contract_status', ['signed', 'approved'])
            ->whereNotNull('contract_signed_at');

        $confirmedTotal = (float) (clone $confirmed)->sum('contract_amount_eur');

        // Talep edilmiş ama henüz imzalanmamış = bekleyen gelir
        $pendingTotal = (float) (clone $base)
            ->where('contract_status', 'requested')
            ->sum('contract_amount_eur');

        // İptal edilen
        $cancelledTotal = (float) (clone $base)
            ->where('contract_status', 'cancelled')
            ->sum('contract_amount_eur');

        // Tahsilat oranı = tahsil edilen / (tahsil edilen + bekleyen)
        $denominator  = $confirmedTotal + $pendingTotal;
        $collectionRate = $denominator > 0
            ? round($confirmedTotal / $denominator * 100, 1)
            : 0.0;

        // Aylık onaylı gelirler (son 12 ay)
        $monthlyRevenue = collect(range(11, 0))->mapWithKeys(function ($i) use ($confirmed) {
            $d     = now()->subMonths($i);
            $year  = $d->year;
            $month = $d->month;
            $amount = (float) (clone $confirmed)
                ->whereYear('contract_signed_at', $year)
                ->whereMonth('contract_signed_at', $month)
                ->sum('contract_amount_eur');
            return [$d->format('Y-m') => $amount];
        });

        // Durum kırılımı
        $statusBreakdown = (clone $base)
            ->selectRaw('contract_status, COUNT(*) as cnt, SUM(contract_amount_eur) as total')
            ->groupBy('contract_status')
            ->get();

        // Son 8 onaylı sözleşme
        $recentContracts = (clone $confirmed)
            ->select(['id','first_name','last_name','contract_amount_eur',
                      'contract_status','contract_signed_at','selected_package_title'])
            ->orderByDesc('contract_signed_at')
            ->limit(8)->get();

        return compact(
            'monthlyRevenue', 'confirmedTotal', 'pendingTotal',
            'cancelledTotal', 'collectionRate', 'statusBreakdown', 'recentContracts'
        );
    }

    // ── Dashboard ─────────────────────────────────────────────────────────────
    public function dashboard(Request $request)
    {
        $cid    = $this->companyId();
        $period = $request->query('period', now()->format('Y-m'));
        [$year, $month] = explode('-', $period);

        $base = CompanyFinanceEntry::when($cid > 0, fn($q) => $q->where('company_id', $cid));

        // Manuel kayıtlar — bu ay
        $manualIncome   = (float)(clone $base)->where('type','income')
            ->whereYear('entry_date',$year)->whereMonth('entry_date',$month)->sum('amount');
        $thisMonthExpense = (float)(clone $base)->where('type','expense')
            ->whereYear('entry_date',$year)->whereMonth('entry_date',$month)->sum('amount');

        // Sözleşme geliri — bu ay
        $contractThisMonth = (float) GuestApplication::query()
            ->whereIn('contract_status', ['signed','approved'])
            ->whereNotNull('contract_amount_eur')
            ->where('contract_amount_eur', '>', 0)
            ->when($cid > 0, fn($q) => $q->where('company_id', $cid))
            ->whereYear('contract_signed_at', $year)
            ->whereMonth('contract_signed_at', $month)
            ->sum('contract_amount_eur');

        $thisMonthIncome = $manualIncome + $contractThisMonth;

        // Yıllık toplamlar
        $yearManualIncome = (float)(clone $base)->where('type','income')->whereYear('entry_date',$year)->sum('amount');
        $yearExpense      = (float)(clone $base)->where('type','expense')->whereYear('entry_date',$year)->sum('amount');
        $yearContractIncome = (float) GuestApplication::query()
            ->whereIn('contract_status', ['signed','approved'])
            ->whereNotNull('contract_amount_eur')
            ->where('contract_amount_eur', '>', 0)
            ->when($cid > 0, fn($q) => $q->where('company_id', $cid))
            ->whereYear('contract_signed_at', $year)
            ->sum('contract_amount_eur');
        $yearIncome = $yearManualIncome + $yearContractIncome;

        // Sözleşme verisi (canlı)
        $cr = $this->contractRevenueData($cid);

        // Son 12 ay trend (manuel + sözleşme birleşik)
        $trend = collect(range(11, 0))->map(function ($i) use ($cid) {
            $d = now()->subMonths($i);
            $y  = $d->year; $mo = $d->month;
            $q  = CompanyFinanceEntry::when($cid > 0, fn($q) => $q->where('company_id', $cid))
                ->whereYear('entry_date', $y)->whereMonth('entry_date', $mo);
            $manInc  = (float)(clone $q)->where('type','income')->sum('amount');
            $expense = (float)(clone $q)->where('type','expense')->sum('amount');
            $contInc = (float) GuestApplication::query()
                ->whereIn('contract_status',['signed','approved'])
                ->whereNotNull('contract_amount_eur')->where('contract_amount_eur', '>', 0)
                ->when($cid > 0, fn($q) => $q->where('company_id', $cid))
                ->whereYear('contract_signed_at', $y)->whereMonth('contract_signed_at', $mo)
                ->sum('contract_amount_eur');
            return [
                'period'          => $d->format('Y-m'),
                'label'           => $d->locale('tr')->isoFormat('MMM YY'),
                'income'          => $manInc + $contInc,
                'contract_income' => $contInc,
                'manual_income'   => $manInc,
                'expense'         => $expense,
            ];
        });

        // Kategori gider dağılımı
        $expenseByCategory = (clone $base)->where('type','expense')
            ->whereYear('entry_date',$year)->whereMonth('entry_date',$month)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')->pluck('total','category');

        // Son 10 manuel kayıt
        $recentEntries = (clone $base)->orderByDesc('entry_date')->orderByDesc('id')->limit(10)->get();

        $periods = collect(range(0,11))->map(fn($i) => now()->subMonths($i)->format('Y-m'))->all();

        return view('manager.finance.dashboard', compact(
            'period','periods',
            'thisMonthIncome','thisMonthExpense',
            'contractThisMonth','manualIncome',
            'yearIncome','yearExpense',
            'trend','expenseByCategory','recentEntries',
            'cr'
        ));
    }

    // ── Raporlar & Projeksiyon ────────────────────────────────────────────────
    public function reports(Request $request)
    {
        $cid    = $this->companyId();
        $period = $request->get('period', '6m'); // 1m, 3m, 6m, 1y
        $months = match($period) { '1m' => 1, '3m' => 3, '1y' => 12, default => 6 };

        // ── Geçmiş dönem verileri ─────────────────────────────────────────────
        $history = collect(range($months - 1, 0))->map(function ($i) use ($cid) {
            $d = now()->subMonths($i);
            [$y, $mo] = [$d->year, $d->month];

            $q = CompanyFinanceEntry::when($cid > 0, fn($q) => $q->where('company_id', $cid))
                ->whereYear('entry_date', $y)->whereMonth('entry_date', $mo);

            $manInc  = (float)(clone $q)->where('type', 'income')->sum('amount');
            $expense = (float)(clone $q)->where('type', 'expense')->sum('amount');
            $contInc = (float) GuestApplication::whereIn('contract_status', ['signed', 'approved'])
                ->whereNotNull('contract_amount_eur')->where('contract_amount_eur', '>', 0)
                ->when($cid > 0, fn($q) => $q->where('company_id', $cid))
                ->whereYear('contract_signed_at', $y)->whereMonth('contract_signed_at', $mo)
                ->sum('contract_amount_eur');

            $income = $manInc + $contInc;

            // Geçen yıl aynı dönem
            $lyQ = CompanyFinanceEntry::when($cid > 0, fn($q) => $q->where('company_id', $cid))
                ->whereYear('entry_date', $y - 1)->whereMonth('entry_date', $mo);
            $lyManInc = (float)(clone $lyQ)->where('type', 'income')->sum('amount');
            $lyExp    = (float)(clone $lyQ)->where('type', 'expense')->sum('amount');
            $lyContInc = (float) GuestApplication::whereIn('contract_status', ['signed', 'approved'])
                ->whereNotNull('contract_amount_eur')->where('contract_amount_eur', '>', 0)
                ->when($cid > 0, fn($q) => $q->where('company_id', $cid))
                ->whereYear('contract_signed_at', $y - 1)->whereMonth('contract_signed_at', $mo)
                ->sum('contract_amount_eur');
            $lyIncome = $lyManInc + $lyContInc;

            return [
                'period'           => $d->format('Y-m'),
                'label'            => $d->locale('tr')->isoFormat('MMM YY'),
                'income'           => $income,
                'expense'          => $expense,
                'net'              => $income - $expense,
                'last_year_income' => $lyIncome,
                'last_year_exp'    => $lyExp,
                'yoy_change'       => $lyIncome > 0 ? round(($income - $lyIncome) / $lyIncome * 100, 1) : null,
            ];
        });

        $historyTotal = [
            'income'           => $history->sum('income'),
            'expense'          => $history->sum('expense'),
            'net'              => $history->sum('net'),
            'last_year_income' => $history->sum('last_year_income'),
        ];

        // ── Projeksiyon 1: Mevcut Sözleşme Pipeline ──────────────────────────
        $pendingContracts = GuestApplication::where('contract_status', 'requested')
            ->whereNotNull('contract_amount_eur')->where('contract_amount_eur', '>', 0)
            ->when($cid > 0, fn($q) => $q->where('company_id', $cid))
            ->select(['id', 'first_name', 'last_name', 'contract_amount_eur',
                      'contract_requested_at', 'selected_package_title'])
            ->orderByDesc('contract_requested_at')
            ->get();

        $pipelineTotal = (float) $pendingContracts->sum('contract_amount_eur');

        // Ortalama imzalama süresi (gün) — son 6 ay
        $isSqlite = config('database.default') === 'sqlite';
        $diffExpr = $isSqlite
            ? 'CAST((julianday(contract_signed_at) - julianday(contract_requested_at)) AS INTEGER) as avg_days'
            : 'AVG(DATEDIFF(contract_signed_at, contract_requested_at)) as avg_days';

        $avgTimeToSign = (float) GuestApplication::whereIn('contract_status', ['signed', 'approved'])
            ->whereNotNull('contract_signed_at')->whereNotNull('contract_requested_at')
            ->where('contract_signed_at', '>=', now()->subMonths(6))
            ->when($cid > 0, fn($q) => $q->where('company_id', $cid))
            ->selectRaw($isSqlite
                ? 'AVG(CAST((julianday(contract_signed_at) - julianday(contract_requested_at)) AS INTEGER)) as avg_days'
                : 'AVG(DATEDIFF(contract_signed_at, contract_requested_at)) as avg_days')
            ->value('avg_days') ?? 30;

        $avgTimeToSign = (int) max(7, min(120, $avgTimeToSign));

        // Pipeline dağılımı — önümüzdeki 3 ay
        $pipelineProjection = collect(range(1, 3))->map(function ($i) use ($pendingContracts, $avgTimeToSign) {
            $mStart = now()->addMonths($i)->startOfMonth();
            $mEnd   = now()->addMonths($i)->endOfMonth();

            $expected = $pendingContracts->filter(function ($c) use ($mStart, $mEnd, $avgTimeToSign) {
                if (!$c->contract_requested_at) return $i === 1; // no date → assume next month
                $est = Carbon::parse($c->contract_requested_at)->addDays($avgTimeToSign);
                return $est->between($mStart, $mEnd);
            })->sum('contract_amount_eur');

            return [
                'period'   => now()->addMonths($i)->format('Y-m'),
                'label'    => now()->addMonths($i)->locale('tr')->isoFormat('MMM YY'),
                'expected' => (float) $expected,
            ];
        });

        // Kalan (3 ayın dışında kalan) pipeline
        $pipelineUnscheduled = $pipelineTotal - $pipelineProjection->sum('expected');

        // ── Projeksiyon 2: Trend (doğrusal regresyon, son 6 ay) ──────────────
        $last6 = collect(range(5, 0))->map(function ($i) use ($cid) {
            $d = now()->subMonths($i);
            [$y, $mo] = [$d->year, $d->month];
            $q = CompanyFinanceEntry::when($cid > 0, fn($q) => $q->where('company_id', $cid))
                ->whereYear('entry_date', $y)->whereMonth('entry_date', $mo);
            $man  = (float)(clone $q)->where('type', 'income')->sum('amount');
            $cont = (float) GuestApplication::whereIn('contract_status', ['signed', 'approved'])
                ->whereNotNull('contract_amount_eur')->where('contract_amount_eur', '>', 0)
                ->when($cid > 0, fn($q) => $q->where('company_id', $cid))
                ->whereYear('contract_signed_at', $y)->whereMonth('contract_signed_at', $mo)
                ->sum('contract_amount_eur');
            return $man + $cont;
        })->values();

        $n = $last6->count(); // 6
        $xMean = ($n - 1) / 2.0;
        $yMean = $last6->avg() ?: 0;
        $num = 0; $den = 0;
        foreach ($last6 as $x => $y) {
            $num += ($x - $xMean) * ($y - $yMean);
            $den += ($x - $xMean) ** 2;
        }
        $slope     = $den > 0 ? $num / $den : 0;
        $intercept = $yMean - $slope * $xMean;

        $trendProjection = collect(range(1, 3))->map(function ($i) use ($n, $slope, $intercept) {
            return [
                'period'    => now()->addMonths($i)->format('Y-m'),
                'label'     => now()->addMonths($i)->locale('tr')->isoFormat('MMM YY'),
                'projected' => round(max(0, $intercept + $slope * ($n - 1 + $i)), 2),
            ];
        });

        // YoY büyüme bazlı projeksiyon
        $yoyRates = $history->filter(fn($m) => ($m['last_year_income'] ?? 0) > 0)
            ->map(fn($m) => ($m['yoy_change'] ?? 0) / 100.0);
        $avgYoyRate = $yoyRates->isNotEmpty() ? $yoyRates->avg() : 0;

        $yoyProjection = collect(range(1, 3))->map(function ($i) use ($cid, $avgYoyRate) {
            $d = now()->addMonths($i);
            [$y, $mo] = [$d->year - 1, $d->month];
            $q = CompanyFinanceEntry::when($cid > 0, fn($q) => $q->where('company_id', $cid))
                ->whereYear('entry_date', $y)->whereMonth('entry_date', $mo);
            $lyMan  = (float)(clone $q)->where('type', 'income')->sum('amount');
            $lyCont = (float) GuestApplication::whereIn('contract_status', ['signed', 'approved'])
                ->whereNotNull('contract_amount_eur')->where('contract_amount_eur', '>', 0)
                ->when($cid > 0, fn($q) => $q->where('company_id', $cid))
                ->whereYear('contract_signed_at', $y)->whereMonth('contract_signed_at', $mo)
                ->sum('contract_amount_eur');
            $lyIncome  = $lyMan + $lyCont;
            return [
                'period'    => $d->format('Y-m'),
                'label'     => $d->locale('tr')->isoFormat('MMM YY'),
                'last_year' => $lyIncome,
                'projected' => round(max(0, $lyIncome * (1 + $avgYoyRate)), 2),
            ];
        });

        return view('manager.finance.reports', compact(
            'period', 'months', 'history', 'historyTotal',
            'pendingContracts', 'pipelineTotal', 'avgTimeToSign',
            'pipelineProjection', 'pipelineUnscheduled',
            'trendProjection', 'yoyProjection', 'avgYoyRate',
            'slope', 'yMean'
        ));
    }

    // ── Kayıt Listesi ─────────────────────────────────────────────────────────
    public function entries(Request $request)
    {
        $cid      = $this->companyId();
        $type     = $request->query('type', '');
        $category = $request->query('category', '');
        $month    = $request->query('month', now()->format('Y-m'));

        [$year, $mo] = explode('-', $month);

        $entries = CompanyFinanceEntry::when($cid > 0, fn($q) => $q->where('company_id', $cid))
            ->whereYear('entry_date', $year)->whereMonth('entry_date', $mo)
            ->when($type !== '', fn($q) => $q->where('type', $type))
            ->when($category !== '', fn($q) => $q->where('category', $category))
            ->orderByDesc('entry_date')->orderByDesc('id')->get();

        $totalIncome  = $entries->where('type','income')->sum('amount');
        $totalExpense = $entries->where('type','expense')->sum('amount');

        // Sözleşme gelirleri bu ay (entries sayfasında da göster)
        $contractEntries = GuestApplication::query()
            ->whereIn('contract_status',['signed','approved'])
            ->whereNotNull('contract_amount_eur')->where('contract_amount_eur', '>', 0)
            ->when($cid > 0, fn($q) => $q->where('company_id', $cid))
            ->whereYear('contract_signed_at', $year)->whereMonth('contract_signed_at', $mo)
            ->select(['id','first_name','last_name','contract_amount_eur',
                      'contract_status','contract_signed_at','selected_package_title','selected_package_code'])
            ->orderByDesc('contract_signed_at')->get();

        $contractIncomeTotal = (float) $contractEntries->sum('contract_amount_eur');
        $totalIncome += $contractIncomeTotal;

        $months = collect(range(0,11))->map(fn($i) => now()->subMonths($i)->format('Y-m'))->all();

        return view('manager.finance.entries', compact(
            'entries','type','category','month','months',
            'totalIncome','totalExpense','contractEntries','contractIncomeTotal'
        ));
    }

    // ── CRUD ──────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $cid  = $this->companyId();
        $data = $request->validate([
            'entry_date'   => 'required|date',
            'type'         => 'required|in:income,expense',
            'category'     => 'required|string|max:60',
            'title'        => 'required|string|max:200',
            'amount'       => 'required|numeric|min:0.01',
            'currency'     => 'required|string|size:3',
            'reference_no' => 'nullable|string|max:100',
            'notes'        => 'nullable|string|max:1000',
        ]);

        CompanyFinanceEntry::create(array_merge($data, [
            'company_id' => $cid ?: null,
            'source'     => 'manual',
            'created_by' => auth()->id(),
        ]));

        return back()->with('status', 'Kayıt eklendi.');
    }

    public function update(Request $request, CompanyFinanceEntry $entry)
    {
        $cid = $this->companyId();
        abort_if($cid > 0 && (int) $entry->company_id !== $cid, 403);

        $data = $request->validate([
            'entry_date'   => 'required|date',
            'type'         => 'required|in:income,expense',
            'category'     => 'required|string|max:60',
            'title'        => 'required|string|max:200',
            'amount'       => 'required|numeric|min:0.01',
            'currency'     => 'required|string|size:3',
            'reference_no' => 'nullable|string|max:100',
            'notes'        => 'nullable|string|max:1000',
        ]);

        $entry->update($data);
        return back()->with('status', 'Kayıt güncellendi.');
    }

    public function destroy(CompanyFinanceEntry $entry)
    {
        $cid = $this->companyId();
        abort_if($cid > 0 && (int) $entry->company_id !== $cid, 403);
        $entry->delete();
        return back()->with('status', 'Kayıt silindi.');
    }

    // ── CSV Import ────────────────────────────────────────────────────────────
    public function importCsv(Request $request)
    {
        $request->validate(['csv_file' => 'required|file|mimes:csv,txt|max:2048']);
        $cid    = $this->companyId();
        $handle = fopen($request->file('csv_file')->getRealPath(), 'r');
        fgetcsv($handle, 0, ';'); // header
        $imported = 0; $skipped = 0;

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) < 5) { $skipped++; continue; }
            [$date,$title,$amount,$currency,$type] = array_map('trim', $row);
            $refNo    = $row[5] ?? null;
            $txId     = $row[6] ?? null;
            $category = $row[7] ?? 'other_expense';

            if ($txId && CompanyFinanceEntry::where('bank_transaction_id', $txId)->exists()) {
                $skipped++; continue;
            }

            $parsed = (float) str_replace(['.', ','], ['', '.'], $amount);
            if ($parsed <= 0) { $skipped++; continue; }

            CompanyFinanceEntry::create([
                'company_id'          => $cid ?: null,
                'entry_date'          => $date,
                'type'                => in_array(strtolower($type), ['income','gelir']) ? 'income' : 'expense',
                'category'            => $category,
                'title'               => substr($title, 0, 200),
                'amount'              => $parsed,
                'currency'            => strtoupper($currency) ?: 'EUR',
                'reference_no'        => $refNo,
                'source'              => 'bank_import',
                'bank_transaction_id' => $txId,
                'created_by'          => auth()->id(),
            ]);
            $imported++;
        }
        fclose($handle);
        return back()->with('status', "{$imported} kayıt aktarıldı, {$skipped} atlandı.");
    }
}
