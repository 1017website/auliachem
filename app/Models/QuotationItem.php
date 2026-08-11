<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    protected $fillable = ['quotation_id', 'item_name', 'description', 'unit', 'qty', 'unit_price'];
    protected $casts = ['qty' => 'decimal:3', 'unit_price' => 'decimal:2'];

    public function quotation(): BelongsTo { return $this->belongsTo(Quotation::class); }
    public function getSubtotalAttribute(): float { return (float) $this->qty * (float) $this->unit_price; }
}
