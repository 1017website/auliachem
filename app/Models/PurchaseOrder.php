<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id', 'supplier_id', 'product_category_id',
        'po_number', 'po_date', 'total_amount', 'cogs', 'gross_profit', 'status',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'po_date'       => 'date',
        'total_amount'  => 'decimal:2',
        'cogs'          => 'decimal:2',
        'gross_profit'  => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'po_id');
    }

    public function getNettProfitAttribute(): float
    {
        return (float) $this->gross_profit - $this->expenses()->sum('amount');
    }

    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy() { return $this->belongsTo(User::class, 'updated_by'); }
    public function deletedBy() { return $this->belongsTo(User::class, 'deleted_by'); }
}