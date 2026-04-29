<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrder;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PoVolumeChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Volume Purchase Order per Bulan';
    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        [$start, $end] = $this->getPeriodRange();

        $months = $start->diffInMonths($end) + 1;
        $labels = [];
        $counts = [];
        $values = [];

        $cursor = $start->copy()->startOfMonth();
        for ($i = 0; $i < $months; $i++) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd   = $cursor->copy()->endOfMonth();

            $labels[] = $cursor->isoFormat('MMM YY');

            $counts[] = PurchaseOrder::whereBetween('po_date', [$monthStart, $monthEnd])->count();
            $values[] = (float) PurchaseOrder::whereBetween('po_date', [$monthStart, $monthEnd])->sum('total_amount');

            $cursor->addMonth();
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Jumlah PO',
                    'data'            => $counts,
                    'backgroundColor' => 'rgba(99, 102, 241, 0.7)',
                    'borderColor'     => 'rgb(99, 102, 241)',
                    'borderWidth'     => 1,
                    'yAxisID'         => 'y',
                ],
                [
                    'label'           => 'Nilai PO (Rp)',
                    'type'            => 'line',
                    'data'            => $values,
                    'borderColor'     => 'rgb(16, 185, 129)',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'tension'         => 0.35,
                    'yAxisID'         => 'y1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
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
                    'type'        => 'linear',
                    'position'    => 'left',
                    'beginAtZero' => true,
                    'title'       => ['display' => true, 'text' => 'Jumlah PO'],
                ],
                'y1' => [
                    'type'        => 'linear',
                    'position'    => 'right',
                    'beginAtZero' => true,
                    'grid'        => ['drawOnChartArea' => false],
                    'title'       => ['display' => true, 'text' => 'Nilai (Rp)'],
                    'ticks'       => [
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
