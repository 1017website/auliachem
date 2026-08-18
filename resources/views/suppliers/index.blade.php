@extends('layouts.app')
@section('title', 'Database Supplier')
@section('page-title', 'Database Supplier')
@section('page-subtitle', 'Kelola data supplier Local dan Import')

@section('content')
<div class="row g-3">
<div class="col-lg-{{ $selectedSupplier ? '8' : '12' }}">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                <i class="fas fa-plus me-1"></i> Tambah Supplier
            </button>
            <a href="{{ route('suppliers.export', request()->query()) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-download me-1"></i> Export Excel
            </a>
            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#importSupplierModal">
                <i class="fas fa-file-import me-1"></i> Import Excel
            </button>
        </div>
        <div class="d-flex gap-3 flex-wrap">
            @foreach([[$totalSupplier,'Total','#111'],[$localSupplier,'Local','var(--primary)'],[$importSupplier,'Import','#7c3aed'],[$existingSupplier,'Existing','#059669'],[$potentialSupplier,'Potential','#f97316']] as $s)
            <div class="text-center {{ !$loop->first ? 'ps-3' : '' }}" style="{{ !$loop->first ? 'border-left:1px solid var(--border-color)' : '' }}">
                <div style="font-size:1.2rem;font-weight:800;color:{{ $s[2] }}">{{ $s[0] }}</div>
                <div style="font-size:.68rem;color:var(--text-muted)">{{ $s[1] }}</div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('suppliers.index') }}">
        <div class="card mb-3"><div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <select name="source_type" class="form-select form-select-sm">
                        <option value="all">All Source</option>
                        <option value="Local"  @selected($sourceType=='Local')>Local</option>
                        <option value="Import" @selected($sourceType=='Import')>Import</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="relationship_status" class="form-select form-select-sm">
                        <option value="all">All Relationship</option>
                        <option value="Existing"  @selected($relationshipStatus=='Existing')>Existing</option>
                        <option value="Potential" @selected($relationshipStatus=='Potential')>Potential</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="all">All Status</option>
                        <option value="Active"     @selected($status=='Active')>Active</option>
                        <option value="Non-Active" @selected($status=='Non-Active')>Non-Active</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari supplier, produk..." value="{{ $search }}">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-search"></i></button>
                </div>
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
                            <th class="px-3 py-2">No.</th>
                            <th class="px-3 py-2">Supplier</th>
                            <th class="py-2">PIC</th>
                            <th class="py-2">Phone</th>
                            <th class="py-2">Kategori Produk</th>
                            <th class="py-2">Produk Supplier</th>
                            <th class="py-2">Source</th>
                            <th class="py-2">Relationship</th>
                            <th class="py-2">Status</th>
                            <th class="py-2">Rating</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suppliers as $i => $s)
                        <tr>
                            <td class="px-3 py-2" style="color:#9ca3af;font-size:.75rem">{{ $suppliers->firstItem() + $i }}</td>
                            <td class="px-3 py-2">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="user-avatar" style="width:30px;height:30px;font-size:.65rem;border-radius:6px;flex-shrink:0">{{ $s->logo_initials }}</div>
                                    <div>
                                        <a href="{{ route('suppliers.index', array_merge(request()->query(), ['selected_id'=>$s->id])) }}"
                                            style="font-weight:700;color:#111;text-decoration:none">{{ $s->supplier_name }}</a>
                                        @if($s->is_preferred)<div style="font-size:10px;color:#d97706">⭐ Preferred</div>@endif
                                    </div>
                                </div>
                            </td>
                            <td class="py-2">
                                <div>{{ $s->pic_name }}</div>
                                <div style="font-size:11px;color:#6b7280">{{ $s->pic_position }}</div>
                            </td>
                            <td class="py-2" style="font-size:12px">{{ $s->phone }}</td>
                            <td class="py-2" style="font-size:12px">{{ $s->product_category ?? '-' }}</td>
                            <td class="py-2" style="font-size:12px;max-width:220px">
                                @php
                                    $productNames = $s->products->map(function ($p) {
                                        $name = trim($p->product_name ?? '');
                                        $unit = trim($p->unit ?? '');

                                        if ($name === '') {
                                            return null;
                                        }

                                        return $unit !== '' ? $name . ' (' . $unit . ')' : $name;
                                    })->filter()->values();
                                @endphp
                                @if($productNames->count() > 0)
                                    <div title="{{ $productNames->implode(', ') }}">{{ \Illuminate\Support\Str::limit($productNames->implode(', '), 70) }}</div>
                                @else
                                    <span style="color:#9ca3af">-</span>
                                @endif
                            </td>
                            <td class="py-2">
                                <span style="font-size:11px;padding:2px 8px;border-radius:20px;font-weight:600;
                                    background:{{ $s->source_type==='Local'?'var(--primary-light)':'#ede9fe' }};
                                    color:{{ $s->source_type==='Local'?'var(--primary-dark)':'#7c3aed' }}">
                                    {{ $s->source_type }}
                                    @if($s->source_type==='Import' && $s->origin_country)
                                    <span style="font-size:10px">({{ $s->origin_country }})</span>
                                    @endif
                                </span>
                            </td>
                            <td class="py-2">
                                <span style="font-size:11px;padding:2px 8px;border-radius:20px;font-weight:600;
                                    background:{{ $s->relationship_status==='Existing'?'#d1fae5':'#fff7ed' }};
                                    color:{{ $s->relationship_status==='Existing'?'#059669':'#ea580c' }}">
                                    {{ $s->relationship_status }}
                                </span>
                            </td>
                            <td class="py-2">
                                <span class="{{ $s->status==='Active'?'badge-existing':'badge-overdue' }}">{{ $s->status }}</span>
                            </td>
                            <td class="py-2" style="font-size:12px">{{ $s->rating > 0 ? $s->rating : '-' }}</td>
                            <td class="py-2">
                                <a href="{{ route('suppliers.index', array_merge(request()->query(), ['selected_id'=>$s->id])) }}"
                                    class="btn btn-sm btn-outline-primary" style="padding:3px 7px" title="Detail">
                                    <i class="fas fa-eye" style="font-size:.7rem"></i>
                                </a>
                                <button class="btn btn-sm btn-outline-secondary" style="padding:3px 7px"
                                    onclick="openEditSupplier({{ $s->id }})">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-info" style="padding:3px 7px" title="Produk"
                                    onclick="openProductModal({{ $s->id }}, '{{ addslashes($s->supplier_name) }}')">
                                    <i class="fas fa-boxes" style="font-size:.7rem"></i>
                                </button>
                                <x-delete-request-button
                                    module="suppliers"
                                    :model-id="$s->id"
                                    :label="'supplier ' . $s->supplier_name" />
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="11" class="text-center py-4" style="color:#9ca3af">Belum ada data supplier</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($suppliers->hasPages())
            <div class="px-3 py-2">{{ $suppliers->links() }}</div>
            @endif
        </div>
    </div>
