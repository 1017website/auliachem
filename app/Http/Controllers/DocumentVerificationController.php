<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;

class DocumentVerificationController extends Controller
{
    public function show(string $kind, int $id)
    {
        [$document, $config] = $this->resolveDocument($kind, $id);

        $relations = ['salesUser'];
        if ($kind === 'purchase_order') {
            $relations[] = 'supplier';
        }
        $document->load($relations);

        return view('documents.verify', [
            'document' => $document,
            'config' => $config,
            'settings' => Setting::getAll(),
            'counterpartyName' => $kind === 'purchase_order'
                ? ($document->supplier?->supplier_name ?? '-')
                : $document->customer_name,
        ]);
    }

    /** @return array{0: Model, 1: array<string, string>} */
    private function resolveDocument(string $kind, int $id): array
    {
        $config = match ($kind) {
            'quotation' => [
                'model' => Quotation::class,
                'label' => 'Penawaran Harga',
                'number_field' => 'quotation_number',
                'date_field' => 'quotation_date',
            ],
            'invoice' => [
                'model' => Invoice::class,
                'label' => 'Invoice',
                'number_field' => 'invoice_number',
                'date_field' => 'invoice_date',
            ],
            'purchase_order' => [
                'model' => PurchaseOrder::class,
                'label' => 'Purchase Order',
                'number_field' => 'po_number',
                'date_field' => 'order_date',
            ],
            default => abort(404),
        };

        /** @var class-string<Model> $modelClass */
        $modelClass = $config['model'];

        return [$modelClass::query()->findOrFail($id), $config];
    }
}
