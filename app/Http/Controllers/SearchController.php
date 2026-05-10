<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $q = trim($request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $results = collect();

        // Search Leads
        $leads = Lead::where('company_name', 'like', "%$q%")
            ->orWhere('pic_name', 'like', "%$q%")
            ->orWhere('service_type', 'like', "%$q%")
            ->limit(4)->get();

        foreach ($leads as $lead) {
            $results->push([
                'type'     => 'lead',
                'title'    => $lead->company_name,
                'subtitle' => $lead->pipeline_stage . ' · ' . ($lead->service_type ?? '-'),
                'url'      => route('leads.show', $lead->id),
            ]);
        }

        // Search Customers
        $customers = Customer::where('company_name', 'like', "%$q%")
            ->orWhere('pic_name', 'like', "%$q%")
            ->limit(3)->get();

        foreach ($customers as $customer) {
            $results->push([
                'type'     => 'customer',
                'title'    => $customer->company_name,
                'subtitle' => ($customer->industry ?? '') . ' · ' . $customer->status,
                'url'      => route('customers.index', ['selected_id' => $customer->id]),
            ]);
        }

        // Search Vendors
        $suppliers = Supplier::where('supplier_name', 'like', "%$q%")
            ->orWhere('pic_name', 'like', "%$q%")
            ->limit(2)->get();

        foreach ($suppliers as $supplier) {
            $results->push([
                'type'     => 'supplier',
                'title'    => $supplier->supplier_name,
                'subtitle' => $supplier->source_type . ' · ' . $supplier->status,
                'url'      => route('suppliers.index'),
            ]);
        }

        return response()->json($results->take(8)->values());
    }
}
