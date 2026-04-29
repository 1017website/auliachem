<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrder;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TopCustomersWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Top 5 Customer berdasarkan Revenue';
    protected static ?int $sort = 6;
    protected int|string|array $columnSpan = ['md' => 1];
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        [$start, $end] = $this->getPeriodRange();

        $rows = PurchaseOrder::query()
            ->join('customers', 'purchase_orders.customer_id', '=', 'customers.id')
            ->whereBetween('purchase_orders.po_date', [$start, $end])
            ->groupBy('customers.id', 'customers.company_name')
            ->select('customers.company_name as name', DB::raw('SUM(purchase_orders.total_amount) as total'))
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        if ($rows->isEmpty()) {
            return [
                'datasets' => [[
                    'label' => 'Revenue',
                    'data' => [0],
                    'backgroundColor' => 'rgba(148, 163, 184, 0.3)',
                ]],
                'labels' => ['Belum ada data'],
            ];
        }

        return [
            'datasets' => [[
                'label'           => 'Revenue',
                'data'            => $rows->pluck('total')->map(fn ($v) => (float) $v)->toArray(),
                'backgroundColor' => 'rgba(16, 185, 129, 0.7)',
                'borderColor'     => 'rgb(16, 185, 129)',
                'borderWidth'     => 1,
            ]],
            'labels' => $rows->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'x' => [
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
