<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use RuntimeException;

class PurchaseOrder extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'po_number','customer_id','supplier_id','lead_id','user_id',
        'currency','status','order_date','notes'
    ];

    protected $casts = ['order_date' => 'date'];

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function lead(): BelongsTo     { return $this->belongsTo(Lead::class); }
    public function salesUser(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'user_id'); }
    public function items(): HasMany      { return $this->hasMany(PurchaseOrderItem::class); }

    /** Total Revenue = SUM(qty × sell_price) */
    public function getTotalRevenueAttribute(): float
    {
        return $this->items->sum(fn($i) => $i->qty * $i->sell_price);
    }

    /** Total HPP = SUM(qty × buy_price) */
    public function getTotalCostAttribute(): float
    {
        return $this->items->sum(fn($i) => $i->qty * $i->buy_price);
    }

    /** Gross Profit = Revenue - HPP */
    public function getGrossProfitAttribute(): float
    {
        return $this->total_revenue - $this->total_cost;
    }

    /** Gross Margin % */
    public function getGrossMarginAttribute(): float
    {
        if ($this->total_revenue == 0) return 0;
        return round(($this->gross_profit / $this->total_revenue) * 100, 1);
    }

    public static function generatePoNumber(): string
    {
        $prefix = 'PO-' . date('Ym') . '-';

        // Pakai withTrashed() karena po_number tetap terkunci oleh unique index
        // meskipun PO sudah soft delete. lockForUpdate() membantu mencegah
        // dua request bersamaan mengambil nomor urut yang sama.
        $last = static::withTrashed()
            ->where('po_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('po_number')
            ->value('po_number');

        $seq = $last ? (intval(substr($last, -4)) + 1) : 1;

        do {
            $number = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
            $exists = static::withTrashed()->where('po_number', $number)->exists();
            $seq++;
        } while ($exists && $seq <= 9999);

        return $number;
    }

    public static function createWithUniqueNumber(array $attributes): self
    {
        unset($attributes['po_number']);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $attributes['po_number'] = static::generatePoNumber();

            try {
                return static::create($attributes);
            } catch (QueryException $e) {
                if (!static::isDuplicatePoNumberException($e)) {
                    throw $e;
                }
            }
        }

        throw new RuntimeException('Gagal membuat po_number unik. Silakan coba simpan ulang.');
    }

    protected static function isDuplicatePoNumberException(QueryException $e): bool
    {
        $message = $e->getMessage();
        return str_contains($message, 'purchase_orders_po_number_unique')
            || str_contains($message, 'po_number')
            || str_contains($message, 'Duplicate entry');
    }
}
