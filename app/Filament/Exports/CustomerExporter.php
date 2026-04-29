<?php

namespace App\Filament\Exports;

use App\Models\Customer;
use Illuminate\Support\Collection;

class CustomerExporter extends BaseExporter
{
    protected static function fileName(): string
    {
        return 'customers';
    }

    protected static function headers(): array
    {
        return [
            'No', 'Nama Perusahaan', 'PIC', 'No. HP', 'Email',
            'Kota', 'Alamat', 'Industri', 'Tipe', 'Status',
            'Sales Assigned', 'Stage Terakhir', 'Tgl Kontak Terakhir',
            'Total Aktivitas', 'Tgl Dibuat',
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

        return Customer::with(['industry', 'assignedTo', 'latestActivity'])
            ->withCount('activities')
            ->get()
            ->values()
            ->map(fn ($c, $i) => [
                'no'           => $i + 1,
                'company_name' => $c->company_name,
                'pic_name'     => $c->pic_name,
                'phone'        => $c->phone,
                'email'        => $c->email ?? '-',
                'city'         => $c->city ?? '-',
                'address'      => $c->address ?? '-',
                'industry'     => $c->industry?->name ?? '-',
                'type'         => ucfirst($c->type),
                'status'       => ucfirst($c->status),
                'sales'        => $c->assignedTo?->name ?? '-',
                'stage'        => $stageLabels[$c->latestActivity?->stage] ?? '-',
                'last_contact' => $c->latestActivity?->activity_date?->format('d M Y') ?? '-',
                'activities'   => $c->activities_count,
                'created'      => $c->created_at?->format('d M Y') ?? '-',
            ]);
    }
}