</div>

{{-- RIGHT: Detail Panel --}}
@if($selectedSupplier)
<div class="col-lg-4">
    <div class="card" style="position:sticky;top:70px">
        <div class="card-body p-3">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="user-avatar" style="width:44px;height:44px;border-radius:8px;font-size:.85rem">{{ $selectedSupplier->logo_initials }}</div>
                    <div>
                        <div style="font-weight:700;font-size:.9rem">{{ $selectedSupplier->supplier_name }}</div>
                        <div class="d-flex gap-1 mt-1 flex-wrap">
                            <span style="font-size:11px;padding:2px 8px;border-radius:20px;font-weight:600;
                                background:{{ $selectedSupplier->source_type==='Local'?'var(--primary-light)':'#ede9fe' }};
                                color:{{ $selectedSupplier->source_type==='Local'?'var(--primary-dark)':'#7c3aed' }}">{{ $selectedSupplier->source_type }}</span>
                            <span style="font-size:11px;padding:2px 8px;border-radius:20px;font-weight:600;
                                background:{{ $selectedSupplier->relationship_status==='Existing'?'#d1fae5':'#fff7ed' }};
                                color:{{ $selectedSupplier->relationship_status==='Existing'?'#059669':'#ea580c' }}">{{ $selectedSupplier->relationship_status }}</span>
                            @if($selectedSupplier->is_preferred)
                            <span style="background:#fef3c7;color:#b45309;font-size:.65rem;padding:2px 7px;border-radius:20px;font-weight:600">⭐ Preferred</span>
                            @endif
                        </div>
                    </div>
                </div>
                <a href="{{ route('suppliers.index', request()->except('selected_id')) }}" style="color:var(--text-muted)"><i class="fas fa-times"></i></a>
            </div>

            <ul class="nav nav-tabs mb-3" style="font-size:.75rem" id="supTabs">
                <li class="nav-item"><a class="nav-link active" href="#" onclick="showSupTab('overview',this);return false" style="padding:6px 10px">Overview</a></li>
                <li class="nav-item"><a class="nav-link" href="#" onclick="showSupTab('pics',this);return false" style="padding:6px 10px">PICs</a></li>
                <li class="nav-item"><a class="nav-link" href="#" onclick="showSupTab('products',this);return false" style="padding:6px 10px">Produk</a></li>
                <li class="nav-item"><a class="nav-link" href="#" onclick="showSupTab('transaction',this);return false" style="padding:6px 10px">PO</a></li>
            </ul>

            {{-- Tab Overview --}}
            <div id="suptab-overview">
                @foreach([
                    ['PIC Utama',$selectedSupplier->pic_name],
                    ['Jabatan',$selectedSupplier->pic_position??'-'],
                    ['Phone',$selectedSupplier->phone??'-'],
                    ['Email',$selectedSupplier->email??'-'],
                    ['Kategori Produk',$selectedSupplier->product_category??'-'],
                    ['Source Type',$selectedSupplier->source_type],
                    ['Negara Asal',$selectedSupplier->origin_country??'-'],
                    ['Payment Term',$selectedSupplier->payment_term??'-'],
                    ['Rating',$selectedSupplier->rating > 0 ? $selectedSupplier->rating : '-'],
                    ['Supplier Since',$selectedSupplier->supplier_since?->format('d M Y')??'-'],
                ] as $f)
                <div class="d-flex justify-content-between py-1" style="border-bottom:1px solid #f9fafb;font-size:.77rem">
                    <span style="color:var(--text-muted);min-width:90px">{{ $f[0] }}</span>
                    <span style="font-weight:500;text-align:right;max-width:55%">{{ $f[1] }}</span>
                </div>
                @endforeach

                @if($selectedSupplier->address)
                <div class="mt-2 pt-2" style="border-top:1px solid #f3f4f6">
                    <div style="font-size:.72rem;color:var(--text-muted);margin-bottom:4px">Alamat</div>
                    <div style="font-size:.78rem">{{ $selectedSupplier->address }}</div>
                </div>
                @endif

                <div class="row g-2 mt-3 mb-3 text-center">
                    <div class="col-6">
                        <div style="background:var(--primary-soft);border-radius:8px;padding:10px">
                            <div style="font-size:1rem;font-weight:800;color:var(--primary)">{{ $selectedSupplier->total_revenue > 0 ? idrm($selectedSupplier->total_revenue) : 'Rp 0' }}</div>
                            <div style="font-size:.65rem;color:var(--text-muted)">Total Pembelian</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div style="background:#f0fdf4;border-radius:8px;padding:10px">
                            <div style="font-size:1rem;font-weight:800;color:#16a34a">{{ $selectedSupplier->purchaseOrders->count() }}</div>
                            <div style="font-size:.65rem;color:var(--text-muted)">Total PO</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary flex-fill" style="font-size:.75rem"
                        onclick="openEditSupplier({{ $selectedSupplier->id }})">
                        <i class="fas fa-edit me-1"></i> Edit
                    </button>
                    <x-delete-request-button
                        module="suppliers"
                        :model-id="$selectedSupplier->id"
                        :label="'supplier ' . $selectedSupplier->supplier_name"
                        wrapper-class="flex-fill"
                        button-class="btn btn-sm btn-outline-danger w-100"
                        button-style="font-size:.75rem"
                        :show-text="true" />
                </div>
            </div>

            {{-- Tab PICs --}}
            <div id="suptab-pics" style="display:none">
                <strong style="font-size:.8rem;display:block;margin-bottom:10px">Daftar PIC</strong>
                {{-- PIC Utama --}}
                <div class="d-flex align-items-start gap-2 mb-3 pb-2" style="border-bottom:1px solid #f3f4f6">
                    <div style="width:32px;height:32px;background:var(--primary-light);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="fas fa-user" style="color:var(--primary);font-size:.65rem"></i>
                    </div>
                    <div style="flex:1">
                        <div style="font-size:.8rem;font-weight:600">{{ $selectedSupplier->pic_name }}
                            <span style="font-size:.65rem;background:var(--primary-light);color:var(--primary-dark);padding:1px 6px;border-radius:10px;margin-left:4px">Utama</span>
                        </div>
                        @if($selectedSupplier->pic_position)<div style="font-size:.72rem;color:var(--text-muted)">{{ $selectedSupplier->pic_position }}</div>@endif
                        @if($selectedSupplier->phone)<div style="font-size:.72rem">{{ $selectedSupplier->phone }}</div>@endif
                        @if($selectedSupplier->email)<div style="font-size:.72rem;color:var(--primary)">{{ $selectedSupplier->email }}</div>@endif
                    </div>
                </div>
                {{-- PIC tambahan --}}
                @forelse($selectedSupplier->pics as $pic)
                <div class="d-flex align-items-start gap-2 mb-2">
                    <div style="width:32px;height:32px;background:#f3f4f6;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="fas fa-user" style="color:#6b7280;font-size:.65rem"></i>
                    </div>
                    <div style="flex:1">
                        <div style="font-size:.8rem;font-weight:600">{{ $pic->pic_name }}</div>
                        @if($pic->pic_position)<div style="font-size:.72rem;color:var(--text-muted)">{{ $pic->pic_position }}</div>@endif
                        @if($pic->phone)<div style="font-size:.72rem">{{ $pic->phone }}</div>@endif
                        @if($pic->email)<div style="font-size:.72rem;color:var(--primary)">{{ $pic->email }}</div>@endif
                    </div>
                    <x-delete-request-button
                        module="supplier-pics"
                        :model-id="$pic->id"
                        :label="'PIC ' . $pic->pic_name"
                        button-class="btn btn-sm p-0"
                        button-style="color:#ef4444;background:none;border:none" />
                </div>
                @empty
                <div class="text-center py-3" style="color:var(--text-muted);font-size:.8rem">Belum ada PIC tambahan.</div>
                @endforelse
            </div>

            {{-- Tab Produk --}}
            <div id="suptab-products" style="display:none">
                <strong style="font-size:.8rem;display:block;margin-bottom:10px">Produk Supplier</strong>
                @forelse($selectedSupplier->products as $prod)
                <div class="d-flex align-items-start gap-2 mb-2 pb-2" style="border-bottom:1px solid #f9fafb">
                    <div style="width:32px;height:32px;border-radius:8px;background:#f3e8ff;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="fas fa-box" style="font-size:.7rem;color:#7c3aed"></i>
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:.78rem;font-weight:600">{{ $prod->product_name }}</div>
                        @if($prod->unit)<div style="font-size:.7rem;color:var(--text-muted)">Satuan: {{ $prod->unit }}</div>@endif
                        @if($prod->description)<div style="font-size:.7rem;color:#374151">{{ $prod->description }}</div>@endif
                    </div>
                </div>
                @empty
                <div class="text-center py-3" style="color:var(--text-muted);font-size:.8rem">Belum ada produk supplier.</div>
                @endforelse
            </div>

            {{-- Tab PO --}}
            <div id="suptab-transaction" style="display:none">
                <strong style="font-size:.8rem;display:block;margin-bottom:10px">Purchase Orders</strong>
                @forelse($selectedSupplier->purchaseOrders->sortByDesc('order_date') as $po)
                <div class="d-flex align-items-start gap-2 mb-3 pb-2" style="border-bottom:1px solid #f9fafb">
                    <div style="width:32px;height:32px;border-radius:8px;background:var(--primary-soft);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="fas fa-file-invoice" style="font-size:.7rem;color:var(--primary)"></i>
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:.78rem;font-weight:600">{{ $po->po_number }}</div>
                        <div style="font-size:.7rem;color:var(--text-muted)">{{ $po->customer?->company_name ?? '-' }}</div>
                        <div style="font-size:.7rem;color:var(--text-muted)">{{ \Carbon\Carbon::parse($po->order_date)->format('d M Y') }}</div>
                    </div>
                    <div class="text-end" style="flex-shrink:0">
                        <div style="font-size:.75rem;font-weight:600">{{ idrm($po->total_revenue) }}</div>
                        @php $sc = ['Done' => ['#d1fae5', '#059669'], 'In Progress' => ['var(--primary-light)', 'var(--primary)'], 'Cancelled' => ['#fee2e2', '#dc2626']][$po->status] ?? ['#f3f4f6', '#6b7280']; @endphp
                        <span style="font-size:.62rem;padding:2px 8px;border-radius:20px;font-weight:600;background:{{ $sc[0] }};color:{{ $sc[1] }}">{{ $po->status }}</span>
                    </div>
                </div>
                @empty
                <div class="text-center py-3" style="color:var(--text-muted);font-size:.8rem">Belum ada PO.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endif
