<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentDownloadFilenameTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_download_names_use_the_configurable_prefix(): void
    {
        Carbon::setTestNow('2026-08-18 14:30:25');

        $admin = User::factory()->create([
            'role' => 'Admin',
            'status' => 'Active',
        ]);

        $this->actingAs($admin)->get(route('settings.index'))
            ->assertOk()
            ->assertSee('name="document_file_prefix"', false)
            ->assertSee('value="AP"', false);

        $this->actingAs($admin)->put(route('settings.update'), [
            'company_name' => 'Auliachem Perkasa',
            'document_file_prefix' => 'DOC',
        ])->assertRedirect(route('settings.index'));

        $this->assertSame('DOC', Setting::get('document_file_prefix'));

        $quotation = Quotation::createWithUniqueNumber([
            'user_id' => $admin->id,
            'customer_name' => 'PT Customer',
            'quotation_date' => '2026-08-18',
            'currency' => 'IDR',
            'tax_percent' => 0,
            'status' => 'Draft',
        ]);
        $invoice = Invoice::createWithUniqueNumber([
            'user_id' => $admin->id,
            'customer_name' => 'PT Customer',
            'invoice_date' => '2026-08-18',
            'currency' => 'IDR',
            'tax_percent' => 0,
            'status' => 'Draft',
        ]);
        $purchaseOrder = PurchaseOrder::createWithUniqueNumber([
            'user_id' => $admin->id,
            'order_date' => '2026-08-18',
            'currency' => 'IDR',
            'status' => 'In Progress',
        ]);

        $this->actingAs($admin)->get(route('quotations.print', $quotation->id))
            ->assertOk()->assertSee('<title>DOC1-' . $quotation->quotation_number . '</title>', false);
        $this->actingAs($admin)->get(route('invoices.print', $invoice->id))
            ->assertOk()->assertSee('<title>DOC2-' . $invoice->invoice_number . '</title>', false);
        $this->actingAs($admin)->get(route('purchase-orders.print', $purchaseOrder))
            ->assertOk()->assertSee('<title>DOC3-' . $purchaseOrder->po_number . '</title>', false);

        $this->actingAs($admin)->get(route('quotations.export'))
            ->assertOk()->assertDownload('DOC1-20260818-143025.xlsx');
        $this->actingAs($admin)->get(route('invoices.export'))
            ->assertOk()->assertDownload('DOC2-20260818-143025.xlsx');
        $this->actingAs($admin)->get(route('purchase-orders.export'))
            ->assertOk()->assertDownload('DOC3-20260818-143025.xlsx');

        Carbon::setTestNow();
    }
}
