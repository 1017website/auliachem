<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = ['invoice_id', 'item_name', 'description', 'unit', 'qty', 'unit_price'];
    protected $casts = ['qty' => 'decimal:3', 'unit_price' => 'decimal:2'];

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function getSubtotalAttribute(): float { return (float) $this->qty * (float) $this->unit_price; }
}
