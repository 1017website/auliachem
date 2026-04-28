<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PipelineLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'customer_id', 'stage', 'contact_type', 'contact_date', 'notes', 'created_by',
    ];

    protected $casts = [
        'contact_date' => 'date',
        'created_at'   => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}