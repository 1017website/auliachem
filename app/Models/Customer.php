<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'company_name','pic_name','pic_position','phone','email','address',
        'industry','location','status','value_tag','user_id','customer_since','logo','notes','products'
    ];

    protected $casts = ['customer_since' => 'date'];

    public function user(): BelongsTo         { return $this->belongsTo(User::class, 'user_id'); }
    public function salesUser(): BelongsTo    { return $this->belongsTo(User::class, 'user_id'); }
    public function leads(): HasMany          { return $this->hasMany(Lead::class); }
    public function activities(): HasMany     { return $this->hasMany(Activity::class); }
    public function purchaseOrders(): HasMany { return $this->hasMany(PurchaseOrder::class); }
    public function pics(): HasMany           { return $this->hasMany(CustomerPic::class); }

    public function getTotalRevenueAttribute(): float
    {
        return $this->purchaseOrders()
            ->where('status', 'Done')->where('currency', 'IDR')
            ->with('items')->get()->sum(fn($po) => $po->total_revenue);
    }

    public function getLogoInitialsAttribute(): string
    {
        $parts = explode(' ', $this->company_name);
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) $initials .= strtoupper(substr($part, 0, 1));
        return $initials;
    }
}
