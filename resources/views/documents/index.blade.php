@extends('layouts.app')
@section('title', $config['label_plural'])
@section('page-title', $config['label_plural'])
@section('page-subtitle', 'Kelola, pantau status, dan cetak ' . strtolower($config['label_plural']))

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div class="d-flex gap-2">
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addDocumentModal">
            <i class="fas fa-plus me-1"></i> Tambah {{ $config['label'] }}
        </button>
        <a href="{{ route($config['route_prefix'] . '.export', request()->query()) }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-download me-1"></i> Export Excel
        </a>
    </div>
    <div class="d-flex gap-3">
        <div class="text-end">
            <div style="font-size:1.2rem;font-weight:800">{{ $documentCount }}</div>
            <div style="font-size:.68rem;color:var(--text-muted)">Total Dokumen</div>
        </div>
        <div class="text-end ps-3" style="border-left:1px solid var(--border-color)">
            <div style="font-size:1rem;font-weight:800;color:var(--primary)">{{ idr($documentTotal) }}</div>
            <div style="font-size:.68rem;color:var(--text-muted)">Nilai Periode</div>
        </div>
    </div>
</div>

<form method="GET" action="{{ route($config['route_prefix'] . '.index') }}">
    <div class="card mb-3"><div class="card-body p-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-2"><label class="form-label small">Dari</label><input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}"></div>
            <div class="col-md-2"><label class="form-label small">Sampai</label><input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}"></div>
            <div class="col-md-2"><label class="form-label small">Status</label><select name="status" class="form-select form-select-sm">
                <option value="all">Semua Status</option>
                @foreach($config['statuses'] as $option)<option value="{{ $option }}" @selected($status === $option)>{{ $option }}</option>@endforeach
            </select></div>
            <div class="col-md-4"><label class="form-label small">Pencarian</label><input type="text" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Nomor, customer, atau item..."></div>
            <div class="col-md-2"><button class="btn btn-primary btn-sm w-100"><i class="fas fa-search me-1"></i> Cari</button></div>
        </div>
    </div></div>
</form>

<div class="card"><div class="card-body p-0"><div class="table-responsive">
    <table class="table table-hover mb-0" style="font-size:13px">
        <thead style="background:#f8f9fa"><tr>
            <th class="px-3 py-2">Nomor</th><th class="py-2">Customer</th><th class="py-2">Tanggal</th>
            <th class="py-2">Sales PIC</th><th class="py-2 text-end">Subtotal</th><th class="py-2 text-end">Total</th>
            <th class="py-2">Status</th><th class="py-2 text-end pe-3">Aksi</th>
        </tr></thead>
        <tbody>
        @forelse($documents as $document)
            @php
                $number = $document->{$config['number_field']};
                $statusColors = [
                    'Draft'=>['#f3f4f6','#4b5563'], 'Sent'=>['#dbeafe','#2563eb'], 'Paid'=>['#d1fae5','#059669'],
                    'Approved'=>['#d1fae5','#059669'], 'Rejected'=>['#fee2e2','#dc2626'], 'Cancelled'=>['#fee2e2','#dc2626'],
                    'Overdue'=>['#ffedd5','#ea580c'], 'Expired'=>['#ffedd5','#ea580c'],
                ];
                $colors = $statusColors[$document->status] ?? ['#f3f4f6','#6b7280'];
            @endphp
            <tr>
                <td class="px-3 py-2"><div style="font-weight:700;color:var(--primary)">{{ $number }}</div><div class="text-muted" style="font-size:11px">{{ $document->items->count() }} item</div></td>
                <td class="py-2"><div style="font-weight:600">{{ $document->customer_name }}</div><div class="text-muted text-truncate" style="font-size:11px;max-width:230px">{{ $document->customer_address ?: '-' }}</div></td>
                <td class="py-2">{{ $document->{$config['date_field']}?->format('d M Y') }}<div class="text-muted" style="font-size:11px">{{ $config['secondary_date_label'] }}: {{ $document->{$config['secondary_date_field']}?->format('d M Y') ?? '-' }}</div></td>
                <td class="py-2">{{ $document->salesUser?->name ?? '-' }}</td>
                <td class="py-2 text-end">{{ idr($document->subtotal) }}</td>
                <td class="py-2 text-end" style="font-weight:700;color:var(--primary)">{{ idr($document->grand_total) }}</td>
                <td class="py-2"><span style="font-size:11px;padding:3px 8px;border-radius:20px;font-weight:600;background:{{ $colors[0] }};color:{{ $colors[1] }}">{{ $document->status }}</span></td>
                <td class="py-2 text-end pe-3" style="white-space:nowrap">
                    <a class="btn btn-sm btn-outline-primary" style="padding:3px 7px" target="_blank" href="{{ route($config['route_prefix'] . '.print', $document->id) }}" title="Cetak"><i class="fas fa-print"></i></a>
                    <button class="btn btn-sm btn-outline-secondary" style="padding:3px 7px" onclick="openEditDocument({{ $document->id }})" title="Edit"><i class="fas fa-pencil-alt"></i></button>
                    <x-delete-request-button module="{{ $config['route_prefix'] }}" :model-id="$document->id" :label="$config['label'] . ' ' . $number" />
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-center py-5 text-muted"><i class="fas fa-file-alt d-block mb-2" style="font-size:28px;opacity:.35"></i>Belum ada {{ strtolower($config['label']) }} pada periode ini.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>@if($documents->hasPages())<div class="px-3 py-2">{{ $documents->links() }}</div>@endif</div></div>

