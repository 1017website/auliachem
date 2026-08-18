<?php

namespace App\Http\Controllers;

use App\Helpers\ExcelExport;
use App\Helpers\ExcelImport;
use App\Models\Customer;
use App\Models\CustomerPic;
use App\Models\User;
use App\Models\Activity;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $status   = $request->get('status');
        $industry = $request->get('industry');
        $search   = $request->get('search');
        $salesId  = $request->get('user_id');

        $query = Customer::with(['salesUser', 'purchaseOrders', 'activities', 'pics', 'productItems']);
        if ($status && $status !== 'all')     $query->where('status', $status);
        if ($industry && $industry !== 'all') $query->where('industry', $industry);

        // Sales Executive hanya lihat customer miliknya
        if (auth()->user()->isSalesExecutive()) {
            $query->where('user_id', auth()->id());
        } elseif ($salesId) {
            $query->where('user_id', $salesId);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%$search%")
                  ->orWhere('pic_name', 'like', "%$search%")
                  ->orWhere('phone', 'like', "%$search%");
            });
        }

        $customers         = $query->orderBy('company_name')->paginate(10)->withQueryString();
        $totalCustomer     = Customer::count();
        $potentialCustomer = Customer::where('status', 'Potential')->count();
        $existingCustomer  = Customer::where('status', 'Existing')->count();
        $industries        = Customer::whereNotNull('industry')->distinct()->pluck('industry')->filter()->sort()->values();
        $salesUsers        = User::assignable()->orderBy('name')->get();

        $selectedCustomer = $request->get('selected_id')
            ? Customer::with(['salesUser','purchaseOrders','activities.salesUser','leads','pics','productItems'])->find($request->get('selected_id'))
            : null;

        return view('customers.index', compact(
            'customers','totalCustomer','potentialCustomer','existingCustomer',
            'industries','salesUsers','selectedCustomer','status','industry','search','salesId'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name'   => 'required|string|max:255',
            'pic_name'       => 'required|string|max:255',
            'pic_position'   => 'nullable|string|max:100',
            'phone'          => 'required|string|max:20',
            'email'          => 'nullable|email|max:255',
            'industry'       => 'nullable|string|max:100',
            'location'       => 'nullable|string|max:255',
            'address'        => 'nullable|string',
            'user_id'        => ['required', Rule::exists('users', 'id')->where(
                fn ($query) => $query->where('status', 'Active')->where('role', '!=', User::ROLE_DEVELOPER)
            )],
            'customer_since' => 'nullable|date',
            'notes'          => 'nullable|string',
            'pics'                => 'nullable|array',
            'pics.*.pic_name'     => 'required_with:pics|string|max:255',
            'pics.*.pic_position' => 'nullable|string|max:100',
            'pics.*.phone'        => 'nullable|string|max:20',
            'pics.*.email'        => 'nullable|email|max:255',
            // Kebutuhan produk — field disamakan dengan leads (product_name, qty, unit)
            'products_list'                => 'nullable|array',
            'products_list.*.product_name' => 'required_with:products_list|string|max:255',
            'products_list.*.qty'          => 'nullable|numeric|min:0',
            'products_list.*.unit'         => 'nullable|string|max:100',
        ]);

        // Revisi #1: customer dari menu Customer SELALU Existing
        $validated['status'] = 'Existing';

        if (auth()->user()->isSalesExecutive()) {
            $validated['user_id'] = auth()->id();
        }

        $picsData     = $validated['pics'] ?? [];
        $productsList = $validated['products_list'] ?? [];
        unset($validated['pics'], $validated['products_list']);

        $customer = DB::transaction(function () use ($validated, $picsData, $productsList) {

            // Default customer_since jika kosong (karena langsung Existing)
            if (empty($validated['customer_since'])) {
                $validated['customer_since'] = now()->toDateString();
            }

            $customer = Customer::create($validated);

            // PIC utama + PIC tambahan
            foreach ($picsData as $i => $pic) {
                $customer->pics()->create([
                    'pic_name'     => $pic['pic_name'],
                    'pic_position' => $pic['pic_position'] ?? null,
                    'phone'        => $pic['phone'] ?? null,
                    'email'        => $pic['email'] ?? null,
                    'is_primary'   => $i === 0,
                ]);
            }

            // Kebutuhan produk -> tabel relasi customer_products
            foreach ($productsList as $prod) {
                $name = trim($prod['product_name'] ?? '');
                if ($name === '') continue;
                $customer->productItems()->create([
                    'product_name' => $name,
                    'qty'          => $prod['qty'] ?? 0,
                    'unit'         => trim($prod['unit'] ?? '') !== '' ? $prod['unit'] : 'ton',
                ]);
            }

            // Revisi #1: create customer existing sekaligus create lead stage Maintaining
            $lead = Lead::createWithUniqueCode([
                'customer_id'    => $customer->id,
                'company_name'   => $customer->company_name,
                'pic_name'       => $customer->pic_name,
                'pic_position'   => $customer->pic_position,
                'phone'          => $customer->phone,
                'email'          => $customer->email,
                'address'        => $customer->address,
                'industry'       => $customer->industry,
                'location'       => $customer->location,
                'pipeline_stage' => 'Maintaining',
                'temperature'    => 'Warm',
                'user_id'        => $customer->user_id,
            ]);

            // Salin produk customer ke lead products agar konsisten
            foreach ($customer->productItems as $cp) {
                $lead->products()->create([
                    'product_name' => $cp->product_name,
                    'qty'          => $cp->qty ?? 0,
                    'unit'         => $cp->unit ?? 'ton',
                ]);
            }

            return $customer;
        });

        return redirect()->route('customers.index')->with('success', 'Customer berhasil ditambahkan & lead (Maintaining) dibuat otomatis.');
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'company_name'   => 'sometimes|string|max:255',
            'pic_name'       => 'sometimes|string|max:255',
            'pic_position'   => 'nullable|string|max:100',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:255',
            'industry'       => 'nullable|string|max:100',
            'location'       => 'nullable|string|max:255',
            'address'        => 'nullable|string',
            'user_id'        => ['sometimes', Rule::exists('users', 'id')->where(
                fn ($query) => $query->where('status', 'Active')->where('role', '!=', User::ROLE_DEVELOPER)
            )],
            'customer_since' => 'nullable|date',
            'notes'          => 'nullable|string',

            'pics'                => 'nullable|array',
            'pics.*.pic_name'     => 'nullable|string|max:255',
            'pics.*.pic_position' => 'nullable|string|max:100',
            'pics.*.phone'        => 'nullable|string|max:20',
            'pics.*.email'        => 'nullable|email|max:255',

            // Kebutuhan produk — product_name, qty, unit (sama seperti leads)
            'products_list'                => 'nullable|array',
            'products_list.*.product_name' => 'nullable|string|max:255',
            'products_list.*.qty'          => 'nullable|numeric|min:0',
            'products_list.*.unit'         => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($validated, $customer, $request) {

            if (auth()->user()->isSalesExecutive()) {
                $validated['user_id'] = auth()->id();
            }

            // Revisi #2: status customer TIDAK bisa diubah manual dari sini.
            // Status hanya naik ke Existing via sales activity (stage Won), atau
            // sudah Existing sejak dibuat dari menu Customer. Maka unset status.
            unset($validated['status']);

            $picsData     = $validated['pics'] ?? [];
            $productsList = $validated['products_list'] ?? [];
            unset($validated['pics'], $validated['products_list']);

            $customer->update($validated);

            /**
             * products_submitted: daftar produk dari modal edit dianggap final.
             * Replace seluruh customer_products dengan data dari form.
             */
            if ($request->has('products_submitted')) {
                $customer->productItems()->delete();
                foreach ($productsList as $product) {
                    $name = trim($product['product_name'] ?? '');
                    if ($name === '') continue;
                    $customer->productItems()->create([
                        'product_name' => $name,
                        'qty'          => $product['qty'] ?? 0,
                        'unit'         => trim($product['unit'] ?? '') !== '' ? $product['unit'] : 'ton',
                    ]);
                }
            }

            /**
             * pics_submitted: PIC tambahan dari modal edit dianggap final.
             */
            if ($request->has('pics_submitted')) {
                $customer->pics()->delete();

                foreach ($picsData as $pic) {
                    $picName = trim($pic['pic_name'] ?? '');
                    if ($picName === '') continue;

                    $customer->pics()->create([
                        'pic_name'     => $picName,
                        'pic_position' => $pic['pic_position'] ?? null,
                        'phone'        => $pic['phone'] ?? null,
                        'email'        => $pic['email'] ?? null,
                        'is_primary'   => false,
                    ]);
                }
            }

            // Auto-sync balik ke lead terkait SETELAH produk & PIC final,
            // agar mirror dua arah membaca data terbaru (termasuk hasil hapus).
            self::syncToLeads($customer->fresh(['pics', 'productItems']));
        });

        return redirect()->back()->with('success', 'Data customer berhasil diupdate.');
    }

    /**
     * Sync balik data customer ke lead-lead yang terkait (customer_id sama).
     *
     * Hanya field dasar (nama PT, PIC, kontak, industri, lokasi, sales)
     * yang disinkronkan. Pipeline stage & data sales lead TIDAK diubah,
     * karena progres pipeline adalah otoritas modul Leads.
     */
    public static function syncToLeads(Customer $customer): void
    {
        // Cegah loop: jika sedang sync dari arah Lead, jangan balik lagi.
        if (\App\Http\Controllers\LeadsController::$syncing) {
            return;
        }
        \App\Http\Controllers\LeadsController::$syncing = true;

        try {
            $customer->loadMissing(['pics', 'productItems']);

            $leadData = [
                'company_name' => $customer->company_name,
                'pic_name'     => $customer->pic_name,
                'pic_position' => $customer->pic_position,
                'phone'        => $customer->phone,
                'email'        => $customer->email,
                'address'      => $customer->address,
                'industry'     => $customer->industry,
                'location'     => $customer->location,
                'user_id'      => $customer->user_id,
            ];

            // Siapkan baris produk & PIC dari customer (sumber mirror saat ini).
            $productRows = $customer->productItems->map(fn ($p) => [
                'product_name' => $p->product_name,
                'qty'          => $p->qty ?? 0,
                'unit'         => trim($p->unit ?? '') !== '' ? $p->unit : 'ton',
            ])->all();

            $picRows = $customer->pics->map(fn ($pic) => [
                'pic_name'     => $pic->pic_name,
                'pic_position' => $pic->pic_position,
                'phone'        => $pic->phone,
                'email'        => $pic->email,
                'is_primary'   => (bool) $pic->is_primary,
            ])->all();

            $customer->leads()->each(function (Lead $lead) use ($leadData, $productRows, $picRows) {
                // Field dasar — updateQuietly agar tidak memicu notifikasi pipeline.
                $lead->updateQuietly($leadData);

                // Mirror produk lead = produk customer.
                $lead->products()->delete();
                foreach ($productRows as $row) {
                    $lead->products()->create($row);
                }

                // Mirror PIC lead = PIC customer.
                $lead->pics()->delete();
                foreach ($picRows as $row) {
                    $lead->pics()->create($row);
                }
            });
        } finally {
            \App\Http\Controllers\LeadsController::$syncing = false;
        }
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer dihapus.');
    }

    // ── Customer PICs ──
    public function storePic(Request $request, Customer $customer)
    {
        $request->validate([
            'pic_name'     => 'required|string|max:255',
            'pic_position' => 'nullable|string|max:100',
            'phone'        => 'nullable|string|max:20',
            'email'        => 'nullable|email|max:255',
        ]);
        $customer->pics()->create([
            'pic_name'     => $request->pic_name,
            'pic_position' => $request->pic_position,
            'phone'        => $request->phone,
            'email'        => $request->email,
            'is_primary'   => $customer->pics()->count() === 0,
        ]);
        self::syncToLeads($customer->fresh(['pics', 'productItems']));
        return redirect()->back()->with('success', 'PIC ditambahkan.');
    }

    public function destroyPic(Customer $customer, CustomerPic $pic)
    {
        abort_if((int) $pic->customer_id !== (int) $customer->id, 404);
        $pic->delete();
        self::syncToLeads($customer->fresh(['pics', 'productItems']));
        return redirect()->back()->with('success', 'PIC dihapus.');
    }

    // ── Transfer Sales (Admin only) ──
    public function transferSales(Request $request, Customer $customer)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $request->validate(['user_id' => ['required', Rule::exists('users', 'id')->where(
            fn ($query) => $query->where('status', 'Active')->where('role', '!=', User::ROLE_DEVELOPER)
        )]]);
        $customer->update(['user_id' => $request->user_id]);
        return redirect()->back()->with('success', 'Sales PIC berhasil dipindah.');
    }

    public function export(Request $request)
    {
        $status   = $request->get('status');
        $industry = $request->get('industry');
        $search   = $request->get('search');
        $salesId  = $request->get('user_id');

        $query = Customer::with(['salesUser']);

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($industry && $industry !== 'all') {
            $query->where('industry', $industry);
        }

        if (auth()->user()->isSalesExecutive()) {
            $query->where('user_id', auth()->id());
        } elseif ($salesId) {
            $query->where('user_id', $salesId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('pic_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('company_name')->get();
        $headers   = ['Company Name','PIC Name','Position','Phone','Email','Industry','Location','Status','Sales PIC','Customer Since'];
        $rows      = $customers->map(fn($c) => [
            $c->company_name, $c->pic_name, $c->pic_position,
            $c->phone, $c->email, $c->industry, $c->location,
            $c->status, $c->salesUser?->name,
            $c->customer_since?->format('Y-m-d'),
        ])->toArray();

        return \App\Helpers\ExcelExport::download('customers_' . date('Ymd_His'), $headers, $rows, 'Customers');
    }

    public function template()
    {
        return ExcelExport::download(
            'template-import-customers',
            ['Company Name', 'PIC Name', 'Position', 'Phone', 'Email', 'Address', 'Industry', 'Location', 'Sales PIC Email', 'Customer Since'],
            [['PT. Contoh Kimia', 'Budi Santoso', 'Purchasing Manager', '0812-1234-5678', 'budi@contoh.co.id', 'Jl. Industri No. 1', 'Manufacturing', 'Surabaya', 'sales@crm.com', now()->format('Y-m-d')]],
            'Template Customer'
        );
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
        ]);

        $processed = 0;
        $skipped = 0;

        try {
            $rows = ExcelImport::rows($request->file('file'));
        } catch (\Throwable) {
            return back()->withErrors(['file' => 'File tidak dapat dibaca. Pastikan format Excel/CSV valid.']);
        }

        foreach ($rows as $row) {
            $data = [
                'company_name' => trim((string) ExcelImport::value($row, 'company_name', 'nama_perusahaan')),
                'pic_name' => trim((string) ExcelImport::value($row, 'pic_name', 'nama_pic')),
                'pic_position' => trim((string) ExcelImport::value($row, 'position', 'pic_position', 'jabatan')),
                'phone' => trim((string) ExcelImport::value($row, 'phone', 'telepon')),
                'email' => trim((string) ExcelImport::value($row, 'email')),
                'address' => trim((string) ExcelImport::value($row, 'address', 'alamat')),
                'industry' => trim((string) ExcelImport::value($row, 'industry', 'industri')),
                'location' => trim((string) ExcelImport::value($row, 'location', 'lokasi')),
                'sales_email' => trim((string) ExcelImport::value($row, 'sales_pic_email', 'email_sales')),
                'customer_since' => trim((string) ExcelImport::value($row, 'customer_since')),
            ];

            $validator = Validator::make($data, [
                'company_name' => ['required', 'string', 'max:255'],
                'pic_name' => ['required', 'string', 'max:255'],
                'phone' => ['required', 'string', 'max:20'],
                'email' => ['nullable', 'email', 'max:255'],
                'sales_email' => ['nullable', 'email', 'max:255'],
                'customer_since' => ['nullable', 'date'],
            ]);

            if ($validator->fails()) {
                $skipped++;
                continue;
            }

            $salesUser = $request->user()->isSalesExecutive()
                ? $request->user()
                : User::assignable()->where('email', $data['sales_email'])->first();
            $salesUser ??= $request->user()->isDeveloper() ? null : $request->user();

            try {
                DB::transaction(function () use ($data, $salesUser) {
                    $customer = Customer::updateOrCreate(
                        ['company_name' => $data['company_name']],
                        [
                            'pic_name' => $data['pic_name'],
                            'pic_position' => $data['pic_position'] ?: null,
                            'phone' => $data['phone'],
                            'email' => $data['email'] ?: null,
                            'address' => $data['address'] ?: null,
                            'industry' => $data['industry'] ?: null,
                            'location' => $data['location'] ?: null,
                            'status' => 'Existing',
                            'user_id' => $salesUser?->id,
                            'customer_since' => $data['customer_since'] ?: now()->toDateString(),
                        ]
                    );

                    if ($customer->wasRecentlyCreated) {
                        Lead::createWithUniqueCode([
                            'customer_id' => $customer->id,
                            'company_name' => $customer->company_name,
                            'pic_name' => $customer->pic_name,
                            'pic_position' => $customer->pic_position,
                            'phone' => $customer->phone,
                            'email' => $customer->email,
                            'address' => $customer->address,
                            'industry' => $customer->industry,
                            'location' => $customer->location,
                            'pipeline_stage' => 'Maintaining',
                            'temperature' => 'Warm',
                            'user_id' => $customer->user_id,
                        ]);
                    } else {
                        self::syncToLeads($customer);
                    }
                });
                $processed++;
            } catch (\Throwable) {
                $skipped++;
            }
        }

        return redirect()->route('customers.index')->with(
            'success',
            "Import Customer selesai: {$processed} data diproses, {$skipped} baris dilewati."
        );
    }

    // AJAX: Add activity ke customer
    public function storeActivity(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'type'          => 'required|in:Call,Visit,Email,Note,Others',
            'subject'       => 'required|string|max:255',
            'description'   => 'nullable|string',
            'activity_at'   => 'required|date',
            'status'        => 'required|in:Planned,Pending,Done,Overdue',
            'user_id' => ['required', Rule::exists('users', 'id')->where(
                fn ($query) => $query->where('status', 'Active')->where('role', '!=', User::ROLE_DEVELOPER)
            )],
        ]);
        $validated['customer_id'] = $customer->id;
        if (auth()->user()->isSalesExecutive()) {
            $validated['user_id'] = auth()->id();
        }
        $validated['sales_user_id'] = $validated['user_id'];
        Activity::create($validated);
        return redirect()->back()->with('success', 'Activity ditambahkan.');
    }
}
