<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\PurchaseOrder;
use App\Models\SalesActivity;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StatsOverviewWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Range periode dari filter page; default = 6 bulan terakhir
        [$start, $end] = $this->getPeriodRange();

        // ===== Aggregates pada periode =====
        $totalRevenue  = (float) PurchaseOrder::whereBetween('po_date', [$start, $end])->sum('total_amount');
        $grossProfit   = (float) PurchaseOrder::whereBetween('po_date', [$start, $end])->sum('gross_profit');
        $totalExpenses = (float) Expense::whereBetween('expense_date', [$start, $end])->sum('amount');
        $nettProfit    = $grossProfit - $totalExpenses;
        $poCount       = PurchaseOrder::whereBetween('po_date', [$start, $end])->count();

        // ===== Customer & pipeline =====
        $prospects = Customer::where('status', 'prospect')->count();
        $active    = Customer::where('status', 'active')->count();
        $customers = Customer::count();

        $closing = SalesActivity::where('stage', 'closing')
            ->whereIn('id', SalesActivity::query()
                ->selectRaw('MAX(id)')
                ->groupBy('customer_id'))
            ->count();

        // ===== Real trend chart (12 minggu / 6 bulan tergantung range) =====
        $revenueTrend = $this->buildTrend($start, $end, 'po_date', 'total_amount', PurchaseOrder::class);
        $profitTrend  = $this->buildTrend($start, $end, 'po_date', 'gross_profit', PurchaseOrder::class);
        $poTrend      = $this->buildTrend($start, $end, 'po_date', null, PurchaseOrder::class);

        return [
            Stat::make('Total Revenue', 'Rp ' . number_format($totalRevenue, 0, ',', '.'))
                ->description('Periode: ' . $start->format('d M Y') . ' - ' . $end->format('d M Y'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart($revenueTrend),

            Stat::make('Gross Profit', 'Rp ' . number_format($grossProfit, 0, ',', '.'))
                ->description($this->marginLabel($grossProfit, $totalRevenue))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary')
                ->chart($profitTrend),

            Stat::make('Nett Profit', 'Rp ' . number_format($nettProfit, 0, ',', '.'))
                ->description('Expenses: Rp ' . number_format($totalExpenses, 0, ',', '.'))
                ->descriptionIcon($nettProfit >= 0 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($nettProfit >= 0 ? 'success' : 'danger'),

            Stat::make('Volume PO', $poCount)
                ->description('Total purchase order pada periode')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info')
                ->chart($poTrend),

            Stat::make('Prospects', $prospects)
                ->description($closing . ' customer di stage closing')
                ->descriptionIcon('heroicon-m-funnel')
                ->color('warning'),

            Stat::make('Active Customers', $active)
                ->description('Total ' . $customers . ' customers terdaftar')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('success'),
        ];
    }

    /**
     * Bangun array data trend untuk sparkline chart.
     * Jika $sumColumn null -> hitung COUNT(*).
     */
    protected function buildTrend(Carbon $start, Carbon $end, string $dateColumn, ?string $sumColumn, string $modelClass): array
    {
        $months = $start->diffInMonths($end) + 1;
        $points = [];

        $cursor = $start->copy()->startOfMonth();
        for ($i = 0; $i < $months; $i++) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd   = $cursor->copy()->endOfMonth();

            $query = $modelClass::whereBetween($dateColumn, [$monthStart, $monthEnd]);
            $value = $sumColumn ? (float) $query->sum($sumColumn) : (int) $query->count();

            $points[] = $value;
            $cursor->addMonth();
        }

        // Filament butuh minimal 2 titik agar chart muncul rapi
        return count($points) >= 2 ? $points : [...$points, 0];
    }

    protected function marginLabel(float $profit, float $revenue): string
    {
        if ($revenue <= 0) {
            return 'Belum ada revenue pada periode';
        }
        $margin = ($profit / $revenue) * 100;
        return 'Margin ' . number_format($margin, 1) . '% dari revenue';
    }

    /**
     * Ambil range periode dari filter page (start_date, end_date).
     * Default: 6 bulan terakhir (termasuk bulan ini).
     */
    protected function getPeriodRange(): array
    {
        $startDate = $this->filters['start_date'] ?? null;
        $endDate   = $this->filters['end_date'] ?? null;

        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->subMonths(5)->startOfMonth();
        $end   = $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfMonth();

        return [$start, $end];
    }
}
