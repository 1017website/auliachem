<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesDocumentEnglishPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_documents_support_english_print_and_signed_verification(): void
    {
        $admin = User::factory()->create(['role' => 'Admin', 'status' => 'Active']);
        foreach (['quotation' => Quotation::class, 'invoice' => Invoice::class] as $kind => $model) {
            $document = $model::createWithUniqueNumber([
                'user_id' => $admin->id,
                'customer_name' => 'Test Customer',
                $kind . '_date' => now()->startOfMonth(),
                'currency' => 'IDR',
                'status' => 'Draft',
                'notes' => 'Original customer notes',
                'terms' => 'Custom payment terms',
                'bank_details' => 'Bank account 123',
            ]);
            $document->items()->create([
                'item_name' => 'Chemical', 'unit' => 'Kg', 'qty' => 2, 'unit_price' => 1000,
            ]);
            $route = $kind === 'quotation' ? 'quotations' : 'invoices';
            $this->actingAs($admin)->get(route($route . '.index'))
                ->assertOk()->assertSee('Print ' . ucfirst($kind) . ' in English');
            $response = $this->get(route($route . '.print', ['id' => $document->id, 'lang' => 'en']));
            $response->assertOk()->assertSee('<html lang="en">', false)
                ->assertSee('<title>' . $document->{$kind . '_number'} . '-EN</title>', false)
                ->assertSee('DESCRIPTION')->assertSee('PRICE')->assertSee('AMOUNT')
                ->assertSee('Rp 1,000.00')
                ->assertSee('<td class="num">2</td>', false)
                ->assertSee('This document is electronically signed by:')
                ->assertSee('Original customer notes')->assertSee('Custom payment terms')
                ->assertSee($kind === 'quotation' ? 'Valid Until' : 'Due Date')
                ->assertDontSee('DESKRIPSI')->assertDontSee('Syarat &amp; Ketentuan', false);
            if ($kind === 'invoice') {
                $response->assertSee('PAYMENT INFORMATION');
            }
            $url = $response->viewData('verificationUrl');
            $this->get($url)->assertOk()->assertSee('Verified Document')->assertSee(ucfirst($kind));
            $this->get(str_replace('lang=en', 'lang=id', $url))->assertForbidden();
            foreach ([null, 'fr'] as $language) {
                $this->get(route($route . '.print', ['id' => $document->id, 'lang' => $language]))
                    ->assertOk()->assertSee('<html lang="id">', false)
                    ->assertSee('DESKRIPSI')->assertSee('Cetak / Simpan PDF')
                    ->assertSee('Rp 1.000,00');
            }
        }
    }
}
