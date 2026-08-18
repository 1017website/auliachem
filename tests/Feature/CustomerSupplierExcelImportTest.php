<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class CustomerSupplierExcelImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_database_can_be_imported_from_excel_and_reimported_without_duplicates(): void
    {
        $admin = User::factory()->create(['role' => 'Admin', 'status' => 'Active']);
        $headers = ['Company Name', 'PIC Name', 'Position', 'Phone', 'Email', 'Address', 'Industry', 'Location', 'Sales PIC Email', 'Customer Since'];

        [$file, $path] = $this->excelUpload('customers.xlsx', $headers, [
            ['PT. Customer Excel', 'Budi', 'Purchasing', '0812345678', 'budi@example.test', 'Jl. Customer No. 1', 'Manufacturing', 'Surabaya', $admin->email, '2026-08-18'],
            ['', 'Tanpa Perusahaan', '', '0812', '', '', '', '', '', ''],
        ]);

        try {
            $this->actingAs($admin)->get(route('customers.index'))
                ->assertOk()
                ->assertSee('Import Excel')
                ->assertSee(route('customers.template'), false);

            $this->actingAs($admin)->get(route('customers.template'))
                ->assertOk()->assertDownload('template-import-customers.xlsx');

            $this->actingAs($admin)->post(route('customers.import'), ['file' => $file])
                ->assertRedirect(route('customers.index'))
                ->assertSessionHas('success', fn (string $message) => str_contains($message, '1 data diproses, 1 baris dilewati'));

            $customer = Customer::firstOrFail();
            $this->assertSame('PT. Customer Excel', $customer->company_name);
            $this->assertSame('Jl. Customer No. 1', $customer->address);
            $this->assertSame($admin->id, $customer->user_id);
            $this->assertDatabaseHas('leads', [
                'customer_id' => $customer->id,
                'pipeline_stage' => 'Maintaining',
            ]);

            [$updatedFile, $updatedPath] = $this->excelUpload('customers-updated.xlsx', $headers, [
                ['PT. Customer Excel', 'Budi Baru', 'Manager', '0899999999', 'baru@example.test', 'Jl. Customer No. 2', 'Trading', 'Jakarta', $admin->email, '2026-08-18'],
            ]);
            try {
                $this->actingAs($admin)->post(route('customers.import'), ['file' => $updatedFile])
                    ->assertRedirect(route('customers.index'));
            } finally {
                if (is_file($updatedPath)) unlink($updatedPath);
            }

            $this->assertDatabaseCount('customers', 1);
            $this->assertDatabaseHas('customers', [
                'company_name' => 'PT. Customer Excel',
                'pic_name' => 'Budi Baru',
                'phone' => '0899999999',
            ]);
        } finally {
            if (is_file($path)) unlink($path);
        }
    }

    public function test_supplier_database_can_be_imported_from_excel_and_reimported_without_duplicates(): void
    {
        $admin = User::factory()->create(['role' => 'Admin', 'status' => 'Active']);
        $headers = ['Supplier Name', 'Source Type', 'PIC Name', 'Position', 'Phone', 'Email', 'Address', 'Product Category', 'Origin Country', 'Payment Term', 'Status', 'Relationship', 'Preferred', 'Rating', 'Supplier Since'];

        [$file, $path] = $this->excelUpload('suppliers.xlsx', $headers, [
            ['PT. Supplier Excel', 'Import', 'Siti', 'Sales', '0812777777', 'siti@example.test', 'Jl. Supplier No. 1', 'Solvent', 'Singapore', '30 Hari', 'Active', 'Existing', 'Yes', 4.5, '2026-08-18'],
            ['Supplier Salah', 'Tidak Valid', 'PIC', '', '0812', '', '', '', '', '', 'Active', 'Existing', 'No', 3, ''],
        ]);

        try {
            $this->actingAs($admin)->get(route('suppliers.index'))
                ->assertOk()
                ->assertSee('Import Excel')
                ->assertSee(route('suppliers.template'), false);

            $this->actingAs($admin)->get(route('suppliers.template'))
                ->assertOk()->assertDownload('template-import-suppliers.xlsx');

            $this->actingAs($admin)->post(route('suppliers.import'), ['file' => $file])
                ->assertRedirect(route('suppliers.index'))
                ->assertSessionHas('success', fn (string $message) => str_contains($message, '1 data diproses, 1 baris dilewati'));

            $this->assertDatabaseHas('suppliers', [
                'supplier_name' => 'PT. Supplier Excel',
                'source_type' => 'Import',
                'origin_country' => 'Singapore',
                'is_preferred' => 1,
            ]);

            [$updatedFile, $updatedPath] = $this->excelUpload('suppliers-updated.xlsx', $headers, [
                ['PT. Supplier Excel', 'Local', 'Siti Baru', 'Manager', '0898888888', 'baru@example.test', 'Jl. Supplier No. 2', 'Resin', 'Indonesia', '14 Hari', 'Active', 'Existing', 'No', 5, '2026-08-18'],
            ]);
            try {
                $this->actingAs($admin)->post(route('suppliers.import'), ['file' => $updatedFile])
                    ->assertRedirect(route('suppliers.index'));
            } finally {
                if (is_file($updatedPath)) unlink($updatedPath);
            }

            $this->assertDatabaseCount('suppliers', 1);
            $this->assertDatabaseHas('suppliers', [
                'supplier_name' => 'PT. Supplier Excel',
                'source_type' => 'Local',
                'pic_name' => 'Siti Baru',
                'rating' => 5,
            ]);
        } finally {
            if (is_file($path)) unlink($path);
        }
    }

    /** @return array{0: UploadedFile, 1: string} */
    private function excelUpload(string $name, array $headers, array $rows): array
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray($rows, null, 'A2');

        $path = tempnam(sys_get_temp_dir(), 'crm-import-');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return [
            new UploadedFile($path, $name, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
            $path,
        ];
    }
}
