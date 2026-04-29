<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\PoByCategoryWidget;
use App\Filament\Widgets\PoVolumeChartWidget;
use App\Filament\Widgets\RecentActivitiesWidget;
use App\Filament\Widgets\RevenueChartWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\TopCustomersWidget;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Illuminate\Support\Carbon;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Dashboard Analisa';
    protected static ?string $navigationLabel = 'Dashboard';

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filter Periode')
                    ->description('Pilih rentang tanggal untuk semua chart & statistik di bawah')
                    ->icon('heroicon-o-funnel')
                    ->schema([
                        Select::make('preset')
                            ->label('Preset Periode')
                            ->options([
                                'this_month'   => 'Bulan Ini',
                                'last_month'   => 'Bulan Lalu',
                                'last_3'       => '3 Bulan Terakhir',
                                'last_6'       => '6 Bulan Terakhir',
                                'ytd'          => 'Year to Date',
                                'last_year'    => 'Tahun Lalu',
                                'custom'       => 'Custom',
                            ])
                            ->default('last_6')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                [$start, $end] = $this->presetToRange($state);
                                if ($start && $end) {
                                    $set('start_date', $start->toDateString());
                                    $set('end_date', $end->toDateString());
                                }
                            }),
                        DatePicker::make('start_date')
                            ->label('Dari Tanggal')
                            ->default(now()->subMonths(5)->startOfMonth()),
                        DatePicker::make('end_date')
                            ->label('Sampai Tanggal')
                            ->default(now()->endOfMonth()),
                    ])
                    ->columns(3),
            ]);
    }

    public function getWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
            RevenueChartWidget::class,
            PoVolumeChartWidget::class,
            PoByCategoryWidget::class,
            TopCustomersWidget::class,
            RecentActivitiesWidget::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 2;
    }

    protected function presetToRange(string $preset): array
    {
        return match ($preset) {
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'last_3'     => [now()->subMonths(2)->startOfMonth(), now()->endOfMonth()],
            'last_6'     => [now()->subMonths(5)->startOfMonth(), now()->endOfMonth()],
            'ytd'        => [now()->startOfYear(), now()->endOfDay()],
            'last_year'  => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
            default      => [null, null],
        };
    }
}
