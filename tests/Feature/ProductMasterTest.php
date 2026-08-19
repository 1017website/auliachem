<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductMasterTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_manage_product_master(): void
    {
        $user = User::factory()->create(['role' => 'Sales Manager', 'status' => 'Active']);

        $this->actingAs($user)->post(route('products.store'), [
            'product_name' => 'Sodium Sulfite',
            'category' => 'Industrial Chemical',
            'unit' => 'Kg',
            'description' => 'Thailand origin',
            'buy_price' => 12000,
            'sell_price' => 15900,
            'current_stock' => 900,
            'minimum_stock' => 100,
            'status' => 'Active',
        ])->assertRedirect(route('products.index'));

        $product = Product::firstOrFail();
        $this->assertSame('BRG-00001', $product->product_code);
        $this->assertFalse($product->is_low_stock);

        $this->actingAs($user)->put(route('products.update', $product), [
            'product_name' => 'Sodium Sulfite Thailand',
            'category' => 'Industrial Chemical',
            'unit' => 'Kg',
            'buy_price' => 12500,
            'sell_price' => 16000,
            'current_stock' => 50,
            'minimum_stock' => 100,
            'status' => 'Active',
        ])->assertRedirect(route('products.index'));

        $this->assertTrue($product->fresh()->is_low_stock);
        $this->actingAs($user)->get(route('products.index', ['stock' => 'low']))
            ->assertOk()->assertSee('Sodium Sulfite Thailand')->assertSee('BRG-00001');
    }

    public function test_product_master_is_available_on_sales_document_forms(): void
    {
        $user = User::factory()->create(['role' => 'Sales Manager', 'status' => 'Active']);
        Product::createWithUniqueCode([
            'product_name' => 'Caustic Soda Flake',
            'unit' => 'Kg',
            'buy_price' => 12000,
            'sell_price' => 14500,
            'current_stock' => 1000,
            'minimum_stock' => 100,
            'status' => 'Active',
        ]);

        $this->actingAs($user)->get(route('invoices.index'))
            ->assertOk()
            ->assertSee('Caustic Soda Flake')
            ->assertSee('Qty: titik = ribuan, koma = desimal')
            ->assertSee('inputmode="decimal"', false)
            ->assertSee('doc-qty-hidden', false);
        $this->actingAs($user)->get(route('quotations.index'))
            ->assertOk()
            ->assertSee('Caustic Soda Flake')
            ->assertSee('Qty: titik = ribuan, koma = desimal');
        $this->actingAs($user)->get(route('purchase-orders.index'))
            ->assertOk()
            ->assertSee('Caustic Soda Flake')
            ->assertSee('Qty: titik = ribuan, koma = desimal')
            ->assertSee('inputmode="decimal"', false)
            ->assertSee('item-qty-hidden', false);
    }
}
