<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditObserver
{
    public function creating(Model $model): void
    {
        if (Auth::check()) {
            $model->created_by = Auth::id();
        }
    }

    public function updating(Model $model): void
    {
        if (Auth::check()) {
            $model->updated_by = Auth::id();
        }
    }

    public function deleting(Model $model): void
    {
        if (Auth::check() && in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($model))) {
            $model->deleted_by = Auth::id();
            $model->saveQuietly();
        }
    }
}
