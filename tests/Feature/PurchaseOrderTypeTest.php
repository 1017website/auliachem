<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PurchaseOrderTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_forms_offer_po_type_and_each_order_has_one_automatic_print_link(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'Admin', 'status' => 'Active']));
        $orders = [];
        foreach (['Local', 'Import'] as $type) {
            $orders[] = PurchaseOrder::createWithUniqueNumber([
                'po_type' => $type, 'currency' => 'IDR', 'status' => 'In Progress', 'order_date' => now(),
            ]);
        }

        $response = $this->get(route('purchase-orders.index'))->assertOk()
            ->assertSee('id="addPoType"', false)
            ->assertSee('id="epPoType"', false)
            ->assertSee('Lokal (Bahasa Indonesia)')
            ->assertSee('Import (Bahasa Inggris)')
            ->assertSee('Cetak ID')->assertSee('Cetak EN')
            ->assertDontSee('lang=en', false)->assertDontSee('lang=id', false);
        foreach ($orders as $order) {
            $this->assertSame(1, substr_count($response->getContent(), route('purchase-orders.print', $order)));
        }
    }

    public function test_type_is_required_and_can_be_saved_and_changed(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'Admin', 'status' => 'Active']));
        $data = [
            'currency' => 'IDR', 'status' => 'In Progress', 'order_date' => '2026-09-15',
            'items' => [[
                'product_name' => 'Chemical', 'unit' => 'Kg', 'qty' => 1,
                'buy_price' => 1000, 'sell_price' => 1200,
            ]],
        ];
        $this->post(route('purchase-orders.store'), $data)->assertSessionHasErrors('po_type');
        $this->post(route('purchase-orders.store'), $data + ['po_type' => 'Other'])
            ->assertSessionHasErrors('po_type');
        $this->post(route('purchase-orders.store'), $data + ['po_type' => 'Import'])
            ->assertSessionHasNoErrors()->assertRedirect(route('purchase-orders.index'));
        $po = PurchaseOrder::sole();
        $this->assertSame('Import', $po->po_type);
        $this->assertSame('en', $po->printLanguage());
        $this->get(route('purchase-orders.edit', $po))->assertJsonPath('po_type', 'Import');
        $this->put(route('purchase-orders.update', $po), $data + ['po_type' => 'Other'])
            ->assertSessionHasErrors('po_type');
        $this->put(route('purchase-orders.update', $po), $data + ['po_type' => 'Local'])
            ->assertSessionHasNoErrors();
        $this->assertSame('id', $po->fresh()->printLanguage());
    }

    public function test_print_and_verification_follow_type_even_with_conflicting_language(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'Admin', 'status' => 'Active']));
        foreach (['Local' => 'id', 'Import' => 'en'] as $type => $language) {
            $po = PurchaseOrder::createWithUniqueNumber([
                'po_type' => $type, 'currency' => 'USD', 'status' => 'In Progress', 'order_date' => now(),
            ]);
            foreach ([null, 'id', 'en'] as $queryLanguage) {
                $this->get(route('purchase-orders.print', ['purchaseOrder' => $po, 'lang' => $queryLanguage]))
                    ->assertOk()->assertSee('<html lang="'.$language.'">', false);
                $this->get(URL::signedRoute('documents.verify', [
                    'kind' => 'purchase_order', 'id' => $po->id, 'lang' => $queryLanguage,
                ], absolute: false))->assertOk()->assertViewHas('language', $language);
            }
        }
    }
}