</div>

{{-- Modal Tambah --}}
<div class="modal fade" id="addSupplierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Tambah Supplier</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('suppliers.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Supplier <span class="text-danger">*</span></label>
                            <input type="text" name="supplier_name" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Source Type <span class="text-danger">*</span></label>
                            <select name="source_type" class="form-select" id="addSourceType" onchange="toggleOrigin('add')">
                                <option value="Local">Local</option>
                                <option value="Import">Import</option>
                            </select>
                        </div>
                        <div class="col-md-3" id="addOriginWrap" style="display:none">
                            <label class="form-label">Negara Asal</label>
                            <input type="text" name="origin_country" class="form-control" placeholder="China, India, dll">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategori Produk</label>
                            <input type="text" name="product_category" class="form-control" placeholder="Solvent, Resin, Pigment, dll">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Term</label>
                            <input type="text" name="payment_term" class="form-control" placeholder="Net 30, COD, dll">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">PIC Name <span class="text-danger">*</span></label>
                            <input type="text" name="pic_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Posisi PIC</label>
                            <input type="text" name="pic_position" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Rating (0-5)</label>
                            <input type="number" name="rating" class="form-control" min="0" max="5" step="0.1" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="Active">Active</option>
                                <option value="Non-Active">Non-Active</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Relationship</label>
                            <select name="relationship_status" class="form-select">
                                <option value="Potential">Potential</option>
                                <option value="Existing">Existing</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check">
                                <input type="hidden" name="is_preferred" value="0">
                                <input type="checkbox" name="is_preferred" value="1" class="form-check-input" id="addPreferred">
                                <label class="form-check-label" for="addPreferred">Preferred Supplier</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alamat</label>
                            <textarea name="address" class="form-control" rows="2"></textarea>
                        </div>

                        {{-- Inline PICs --}}
                        <div class="col-12 mt-2">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div style="font-size:.78rem;font-weight:600;color:var(--primary)"><i class="fas fa-users me-1"></i> PIC Perusahaan</div>
                                <button type="button" class="btn btn-sm btn-outline-primary" style="font-size:.7rem;padding:2px 8px" onclick="addSupPicRow('addSupPicsContainer')"><i class="fas fa-plus me-1"></i> Add PIC</button>
                            </div>
                            <div id="addSupPicsContainer"></div>
                        </div>

                        {{-- Inline Products --}}
                        <div class="col-12 mt-1">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div style="font-size:.78rem;font-weight:600;color:var(--primary)"><i class="fas fa-box me-1"></i> Produk Supplier</div>
                                <button type="button" class="btn btn-sm btn-outline-primary" style="font-size:.7rem;padding:2px 8px" onclick="addSupProductRow('addSupProductsContainer')"><i class="fas fa-plus me-1"></i> Add Produk</button>
                            </div>
                            <div id="addSupProductsContainer"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Edit --}}
