<?php

namespace App\Http\Controllers;

use App\Helpers\ExcelExport;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search'));
        $category = trim((string) $request->get('category'));
        $status = (string) $request->get('status', 'all');
        $stock = (string) $request->get('stock', 'all');

        $query = Product::query();
        if ($search !== '') {
            $query->where(fn ($query) => $query
                ->where('product_code', 'like', "%{$search}%")
                ->orWhere('product_name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"));
        }
        if ($category !== '') $query->where('category', $category);
        if (in_array($status, ['Active', 'Inactive'], true)) $query->where('status', $status);
        if ($stock === 'low') $query->where('minimum_stock', '>', 0)->whereColumn('current_stock', '<=', 'minimum_stock');

        $products = $query->orderBy('product_name')->paginate(20)->withQueryString();
        $categories = Product::whereNotNull('category')->where('category', '!=', '')->distinct()->orderBy('category')->pluck('category');

        return view('products.index', [
            'products' => $products,
            'categories' => $categories,
            'search' => $search,
            'category' => $category,
            'status' => $status,
            'stock' => $stock,
            'activeCount' => Product::where('status', 'Active')->count(),
            'lowStockCount' => Product::where('minimum_stock', '>', 0)->whereColumn('current_stock', '<=', 'minimum_stock')->count(),
            'inventoryValue' => Product::selectRaw('COALESCE(SUM(current_stock * buy_price), 0) AS value')->value('value'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
        DB::transaction(fn () => Product::createWithUniqueCode($validated));

        return redirect()->route('products.index')->with('success', 'Barang berhasil ditambahkan.');
    }

    public function edit(Product $product)
    {
        return response()->json($product);
    }

    public function update(Request $request, Product $product)
    {
        $product->update($request->validate($this->rules()));
        return redirect()->route('products.index')->with('success', 'Barang berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        $name = $product->product_name;
        $product->delete();
        return redirect()->route('products.index')->with('success', "Barang {$name} berhasil dihapus.");
    }

    public function export()
    {
        $rows = Product::orderBy('product_name')->get()->map(fn (Product $product) => [
            $product->product_code,
            $product->product_name,
            $product->category,
            $product->unit,
            $product->description,
            (float) $product->buy_price,
            (float) $product->sell_price,
            (float) $product->current_stock,
            (float) $product->minimum_stock,
            $product->status,
        ])->all();

        return ExcelExport::download(
            'master-barang-' . now()->format('Y-m-d'),
            ['Kode', 'Nama Barang', 'Kategori', 'Satuan', 'Deskripsi', 'Harga Beli', 'Harga Jual', 'Stok', 'Minimum Stok', 'Status'],
            $rows,
            'Master Barang'
        );
    }

    private function rules(): array
    {
        return [
            'product_name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:2000'],
            'buy_price' => ['required', 'numeric', 'min:0'],
            'sell_price' => ['required', 'numeric', 'min:0'],
            'current_stock' => ['required', 'numeric', 'min:0'],
            'minimum_stock' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ];
    }
}
