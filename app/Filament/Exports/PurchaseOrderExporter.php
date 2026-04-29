<?php

namespace App\Filament\Exports;

use App\Models\PurchaseOrder;
use Illuminate\Support\Collection;

class PurchaseOrderExporter extends BaseExporter
{
    protected static function fileName(): string
    {
        return 'purchase_orders';
    }

    protected static function headers(): array
    {
        return [
            'No', 'No. PO', 'Tgl PO', 'Customer', 'Supplier', 'Status',
            'Item ke-', 'Kategori Produk', 'Nama Produk', 'Qty', 'Unit',
            'Harga Jual/unit', 'COGS/unit',
            'Subtotal', 'Subtotal COGS', 'Subtotal Gross Profit',
            'Total PO', 'Total COGS PO', 'Gross Profit PO',
        ];
    }

    protected static function rows(): Collection
    {
        $rows = collect();
        $no = 0;

        $orders = PurchaseOrder::with(['customer', 'supplier', 'items.productCategory'])
            ->orderBy('po_date', 'desc')
            ->get();

        foreach ($orders as $po) {
            $no++;

            // Kalau PO belum ada items, tetap export 1 row dengan info header
            if ($po->items->isEmpty()) {
                $rows->push([
                    'no'           => $no,
                    'po_number'    => $po->po_number,
                    'po_date'      => $po->po_date?->format('d M Y') ?? '-',
                    'customer'     => $po->customer?->company_name ?? '-',
                    'supplier'     => $po->supplier?->company_name ?? '-',
                    'status'       => ucfirst($po->status),
                    'item_no'      => '-',
                    'category'     => '-',
                    'product'      => '(belum ada item)',
                    'qty'          => '-',
                    'unit'         => '-',
                    'unit_price'   => 0,
                    'unit_cost'    => 0,
                    'subtotal'     => 0,
                    'subtotal_cogs'=> 0,
                    'subtotal_gp'  => 0,
                    'total'        => (float) $po->total_amount,
                    'total_cogs'   => (float) $po->cogs,
                    'gross_profit' => (float) $po->gross_profit,
                ]);
                continue;
            }

            foreach ($po->items as $idx => $item) {
                $rows->push([
                    'no'           => $no,
                    'po_number'    => $po->po_number,
                    'po_date'      => $po->po_date?->format('d M Y') ?? '-',
                    'customer'     => $po->customer?->company_name ?? '-',
                    'supplier'     => $po->supplier?->company_name ?? '-',
                    'status'       => ucfirst($po->status),
                    'item_no'      => $idx + 1,
                    'category'     => $item->productCategory?->name ?? '-',
                    'product'      => $item->product_name,
                    'qty'          => (float) $item->quantity,
                    'unit'         => $item->unit,
                    'unit_price'   => (float) $item->unit_price,
                    'unit_cost'    => (float) $item->unit_cost,
                    'subtotal'     => (float) $item->subtotal,
                    'subtotal_cogs'=> (float) $item->subtotal_cogs,
                    'subtotal_gp'  => (float) $item->subtotal_gross_profit,
                    'total'        => (float) $po->total_amount,
                    'total_cogs'   => (float) $po->cogs,
                    'gross_profit' => (float) $po->gross_profit,
                ]);
            }
        }

        return $rows;
    }
}
