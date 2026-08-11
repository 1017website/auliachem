<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Quotation;

class QuotationController extends SalesDocumentController
{
    protected function config(): array
    {
        return [
            'kind' => 'quotation',
            'model' => Quotation::class,
            'label' => 'Penawaran',
            'label_plural' => 'Penawaran',
            'title' => 'PENAWARAN HARGA',
            'route_prefix' => 'quotations',
            'number_field' => 'quotation_number',
            'date_field' => 'quotation_date',
            'date_label' => 'Tanggal Penawaran',
            'secondary_date_field' => 'valid_until',
            'secondary_date_label' => 'Berlaku Sampai',
            'statuses' => ['Draft', 'Sent', 'Approved', 'Rejected', 'Expired'],
            'default_tax' => 0,
            'default_terms' => "Harga belum termasuk PPN.\nValidity 7 hari.\nHarga di luar biaya bongkar lokasi.",
        ];
    }

    protected function linkedDocuments(): array
    {
        $query = Lead::query()->whereNotIn('pipeline_stage', ['Lost']);
        if (auth()->user()->isSalesExecutive()) {
            $query->where('user_id', auth()->id());
        }

        return $query->latest()->limit(100)->get()
            ->map(fn (Lead $lead) => [
                'id' => $lead->id,
                'label' => $lead->lead_code . ' — ' . $lead->company_name,
                'customer_id' => $lead->customer_id,
                'customer_name' => $lead->company_name,
                'customer_address' => $lead->address,
                'customer_phone' => $lead->phone,
            ])->values()->all();
    }
}
