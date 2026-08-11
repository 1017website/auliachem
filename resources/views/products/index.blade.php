@extends('layouts.app')
@section('title', 'Master Barang')
@section('page-title', 'Master Barang')
@section('page-subtitle', 'Kelola katalog, harga, satuan, dan informasi stok barang')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div class="d-flex gap-2">
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addProductModal"><i class="fas fa-plus me-1"></i> Tambah Barang</button>
        <a href="{{ route('products.export') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-download me-1"></i> Export Excel</a>
    </div>
    <div class="d-flex gap-3 flex-wrap">
        <div class="text-end"><div style="font-size:1.2rem;font-weight:800">{{ $activeCount }}</div><div style="font-size:.68rem;color:var(--text-muted)">Barang Aktif</div></div>
        <div class="text-end ps-3" style="border-left:1px solid var(--border-color)"><div style="font-size:1rem;font-weight:800;color:{{ $lowStockCount ? '#dc2626' : '#10b981' }}">{{ $lowStockCount }}</div><div style="font-size:.68rem;color:var(--text-muted)">Stok Minimum</div></div>
        <div class="text-end ps-3" style="border-left:1px solid var(--border-color)"><div style="font-size:1rem;font-weight:800;color:var(--primary)">{{ idr($inventoryValue) }}</div><div style="font-size:.68rem;color:var(--text-muted)">Nilai Persediaan</div></div>
    </div>
</div>

