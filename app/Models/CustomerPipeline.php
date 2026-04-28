<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPipeline extends Model
{
    protected $fillable = [
        'customer_id', 'stage', 'contact_type', 'contact_date', 'notes',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'contact_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy() { return $this->belongsTo(User::class, 'updated_by'); }
}