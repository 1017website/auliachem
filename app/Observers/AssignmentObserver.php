<?php

namespace App\Observers;

use App\Models\AssignmentHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AssignmentObserver
{
    public function updating(Model $model): void
    {
        if (! $model->isDirty('assigned_to')) {
            return;
        }

        $oldUserId = $model->getOriginal('assigned_to');
        $newUserId = $model->assigned_to;

        if ($oldUserId === $newUserId) {
            return;
        }

        AssignmentHistory::create([
            'assignable_type' => get_class($model),
            'assignable_id'   => $model->id,
            'from_user_id'    => $oldUserId,
            'to_user_id'      => $newUserId,
            'reassigned_by'   => Auth::id() ?? $newUserId,
            'created_by'      => Auth::id(),
            'created_at'      => now(),
        ]);
    }
}
