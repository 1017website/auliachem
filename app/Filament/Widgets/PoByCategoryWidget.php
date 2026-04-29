<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrder;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PoByCategoryWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Distribusi Revenue per Kategori Produk';
    protected static ?int $sort = 5;
    protected int|string|array $columnSpan = ['md' => 1];
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        [$start, $end] = $this->getPeriodRange();

        $rows = PurchaseOrder::query()
            ->join('product_categories', 'purchase_orders.product_category_id', '=', 'product_categories.id')
            ->whereBetween('purchase_orders.po_date', [$start, $end])
            ->groupBy('product_categories.id', 'product_categories.name')
            ->select('product_categories.name as category', DB::raw('SUM(purchase_orders.total_amount) as total'))
            ->orderByDesc('total')
            ->get();

        if ($rows->isEmpty()) {
            return [
                'datasets' => [[
                    'label' => 'Revenue',
                    'data' => [1],
                    'backgroundColor' => ['rgba(148, 163, 184, 0.3)'],
                ]],
                'labels' => ['Belum ada data'],
            ];
        }

        $palette = [
            'rgba(99, 102, 241, 0.85)',
            'rgba(16, 185, 129, 0.85)',
            'rgba(245, 158, 11, 0.85)',
            'rgba(239, 68, 68, 0.85)',
            'rgba(14, 165, 233, 0.85)',
            'rgba(168, 85, 247, 0.85)',
            'rgba(236, 72, 153, 0.85)',
        ];

        return [
            'datasets' => [[
                'label'           => 'Revenue',
                'data'            => $rows->pluck('total')->map(fn ($v) => (float) $v)->toArray(),
                'backgroundColor' => $rows->values()->map(fn ($_, $i) => $palette[$i % count($palette)])->toArray(),
                'borderWidth'     => 0,
            ]],
            'labels' => $rows->pluck('category')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
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
