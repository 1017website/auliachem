<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesActivity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sales_lead_id', 'type', 'activity_date', 'notes',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'activity_date' => 'date',
    ];

    public function salesLead()
    {
        return $this->belongsTo(SalesLead::class);
    }

    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy() { return $this->belongsTo(User::class, 'updated_by'); }
    public function deletedBy() { return $this->belongsTo(User::class, 'deleted_by'); }
}
