<?php
namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Customer;
use App\Models\Activity;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $now        = Carbon::now();
        $startMonth = $now->copy()->startOfMonth();
        $endMonth   = $now->copy()->endOfMonth();
        $startPrev  = $now->copy()->subMonth()->startOfMonth();
        $endPrev    = $now->copy()->subMonth()->endOfMonth();
        $prevLabel  = $now->copy()->subMonth()->format('M Y');

        // ── KPI bulan ini ──
        $donePOs    = PurchaseOrder::with('items')->whereBetween('order_date', [$startMonth, $endMonth])->where('status','Done')->where('currency','IDR')->get();
        $revenue    = $donePOs->sum(fn($po) => $po->total_revenue);
        $totalPo    = PurchaseOrder::whereBetween('order_date', [$startMonth, $endMonth])->count();
        $activeLeads = Lead::whereNotIn('pipeline_stage', ['Won','Lost'])->count();
        $dealClosed  = Lead::where('pipeline_stage','Won')->whereBetween('updated_at', [$startMonth, $endMonth])->count();
        $totalLeads  = Lead::count();
        $conversionRate = $totalLeads > 0 ? round(($dealClosed / max($totalLeads, 1)) * 100, 1) : 0;

        // ── KPI bulan lalu (growth %) ──
        $donePOsPrev    = PurchaseOrder::with('items')->whereBetween('order_date', [$startPrev, $endPrev])->where('status','Done')->where('currency','IDR')->get();
        $revenuePrev    = $donePOsPrev->sum(fn($po) => $po->total_revenue);
        $totalPoPrev    = PurchaseOrder::whereBetween('order_date', [$startPrev, $endPrev])->count();
        $dealClosedPrev = Lead::where('pipeline_stage','Won')->whereBetween('updated_at', [$startPrev, $endPrev])->count();
        $activeLeadsPrev = Lead::whereNotIn('pipeline_stage', ['Won','Lost'])->where('created_at','<',$startMonth)->count();

        $growth = fn($now, $prev) => $prev > 0 ? round((($now - $prev) / $prev) * 100, 1) : ($now > 0 ? 100 : 0);
        $revenueGrowth = $growth($revenue, $revenuePrev);
        $doGrowth      = $growth($totalPo, $totalPoPrev);
        $dealGrowth    = $growth($dealClosed, $dealClosedPrev);
        $leadsGrowth   = $growth($activeLeads, $activeLeadsPrev);

        // ── Pipeline by stage ── (samakan dengan menu Pipeline)
        $pipelineStages = [
            'Identifying' => Lead::where('pipeline_stage','Identifying')->get(),
            'Approaching' => Lead::where('pipeline_stage','Approaching')->get(),
            'Follow Up'   => Lead::where('pipeline_stage','Follow Up')->get(),
            'Won'         => Lead::where('pipeline_stage','Won')->get(),
            'Maintaining' => Lead::where('pipeline_stage','Maintaining')->get(),
        ];

        // ── Today reminders ──
        $todayReminders = Activity::where(function($q) {
                $q->whereDate('activity_at', today())->where('status','!=','Done');
            })->orWhere('status','Overdue')
            ->with(['lead','customer','salesUser'])
            ->orderBy('activity_at')
            ->limit(5)->get();

        // ── Recent activities ──
        $recentActivities = Activity::with(['lead','customer','salesUser'])
            ->orderBy('activity_at','desc')->limit(5)->get();

        // ── Top sales ──
        $topSales = User::withCount(['leads as deals_closed' => fn($q) => $q->where('pipeline_stage','Won')])
            ->get()->sortByDesc('deals_closed')->take(5);

        // ── Revenue chart (30 hari terakhir) ──
        $revenueChart = [];
        for ($i = 30; $i >= 0; $i--) {
            $date    = $now->copy()->subDays($i);
            $dayPOs  = PurchaseOrder::with('items')->whereDate('order_date', $date)->where('status','Done')->where('currency','IDR')->get();
            $revenueChart[] = [
                'date'  => $date->format('d M'),
                'value' => $dayPOs->sum(fn($po) => $po->total_revenue),
            ];
        }

        // ── Volume PO chart (30 hari terakhir) ──
        $volumeChart = [];
        for ($i = 30; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $volumeChart[] = [
                'date'  => $date->format('d M'),
                'value' => PurchaseOrder::whereDate('order_date', $date)->count(),
            ];
        }

        // ── Trend closing chart ──
        $trendWon  = [];
        $trendLost = [];
        for ($i = 30; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $trendWon[]  = ['date' => $date->format('d M'), 'value' => Lead::where('pipeline_stage','Won')->whereDate('updated_at', $date)->count()];
            $trendLost[] = ['date' => $date->format('d M'), 'value' => Lead::where('pipeline_stage','Lost')->whereDate('updated_at', $date)->count()];
        }

        // alias untuk kompatibilitas view (totalDo → totalPo)
        $totalDo = $totalPo;

        return view('dashboard.index', compact(
            'revenue','totalDo','totalPo','activeLeads','dealClosed','conversionRate',
            'revenueGrowth','doGrowth','dealGrowth','leadsGrowth','prevLabel',
            'pipelineStages','todayReminders','recentActivities','topSales',
            'revenueChart','volumeChart','trendWon','trendLost'
        ));
    }
}