<form method="GET" action="{{ route('products.index') }}">
    <div class="card mb-3"><div class="card-body p-3"><div class="row g-2 align-items-end">
        <div class="col-md-4"><label class="form-label small">Pencarian</label><input name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="Kode, nama, atau deskripsi barang..."></div>
        <div class="col-md-3"><label class="form-label small">Kategori</label><select name="category" class="form-select form-select-sm"><option value="">Semua Kategori</option>@foreach($categories as $option)<option value="{{ $option }}" @selected($category === $option)>{{ $option }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label small">Status</label><select name="status" class="form-select form-select-sm"><option value="all">Semua Status</option><option value="Active" @selected($status === 'Active')>Active</option><option value="Inactive" @selected($status === 'Inactive')>Inactive</option></select></div>
        <div class="col-md-2"><label class="form-label small">Stok</label><select name="stock" class="form-select form-select-sm"><option value="all">Semua Stok</option><option value="low" @selected($stock === 'low')>Stok Minimum</option></select></div>
        <div class="col-md-1"><button class="btn btn-primary btn-sm w-100"><i class="fas fa-search"></i></button></div>
    </div></div></div>
</form>

<div class="card"><div class="card-body p-0"><div class="table-responsive">
    <table class="table table-hover mb-0" style="font-size:13px"><thead style="background:#f8f9fa"><tr>
        <th class="px-3 py-2">Kode</th><th class="py-2">Nama Barang</th><th class="py-2">Kategori</th><th class="py-2">Satuan</th>
        <th class="py-2 text-end">Harga Beli</th><th class="py-2 text-end">Harga Jual</th><th class="py-2 text-end">Stok</th><th class="py-2">Status</th><th class="py-2 text-end pe-3">Aksi</th>
    </tr></thead><tbody>
        @forelse($products as $product)
        <tr>
            <td class="px-3 py-2" style="font-weight:700;color:var(--primary)">{{ $product->product_code }}</td>
            <td class="py-2"><div style="font-weight:600">{{ $product->product_name }}</div><div class="text-muted text-truncate" style="font-size:11px;max-width:250px">{{ $product->description ?: '-' }}</div></td>
            <td class="py-2">{{ $product->category ?: '-' }}</td><td class="py-2">{{ $product->unit }}</td>
            <td class="py-2 text-end">{{ idr($product->buy_price) }}</td><td class="py-2 text-end" style="font-weight:600;color:var(--primary)">{{ idr($product->sell_price) }}</td>
            <td class="py-2 text-end"><span style="font-weight:700;color:{{ $product->is_low_stock ? '#dc2626' : '#111827' }}">{{ number_format((float)$product->current_stock, 3, ',', '.') }}</span><div class="text-muted" style="font-size:10px">Min. {{ number_format((float)$product->minimum_stock, 3, ',', '.') }}</div></td>
            <td class="py-2"><span style="font-size:11px;padding:3px 8px;border-radius:20px;font-weight:600;background:{{ $product->status === 'Active' ? '#d1fae5' : '#f3f4f6' }};color:{{ $product->status === 'Active' ? '#059669' : '#6b7280' }}">{{ $product->status }}</span></td>
            <td class="py-2 text-end pe-3"><button class="btn btn-sm btn-outline-secondary" style="padding:3px 7px" onclick="openEditProduct({{ $product->id }})"><i class="fas fa-pencil-alt"></i></button> <x-delete-request-button module="products" :model-id="$product->id" :label="$product->product_code . ' - ' . $product->product_name" /></td>
        </tr>
        @empty<tr><td colspan="9" class="text-center py-5 text-muted"><i class="fas fa-box-open d-block mb-2" style="font-size:28px;opacity:.35"></i>Belum ada master barang.</td></tr>@endforelse
    </tbody></table>
</div>@if($products->hasPages())<div class="px-3 py-2">{{ $products->links() }}</div>@endif</div></div>

<datalist id="categoryOptions">@foreach($categories as $option)<option value="{{ $option }}">@endforeach</datalist>

@foreach(['add' => 'Tambah', 'edit' => 'Edit'] as $mode => $title)
<div class="modal fade" id="{{ $mode }}ProductModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title fw-bold">{{ $title }} Barang <span id="editProductCode"></span></h6><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
    <form method="POST" id="{{ $mode }}ProductForm" action="{{ $mode === 'add' ? route('products.store') : '' }}">@csrf @if($mode === 'edit') @method('PUT') @endif
        <div class="modal-body"><div class="row g-3">
            <div class="col-md-8"><label class="form-label">Nama Barang <span class="text-danger">*</span></label><input name="product_name" id="{{ $mode }}ProductName" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Status</label><select name="status" id="{{ $mode }}Status" class="form-select"><option>Active</option><option>Inactive</option></select></div>
            <div class="col-md-6"><label class="form-label">Kategori</label><input name="category" id="{{ $mode }}Category" class="form-control" list="categoryOptions" placeholder="Contoh: Industrial Chemical"></div>
            <div class="col-md-6"><label class="form-label">Satuan <span class="text-danger">*</span></label><input name="unit" id="{{ $mode }}Unit" class="form-control" value="Kg" placeholder="Kg, Liter, Drum, Pcs..." required></div>
            <div class="col-12"><label class="form-label">Deskripsi / Spesifikasi</label><textarea name="description" id="{{ $mode }}Description" class="form-control" rows="2"></textarea></div>
            <div class="col-md-6"><label class="form-label">Harga Beli</label><div class="input-group"><span class="input-group-text">Rp</span><input name="buy_price" id="{{ $mode }}BuyPrice" class="form-control idr-input text-end" value="0" required></div></div>
            <div class="col-md-6"><label class="form-label">Harga Jual</label><div class="input-group"><span class="input-group-text">Rp</span><input name="sell_price" id="{{ $mode }}SellPrice" class="form-control idr-input text-end" value="0" required></div></div>
            <div class="col-md-6"><label class="form-label">Stok Saat Ini</label><input type="number" name="current_stock" id="{{ $mode }}CurrentStock" class="form-control" min="0" step="0.001" value="0" required></div>
            <div class="col-md-6"><label class="form-label">Minimum Stok</label><input type="number" name="minimum_stock" id="{{ $mode }}MinimumStock" class="form-control" min="0" step="0.001" value="0" required></div>
        </div></div>
        <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary btn-sm">Simpan Barang</button></div>
    </form>
</div></div></div>
@endforeach
@endsection

@push('scripts')
<script>
async function openEditProduct(id) {
    const response = await fetch(`{{ url('/products') }}/${id}/edit`, {headers:{'Accept':'application/json'}});
    if (!response.ok) return alert('Data barang gagal dimuat.');
    const product = await response.json();
    document.getElementById('editProductForm').action = `{{ url('/products') }}/${id}`;
    document.getElementById('editProductCode').textContent = '— ' + product.product_code;
    document.getElementById('editProductName').value = product.product_name || '';
    document.getElementById('editCategory').value = product.category || '';
    document.getElementById('editUnit').value = product.unit || '';
    document.getElementById('editDescription').value = product.description || '';
    document.getElementById('editBuyPrice').value = Math.round(product.buy_price || 0).toLocaleString('id-ID');
    document.getElementById('editSellPrice').value = Math.round(product.sell_price || 0).toLocaleString('id-ID');
    document.getElementById('editCurrentStock').value = product.current_stock || 0;
    document.getElementById('editMinimumStock').value = product.minimum_stock || 0;
    document.getElementById('editStatus').value = product.status;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('editProductModal')).show();
}
</script>
@endpush
