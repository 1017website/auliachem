{{--
    Reusable Add Activity Modal (Revisi #6)
    Satu modal untuk semua menu: Sales Activity, Customers, Leads, Calendar.
    Selalu submit ke route('sales.activity.store') agar pipeline_stage & syncToCustomer
    konsisten dijalankan (memperbaiki Revisi #1 & #4).

    Parameter opsional (via @include('components.activity-modal', [...])):
    - $lockClient (bool)  : true = client sudah ditentukan, dropdown disembunyikan/dikunci
    - $preType    (string): 'lead' | 'customer'
    - $preId      (int)   : id lead / customer yang dipilih
    - $preName    (string): nama company untuk ditampilkan saat terkunci
    - $preStatus  (string): 'Existing' | 'Potential' (khusus customer)
    - $preStage   (string): pipeline stage saat ini (khusus lead)
    - $redirect   (string): url tujuan setelah simpan (opsional)
--}}
@php
    $lockClient = $lockClient ?? false;
    $preType    = $preType    ?? null;
    $preId      = $preId      ?? null;
    $preName    = $preName    ?? null;
    $preStatus  = $preStatus  ?? null;
    $preStage   = $preStage   ?? null;
    $redirect   = $redirect   ?? null;

    // Sumber data dropdown diambil langsung dari tabel customers — bukan dari leads —
    // untuk menghindari duplikasi (setiap lead selalu punya pasangan customer):
    //   - Customer status "Potential" => entitas "Leads" (prospek)
    //   - Customer status "Existing"  => entitas "Customer Existing"
    // client_ref dikirim sebagai customer:{id} untuk keduanya; store() otomatis
    // me-resolve lead terkait dari customer_id.
    $potentialCustomers = \App\Models\Customer::where('status','Potential')->orderBy('company_name')->get();
    $existingCustomers  = \App\Models\Customer::where('status','Existing')->orderBy('company_name')->get();
@endphp

<div class="modal fade" id="addActivityModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Add Activity</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('sales.activity.store') }}" enctype="multipart/form-data" id="activityForm">
                @csrf
                @if($redirect)
                    <input type="hidden" name="redirect_to" value="{{ $redirect }}">
                @endif
                <div class="modal-body">

                    @if($lockClient && $preId)
                        {{-- Client terkunci (dipanggil dari halaman customer/lead tertentu) --}}
                        <div class="mb-3">
                            <label class="form-label">Client / Company</label>
                            <input type="text" class="form-control" value="{{ $preName }}" readonly
                                style="background:#f9fafb;font-weight:600">
                            <input type="hidden" name="client_ref"
                                value="{{ $preType }}:{{ $preId }}"
                                id="actLeadSelect"
                                data-type="{{ $preType }}"
                                data-id="{{ $preId }}"
                                data-status="{{ $preStatus }}"
                                data-stage="{{ $preStage }}">
                            <input type="hidden" name="lead_id"     id="actLeadHidden"     value="{{ $preType === 'lead' ? $preId : '' }}">
                            <input type="hidden" name="customer_id" id="actCustomerHidden" value="{{ $preType === 'customer' ? $preId : '' }}">
                        </div>
                    @else
                        {{-- Pilih client bebas --}}
                        <div class="mb-3">
                            <label class="form-label">Client / Company <span class="text-danger">*</span></label>
                            <select name="client_ref" class="form-select" id="actLeadSelect" onchange="onLeadChange(this)">
                                <option value="">Pilih atau cari client</option>
                                <optgroup label="— Leads —">
                                @foreach($potentialCustomers as $cust)
                                    <option value="customer:{{ $cust->id }}" data-type="customer" data-id="{{ $cust->id }}" data-status="Potential" data-stage="">{{ $cust->company_name }} (Lead)</option>
                                @endforeach
                                </optgroup>
                                <optgroup label="— Customer Existing —">
                                @foreach($existingCustomers as $cust)
                                    <option value="customer:{{ $cust->id }}" data-type="customer" data-id="{{ $cust->id }}" data-status="Existing" data-stage="">{{ $cust->company_name }} (Existing)</option>
                                @endforeach
                                </optgroup>
                            </select>
                            <input type="hidden" name="lead_id"     id="actLeadHidden"     value="">
                            <input type="hidden" name="customer_id" id="actCustomerHidden" value="">
                        </div>
                    @endif

                    {{-- Pipeline Stage — muncul setelah client dipilih --}}
                    <div class="mb-3" id="stageWrap" style="display:none">
                        <label class="form-label">Update Pipeline Stage</label>
                        <select name="pipeline_stage" class="form-select" id="actStageSelect"></select>
                        <div class="form-text">Opsional — biarkan jika tidak ingin mengubah stage</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Activity Type <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2 flex-wrap">
                            @foreach(['Call','Visit','Email','Note','Others'] as $t)
                            <div>
                                <input type="radio" class="btn-check" name="type" id="type_{{ $t }}" value="{{ $t }}"
                                    {{ $t === 'Call' ? 'checked' : '' }} onchange="onTypeChange('{{ $t }}')">
                                <label class="btn btn-sm btn-outline-secondary" for="type_{{ $t }}">{{ $t }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Foto Upload (muncul saat Visit) --}}
                    <div class="mb-3" id="photoWrap" style="display:none">
                        <label class="form-label">Foto Kunjungan</label>
                        <input type="file" name="photo" id="photoFileInput" class="form-control"
                            accept="image/jpg,image/jpeg,image/png,image/webp" onchange="previewPhoto(this)">
                        <div class="form-text">JPG/PNG/WebP, maks 3MB</div>
                        <div id="photoPreview" class="mt-2" style="display:none">
                            <img id="previewImg" src="" alt="Preview"
                                style="max-width:100%;max-height:180px;border-radius:8px;border:1px solid #e5e7eb">
                            <div style="font-size:.7rem;color:#059669;margin-top:4px">
                                <i class="fas fa-check-circle me-1"></i>Foto siap diupload
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label class="form-label">Date &amp; Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="activity_at" id="actDateTime" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject <span class="text-danger">*</span></label>
                        <input type="text" name="subject" class="form-control" placeholder="Judul aktivitas..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Tulis catatan aktivitas..."></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label">Next Follow Up</label>
                            <input type="date" name="next_follow_up" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select">
                                <option value="Pending">Pending</option>
                                <option value="Done">Done</option>
                                <option value="Planned">Planned</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Save Activity</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// ── Unified Add Activity modal logic (Revisi #6) ──
(function() {
    if (window.__activityModalInit) return;   // cegah definisi ganda
    window.__activityModalInit = true;

    const STAGE_FULL = [
        ['Identifying','Identifying'],
        ['Approaching','Approaching'],
        ['Follow Up','Follow Up'],
        ['Won','Won/Closing'],
        ['Lost','Lost'],
        ['Maintaining','Maintaining'],
    ];
    const STAGE_EXISTING = [
        ['Follow Up','Follow Up'],
        ['Won','Won/Closing'],
        ['Lost','Lost'],
        ['Maintaining','Maintaining'],
    ];

    function fillStageOptions(list, currentStage) {
        const stSel = document.getElementById('actStageSelect');
        if (!stSel) return;
        stSel.innerHTML = '';
        list.forEach(function(pair) {
            const opt = document.createElement('option');
            opt.value = pair[0];
            opt.textContent = pair[1];
            if (currentStage && currentStage === pair[0]) opt.selected = true;
            stSel.appendChild(opt);
        });
    }

    function applyClient(type, id, stage, status) {
        const wrap = document.getElementById('stageWrap');
        const leadHidden = document.getElementById('actLeadHidden');
        const custHidden = document.getElementById('actCustomerHidden');
        if (!wrap) return;

        if (!type || !id) {
            wrap.style.display = 'none';
            if (leadHidden) leadHidden.value = '';
            if (custHidden) custHidden.value = '';
            return;
        }

        if (type === 'lead') {
            if (leadHidden) leadHidden.value = id;
            if (custHidden) custHidden.value = '';
            fillStageOptions(STAGE_FULL, stage || '');
        } else {
            if (leadHidden) leadHidden.value = '';
            if (custHidden) custHidden.value = id;
            fillStageOptions(status === 'Existing' ? STAGE_EXISTING : STAGE_FULL, '');
        }
        wrap.style.display = 'block';
    }

    // Dropdown change handler (mode bebas)
    window.onLeadChange = function(sel) {
        const opt = sel.options ? sel.options[sel.selectedIndex] : null;
        if (!sel.value || !opt) { applyClient(null); return; }
        applyClient(opt.dataset.type, opt.dataset.id, opt.dataset.stage || '', opt.dataset.status || '');
    };

    window.onTypeChange = function(type) {
        const pw = document.getElementById('photoWrap');
        const pp = document.getElementById('photoPreview');
        if (pw) pw.style.display = type === 'Visit' ? 'block' : 'none';
        if (type !== 'Visit' && pp) pp.style.display = 'none';
    };

    window.previewPhoto = function(input) {
        const preview = document.getElementById('photoPreview');
        const img = document.getElementById('previewImg');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => { img.src = e.target.result; preview.style.display = 'block'; };
            reader.readAsDataURL(input.files[0]);
        }
    };

    // Saat modal dibuka: jika client sudah terkunci (hidden input), set stage options.
    document.addEventListener('shown.bs.modal', function(e) {
        if (e.target.id !== 'addActivityModal') return;
        const ref = document.getElementById('actLeadSelect');
        if (ref && ref.tagName === 'INPUT' && ref.dataset.id) {
            applyClient(ref.dataset.type, ref.dataset.id, ref.dataset.stage || '', ref.dataset.status || '');
        }
    });

    // Helper: format Date → 'YYYY-MM-DD HH:mm:ss'
    function _fmtDateTime(d) {
        if (!(d instanceof Date) || isNaN(d)) return '';
        const p = n => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${p(d.getMonth()+1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}:00`;
    }
    function _fmtDate(d) {
        if (!(d instanceof Date) || isNaN(d)) return '';
        const p = n => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${p(d.getMonth()+1)}-${p(d.getDate())}`;
    }
    // Ambil Date dari instance Air Datepicker, atau parse dari string value.
    function _resolveDate(inputEl) {
        if (!inputEl) return null;
        if (inputEl._adp && inputEl._adp.selectedDates && inputEl._adp.selectedDates[0]) {
            return inputEl._adp.selectedDates[0];
        }
        const v = (inputEl.value || '').trim().replace(' ', 'T');
        const d = v ? new Date(v) : null;
        return (d && !isNaN(d)) ? d : null;
    }

    // ── Submit guard: pastikan data valid sebelum POST ──
    const _actForm = document.getElementById('activityForm');
    if (_actForm) {
        _actForm.addEventListener('submit', function(ev) {
            // Field TAMPILAN (punya _adp). Hidden field membawa name="activity_at".
            const dtView   = document.getElementById('actDateTime');
            const dtHidden = _actForm.querySelector('input[name="activity_at"]');
            const nfHidden = _actForm.querySelector('input[name="next_follow_up"]');
            // next_follow_up: field tampilan = sibling sebelum hidden-nya
            const nfView   = nfHidden && nfHidden.previousElementSibling && nfHidden.previousElementSibling._adp
                             ? nfHidden.previousElementSibling : null;

            // (1) activity_at → ISO 'Y-m-d H:i:s' yang selalu valid di Laravel
            const dtDate = _resolveDate(dtView) || _resolveDate(dtHidden);
            if (dtHidden) dtHidden.value = dtDate ? _fmtDateTime(dtDate) : '';

            // (2) next_follow_up → 'Y-m-d' (opsional)
            if (nfHidden) {
                const nfDate = _resolveDate(nfView) || _resolveDate(nfHidden);
                nfHidden.value = nfDate ? _fmtDate(nfDate) : '';
            }

            // (3) Pastikan ada activity type terpilih
            const typeChecked = _actForm.querySelector('input[name="type"]:checked');
            if (!typeChecked) {
                ev.preventDefault();
                alert('Pilih jenis activity terlebih dahulu.');
                return;
            }

            // (4) Pastikan subject & tanggal terisi
            const subj = _actForm.querySelector('input[name="subject"]');
            if (subj && !subj.value.trim()) {
                ev.preventDefault();
                alert('Subject wajib diisi.');
                subj.focus();
                return;
            }
            if (dtHidden && !dtHidden.value.trim()) {
                ev.preventDefault();
                alert('Tanggal & waktu wajib diisi. Klik field tanggal lalu pilih.');
                return;
            }
        });
    }
})();
</script>
@endpush