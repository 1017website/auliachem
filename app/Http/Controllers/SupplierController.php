<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $sourceType         = $request->get('source_type');
        $status             = $request->get('status');
        $relationshipStatus = $request->get('relationship_status');
        $search             = $request->get('search');

        $query = Supplier::with('purchaseOrders');
        if ($sourceType         && $sourceType         !== 'all') $query->where('source_type', $sourceType);
        if ($status             && $status             !== 'all') $query->where('status', $status);
        if ($relationshipStatus && $relationshipStatus !== 'all') $query->where('relationship_status', $relationshipStatus);
        if ($search) {
            $query->where(fn($q) => $q
                ->where('supplier_name', 'like', "%$search%")
                ->orWhere('pic_name',    'like', "%$search%")
                ->orWhere('phone',       'like', "%$search%")
                ->orWhere('product_category', 'like', "%$search%")
            );
        }

        $suppliers           = $query->orderBy('is_preferred', 'desc')->orderBy('rating', 'desc')->paginate(10)->withQueryString();
        $totalSupplier       = Supplier::count();
        $localSupplier       = Supplier::where('source_type', 'Local')->count();
        $importSupplier      = Supplier::where('source_type', 'Import')->count();
        $existingSupplier    = Supplier::where('relationship_status', 'Existing')->count();
        $potentialSupplier   = Supplier::where('relationship_status', 'Potential')->count();

        $selectedSupplier = $request->get('selected_id')
            ? Supplier::with('purchaseOrders')->find($request->get('selected_id'))
            : null;

        return view('suppliers.index', compact(
            'suppliers','totalSupplier','localSupplier','importSupplier',
            'existingSupplier','potentialSupplier','selectedSupplier',
            'sourceType','status','relationshipStatus','search'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_name'       => 'required|string|max:255',
            'source_type'         => 'required|in:Local,Import',
            'pic_name'            => 'required|string|max:255',
            'pic_position'        => 'nullable|string|max:100',
            'phone'               => 'required|string|max:20',
            'email'               => 'nullable|email|max:255',
            'address'             => 'nullable|string',
            'product_category'    => 'nullable|string|max:255',
            'origin_country'      => 'nullable|string|max:100',
            'payment_term'        => 'nullable|string|max:100',
            'status'              => 'required|in:Active,Non-Active',
            'relationship_status' => 'required|in:Potential,Existing',
            'is_preferred'        => 'boolean',
            'rating'              => 'nullable|numeric|min:0|max:5',
            'supplier_since'      => 'nullable|date',
        ]);
        $validated['is_preferred'] = $request->boolean('is_preferred');
        $validated['rating']       = $validated['rating'] ?? 0;
        Supplier::create($validated);

        return redirect()->route('suppliers.index')->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'supplier_name'       => 'sometimes|string|max:255',
            'source_type'         => 'sometimes|in:Local,Import',
            'pic_name'            => 'sometimes|string|max:255',
            'pic_position'        => 'nullable|string|max:100',
            'phone'               => 'nullable|string|max:20',
            'email'               => 'nullable|email|max:255',
            'address'             => 'nullable|string',
            'product_category'    => 'nullable|string|max:255',
            'origin_country'      => 'nullable|string|max:100',
            'payment_term'        => 'nullable|string|max:100',
            'status'              => 'sometimes|in:Active,Non-Active',
            'relationship_status' => 'sometimes|in:Potential,Existing',
            'is_preferred'        => 'boolean',
            'rating'              => 'nullable|numeric|min:0|max:5',
        ]);
        $validated['is_preferred'] = $request->boolean('is_preferred');
        if (array_key_exists('rating', $validated)) $validated['rating'] = $validated['rating'] ?? 0;
        $supplier->update($validated);

        return redirect()->route('suppliers.index')->with('success', 'Supplier berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return redirect()->route('suppliers.index')->with('success', 'Supplier berhasil dihapus.');
    }

    public function export(Request $request)
    {
        $suppliers = Supplier::all();
        $headers   = ['Supplier Name', 'Source Type', 'PIC', 'Phone', 'Email', 'Product Category', 'Origin Country', 'Relationship', 'Status', 'Preferred', 'Rating'];
        $rows      = $suppliers->map(fn($s) => [
            $s->supplier_name, $s->source_type, $s->pic_name, $s->phone, $s->email,
            $s->product_category, $s->origin_country, $s->relationship_status,
            $s->status, $s->is_preferred ? 'Yes' : 'No', $s->rating,
        ])->toArray();

        return \App\Helpers\ExcelExport::download('suppliers-' . date('Ymd'), $headers, $rows, 'Suppliers');
    }
}