<div class="modal fade" id="editSupplierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Edit Supplier</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editSupplierForm">
                @csrf @method('PUT')
                <input type="hidden" name="pics_submitted" value="1">
                <input type="hidden" name="products_submitted" value="1">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Supplier <span class="text-danger">*</span></label>
                            <input type="text" name="supplier_name" id="esName" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Source Type</label>
                            <select name="source_type" id="esSourceType" class="form-select" onchange="toggleOrigin('edit')">
                                <option value="Local">Local</option>
                                <option value="Import">Import</option>
                            </select>
                        </div>
                        <div class="col-md-3" id="editOriginWrap" style="display:none">
                            <label class="form-label">Negara Asal</label>
                            <input type="text" name="origin_country" id="esOrigin" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategori Produk</label>
                            <input type="text" name="product_category" id="esCategory" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">PIC Name</label>
                            <input type="text" name="pic_name" id="esPic" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Posisi PIC</label>
                            <input type="text" name="pic_position" id="esPicPosition" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" id="esPhone" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="esEmail" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Rating</label>
                            <input type="number" name="rating" id="esRating" class="form-control" min="0" max="5" step="0.1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" id="esStatus" class="form-select">
                                <option value="Active">Active</option>
                                <option value="Non-Active">Non-Active</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Relationship</label>
                            <select name="relationship_status" id="esRelationship" class="form-select">
                                <option value="Potential">Potential</option>
                                <option value="Existing">Existing</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check">
                                <input type="hidden" name="is_preferred" value="0">
                                <input type="checkbox" name="is_preferred" value="1" class="form-check-input" id="esPreferred">
                                <label class="form-check-label" for="esPreferred">Preferred Supplier</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Term</label>
                            <input type="text" name="payment_term" id="esPaymentTerm" class="form-control" placeholder="Net 30, COD, dll">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alamat</label>
                            <textarea name="address" id="esAddress" class="form-control" rows="2"></textarea>
                        </div>
                    </div>

                    {{-- Inline PICs (edit) --}}
                    <div class="mt-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div style="font-size:.78rem;font-weight:600;color:var(--primary)"><i class="fas fa-users me-1"></i> PIC Perusahaan</div>
                            <button type="button" class="btn btn-sm btn-outline-primary" style="font-size:.7rem;padding:2px 8px" onclick="addSupPicRow('editSupPicsContainer')"><i class="fas fa-plus me-1"></i> Add PIC</button>
                        </div>
                        <div id="editSupPicsContainer"></div>
                        <div id="editSupPicsExisting" class="mt-2"></div>
                    </div>

                    {{-- Inline Products (edit) --}}
                    <div class="mt-2">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div style="font-size:.78rem;font-weight:600;color:var(--primary)"><i class="fas fa-box me-1"></i> Produk Supplier</div>
                            <button type="button" class="btn btn-sm btn-outline-primary" style="font-size:.7rem;padding:2px 8px" onclick="addSupProductRow('editSupProductsContainer')"><i class="fas fa-plus me-1"></i> Add Produk</button>
                        </div>
                        <div id="editSupProductsExisting" class="mt-1 mb-2"></div>
                        <div id="editSupProductsContainer"></div>
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

