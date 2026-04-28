<?php

namespace App\Observers;

use App\Models\CustomerPipeline;
use App\Models\PipelineLog;

class PipelineObserver
{
    // Log when pipeline is first created
    public function created(CustomerPipeline $pipeline): void
    {
        PipelineLog::create([
            'customer_id'  => $pipeline->customer_id,
            'stage'        => $pipeline->stage,
            'contact_type' => $pipeline->contact_type,
            'contact_date' => $pipeline->contact_date,
            'notes'        => $pipeline->notes,
            'created_by'   => auth()->id(),
        ]);
    }

    // Log only when stage actually changes
    public function updated(CustomerPipeline $pipeline): void
    {
        if ($pipeline->wasChanged('stage')) {
            PipelineLog::create([
                'customer_id'  => $pipeline->customer_id,
                'stage'        => $pipeline->stage,
                'contact_type' => $pipeline->contact_type,
                'contact_date' => $pipeline->contact_date,
                'notes'        => $pipeline->notes,
                'created_by'   => auth()->id(),
            ]);
        }
    }
}