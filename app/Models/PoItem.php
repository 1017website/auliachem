<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'product_category_id',
        'product_name',
        'quantity',
        'unit',
        'unit_price',
        'unit_cost',
        'subtotal',
        'subtotal_cogs',
        'subtotal_gross_profit',
        'notes',
    ];

    protected $casts = [
        'quantity'              => 'decimal:2',
        'unit_price'            => 'decimal:2',
        'unit_cost'             => 'decimal:2',
        'subtotal'              => 'decimal:2',
        'subtotal_cogs'         => 'decimal:2',
        'subtotal_gross_profit' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // Auto-calculate subtotals saat saving
        static::saving(function (PoItem $item) {
            $qty   = (float) $item->quantity;
            $price = (float) $item->unit_price;
            $cost  = (float) $item->unit_cost;

            $item->subtotal              = $qty * $price;
            $item->subtotal_cogs         = $qty * $cost;
            $item->subtotal_gross_profit = $item->subtotal - $item->subtotal_cogs;
        });

        // Recalc parent PO setelah item berubah
        static::saved(fn (PoItem $item) => $item->purchaseOrder?->recalculateTotals());
        static::deleted(fn (PoItem $item) => $item->purchaseOrder?->recalculateTotals());
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class);
    }
}
