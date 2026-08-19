<?php

namespace App\Models;

use App\Models\Concerns\HasSalesDocumentNumber;
use App\Support\DocumentPrefix;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasSalesDocumentNumber, SoftDeletes;

    protected $fillable = [
        'invoice_number', 'customer_id', 'purchase_order_id', 'user_id',
        'customer_name', 'customer_address', 'customer_phone', 'invoice_date',
        'due_date', 'currency', 'tax_percent', 'status', 'notes', 'terms', 'bank_details',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'tax_percent' => 'decimal:2',
    ];

    protected static function numberColumn(): string { return 'invoice_number'; }
    protected static function numberPrefix(): string { return DocumentPrefix::for('invoice'); }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class); }
    public function salesUser(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function items(): HasMany { return $this->hasMany(InvoiceItem::class); }

    public function getSubtotalAttribute(): float { return $this->items->sum(fn ($item) => $item->subtotal); }
    public function getTaxAmountAttribute(): float { return $this->subtotal * ((float) $this->tax_percent / 100); }
    public function getGrandTotalAttribute(): float { return $this->subtotal + $this->tax_amount; }
}