<datalist id="masterProductOptions">@foreach($masterProducts as $masterProduct)<option value="{{ $masterProduct->product_name }}">{{ $masterProduct->product_code }} · {{ $masterProduct->unit }}</option>@endforeach</datalist>

@foreach(['add' => 'Tambah', 'edit' => 'Edit'] as $mode => $heading)
<div class="modal fade" id="{{ $mode }}DocumentModal" tabindex="-1">
    <div class="modal-dialog modal-xl"><div class="modal-content">
        <div class="modal-header"><h6 class="modal-title fw-bold">{{ $heading }} {{ $config['label'] }} <span id="editDocumentNumber"></span></h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" id="{{ $mode }}DocumentForm" action="{{ $mode === 'add' ? route($config['route_prefix'] . '.store') : '' }}">
            @csrf @if($mode === 'edit') @method('PUT') @endif
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">{{ $config['kind'] === 'invoice' ? 'Referensi Purchase Order' : 'Linked Lead' }}</label>
                        <select name="{{ $config['kind'] === 'invoice' ? 'purchase_order_id' : 'lead_id' }}" id="{{ $mode }}Linked" class="form-select" onchange="applyLinkedDocument('{{ $mode }}')">
                            <option value="">-- Opsional --</option>@foreach($linkedDocuments as $linked)<option value="{{ $linked['id'] }}">{{ $linked['label'] }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label">Customer dari Database</label><select name="customer_id" id="{{ $mode }}Customer" class="form-select" onchange="applyCustomer('{{ $mode }}')">
                        <option value="">-- Pilih atau isi manual --</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" data-name="{{ $customer->company_name }}" data-address="{{ $customer->address }}" data-phone="{{ $customer->phone }}">{{ $customer->company_name }}</option>@endforeach
                    </select></div>
                    <div class="col-md-4"><label class="form-label">Nama Customer <span class="text-danger">*</span></label><input name="customer_name" id="{{ $mode }}CustomerName" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Alamat Customer</label><textarea name="customer_address" id="{{ $mode }}CustomerAddress" class="form-control" rows="2"></textarea></div>
                    <div class="col-md-3"><label class="form-label">Telepon</label><input name="customer_phone" id="{{ $mode }}CustomerPhone" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label">{{ $config['date_label'] }} <span class="text-danger">*</span></label><input type="date" name="{{ $config['date_field'] }}" id="{{ $mode }}PrimaryDate" value="{{ date('Y-m-d') }}" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label">{{ $config['secondary_date_label'] }}</label><input type="date" name="{{ $config['secondary_date_field'] }}" id="{{ $mode }}SecondaryDate" value="{{ date('Y-m-d', strtotime('+7 days')) }}" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label">Status</label><select name="status" id="{{ $mode }}Status" class="form-select">@foreach($config['statuses'] as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label">Mata Uang</label><select name="currency" id="{{ $mode }}Currency" class="form-select"><option>IDR</option><option>USD</option><option>SGD</option></select></div>
                    <div class="col-md-3"><label class="form-label">PPN (%)</label><input type="number" name="tax_percent" id="{{ $mode }}Tax" min="0" max="100" step="0.01" value="{{ $config['default_tax'] }}" class="form-control" oninput="recalcDocument('{{ $mode }}')" required></div>
                </div>

                <div class="d-flex align-items-center justify-content-between mt-4 mb-2"><div style="font-size:12px;font-weight:700;color:#374151">ITEM DOKUMEN</div><button type="button" class="btn btn-outline-primary btn-sm" onclick="addDocumentItem('{{ $mode }}')"><i class="fas fa-plus me-1"></i>Tambah Item</button></div>
                <div class="table-responsive"><table class="table table-bordered mb-2" style="font-size:12px">
                    <thead style="background:#f8f9fa"><tr><th style="min-width:190px">Item</th><th style="min-width:180px">Deskripsi</th><th style="width:80px">Unit</th><th style="width:100px">Qty</th><th style="width:150px">Harga Satuan</th><th style="width:150px">Jumlah</th><th style="width:42px"></th></tr></thead>
                    <tbody id="{{ $mode }}ItemsBody"></tbody>
                    <tfoot><tr><td colspan="5" class="text-end fw-bold">Subtotal</td><td class="text-end fw-bold" id="{{ $mode }}Subtotal">Rp 0</td><td></td></tr><tr><td colspan="5" class="text-end fw-bold">PPN</td><td class="text-end" id="{{ $mode }}TaxAmount">Rp 0</td><td></td></tr><tr style="background:var(--primary-soft)"><td colspan="5" class="text-end fw-bold">TOTAL</td><td class="text-end fw-bold" style="color:var(--primary)" id="{{ $mode }}GrandTotal">Rp 0</td><td></td></tr></tfoot>
                </table></div>

                <div class="row g-3 mt-2">
                    <div class="col-md-6"><label class="form-label">Catatan</label><textarea name="notes" id="{{ $mode }}Notes" class="form-control" rows="3" placeholder="Catatan yang tampil pada dokumen"></textarea></div>
                    <div class="col-md-6"><label class="form-label">Syarat & Ketentuan</label><textarea name="terms" id="{{ $mode }}Terms" class="form-control" rows="3">{{ $config['default_terms'] }}</textarea></div>
                    @if($config['kind'] === 'invoice')<div class="col-12"><label class="form-label">Informasi Bank</label><textarea name="bank_details" id="{{ $mode }}BankDetails" class="form-control" rows="2" placeholder="Nama bank, nomor rekening, dan nama pemilik rekening"></textarea></div>@endif
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary btn-sm">Simpan {{ $config['label'] }}</button></div>
        </form>
    </div></div>
</div>
@endforeach
@endsection

@push('scripts')
<script>
const documentConfig = @json($config);
const linkedDocuments = @json($linkedDocuments);
const masterProducts = @json($masterProducts);
let documentItemIndex = 0;

function docParse(value) { return parseFloat(String(value || '').replace(/\./g, '').replace(',', '.')) || 0; }
function docMoney(value, mode) {
    const currency = document.getElementById(mode + 'Currency')?.value || 'IDR';
    return (currency === 'IDR' ? 'Rp ' : currency + ' ') + Math.round(value).toLocaleString('id-ID');
}

function addDocumentItem(mode, item = {}) {
    const index = documentItemIndex++;
    const row = document.createElement('tr');
    row.innerHTML = `<td><input class="form-control form-control-sm doc-item-name" list="masterProductOptions" name="items[${index}][item_name]" value="${escapeDoc(item.item_name || '')}" onchange="applyMasterProduct(this, '${mode}')" placeholder="Pilih atau ketik" required></td>
        <td><input class="form-control form-control-sm" name="items[${index}][description]" value="${escapeDoc(item.description || '')}"></td>
        <td><input class="form-control form-control-sm" name="items[${index}][unit]" value="${escapeDoc(item.unit || 'Kg')}" required></td>
        <td><input type="number" min="0.001" step="0.001" class="form-control form-control-sm doc-qty" name="items[${index}][qty]" value="${item.qty || 1}" oninput="recalcDocument('${mode}')" required></td>
        <td><input class="form-control form-control-sm idr-input doc-price text-end" name="items[${index}][unit_price]" value="${item.unit_price ? Math.round(item.unit_price).toLocaleString('id-ID') : ''}" oninput="recalcDocument('${mode}')" required></td>
        <td class="text-end align-middle fw-bold doc-line-total">Rp 0</td>
        <td class="text-center"><button type="button" class="btn btn-sm text-danger" onclick="this.closest('tr').remove();recalcDocument('${mode}')"><i class="fas fa-times"></i></button></td>`;
    document.getElementById(mode + 'ItemsBody').appendChild(row);
    recalcDocument(mode);
}

function escapeDoc(value) { const el = document.createElement('div'); el.textContent = value; return el.innerHTML.replace(/"/g, '&quot;'); }

function applyMasterProduct(input, mode) {
    const product = masterProducts.find(item => item.product_name.toLowerCase() === input.value.trim().toLowerCase());
    if (!product) return;
    const row = input.closest('tr');
    row.querySelector('input[name*="[description]"]').value = product.description || '';
    row.querySelector('input[name*="[unit]"]').value = product.unit || 'Kg';
    row.querySelector('.doc-price').value = Math.round(product.sell_price || 0).toLocaleString('id-ID');
    recalcDocument(mode);
}

function recalcDocument(mode) {
    let subtotal = 0;
    document.querySelectorAll(`#${mode}ItemsBody tr`).forEach(row => {
        const total = (parseFloat(row.querySelector('.doc-qty').value) || 0) * docParse(row.querySelector('.doc-price').value);
        subtotal += total; row.querySelector('.doc-line-total').textContent = docMoney(total, mode);
    });
    const tax = subtotal * ((parseFloat(document.getElementById(mode + 'Tax').value) || 0) / 100);
    document.getElementById(mode + 'Subtotal').textContent = docMoney(subtotal, mode);
    document.getElementById(mode + 'TaxAmount').textContent = docMoney(tax, mode);
    document.getElementById(mode + 'GrandTotal').textContent = docMoney(subtotal + tax, mode);
}

function applyCustomer(mode) {
    const option = document.getElementById(mode + 'Customer').selectedOptions[0];
    if (!option?.value) return;
    document.getElementById(mode + 'CustomerName').value = option.dataset.name || '';
    document.getElementById(mode + 'CustomerAddress').value = option.dataset.address || '';
    document.getElementById(mode + 'CustomerPhone').value = option.dataset.phone || '';
}

function applyLinkedDocument(mode) {
    const id = Number(document.getElementById(mode + 'Linked').value);
    const linked = linkedDocuments.find(item => Number(item.id) === id);
    if (!linked) return;
    if (linked.customer_id) document.getElementById(mode + 'Customer').value = linked.customer_id;
    document.getElementById(mode + 'CustomerName').value = linked.customer_name || '';
    document.getElementById(mode + 'CustomerAddress').value = linked.customer_address || '';
    document.getElementById(mode + 'CustomerPhone').value = linked.customer_phone || '';
    if (linked.items?.length && confirm('Salin item dari Purchase Order ini?')) {
        document.getElementById(mode + 'ItemsBody').innerHTML = '';
        linked.items.forEach(item => addDocumentItem(mode, item));
    }
}

async function openEditDocument(id) {
    const response = await fetch(`{{ url('/' . $config['route_prefix']) }}/${id}/edit`, {headers:{'Accept':'application/json'}});
    if (!response.ok) return alert('Data dokumen gagal dimuat.');
    const data = await response.json();
    document.getElementById('editDocumentForm').action = `{{ url('/' . $config['route_prefix']) }}/${id}`;
    document.getElementById('editDocumentNumber').textContent = '— ' + data[documentConfig.number_field];
    const linkedField = documentConfig.kind === 'invoice' ? 'purchase_order_id' : 'lead_id';
    document.getElementById('editLinked').value = data[linkedField] || '';
    document.getElementById('editCustomer').value = data.customer_id || '';
    document.getElementById('editCustomerName').value = data.customer_name || '';
    document.getElementById('editCustomerAddress').value = data.customer_address || '';
    document.getElementById('editCustomerPhone').value = data.customer_phone || '';
    window.setAdpDate(document.getElementById('editPrimaryDate'), data[documentConfig.date_field]);
    window.setAdpDate(document.getElementById('editSecondaryDate'), data[documentConfig.secondary_date_field]);
    document.getElementById('editStatus').value = data.status;
    document.getElementById('editCurrency').value = data.currency;
    document.getElementById('editTax').value = data.tax_percent;
    document.getElementById('editNotes').value = data.notes || '';
    document.getElementById('editTerms').value = data.terms || '';
    if (documentConfig.kind === 'invoice') document.getElementById('editBankDetails').value = data.bank_details || '';
    document.getElementById('editItemsBody').innerHTML = '';
    data.items.forEach(item => addDocumentItem('edit', item));
    bootstrap.Modal.getOrCreateInstance(document.getElementById('editDocumentModal')).show();
}

document.getElementById('addDocumentModal').addEventListener('shown.bs.modal', function () {
    if (!document.getElementById('addItemsBody').children.length) addDocumentItem('add');
});
</script>
@endpush
