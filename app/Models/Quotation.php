<?php

namespace App\Models;

use App\Models\Concerns\HasSalesDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use HasSalesDocumentNumber, SoftDeletes;

    protected $fillable = [
        'quotation_number', 'customer_id', 'lead_id', 'user_id', 'customer_name',
        'customer_address', 'customer_phone', 'quotation_date', 'valid_until',
        'currency', 'tax_percent', 'status', 'notes', 'terms',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'valid_until' => 'date',
        'tax_percent' => 'decimal:2',
    ];

    protected static function numberColumn(): string { return 'quotation_number'; }
    protected static function numberPrefix(): string { return 'QTN'; }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function lead(): BelongsTo { return $this->belongsTo(Lead::class); }
    public function salesUser(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function items(): HasMany { return $this->hasMany(QuotationItem::class); }

    public function getSubtotalAttribute(): float { return $this->items->sum(fn ($item) => $item->subtotal); }
    public function getTaxAmountAttribute(): float { return $this->subtotal * ((float) $this->tax_percent / 100); }
    public function getGrandTotalAttribute(): float { return $this->subtotal + $this->tax_amount; }
}
