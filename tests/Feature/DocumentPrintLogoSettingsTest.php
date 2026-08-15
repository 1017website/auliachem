<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class DocumentPrintLogoSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_and_use_a_different_logo_for_each_document_type(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'Admin', 'status' => 'Active']);

        $this->actingAs($admin)->get(route('settings.index'))
            ->assertOk()
            ->assertSee('name="company_tax_number"', false)
            ->assertSee('name="quotation_print_logo"', false)
            ->assertSee('name="invoice_print_logo"', false)
            ->assertSee('name="purchase_order_print_logo"', false);

        $this->actingAs($admin)->put(route('settings.update'), [
            'company_name' => 'Auliachem Perkasa',
            'company_tax_number' => '71.579.461.6-609.000',
            'quotation_print_logo' => UploadedFile::fake()->image('quotation.png', 600, 120),
            'invoice_print_logo' => UploadedFile::fake()->image('invoice.png', 600, 120),
            'purchase_order_print_logo' => UploadedFile::fake()->image('purchase-order.png', 600, 120),
        ])->assertRedirect(route('settings.index'));

        $quotationLogo = Setting::get('quotation_print_logo');
        $invoiceLogo = Setting::get('invoice_print_logo');
        $purchaseOrderLogo = Setting::get('purchase_order_print_logo');
        $this->assertSame('71.579.461.6-609.000', Setting::get('company_tax_number'));

        Storage::disk('public')->assertExists($quotationLogo);
        Storage::disk('public')->assertExists($invoiceLogo);
        Storage::disk('public')->assertExists($purchaseOrderLogo);

        $quotation = Quotation::createWithUniqueNumber([
            'user_id' => $admin->id,
            'customer_name' => 'PT Customer Penawaran',
            'quotation_date' => '2026-08-15',
            'valid_until' => '2026-08-22',
            'currency' => 'IDR',
            'tax_percent' => 0,
            'status' => 'Draft',
        ]);
        $quotation->items()->create([
            'item_name' => 'Produk Penawaran', 'unit' => 'Kg', 'qty' => 1, 'unit_price' => 1000,
        ]);

        $invoice = Invoice::createWithUniqueNumber([
            'user_id' => $admin->id,
            'customer_name' => 'PT Customer Invoice',
            'invoice_date' => '2026-08-15',
            'due_date' => '2026-08-22',
            'currency' => 'IDR',
            'tax_percent' => 0,
            'status' => 'Draft',
        ]);
        $invoice->items()->create([
            'item_name' => 'Produk Invoice', 'unit' => 'Kg', 'qty' => 1, 'unit_price' => 1000,
        ]);

        $purchaseOrder = PurchaseOrder::createWithUniqueNumber([
            'user_id' => $admin->id,
            'order_date' => '2026-08-15',
            'currency' => 'IDR',
            'status' => 'In Progress',
        ]);

        $this->actingAs($admin)->get(route('quotations.print', $quotation->id))
            ->assertOk()
            ->assertSee(asset('storage/'.$quotationLogo), false)
            ->assertSee('Tax No: 71.579.461.6-609.000');
        $this->actingAs($admin)->get(route('invoices.print', $invoice->id))
            ->assertOk()
            ->assertSee(asset('storage/'.$invoiceLogo), false)
            ->assertSee('Tax No: 71.579.461.6-609.000');
        $this->actingAs($admin)->get(route('purchase-orders.print', $purchaseOrder))
            ->assertOk()
            ->assertSee(asset('storage/'.$purchaseOrderLogo), false)
            ->assertSee('Tax No:')
            ->assertSee('71.579.461.6-609.000')
            ->assertSee('data:image/png;base64,', false)
            ->assertSee('ditandatangani secara elektronik', false)
            ->assertSee('data-print-document', false);

        $purchaseOrderVerificationUrl = URL::signedRoute('documents.verify', [
            'kind' => 'purchase_order',
            'id' => $purchaseOrder->id,
        ], absolute: false);
        $this->get($purchaseOrderVerificationUrl)
            ->assertOk()
            ->assertSee('Dokumen Terverifikasi')
            ->assertSee($purchaseOrder->po_number)
            ->assertSee('Purchase Order');

        $this->actingAs($admin)->post(route('settings.delete-image'), [
            'type' => 'quotation_print_logo',
        ])->assertRedirect();

        Storage::disk('public')->assertMissing($quotationLogo);
        $this->assertSame('', Setting::get('quotation_print_logo'));
    }
}
