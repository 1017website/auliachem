<?php

namespace App\Filament\Exports;

use App\Models\Expense;
use Illuminate\Support\Collection;

class ExpenseExporter extends BaseExporter
{
    protected static function fileName(): string
    {
        return 'expenses';
    }

    protected static function headers(): array
    {
        return [
            'No', 'Tgl Expense', 'Kategori', 'Deskripsi', 'Amount (Rp)',
            'PO Terkait', 'Tgl Dibuat',
        ];
    }

    protected static function rows(): Collection
    {
        $categoryLabels = [
            'shipping'     => 'Shipping',
            'import_duty'  => 'Import Duty',
            'handling'     => 'Handling',
            'other_po'     => 'Other PO',
            'salary'       => 'Salary',
            'rent_utility' => 'Rent & Utility',
            'marketing'    => 'Marketing',
            'office'       => 'Office',
            'other_ops'    => 'Other Ops',
        ];

        return Expense::with('purchaseOrder')
            ->orderBy('expense_date', 'desc')
            ->get()
            ->values()
            ->map(fn ($e, $i) => [
                'no'           => $i + 1,
                'date'         => $e->expense_date?->format('d M Y') ?? '-',
                'category'     => $categoryLabels[$e->category] ?? $e->category,
                'description'  => $e->description,
                'amount'       => (float) $e->amount,
                'po'           => $e->purchaseOrder?->po_number ?? '-',
                'created'      => $e->created_at?->format('d M Y H:i') ?? '-',
            ]);
    }
}
