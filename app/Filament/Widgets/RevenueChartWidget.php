<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\PurchaseOrder;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class RevenueChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Trend Revenue, Gross Profit & Nett Profit';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $maxHeight = '320px';

    protected function getData(): array
    {
        [$start, $end] = $this->getPeriodRange();

        $months = $start->diffInMonths($end) + 1;

        $labels   = [];
        $revenue  = [];
        $gross    = [];
        $nett     = [];

        $cursor = $start->copy()->startOfMonth();
        for ($i = 0; $i < $months; $i++) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd   = $cursor->copy()->endOfMonth();

            $monthRevenue = (float) PurchaseOrder::whereBetween('po_date', [$monthStart, $monthEnd])->sum('total_amount');
            $monthGross   = (float) PurchaseOrder::whereBetween('po_date', [$monthStart, $monthEnd])->sum('gross_profit');
            $monthExpense = (float) Expense::whereBetween('expense_date', [$monthStart, $monthEnd])->sum('amount');

            $labels[]  = $cursor->isoFormat('MMM YY');
            $revenue[] = $monthRevenue;
            $gross[]   = $monthGross;
            $nett[]    = $monthGross - $monthExpense;

            $cursor->addMonth();
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Revenue',
                    'data'            => $revenue,
                    'borderColor'     => 'rgb(16, 185, 129)',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.15)',
                    'tension'         => 0.35,
                    'fill'            => true,
                ],
                [
                    'label'           => 'Gross Profit',
                    'data'            => $gross,
                    'borderColor'     => 'rgb(99, 102, 241)',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.15)',
                    'tension'         => 0.35,
                    'fill'            => true,
                ],
                [
                    'label'           => 'Nett Profit',
                    'data'            => $nett,
                    'borderColor'     => 'rgb(245, 158, 11)',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.15)',
                    'tension'         => 0.35,
                    'fill'            => false,
                    'borderDash'      => [6, 4],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display'  => true,
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value){ return "Rp " + value.toLocaleString("id-ID"); }',
                    ],
                ],
            ],
        ];
    }

    protected function getPeriodRange(): array
    {
        $startDate = $this->filters['start_date'] ?? null;
        $endDate   = $this->filters['end_date'] ?? null;

        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->subMonths(5)->startOfMonth();
        $end   = $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfMonth();

        return [$start, $end];
    }
}
