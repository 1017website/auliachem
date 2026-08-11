<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PurchaseOrder;

class InvoiceController extends SalesDocumentController
{
    protected function config(): array
    {
        return [
            'kind' => 'invoice',
            'model' => Invoice::class,
            'label' => 'Invoice',
            'label_plural' => 'Invoice',
            'title' => 'INVOICE',
            'route_prefix' => 'invoices',
            'number_field' => 'invoice_number',
            'date_field' => 'invoice_date',
            'date_label' => 'Tanggal Invoice',
            'secondary_date_field' => 'due_date',
            'secondary_date_label' => 'Jatuh Tempo',
            'statuses' => ['Draft', 'Sent', 'Paid', 'Overdue', 'Cancelled'],
            'default_tax' => 11,
            'default_terms' => 'Pembayaran sesuai tanggal jatuh tempo.',
        ];
    }

    protected function linkedDocuments(): array
    {
        return PurchaseOrder::with(['customer:id,company_name,address,phone', 'items'])
            ->where('status', '!=', 'Cancelled')
            ->latest('order_date')->limit(100)->get()
            ->map(fn (PurchaseOrder $po) => [
                'id' => $po->id,
                'label' => $po->po_number . ' — ' . ($po->customer?->company_name ?? 'Tanpa customer'),
                'customer_id' => $po->customer_id,
                'customer_name' => $po->customer?->company_name,
                'customer_address' => $po->customer?->address,
                'customer_phone' => $po->customer?->phone,
                'items' => $po->items->map(fn ($item) => [
                    'item_name' => $item->product_name,
                    'description' => $item->description,
                    'unit' => $item->unit,
                    'qty' => (float) $item->qty,
                    'unit_price' => (float) $item->sell_price,
                ])->values(),
            ])->values()->all();
    }
}