{{-- Modal Import Supplier --}}
<div class="modal fade" id="importSupplierModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h6 class="modal-title fw-bold">Import Database Supplier</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" action="{{ route('suppliers.import') }}" enctype="multipart/form-data">@csrf
            <div class="modal-body">
                <div class="alert alert-info py-2" style="font-size:.75rem">
                    Gunakan template agar susunan kolom sesuai. Data dengan nama supplier yang sama akan diperbarui.
                </div>
                <label class="form-label">File Excel</label>
                <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                <div class="form-text">Format: XLSX, XLS, atau CSV. Maksimal 5 MB.</div>
                <a href="{{ route('suppliers.template') }}" class="btn btn-link btn-sm px-0 mt-2"><i class="fas fa-download me-1"></i> Download Template Excel Supplier</a>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-file-import me-1"></i> Import</button></div>
        </form>
    </div></div>
</div>

{{-- Modal Produk Supplier --}}
<div class="modal fade" id="supplierProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Produk Supplier — <span id="spModalName"></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                {{-- List produk existing --}}
                <div id="spProductList" class="mb-3"></div>
                {{-- Form tambah produk --}}
                <div style="background:#f9fafb;border-radius:8px;padding:12px">
                    <div style="font-size:.78rem;font-weight:600;margin-bottom:8px">Tambah Produk</div>
                    <form id="addSupplierProductForm" method="POST">
                        @csrf
                        <div class="row g-2">
                            <div class="col-5">
                                <input type="text" name="product_name" class="form-control form-control-sm" placeholder="Nama produk *" required>
                            </div>
                            <div class="col-3">
                                <select name="unit" class="form-select form-select-sm">
                                    <option value="ton">ton</option>
                                    <option value="kg">kg</option>
                                    <option value="liter">liter</option>
                                    <option value="drum">drum</option>
                                    <option value="pcs">pcs</option>
                                </select>
                            </div>
                            <div class="col-4">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-plus me-1"></i> Tambah
                                </button>
                            </div>
                            <div class="col-12">
                                <input type="text" name="description" class="form-control form-control-sm" placeholder="Keterangan (opsional)">
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


