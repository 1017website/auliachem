<?php

namespace App\Http\Controllers;

use App\Helpers\ExcelExport;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Endroid\QrCode\Builder\Builder as QrCodeBuilder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

abstract class SalesDocumentController extends Controller
{
    /** @return array<string, mixed> */
    abstract protected function config(): array;

    public function index(Request $request)
    {
        $config = $this->config();
        $search = trim((string) $request->get('search'));
        $status = (string) $request->get('status', 'all');
        $startDate = (string) $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = (string) $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));

        $query = $this->baseQuery()->with(['items', 'customer', 'salesUser'])
            ->whereBetween($config['date_field'], [$startDate, $endDate]);

        if ($status !== 'all' && in_array($status, $config['statuses'], true)) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $numberField = $config['number_field'];
            $query->where(function (Builder $query) use ($search, $numberField) {
                $query->where($numberField, 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhereHas('items', fn (Builder $items) => $items->where('item_name', 'like', "%{$search}%"));
            });
        }

        $documents = $query->orderByDesc($config['date_field'])->orderByDesc('id')
            ->paginate(15)->withQueryString();

        $periodDocuments = $this->baseQuery()->with('items')
            ->whereBetween($config['date_field'], [$startDate, $endDate])->get();

        $customers = Customer::where('status', 'Existing')->orderBy('company_name')
            ->get(['id', 'company_name', 'address', 'phone']);

        return view('documents.index', [
            'config' => $config,
            'documents' => $documents,
            'documentCount' => $periodDocuments->count(),
            'documentTotal' => $periodDocuments->sum(fn (Model $document) => $document->grand_total),
            'customers' => $customers,
            'linkedDocuments' => $this->linkedDocuments(),
            'masterProducts' => Product::where('status', 'Active')->orderBy('product_name')
                ->get(['id', 'product_code', 'product_name', 'unit', 'description', 'buy_price', 'sell_price']),
            'search' => $search,
            'status' => $status,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function store(Request $request)
    {
        $config = $this->config();
        $validated = $request->validate($this->rules());

        DB::transaction(function () use ($validated, $config) {
            $items = $validated['items'];
            unset($validated['items']);
            $validated['user_id'] = auth()->id();
            $validated = $this->hydrateCustomerSnapshot($validated);

            /** @var class-string<Model> $modelClass */
            $modelClass = $config['model'];
            $document = $modelClass::createWithUniqueNumber($validated);
            $document->items()->createMany($items);
        });

        return redirect()->route($config['route_prefix'] . '.index')
            ->with('success', $config['label'] . ' berhasil dibuat.');
    }

    public function edit(int $id)
    {
        $document = $this->findDocument($id)->load(['items', 'customer']);
        $data = $document->toArray();
        foreach ([$this->config()['date_field'], $this->config()['secondary_date_field']] as $field) {
            $data[$field] = $document->{$field}?->format('Y-m-d');
        }

        return response()->json($data);
    }

    public function update(Request $request, int $id)
    {
        $config = $this->config();
        $document = $this->findDocument($id);
        $validated = $request->validate($this->rules());

        DB::transaction(function () use ($document, $validated) {
            $items = $validated['items'];
            unset($validated['items']);
            $validated = $this->hydrateCustomerSnapshot($validated);
            $document->update($validated);
            $document->items()->delete();
            $document->items()->createMany($items);
        });

        return redirect()->route($config['route_prefix'] . '.index')
            ->with('success', $config['label'] . ' berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $config = $this->config();
        $document = $this->findDocument($id);
        $number = $document->{$config['number_field']};
        $document->delete();

        return redirect()->route($config['route_prefix'] . '.index')
            ->with('success', $config['label'] . " {$number} berhasil dihapus.");
    }

    public function print(int $id)
    {
        $config = $this->config();
        $document = $this->findDocument($id)->load(['items', 'customer', 'salesUser']);
        $verificationPath = URL::signedRoute('documents.verify', [
            'kind' => $config['kind'],
            'id' => $document->getKey(),
        ], absolute: false);
        $verificationUrl = request()->getSchemeAndHttpHost().$verificationPath;
        $signatureQr = (new QrCodeBuilder(
            writer: new PngWriter(),
            data: $verificationUrl,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 260,
            margin: 8,
        ))->build()->getDataUri();

        return view('documents.print', [
            'config' => $config,
            'document' => $document,
            'settings' => Setting::getAll(),
            'signatureQr' => $signatureQr,
            'verificationUrl' => $verificationUrl,
        ]);
    }

    public function export(Request $request)
    {
        $config = $this->config();
        $startDate = (string) $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = (string) $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));
        $documents = $this->baseQuery()->with('items')
            ->whereBetween($config['date_field'], [$startDate, $endDate])
            ->orderByDesc($config['date_field'])->get();

        $rows = [];
        foreach ($documents as $document) {
            foreach ($document->items as $item) {
                $rows[] = [
                    $document->{$config['number_field']},
                    $document->customer_name,
                    $item->item_name,
                    $item->unit,
                    (float) $item->qty,
                    (float) $item->unit_price,
                    (float) $item->subtotal,
                    (float) $document->tax_percent,
                    (float) $document->grand_total,
                    $document->currency,
                    $document->status,
                    $document->{$config['date_field']}?->format('Y-m-d'),
                ];
            }
        }

        return ExcelExport::download(
            $config['route_prefix'] . '-' . $startDate . '-sd-' . $endDate,
            ['Nomor', 'Customer', 'Item', 'Unit', 'Qty', 'Harga', 'Subtotal', 'Pajak %', 'Grand Total', 'Currency', 'Status', 'Tanggal'],
            $rows,
            $config['label_plural']
        );
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        $config = $this->config();
        $rules = [
            'customer_id' => ['nullable', Rule::exists('customers', 'id')->where('status', 'Existing')],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_address' => ['nullable', 'string', 'max:2000'],
            'customer_phone' => ['nullable', 'string', 'max:100'],
            $config['date_field'] => ['required', 'date'],
            $config['secondary_date_field'] => ['nullable', 'date', 'after_or_equal:' . $config['date_field']],
            'currency' => ['required', Rule::in(['IDR', 'USD', 'SGD'])],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', Rule::in($config['statuses'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'terms' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string', 'max:1000'],
            'items.*.unit' => ['required', 'string', 'max:50'],
            'items.*.qty' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];

        if ($config['kind'] === 'invoice') {
            $rules['purchase_order_id'] = ['nullable', 'exists:purchase_orders,id'];
            $rules['bank_details'] = ['nullable', 'string', 'max:5000'];
        } else {
            $rules['lead_id'] = ['nullable', 'exists:leads,id'];
        }

        return $rules;
    }

    protected function baseQuery(): Builder
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = $this->config()['model'];
        $query = $modelClass::query();

        if (auth()->user()->isSalesExecutive()) {
            $query->where('user_id', auth()->id());
        }

        return $query;
    }

    protected function findDocument(int $id): Model
    {
        return $this->baseQuery()->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    protected function hydrateCustomerSnapshot(array $data): array
    {
        if (empty($data['customer_id'])) {
            return $data;
        }

        $customer = Customer::find($data['customer_id']);
        if ($customer) {
            $data['customer_name'] = $data['customer_name'] ?: $customer->company_name;
            $data['customer_address'] = $data['customer_address'] ?: $customer->address;
            $data['customer_phone'] = $data['customer_phone'] ?: $customer->phone;
        }

        return $data;
    }

    protected function linkedDocuments(): array
    {
        return [];
    }
}
