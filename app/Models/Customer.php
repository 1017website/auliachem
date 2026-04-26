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
        'industry_id', 'type', 'assigned_to',
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

    public function salesLeads()
    {
        return $this->hasMany(SalesLead::class);
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