@php
    $supplierEditSource = $suppliers->getCollection()
        ->merge($selectedSupplier ? collect([$selectedSupplier]) : collect())
        ->unique('id')
        ->values();

    $supplierEditData = $supplierEditSource->mapWithKeys(function ($s) {
        return [$s->id => [
            'id' => $s->id,
            'supplier_name' => $s->supplier_name,
            'source_type' => $s->source_type,
            'pic_name' => $s->pic_name,
            'pic_position' => $s->pic_position,
            'phone' => $s->phone,
            'email' => $s->email,
            'product_category' => $s->product_category,
            'origin_country' => $s->origin_country,
            'payment_term' => $s->payment_term,
            'address' => $s->address,
            'status' => $s->status,
            'relationship_status' => $s->relationship_status,
            'is_preferred' => (bool) $s->is_preferred,
            'rating' => $s->rating,
            'pics' => $s->relationLoaded('pics')
                ? $s->pics->map(function ($pic) {
                    return [
                        'pic_name' => $pic->pic_name,
                        'pic_position' => $pic->pic_position,
                        'phone' => $pic->phone,
                        'email' => $pic->email,
                    ];
                })->values()
                : [],
            'products' => $s->relationLoaded('products')
                ? $s->products->map(function ($product) {
                    return [
                        'product_name' => $product->product_name,
                        'unit' => $product->unit,
                        'description' => $product->description,
                    ];
                })->values()
                : [],
        ]];
    });
