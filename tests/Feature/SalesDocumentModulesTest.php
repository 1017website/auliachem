<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SalesDocumentModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_user_can_create_and_print_an_invoice(): void
    {
        $user = User::factory()->create(['role' => 'Sales Executive', 'status' => 'Active']);

        $response = $this->actingAs($user)->post(route('invoices.store'), [
            'customer_name' => 'PT Contoh Customer',
            'customer_address' => 'Surabaya',
            'invoice_date' => '2026-08-11',
            'due_date' => '2026-08-18',
            'currency' => 'IDR',
            'tax_percent' => 11,
            'status' => 'Draft',
            'items' => [[
                'item_name' => 'Sodium Sulfite',
                'unit' => 'Kg',
                'qty' => 900,
                'unit_price' => 15900,
            ]],
        ]);

        $invoice = Invoice::with('items')->firstOrFail();
        $response->assertRedirect(route('invoices.index'));
        $this->assertStringStartsWith('INV-202608-', $invoice->invoice_number);
        $this->assertSame(15884100.0, $invoice->grand_total);
        $this->actingAs($user)->get(route('invoices.print', $invoice->id))
            ->assertOk()->assertSee($invoice->invoice_number)->assertSee('INVOICE');
    }

    public function test_sales_user_can_create_and_update_a_quotation(): void
    {
        $user = User::factory()->create(['role' => 'Sales Executive', 'status' => 'Active']);

        $this->actingAs($user)->post(route('quotations.store'), [
            'customer_name' => 'PT Agrinesia Raya',
            'quotation_date' => '2026-08-11',
            'valid_until' => '2026-08-18',
            'currency' => 'IDR',
            'tax_percent' => 0,
            'status' => 'Draft',
            'terms' => 'Validity 7 hari',
            'items' => [[
                'item_name' => 'Caustic Soda Flake',
                'unit' => 'Kg',
                'qty' => 1000,
                'unit_price' => 14500,
            ]],
        ])->assertRedirect(route('quotations.index'));

        $quotation = Quotation::firstOrFail();
        $this->assertStringStartsWith('QTN-202608-', $quotation->quotation_number);

        $this->actingAs($user)->get(route('quotations.print', $quotation->id))
            ->assertOk()
            ->assertSee('CHEMICAL &amp; LABORATORY SOLUTION', false)
            ->assertSee('data:image/png;base64,', false)
            ->assertSee('ditandatangani secara elektronik', false);

        $verificationUrl = URL::signedRoute('documents.verify', [
            'kind' => 'quotation',
            'id' => $quotation->id,
        ], absolute: false);
        $this->get($verificationUrl)
            ->assertOk()
            ->assertSee('Dokumen Terverifikasi')
            ->assertSee($quotation->quotation_number);
        $this->get(route('documents.verify', ['kind' => 'quotation', 'id' => $quotation->id]))
            ->assertForbidden();

        $this->actingAs($user)->put(route('quotations.update', $quotation->id), [
            'customer_name' => 'PT Agrinesia Raya',
            'quotation_date' => '2026-08-11',
            'valid_until' => '2026-08-18',
            'currency' => 'IDR',
            'tax_percent' => 0,
            'status' => 'Sent',
            'items' => [[
                'item_name' => 'Caustic Soda Flake',
                'unit' => 'Kg',
                'qty' => 2000,
                'unit_price' => 14500,
            ]],
        ])->assertRedirect(route('quotations.index'));

        $this->assertDatabaseHas('quotations', ['id' => $quotation->id, 'status' => 'Sent']);
        $this->assertDatabaseHas('quotation_items', ['quotation_id' => $quotation->id, 'qty' => 2000]);
    }

    public function test_quotation_print_hides_company_address_phone_and_recipient_labels(): void
    {
        $user = User::factory()->create(['role' => 'Sales Executive', 'status' => 'Active']);
        Setting::set('company_address', 'Alamat Rahasia Perusahaan');
        Setting::set('company_phone', '031-555-0000');
        Setting::set('company_email', 'sales@example.test');
        Setting::set('company_website', 'https://example.test');

        $quotation = Quotation::createWithUniqueNumber([
            'user_id' => $user->id,
            'customer_name' => 'PT Pelanggan Contoh',
            'customer_address' => 'Sumenep',
            'customer_phone' => '0812-3456-7890',
            'quotation_date' => '2026-08-17',
            'valid_until' => '2026-08-24',
            'currency' => 'IDR',
            'tax_percent' => 0,
            'status' => 'Draft',
        ]);

        $this->actingAs($user)->get(route('quotations.print', $quotation->id))
            ->assertOk()
            ->assertSee('PT Pelanggan Contoh')
            ->assertSee('Sumenep')
            ->assertSee('sales@example.test')
            ->assertSee('example.test')
            ->assertDontSee('Alamat Rahasia Perusahaan')
            ->assertDontSee('031-555-0000')
            ->assertDontSee('Phone &amp; Fax:', false)
            ->assertDontSee('Kepada:')
            ->assertDontSee('Telp 0812-3456-7890');
    }

    public function test_sales_user_cannot_access_another_users_document(): void
    {
        $owner = User::factory()->create(['role' => 'Sales Executive', 'status' => 'Active']);
        $other = User::factory()->create(['role' => 'Sales Executive', 'status' => 'Active']);
        $invoice = Invoice::createWithUniqueNumber([
            'user_id' => $owner->id,
            'customer_name' => 'Private Customer',
            'invoice_date' => '2026-08-11',
            'currency' => 'IDR',
            'tax_percent' => 0,
            'status' => 'Draft',
        ]);

        $this->actingAs($other)->get(route('invoices.edit', $invoice->id))->assertNotFound();
        $this->actingAs($other)->get(route('invoices.print', $invoice->id))->assertNotFound();
    }
}
