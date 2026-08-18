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
        $admin = User::factory()->create([
            'role' => 'Admin',
            'status' => 'Active',
            'position' => 'Director',
        ]);
        $purchaseOrder = PurchaseOrder::createWithUniqueNumber([
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

        $this->actingAs($admin)->get(route('purchase-orders.index'))
            ->assertOk()
            ->assertSee('Print PO in English')
            ->assertSee('lang=en', false);

        $this->actingAs($admin)->get(route('purchase-orders.print', [
            'purchaseOrder' => $purchaseOrder,
            'lang' => 'en',
        ]))
            ->assertOk()
            ->assertSee('<html lang="en">', false)
            ->assertSee('<title>AP3-' . $purchaseOrder->po_number . '-EN</title>', false)
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
}
