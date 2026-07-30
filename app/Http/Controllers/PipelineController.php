<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PipelineController extends Controller
{
    public function index(Request $request)
    {
        // Stage utama yang dipakai di pipeline. Stage "Closing" lama tetap dihitung ke Won/Closing.
        $stageDefinitions = [
            'Identifying' => ['Identifying'],
            'Approaching' => ['Approaching'],
            'Follow Up'   => ['Follow Up'],
            'Won'         => ['Won', 'Closing'],
            'Maintaining' => ['Maintaining'],
        ];

        $pipeline = [];
        foreach ($stageDefinitions as $label => $dbStages) {
            $pipeline[$label] = Lead::whereIn('pipeline_stage', $dbStages)
                ->with(['customer', 'salesUser'])
                ->orderBy('updated_at', 'desc')
                ->get();
        }

        $activeStages = collect($stageDefinitions)->flatten()->all();
        $totalValue = Lead::whereIn('pipeline_stage', $activeStages)->sum('potensi_revenue');
        $totalLeads = Lead::count();
        $potentialDeals = Lead::whereIn('pipeline_stage', ['Follow Up', 'Won', 'Closing'])->count();

        $closedLeadCount = Lead::whereIn('pipeline_stage', ['Won', 'Closing', 'Maintaining'])
            ->whereHas('purchaseOrders')
            ->count();
        $winRate = $totalLeads > 0 ? round(($closedLeadCount / $totalLeads) * 100, 1) : 0;

        // Revenue deal closed sebaiknya dari PO Done aktual, bukan potensi_revenue lead.
        $expectedRevenue = PurchaseOrder::with('items')
            ->where('status', 'Done')
            ->where('currency', 'IDR')
            ->get()
            ->sum(fn ($po) => $po->total_revenue);

        $topSales = User::assignable()
            ->select('users.*')
            ->selectSub(function ($q) {
                $q->from('purchase_orders')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('purchase_orders.user_id', 'users.id')
                    ->where('purchase_orders.status', 'Done');
            }, 'deals_closed')
            ->orderByDesc('deals_closed')
            ->take(5)
            ->get();

        // Grafik summary & trend memakai revenue PO Done aktual.
        // Jika PO tidak menyimpan lead_id, sistem tetap mencoba mencocokkan lewat customer_id.
        $stageRevenue = array_fill_keys(array_keys($stageDefinitions), 0.0);
        $doneOrders = PurchaseOrder::with(['items', 'lead'])
            ->where('status', 'Done')
            ->where('currency', 'IDR')
            ->get();

        foreach ($doneOrders as $po) {
            $stage = $po->lead?->pipeline_stage;

            if (!$stage && $po->customer_id) {
                $stage = Lead::where('customer_id', $po->customer_id)
                    ->orderByDesc('updated_at')
                    ->value('pipeline_stage');
            }

            $stageLabel = $stage === 'Closing' ? 'Won' : $stage;
            if (array_key_exists($stageLabel, $stageRevenue)) {
                $stageRevenue[$stageLabel] += (float) $po->total_revenue;
            }
        }

        $pipelineChartLabels = array_keys($stageRevenue);
        $pipelineChartValues = array_map(
            fn ($value) => round((float) $value / 1000000, 2),
            array_values($stageRevenue)
        );

        $trendLabels = [];
        $trendData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthlyRevenue = PurchaseOrder::with('items')
                ->where('status', 'Done')
                ->where('currency', 'IDR')
                ->whereYear('order_date', $month->year)
                ->whereMonth('order_date', $month->month)
                ->get()
                ->sum(fn ($po) => $po->total_revenue);

            $trendLabels[] = $month->format('M Y');
            $trendData[] = round((float) $monthlyRevenue / 1000000, 2);
        }

        return view('pipeline.index', compact(
            'pipeline',
            'totalValue',
            'totalLeads',
            'potentialDeals',
            'winRate',
            'expectedRevenue',
            'topSales',
            'stageRevenue',
            'pipelineChartLabels',
            'pipelineChartValues',
            'trendLabels',
            'trendData'
        ));
    }
}
