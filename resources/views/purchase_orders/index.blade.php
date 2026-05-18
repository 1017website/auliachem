@extends('layouts.app')
@section('title', 'Purchase Orders')
@section('page-title', 'Purchase Orders')
@section('page-subtitle', 'Kelola data PO, revenue, dan profit per produk')

@section('content')
<div class="row g-3">
<div class="col-12">

    {{-- Header + KPI --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div class="d-flex gap-2">
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPoModal">
                <i class="fas fa-plus me-1"></i> Tambah PO
            </button>
            <a href="{{ route('purchase-orders.export', request()->query()) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-download me-1"></i> Export Excel
            </a>
        </div>
        <div class="d-flex gap-3 flex-wrap">
            @foreach([[$volumePo,'Volume PO','#111'],[$revenue,'Revenue','#2563eb'],[$grossProfit,'Gross Profit','#10b981']] as $s)
            <div class="text-center {{ !$loop->first ? 'ps-3' : '' }}" style="{{ !$loop->first ? 'border-left:1px solid var(--border-color)' : '' }}">
                <div style="font-size:{{ $loop->index>=1?'1rem':'1.2rem' }};font-weight:800;color:{{ $s[2] }}">
                {{ $loop->index>=1 ? idr($s[0]) : $s[0] }}
                </div>
                <div style="font-size:.68rem;color:var(--text-muted)">{{ $s[1] }}</div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('purchase-orders.index') }}">
        <div class="card mb-3"><div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-2"><input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}"></div>
                <div class="col-md-2"><input type="date" name="end_date"   class="form-control form-control-sm" value="{{ $endDate }}"></div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="all">All Status</option>
                        @foreach(['Done','In Progress','Cancelled'] as $st)
                        <option value="{{ $st }}" @selected($status==$st)>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari PO, customer, produk..." value="{{ $search }}">
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-search"></i></button></div>
            </div>
        </div></div>
    </form>

    {{-- Table --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size:13px">
                    <thead style="background:#f8f9fa">
                        <tr>
                            <th class="px-3 py-2">No. PO</th>
                            <th class="py-2">Customer</th>
                            <th class="py-2">Supplier</th>
                            <th class="py-2">Item(s)</th>
                            <th class="py-2">Sales PIC</th>
                            <th class="py-2">Revenue</th>
                            <th class="py-2">HPP</th>
                            <th class="py-2">Gross Profit</th>
                            <th class="py-2">Margin</th>
                            <th class="py-2">Status</th>
                            <th class="py-2">Tgl Order</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pos as $po)
                        @php
                            $sc = ['Done'=>['#d1fae5','#059669'],'In Progress'=>['#dbeafe','#2563eb'],'Cancelled'=>['#fee2e2','#dc2626']];
                            $c  = $sc[$po->status] ?? ['#f3f4f6','#6b7280'];
                        @endphp
                        <tr>
                            <td class="px-3 py-2" style="font-weight:700;color:var(--primary)">{{ $po->po_number }}</td>
                            <td class="py-2">{{ $po->customer?->company_name ?? '-' }}</td>
                            <td class="py-2" style="color:#6b7280;font-size:12px">{{ $po->supplier?->supplier_name ?? '-' }}</td>
                            <td class="py-2">
                                @foreach($po->items->take(2) as $item)
                                <div style="font-size:11px">{{ $item->product_name }} <span style="color:#9ca3af">({{ number_format($item->qty,0,'.','.') }} {{ $item->unit }})</span></div>
                                @endforeach
                                @if($po->items->count() > 2)
                                <div style="font-size:10px;color:#9ca3af">+{{ $po->items->count()-2 }} item lagi</div>
                                @endif
                            </td>
                            <td class="py-2" style="font-size:12px">
                                <div style="font-weight:600">{{ $po->salesUser?->name ?? '-' }}</div>
                            </td>
                            <td class="py-2" style="font-weight:600;color:var(--primary);white-space:nowrap">{{ idr($po->total_revenue) }}</td>
                            <td class="py-2" style="color:#dc2626;font-size:12px;white-space:nowrap">{{ idr($po->total_cost) }}</td>
                            <td class="py-2" style="font-weight:600;color:#10b981;white-space:nowrap">{{ idr($po->gross_profit) }}</td>
                            <td class="py-2" style="font-size:12px;color:#6b7280">{{ $po->gross_margin }}%</td>
                            <td class="py-2">
                                <span style="font-size:11px;padding:2px 8px;border-radius:20px;font-weight:600;background:{{ $c[0] }};color:{{ $c[1] }}">{{ $po->status }}</span>
                            </td>
                            <td class="py-2" style="color:#6b7280;font-size:12px">{{ $po->order_date?->format('d M Y') }}</td>
                            <td class="py-2">
                                <button class="btn btn-sm btn-outline-secondary" style="padding:3px 7px" onclick="openEditPo({{ $po->id }})">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <form method="POST" action="{{ route('purchase-orders.destroy', $po) }}" class="d-inline"
                                    onsubmit="return confirm('Hapus PO {{ $po->po_number }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" style="padding:3px 7px">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="11" class="text-center py-4" style="color:#9ca3af">Belum ada data PO pada periode ini</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($pos->hasPages())<div class="px-3 py-2">{{ $pos->links() }}</div>@endif
        </div>
    </div>
</div>
</div>

{{-- Modal Tambah PO --}}
<div class="modal fade" id="addPoModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Tambah Purchase Order</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('purchase-orders.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Customer</label>
                            <select name="customer_id" class="form-select">
                                <option value="">-- Pilih Customer --</option>
                                @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Supplier</label>
                            <select name="supplier_id" class="form-select">
                                <option value="">-- Pilih Supplier --</option>
                                @foreach($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->supplier_name }} ({{ $s->source_type }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Linked Lead</label>
                            <select name="lead_id" class="form-select">
                                <option value="">-- Pilih Lead --</option>
                                @foreach($leads as $l)
                                <option value="{{ $l->id }}">[{{ $l->lead_code }}] {{ $l->company_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tgl Order <span class="text-danger">*</span></label>
                            <input type="date" name="order_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="In Progress">In Progress</option>
                                <option value="Done">Done</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Currency</label>
                            <select name="currency" class="form-select">
                                <option value="IDR">IDR</option>
                                <option value="USD">USD</option>
                                <option value="SGD">SGD</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Notes</label>
                            <input type="text" name="notes" class="form-control" placeholder="Keterangan tambahan">
                        </div>
                    </div>

                    {{-- Line Items --}}
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div style="font-size:12px;font-weight:700;color:#374151">ITEM PRODUK</div>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addItemRow('addItemsBody')">
                            <i class="fas fa-plus me-1"></i> Tambah Item
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered mb-2" style="font-size:12px">
                            <thead style="background:#f8f9fa">
                                <tr>
                                    <th style="min-width:200px">Nama Produk <span class="text-danger">*</span></th>
                                    <th style="width:80px">Satuan <span class="text-danger">*</span></th>
                                    <th style="width:100px">Qty <span class="text-danger">*</span></th>
                                    <th style="width:140px">Harga Beli (HPP) <span class="text-danger">*</span></th>
                                    <th style="width:140px">Harga Jual <span class="text-danger">*</span></th>
                                    <th style="width:110px">Gross Profit</th>
                                    <th style="width:40px"></th>
                                </tr>
                            </thead>
                            <tbody id="addItemsBody">
                                {{-- Row pertama default --}}
                                <tr>
                                    <td><input type="text" name="items[0][product_name]" class="form-control form-control-sm" required></td>
                                    <td><input type="text" name="items[0][unit]" class="form-control form-control-sm" placeholder="kg" value="kg"></td>
                                    <td><input type="number" name="items[0][qty]" class="form-control form-control-sm item-qty" step="0.001" min="0" required oninput="calcRow(this)"></td>
                                    <td>
                                        <input type="hidden" name="items[0][buy_price]" class="item-buy-hidden" value="0">
                                        <input type="text" class="form-control form-control-sm item-buy" placeholder="0"
                                            oninput="syncHidden(this,'item-buy-hidden');calcRow(this)"
                                            onblur="formatPriceInput(this)">
                                    </td>
                                    <td>
                                        <input type="hidden" name="items[0][sell_price]" class="item-sell-hidden" value="0">
                                        <input type="text" class="form-control form-control-sm item-sell" placeholder="0"
                                            oninput="syncHidden(this,'item-sell-hidden');calcRow(this)"
                                            onblur="formatPriceInput(this)">
                                    </td>
                                    <td class="item-profit text-end" style="font-weight:600;color:#10b981;vertical-align:middle">Rp 0</td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)" style="padding:2px 6px"><i class="fas fa-times"></i></button></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr style="background:#f8f9fa;font-weight:700">
                                    <td colspan="4" class="text-end">Total:</td>
                                    <td id="addTotalRevenue" class="text-end" style="color:var(--primary)">Rp 0</td>
                                    <td id="addTotalProfit" class="text-end" style="color:#10b981">Rp 0</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan PO</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Edit PO --}}
<div class="modal fade" id="editPoModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Edit Purchase Order — <span id="editPoNumber"></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editPoForm">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Customer</label>
                            <select name="customer_id" id="epCustomer" class="form-select">
                                <option value="">-- Pilih Customer --</option>
                                @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Supplier</label>
                            <select name="supplier_id" id="epSupplier" class="form-select">
                                <option value="">-- Pilih Supplier --</option>
                                @foreach($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->supplier_name }} ({{ $s->source_type }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Linked Lead</label>
                            <select name="lead_id" id="epLead" class="form-select">
                                <option value="">-- Pilih Lead --</option>
                                @foreach($leads as $l)
                                <option value="{{ $l->id }}">[{{ $l->lead_code }}] {{ $l->company_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tgl Order <span class="text-danger">*</span></label>
                            <input type="date" name="order_date" id="epDate" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" id="epStatus" class="form-select" required>
                                <option value="In Progress">In Progress</option>
                                <option value="Done">Done</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Currency</label>
                            <select name="currency" id="epCurrency" class="form-select">
                                <option value="IDR">IDR</option>
                                <option value="USD">USD</option>
                                <option value="SGD">SGD</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Notes</label>
                            <input type="text" name="notes" id="epNotes" class="form-control">
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div style="font-size:12px;font-weight:700;color:#374151">ITEM PRODUK</div>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addItemRow('editItemsBody')">
                            <i class="fas fa-plus me-1"></i> Tambah Item
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered mb-2" style="font-size:12px">
                            <thead style="background:#f8f9fa">
                                <tr>
                                    <th style="min-width:200px">Nama Produk</th>
                                    <th style="width:80px">Satuan</th>
                                    <th style="width:100px">Qty</th>
                                    <th style="width:140px">Harga Beli (HPP)</th>
                                    <th style="width:140px">Harga Jual</th>
                                    <th style="width:110px">Gross Profit</th>
                                    <th style="width:40px"></th>
                                </tr>
                            </thead>
                            <tbody id="editItemsBody"></tbody>
                            <tfoot>
                                <tr style="background:#f8f9fa;font-weight:700">
                                    <td colspan="4" class="text-end">Total:</td>
                                    <td id="editTotalRevenue" class="text-end" style="color:var(--primary)">Rp 0</td>
                                    <td id="editTotalProfit" class="text-end" style="color:#10b981">Rp 0</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
let itemIndex = 1;

function formatNum(n) {
    if (!n && n !== 0) return '';
    return Math.round(n).toLocaleString('id-ID');
}

function parseNum(str) {
    if (!str) return 0;
    return parseFloat(String(str).replace(/\./g, '').replace(',', '.')) || 0;
}

function formatPriceInput(el) {
    const raw = parseNum(el.value);
    if (raw > 0) el.value = formatNum(raw);
    calcRow(el);
}

function formatRp(n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); }

function syncHidden(el, hiddenClass) {
    const row    = el.closest('tr');
    const hidden = row.querySelector('.' + hiddenClass);
    // Simpan posisi cursor
    const pos = el.selectionStart;
    const raw = el.value.replace(/\./g, '').replace(/[^0-9]/g, '');
    const formatted = raw ? parseInt(raw).toLocaleString('id-ID') : '';
    const diff = formatted.length - el.value.length;
    el.value = formatted;
    // Restore cursor
    try { el.setSelectionRange(pos + diff, pos + diff); } catch(e) {}
    if (hidden) hidden.value = raw || 0;
}

function calcRow(el) {
    const row  = el.closest('tr');
    const qty  = parseFloat(row.querySelector('.item-qty')?.value) || 0;
    const buy  = parseNum(row.querySelector('.item-buy')?.value);
    const sell = parseNum(row.querySelector('.item-sell')?.value);
    const profit = (sell - buy) * qty;
    row.querySelector('.item-profit').textContent = formatRp(profit);
    row.querySelector('.item-profit').style.color = profit >= 0 ? '#10b981' : '#dc2626';
    recalcTotal(row.closest('tbody').id);
}

function recalcTotal(bodyId) {
    const body    = document.getElementById(bodyId);
    const prefix  = bodyId === 'addItemsBody' ? 'add' : 'edit';
    let revenue = 0, profit = 0;
    body.querySelectorAll('tr').forEach(row => {
        const qty  = parseFloat(row.querySelector('.item-qty')?.value)  || 0;
        const buy  = parseNum(row.querySelector('.item-buy')?.value);
        const sell = parseNum(row.querySelector('.item-sell')?.value);
        revenue += qty * sell;
        profit  += (sell - buy) * qty;
    });
    document.getElementById(prefix + 'TotalRevenue').textContent = formatRp(revenue);
    document.getElementById(prefix + 'TotalProfit').textContent  = formatRp(profit);
    document.getElementById(prefix + 'TotalProfit').style.color  = profit >= 0 ? '#10b981' : '#dc2626';
}

function addItemRow(bodyId, data = {}) {
    const idx  = itemIndex++;
    const body = document.getElementById(bodyId);
    const prefix = bodyId === 'addItemsBody' ? 'items' : 'items';
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" name="${prefix}[${idx}][product_name]" class="form-control form-control-sm" value="${data.product_name||''}" required></td>
        <td><input type="text" name="${prefix}[${idx}][unit]" class="form-control form-control-sm" value="${data.unit||'kg'}"></td>
        <td><input type="number" name="${prefix}[${idx}][qty]" class="form-control form-control-sm item-qty" step="0.001" min="0" value="${data.qty||''}" required oninput="calcRow(this)"></td>
        <td>
            <input type="hidden" name="${prefix}[${idx}][buy_price]" class="item-buy-hidden" value="${data.buy_price||0}">
            <input type="text" class="form-control form-control-sm item-buy" value="${data.buy_price ? formatNum(data.buy_price) : ''}" placeholder="0"
                oninput="syncHidden(this,'item-buy-hidden');calcRow(this)"
                onblur="formatPriceInput(this)">
        </td>
        <td>
            <input type="hidden" name="${prefix}[${idx}][sell_price]" class="item-sell-hidden" value="${data.sell_price||0}">
            <input type="text" class="form-control form-control-sm item-sell" value="${data.sell_price ? formatNum(data.sell_price) : ''}" placeholder="0"
                oninput="syncHidden(this,'item-sell-hidden');calcRow(this)"
                onblur="formatPriceInput(this)">
        </td>
        <td class="item-profit text-end" style="font-weight:600;color:#10b981;vertical-align:middle">Rp 0</td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)" style="padding:2px 6px"><i class="fas fa-times"></i></button></td>
    `;
    body.appendChild(tr);
    if (data.qty && data.buy_price && data.sell_price) {
        calcRow(tr.querySelector('.item-qty'));
    }
}

function removeRow(btn) {
    const row  = btn.closest('tr');
    const body = row.closest('tbody');
    if (body.querySelectorAll('tr').length <= 1) { alert('Minimal 1 item produk'); return; }
    row.remove();
    recalcTotal(body.id);
}

async function openEditPo(id) {
    const res = await fetch(`/purchase-orders/${id}/edit`);
    const po  = await res.json();

    document.getElementById('editPoForm').action = `/purchase-orders/${id}`;
    document.getElementById('editPoNumber').textContent = po.po_number;

    // Set semua field — clear dulu baru set
    document.getElementById('epStatus').value   = po.status;
    document.getElementById('epCurrency').value = po.currency;
    document.getElementById('epNotes').value    = po.notes || '';

    // Set date — extract Y-m-d jika format ISO

    // Set Select2 values
    const setSelect2 = (elId, val) => {
        const el = document.getElementById(elId);
        if (!el) return;
        if ($(el).data('select2')) {
            $(el).val(val || null).trigger('change');
        } else {
            el.value = val || '';
        }
    };

    setSelect2('epCustomer', po.customer_id);
    setSelect2('epSupplier', po.supplier_id);
    setSelect2('epLead',     po.lead_id);

    // Set date — simple, langsung, tanpa timer
    const epDate = document.getElementById('epDate');
    epDate.value = '';
    epDate.value = (po.order_date || '').split('T')[0];

    // Clear & rebuild items
    const body = document.getElementById('editItemsBody');
    body.innerHTML = '';
    itemIndex = 1000;
    po.items.forEach(item => addItemRow('editItemsBody', item));

    recalcTotal('editItemsBody');
    new bootstrap.Modal(document.getElementById('editPoModal')).show();
}
</script>
@endpush
@endsection