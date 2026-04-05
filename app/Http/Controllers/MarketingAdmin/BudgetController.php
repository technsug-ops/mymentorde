<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\Marketing\MarketingBudget;
use App\Models\MarketingCampaign;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $periodFilter = $this->normalizePeriod((string) $request->query('period', ''));

        $query = MarketingBudget::query()->orderByDesc('period');
        if ($periodFilter !== null) {
            $query->where('period', $periodFilter);
        }

        $budgets = $query->paginate(18)->withQueryString();
        $selected = $budgets->first();

        $totals = [
            'budget' => (float) $budgets->getCollection()->sum('total_budget'),
            'spent' => (float) $budgets->getCollection()->sum('total_spent'),
            'remaining' => (float) $budgets->getCollection()->sum('total_remaining'),
        ];

        return view('marketing-admin.budget.index', [
            'pageTitle' => 'Butce Yonetimi',
            'title' => 'Butce Dagilimi',
            'budgets' => $budgets,
            'selected' => $selected,
            'totals' => $totals,
            'periodFilter' => $periodFilter ?? '',
        ]);
    }

    public function show(string $period)
    {
        $normalized = $this->normalizePeriod($period);
        abort_if($normalized === null, 404);

        return redirect('/mktg-admin/budget?period='.urlencode($normalized));
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);
        [$spent, $remaining] = $this->calculateSpentAndRemaining($data['period'], (float) $data['total_budget']);

        MarketingBudget::query()->updateOrCreate(
            ['period' => (string) $data['period']],
            [
                'total_budget' => (float) $data['total_budget'],
                'currency' => (string) $data['currency'],
                'allocations' => $data['allocations'],
                'total_spent' => $spent,
                'total_remaining' => $remaining,
                'approved_by' => optional($request->user())->id,
            ]
        );

        return redirect('/mktg-admin/budget?period='.urlencode((string) $data['period']))
            ->with('status', "Butce kaydedildi ({$data['period']})");
    }

    public function update(Request $request, string $period)
    {
        $normalized = $this->normalizePeriod($period);
        abort_if($normalized === null, 404);

        $row = MarketingBudget::query()->where('period', $normalized)->firstOrFail();
        $data = $this->validatePayload($request, $normalized);
        [$spent, $remaining] = $this->calculateSpentAndRemaining($data['period'], (float) $data['total_budget']);

        $row->update([
            'period' => (string) $data['period'],
            'total_budget' => (float) $data['total_budget'],
            'currency' => (string) $data['currency'],
            'allocations' => $data['allocations'],
            'total_spent' => $spent,
            'total_remaining' => $remaining,
            'approved_by' => optional($request->user())->id,
        ]);

        return redirect('/mktg-admin/budget?period='.urlencode((string) $data['period']))
            ->with('status', "Butce guncellendi ({$data['period']})");
    }

    private function validatePayload(Request $request, ?string $currentPeriod = null): array
    {
        $data = $request->validate([
            'period' => ['required', 'string', 'max:16'],
            'total_budget' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:3'],
            'allocations_json' => ['nullable', 'string', 'max:8000'],
        ]);

        $period = $this->normalizePeriod((string) $data['period']);
        if ($period === null) {
            throw ValidationException::withMessages([
                'period' => 'Donem formati YYYY-MM olmali.',
            ]);
        }
        $data['period'] = $period;
        $data['currency'] = strtoupper(trim((string) ($data['currency'] ?? 'EUR'))) ?: 'EUR';

        $allocations = $this->parseAllocations((string) ($data['allocations_json'] ?? ''));
        $data['allocations'] = $allocations;

        if ($currentPeriod !== null && $currentPeriod !== $period) {
            $exists = MarketingBudget::query()->where('period', $period)->exists();
            if ($exists) {
                throw ValidationException::withMessages([
                    'period' => 'Bu donem zaten kayitli.',
                ]);
            }
        }

        return $data;
    }

    /**
     * @return array{0:float,1:float}
     */
    private function calculateSpentAndRemaining(string $period, float $budget): array
    {
        $start = Carbon::parse($period.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $spent = (float) MarketingCampaign::query()
            ->where(function ($q) use ($start, $end): void {
                $q->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhere(function ($qq) use ($start, $end): void {
                        $qq->whereNull('start_date')->whereBetween('created_at', [$start, $end]);
                    });
            })
            ->sum('spent_amount');

        $remaining = round($budget - $spent, 2);

        return [round($spent, 2), $remaining];
    }

    /**
     * @return array<string, float>
     */
    private function parseAllocations(string $allocationsJson): array
    {
        $raw = trim($allocationsJson);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw ValidationException::withMessages([
                'allocations_json' => 'Allocation JSON formati gecersiz.',
            ]);
        }

        return collect($decoded)
            ->mapWithKeys(function ($value, $key): array {
                $k = trim((string) $key);
                $v = is_numeric($value) ? round((float) $value, 2) : null;
                if ($k === '' || $v === null) {
                    return [];
                }
                return [$k => max(0, $v)];
            })
            ->all();
    }

    private function normalizePeriod(string $period): ?string
    {
        $raw = trim($period);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}$/', $raw) !== 1) {
            try {
                $parsed = Carbon::parse($raw);
                return $parsed->format('Y-m');
            } catch (\Throwable) {
                return null;
            }
        }

        [$year, $month] = explode('-', $raw);
        $m = (int) $month;
        if ($m < 1 || $m > 12) {
            return null;
        }

        return sprintf('%04d-%02d', (int) $year, $m);
    }
}
