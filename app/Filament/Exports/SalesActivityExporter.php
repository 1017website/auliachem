<?php

namespace App\Filament\Exports;

use App\Models\SalesActivity;
use Illuminate\Support\Collection;

class SalesActivityExporter extends BaseExporter
{
    protected static function fileName(): string
    {
        return 'sales_activities';
    }

    protected static function headers(): array
    {
        return [
            'No', 'Tgl Aktivitas', 'Customer', 'Stage', 'Metode',
            'Catatan', 'Sales', 'Tgl Dibuat',
        ];
    }

    protected static function rows(): Collection
    {
        $stageLabels = [
            'identifying'  => 'Identifying',
            'approaching'  => 'Approaching',
            'following_up' => 'Following Up',
            'closing'      => 'Closing',
            'maintaining'  => 'Maintaining',
        ];

        $typeLabels = [
            'phone'    => 'Phone',
            'visit'    => 'Visit',
            'whatsapp' => 'WhatsApp',
            'email'    => 'Email',
            'other'    => 'Other',
        ];

        return SalesActivity::with(['customer', 'createdBy'])
            ->orderBy('activity_date', 'desc')
            ->get()
            ->values()
            ->map(fn ($a, $i) => [
                'no'           => $i + 1,
                'date'         => $a->activity_date?->format('d M Y') ?? '-',
                'customer'     => $a->customer?->company_name ?? '-',
                'stage'        => $stageLabels[$a->stage] ?? $a->stage,
                'type'         => $typeLabels[$a->type] ?? $a->type,
                'notes'        => $a->notes ?? '-',
                'sales'        => $a->createdBy?->name ?? '-',
                'created'      => $a->created_at?->format('d M Y H:i') ?? '-',
            ]);
    }
}