@endphp

@push('scripts')
<script>
function showSupTab(tab, el) {
    document.querySelectorAll('#supTabs .nav-link').forEach(a => a.classList.remove('active'));
    el.classList.add('active');
    ['overview','pics','products','transaction'].forEach(t => {
        const d = document.getElementById('suptab-'+t);
        if (d) d.style.display = t===tab ? 'block' : 'none';
    });
}

function toggleOrigin(mode) {
    const sel  = document.getElementById(mode === 'add' ? 'addSourceType' : 'esSourceType');
    const wrap = document.getElementById(mode === 'add' ? 'addOriginWrap' : 'editOriginWrap');
    wrap.style.display = sel.value === 'Import' ? 'block' : 'none';
}

const supplierEditData = @json($supplierEditData);

function openEditSupplier(id) {
    const data = supplierEditData[id];
    if (!data) return;

    document.getElementById('editSupplierForm').action = `/suppliers/${id}`;
    document.getElementById('esName').value         = data.supplier_name || '';
    document.getElementById('esSourceType').value   = data.source_type || 'Local';
    document.getElementById('esOrigin').value       = data.origin_country || '';
    document.getElementById('esCategory').value     = data.product_category || '';
    document.getElementById('esPic').value          = data.pic_name || '';
    document.getElementById('esPicPosition').value  = data.pic_position || '';
    document.getElementById('esPaymentTerm').value  = data.payment_term || '';
    document.getElementById('esAddress').value      = data.address || '';
    document.getElementById('esPhone').value        = data.phone || '';
    document.getElementById('esEmail').value        = data.email || '';
    document.getElementById('esStatus').value       = data.status || 'Active';
    document.getElementById('esRelationship').value = data.relationship_status || 'Potential';
    document.getElementById('esPreferred').checked  = !!data.is_preferred;
    document.getElementById('esRating').value       = data.rating || 0;
    toggleOrigin('edit');

    const editSupPicsExisting = document.getElementById('editSupPicsExisting');
    const editSupProductsExisting = document.getElementById('editSupProductsExisting');
    const editSupPicsContainer = document.getElementById('editSupPicsContainer');
    const editSupProductsContainer = document.getElementById('editSupProductsContainer');

    editSupPicsExisting.innerHTML = '';
    editSupProductsExisting.innerHTML = '';
    editSupPicsContainer.innerHTML = '';
    editSupProductsContainer.innerHTML = '';

    supPicIdx = 0;
    supProdIdx = 0;

    (data.pics || []).forEach(function(pic) {
        addSupPicRow('editSupPicsContainer', pic);
    });

    (data.products || []).forEach(function(product) {
        addSupProductRow('editSupProductsContainer', product);
    });

    if ((data.pics || []).length === 0) {
        editSupPicsExisting.innerHTML = '<div style="font-size:.75rem;color:#9ca3af"><i>Belum ada PIC tambahan.</i></div>';
    }

    if ((data.products || []).length === 0) {
        editSupProductsExisting.innerHTML = '<div style="font-size:.75rem;color:#9ca3af"><i>Belum ada produk supplier.</i></div>';
    }

    new bootstrap.Modal(document.getElementById('editSupplierModal')).show();
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

// ── Inline Supplier PIC rows ──
let supPicIdx = 0;
function addSupPicRow(containerId, data = {}) {
    const i = supPicIdx++;
    const html = `<div class="row g-2 mb-2 align-items-center" id="supPic_${i}">
        <div class="col-4"><input type="text" name="pics[${i}][pic_name]" class="form-control form-control-sm" placeholder="Nama PIC *" value="${escapeHtml(data.pic_name)}" required></div>
        <div class="col-3"><input type="text" name="pics[${i}][pic_position]" class="form-control form-control-sm" placeholder="Jabatan" value="${escapeHtml(data.pic_position)}"></div>
        <div class="col-2"><input type="text" name="pics[${i}][phone]" class="form-control form-control-sm" placeholder="Phone" value="${escapeHtml(data.phone)}"></div>
        <div class="col-2"><input type="email" name="pics[${i}][email]" class="form-control form-control-sm" placeholder="Email" value="${escapeHtml(data.email)}"></div>
        <div class="col-1 text-end"><button type="button" class="btn btn-sm btn-outline-danger p-1" onclick="document.getElementById('supPic_${i}').remove()"><i class="fas fa-times"></i></button></div>
    </div>`;
    document.getElementById(containerId).insertAdjacentHTML('beforeend', html);
}

// ── Inline Supplier Product rows ──
let supProdIdx = 0;
function addSupProductRow(containerId, data = {}) {
    const i = supProdIdx++;
    const html = `<div class="row g-2 mb-2 align-items-center" id="supProd_${i}">
        <div class="col-5"><input type="text" name="products[${i}][product_name]" class="form-control form-control-sm" placeholder="Nama Produk *" value="${escapeHtml(data.product_name)}" required></div>
        <div class="col-3"><input type="text" name="products[${i}][unit]" class="form-control form-control-sm" placeholder="Satuan (ton, kg...)" value="${escapeHtml(data.unit)}"></div>
        <div class="col-3"><input type="text" name="products[${i}][description]" class="form-control form-control-sm" placeholder="Keterangan" value="${escapeHtml(data.description)}"></div>
        <div class="col-1 text-end"><button type="button" class="btn btn-sm btn-outline-danger p-1" onclick="document.getElementById('supProd_${i}').remove()"><i class="fas fa-times"></i></button></div>
    </div>`;
    document.getElementById(containerId).insertAdjacentHTML('beforeend', html);
}

// Supplier Products (AJAX via form submit → reload)
const supplierPics = @json($suppliers->pluck('pics', 'id'));
const supplierProducts = @json($suppliers->pluck('products', 'id'));
const pendingSupplierProductIds = @json(\App\Models\DeletionRequest::pendingIdsForModule('supplier-products'));
const canDeleteSupplierProductDirectly = @json(auth()->user()->isAdmin());

function submitSupplierProductDeletion(form) {
    if (canDeleteSupplierProductDirectly) {
        return confirm(`Hapus produk ${form.dataset.label}?`);
    }

    const reason = prompt('Alasan permintaan hapus (opsional):');
    if (reason === null) return false;
    form.elements.reason.value = reason;
    return confirm(`Ajukan permintaan hapus untuk produk ${form.dataset.label}?`);
}

function openProductModal(supplierId, supplierName) {
    document.getElementById('spModalName').textContent = supplierName;
    document.getElementById('addSupplierProductForm').action = `/suppliers/${supplierId}/products`;

    // Render existing products
    const products = supplierProducts[supplierId] || [];
    const list = document.getElementById('spProductList');
    if (products.length === 0) {
        list.innerHTML = '<div style="font-size:.8rem;color:#9ca3af">Belum ada produk.</div>';
    } else {
        list.innerHTML = products.map(p => `
            <div class="d-flex align-items-center justify-content-between mb-2 pb-2" style="border-bottom:1px solid #f3f4f6">
                <div>
                    <div style="font-size:.82rem;font-weight:600">${p.product_name}</div>
                    <div style="font-size:.72rem;color:#6b7280">${p.unit}${p.description ? ' · ' + p.description : ''}</div>
                </div>
                ${pendingSupplierProductIds.includes(Number(p.id)) && !canDeleteSupplierProductDirectly ? `
                <span class="badge bg-warning text-dark" style="font-size:.65rem"><i class="fas fa-clock me-1"></i>Menunggu Hapus</span>
                ` : `
                <form method="POST" action="{{ route('deletion-requests.store') }}" data-label="${escapeHtml(p.product_name)}" onsubmit="return submitSupplierProductDeletion(this)" style="display:inline">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="module" value="supplier-products">
                    <input type="hidden" name="model_id" value="${p.id}">
                    <input type="hidden" name="reason" value="">
                    <button type="submit" style="color:${canDeleteSupplierProductDirectly ? '#ef4444' : '#d97706'};background:none;border:none;cursor:pointer"><i class="fas ${canDeleteSupplierProductDirectly ? 'fa-times' : 'fa-trash-restore-alt'}"></i></button>
                </form>
                `}
            </div>
        `).join('');
    }

    new bootstrap.Modal(document.getElementById('supplierProductModal')).show();
}
</script>
@endpush
@endsection
