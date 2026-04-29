<?php

namespace App\Filament\Exports;

use App\Models\Supplier;
use Illuminate\Support\Collection;

class SupplierExporter extends BaseExporter
{
    protected static function fileName(): string
    {
        return 'suppliers';
    }

    protected static function headers(): array
    {
        return [
            'No', 'Nama Perusahaan', 'PIC', 'No. HP', 'Negara',
            'Tipe', 'Source', 'Principal', 'Kategori Produk',
            'Lead Time (hari)', 'Currency', 'Tgl Dibuat',
        ];
    }

    protected static function rows(): Collection
    {
        return Supplier::with(['principal', 'productCategory'])
            ->get()
            ->values()
            ->map(fn ($s, $i) => [
                'no'            => $i + 1,
                'company_name'  => $s->company_name,
                'pic_name'      => $s->pic_name,
                'phone'         => $s->phone,
                'country'       => $s->country,
                'type'          => ucfirst($s->type),
                'source'        => ucfirst($s->source),
                'principal'     => $s->principal?->name ?? '-',
                'category'      => $s->productCategory?->name ?? '-',
                'lead_time'     => $s->lead_time_days ?? '-',
                'currency'      => $s->currency,
                'created'       => $s->created_at?->format('d M Y') ?? '-',
            ]);
    }
}
