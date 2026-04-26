<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignmentHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'assignable_type', 'assignable_id',
        'from_user_id', 'to_user_id', 'reassigned_by',
        'notes', 'created_at', 'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function assignable()
    {
        return $this->morphTo();
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function reassignedBy()
    {
        return $this->belongsTo(User::class, 'reassigned_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
