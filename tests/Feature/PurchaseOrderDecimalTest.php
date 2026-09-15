<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderDecimalTest extends TestCase
{
    use RefreshDatabase;

    public function test_prices_survive_create_edit_and_update_with_currency(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'Admin', 'status' => 'Active']));
        $data = [
            'po_type' => 'Import', 'currency' => 'USD', 'status' => 'In Progress',
            'order_date' => now()->format('Y-m-d'),
            'items' => [[
                'product_name' => 'Chemical', 'unit' => 'kg', 'qty' => '2.500',
                'buy_price' => '14.56', 'sell_price' => '20.123',
            ]],
        ];
        $this->post(route('purchase-orders.store'), $data)->assertSessionHasNoErrors();
        $po = PurchaseOrder::sole();
        $this->get(route('purchase-orders.edit', $po))->assertOk()
            ->assertJsonPath('items.0.buy_price', '14.560')
            ->assertJsonPath('items.0.sell_price', '20.123');
        $this->assertEqualsWithDelta(13.9075, $po->gross_profit, 0.00001);
        $this->get(route('purchase-orders.index'))->assertOk()
            ->assertSee('USD 14,56')->assertSee('USD 20,123')->assertSee('USD 13,908');

        $data['currency'] = 'SGD';
        $data['items'][0]['buy_price'] = '15.789';
        $this->put(route('purchase-orders.update', $po), $data)->assertSessionHasNoErrors();
        $this->get(route('purchase-orders.edit', $po))->assertOk()
            ->assertJsonPath('currency', 'SGD')->assertJsonPath('items.0.buy_price', '15.789');
        $this->get(route('purchase-orders.index'))->assertOk()->assertSee('SGD 15,789');
    }
}
