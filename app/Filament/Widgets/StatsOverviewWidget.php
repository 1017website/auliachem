<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\PurchaseOrder;
use App\Models\SalesActivity;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $totalRevenue  = PurchaseOrder::sum('total_amount');
        $grossProfit   = PurchaseOrder::sum('gross_profit');
        $totalExpenses = Expense::sum('amount');
        $nettProfit    = $grossProfit - $totalExpenses;

        $prospects   = Customer::where('status', 'prospect')->count();
        $active      = Customer::where('status', 'active')->count();
        $closing     = SalesActivity::where('stage', 'closing')
                         ->whereIn('id', SalesActivity::query()
                             ->selectRaw('MAX(id)')
                             ->groupBy('customer_id'))
                         ->count();
        $customers   = Customer::count();
        $poThisMonth = PurchaseOrder::whereMonth('po_date', now()->month)->count();

        return [
            Stat::make('Total Revenue', 'Rp ' . number_format($totalRevenue, 0, ',', '.'))
                ->description('Semua Purchase Orders')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([40, 55, 45, 70, 60, 85, $poThisMonth * 5]),

            Stat::make('Gross Profit', 'Rp ' . number_format($grossProfit, 0, ',', '.'))
                ->description('Revenue - COGS')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary')
                ->chart([30, 42, 38, 55, 48, 65, round($grossProfit / max($totalRevenue, 1) * 100)]),

            Stat::make('Nett Profit', 'Rp ' . number_format($nettProfit, 0, ',', '.'))
                ->description($totalExpenses > 0 ? 'Setelah dikurangi expenses' : 'Belum ada data expense')
                ->descriptionIcon($nettProfit >= 0 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($nettProfit >= 0 ? 'success' : 'danger'),

            Stat::make('Prospects', $prospects)
                ->description($closing . ' customer sedang closing')
                ->descriptionIcon('heroicon-m-funnel')
                ->color('warning')
                ->chart([5, 8, 6, 10, 8, 12, $prospects]),

            Stat::make('Active Customers', $active)
                ->description('Total ' . $customers . ' customers')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('success'),

            Stat::make('PO Bulan Ini', $poThisMonth)
                ->description('Purchase Orders ' . now()->format('M Y'))
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),
        ];
    }
}