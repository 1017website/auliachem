<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_name', 'pic_name', 'phone', 'email', 'address', 'city',
        'industry_id', 'type', 'status', 'assigned_to',
        'created_by', 'updated_by', 'deleted_by',
    ];

    public function industry()
    {
        return $this->belongsTo(Industry::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function activities()
    {
        return $this->hasMany(SalesActivity::class);
    }

    public function latestActivity()
    {
        return $this->hasOne(SalesActivity::class)->latestOfMany('activity_date');
    }

    public function productCategories()
    {
        return $this->belongsToMany(ProductCategory::class, 'customer_product_categories');
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function assignmentHistories()
    {
        return $this->morphMany(AssignmentHistory::class, 'assignable');
    }

    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy() { return $this->belongsTo(User::class, 'updated_by'); }
    public function deletedBy() { return $this->belongsTo(User::class, 'deleted_by'); }
}