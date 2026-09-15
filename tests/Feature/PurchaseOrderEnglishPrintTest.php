<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PurchaseOrderEnglishPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_order_can_be_printed_and_verified_in_english(): void
    {
        $this->travelTo(now()->setDate(2026, 8, 18));
        $admin = User::factory()->create([
            'role' => 'Admin',
            'status' => 'Active',
            'position' => 'Director',
        ]);
        $purchaseOrder = PurchaseOrder::createWithUniqueNumber([
            'po_type' => 'Import',
            'user_id' => $admin->id,
            'order_date' => '2026-08-18',
            'currency' => 'IDR',
            'status' => 'In Progress',
        ]);
        $purchaseOrder->items()->create([
            'product_name' => 'Chemical Product',
            'unit' => 'Kg',
            'qty' => 2,
            'buy_price' => 1000,
            'sell_price' => 1200,
        ]);

        $this->actingAs($admin)->get(route('purchase-orders.print', [
            'purchaseOrder' => $purchaseOrder,
            'lang' => 'id',
        ]))
            ->assertOk()
            ->assertSee('<html lang="en">', false)
            ->assertSee('<title>' . $purchaseOrder->po_number . '-EN</title>', false)
            ->assertSee('class="letterhead split-letterhead"', false)
            ->assertSee('SHIP TO')
            ->assertSee('DESCRIPTION')
            ->assertSee('PRICE / UNIT')
            ->assertSee('Special Instructions')
            ->assertSee('This document is electronically signed by:')
            ->assertDontSee('DIKIRIM KE');

        $englishVerificationUrl = URL::signedRoute('documents.verify', [
            'kind' => 'purchase_order',
            'id' => $purchaseOrder->id,
            'lang' => 'en',
        ], absolute: false);

        $this->get($englishVerificationUrl)
            ->assertOk()
            ->assertSee('Verified Document')
            ->assertSee('Document number')
            ->assertSee('Signed by');
    }
    public function test_import_print_formats_whole_quantities_and_preserves_three_decimal_precision(): void
    {
        $admin = User::factory()->create(['role' => 'Admin', 'status' => 'Active']);
        $this->actingAs($admin);

        foreach ([
            [350, 13, '350', 'USD 13.00', 'USD 4,550.00'],
            [1000.125, 1, '1,000.125', 'USD 1.00', 'USD 1,000.125'],
        ] as [$qty, $price, $printedQty, $printedPrice, $printedTotal]) {
            $po = PurchaseOrder::createWithUniqueNumber([
                'po_type' => 'Import', 'user_id' => $admin->id,
                'order_date' => now(), 'currency' => 'USD', 'status' => 'In Progress',
            ]);
            $po->items()->create([
                'product_name' => 'Chemical Product', 'unit' => 'Kg',
                'qty' => $qty, 'buy_price' => $price, 'sell_price' => $price,
            ]);

            $this->get(route('purchase-orders.print', ['purchaseOrder' => $po]))
                ->assertOk()
                ->assertSee('<td class="right">' . $printedQty . '</td>', false)
                ->assertSee($printedPrice)
                ->assertSee($printedTotal);
        }
    }

    public function test_print_uses_purchase_order_currency_in_both_languages(): void
    {
        $admin = User::factory()->create(['role' => 'Admin', 'status' => 'Active']);
        $this->actingAs($admin);

        foreach (['IDR', 'USD'] as $currency) {
            $po = PurchaseOrder::createWithUniqueNumber([
                'user_id' => $admin->id,
                'order_date' => now(),
                'currency' => $currency,
                'status' => 'In Progress',
            ]);
            $po->items()->create([
                'product_name' => 'Chemical Product', 'unit' => 'Kg', 'qty' => 2.5,
                'buy_price' => 1235, 'sell_price' => 1500,
            ]);

            foreach (['id', 'en'] as $language) {
                $po->update(['po_type' => $language === 'en' ? 'Import' : 'Local']);
                $response = $this->get(route('purchase-orders.print', [
                    'purchaseOrder' => $po, 'lang' => $language === 'en' ? 'id' : 'en',
                ]))->assertOk();
                $response->assertSee('<html lang="'.$language.'">', false);
                $prefix = $currency === 'IDR' ? 'Rp ' : 'USD ';
                $price = $prefix . ($language === 'en' ? '1,235.00' : '1.235,00');
                $total = $prefix . ($language === 'en' ? '3,087.50' : '3.087,50');
                $response->assertSee('<td class="right">' . ($language === 'en' ? '2.5' : '2,5') . '</td>', false);
                $response->assertSee($price)->assertSee($total);
                $this->assertSame(3, substr_count($response->getContent(), $total));
                $response->assertDontSee($currency === 'IDR' ? 'USD ' : 'Rp ');
            }
        }
    }
}
