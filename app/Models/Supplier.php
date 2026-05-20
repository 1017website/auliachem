<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'supplier_name','pic_name','pic_position','phone','email','address',
        'source_type','product_category','origin_country',
        'status','relationship_status','is_preferred','rating',
        'payment_term','supplier_since','logo'
    ];

    protected $casts = [
        'supplier_since' => 'date',
        'is_preferred'   => 'boolean',
    ];

    public function purchaseOrders(): HasMany { return $this->hasMany(PurchaseOrder::class); }
    public function products(): HasMany       { return $this->hasMany(SupplierProduct::class); }

    public function isExisting(): bool  { return $this->relationship_status === 'Existing'; }
    public function isPotential(): bool { return $this->relationship_status === 'Potential'; }
    public function isLocal(): bool     { return $this->source_type === 'Local'; }
    public function isImport(): bool    { return $this->source_type === 'Import'; }
}
