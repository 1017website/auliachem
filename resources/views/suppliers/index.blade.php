@extends('layouts.app')
@section('title', 'Database Supplier')
@section('page-title', 'Database Supplier')
@section('page-subtitle', 'Kelola data supplier Local dan Import')

@section('content')
<div class="row g-3">
<div class="col-12">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div class="d-flex gap-2">
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                <i class="fas fa-plus me-1"></i> Tambah Supplier
            </button>
            <a href="{{ route('suppliers.export') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-download me-1"></i> Export Excel
            </a>
        </div>
        <div class="d-flex gap-3 flex-wrap">
            @foreach([[$totalSupplier,'Total','#111'],[$localSupplier,'Local','#2563eb'],[$importSupplier,'Import','#7c3aed'],[$existingSupplier,'Existing','#059669'],[$potentialSupplier,'Potential','#f97316']] as $s)
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
                            <th class="px-3 py-2">Supplier</th>
                            <th class="py-2">PIC</th>
                            <th class="py-2">Phone</th>
                            <th class="py-2">Kategori Produk</th>
                            <th class="py-2">Source</th>
                            <th class="py-2">Relationship</th>
                            <th class="py-2">Status</th>
                            <th class="py-2">Rating</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suppliers as $s)
                        <tr>
                            <td class="px-3 py-2">
                                <div style="font-weight:700">{{ $s->supplier_name }}</div>
                                @if($s->is_preferred)<span style="font-size:10px;color:#d97706">⭐ Preferred</span>@endif
                            </td>
                            <td class="py-2">
                                <div>{{ $s->pic_name }}</div>
                                <div style="font-size:11px;color:#6b7280">{{ $s->pic_position }}</div>
                            </td>
                            <td class="py-2" style="font-size:12px">{{ $s->phone }}</td>
                            <td class="py-2" style="font-size:12px">{{ $s->product_category ?? '-' }}</td>
                            <td class="py-2">
                                <span style="font-size:11px;padding:2px 8px;border-radius:20px;font-weight:600;
                                    background:{{ $s->source_type==='Local'?'#dbeafe':'#ede9fe' }};
                                    color:{{ $s->source_type==='Local'?'#1d4ed8':'#7c3aed' }}">
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
                                <button class="btn btn-sm btn-outline-secondary" style="padding:3px 7px"
                                    onclick="openEditSupplier(
                                        {{ $s->id }},'{{ addslashes($s->supplier_name) }}',
                                        '{{ $s->source_type }}','{{ addslashes($s->pic_name) }}',
                                        '{{ $s->phone }}','{{ $s->email }}',
                                        '{{ addslashes($s->product_category) }}','{{ $s->origin_country }}',
                                        '{{ $s->status }}','{{ $s->relationship_status }}',
                                        '{{ $s->is_preferred?1:0 }}','{{ $s->rating }}'
                                    )">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <form method="POST" action="{{ route('suppliers.destroy', $s) }}" class="d-inline"
                                    onsubmit="return confirm('Hapus supplier {{ addslashes($s->supplier_name) }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" style="padding:3px 7px">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center py-4" style="color:#9ca3af">Belum ada data supplier</td></tr>
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
function toggleOrigin(mode) {
    const sel  = document.getElementById(mode === 'add' ? 'addSourceType' : 'esSourceType');
    const wrap = document.getElementById(mode === 'add' ? 'addOriginWrap' : 'editOriginWrap');
    wrap.style.display = sel.value === 'Import' ? 'block' : 'none';
}

function openEditSupplier(id, name, sourceType, pic, phone, email, category, origin, status, relationship, preferred, rating) {
    document.getElementById('editSupplierForm').action = `/suppliers/${id}`;
    document.getElementById('esName').value         = name;
    document.getElementById('esSourceType').value   = sourceType;
    document.getElementById('esOrigin').value       = origin || '';
    document.getElementById('esCategory').value     = category || '';
    document.getElementById('esPic').value          = pic;
    document.getElementById('esPhone').value        = phone;
    document.getElementById('esEmail').value        = email || '';
    document.getElementById('esStatus').value       = status;
    document.getElementById('esRelationship').value = relationship;
    document.getElementById('esPreferred').checked  = preferred == '1';
    document.getElementById('esRating').value       = rating;
    toggleOrigin('edit');
    new bootstrap.Modal(document.getElementById('editSupplierModal')).show();
}
</script>
@endpush
@endsection
