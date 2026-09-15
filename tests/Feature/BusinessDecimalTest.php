<?php
namespace Tests\Feature;

use App\Models\{User, Product, Quotation, Invoice, Lead, Customer, Supplier};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessDecimalTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_prices_and_stocks_round_trip_and_reject_excess_precision(): void
    {
        $this->actingAs(User::factory()->create(['role'=>'Admin','status'=>'Active']));
        $data=['product_name'=>'Decimal','unit'=>'Kg','status'=>'Active', 'buy_price'=>'14,56','sell_price'=>'20,567','current_stock'=>'1.000,567','minimum_stock'=>'0,123'];
        $this->post(route('products.store'),$data)->assertSessionHasNoErrors();
        $product=Product::sole();
        $this->get(route('products.edit',$product))->assertJsonPath('buy_price','14.560')->assertJsonPath('sell_price','20.567')->assertJsonPath('current_stock','1000.567')->assertJsonPath('minimum_stock','0.123');
        foreach (['buy_price','sell_price','current_stock','minimum_stock'] as $field) {
            $this->put(route('products.update',$product),array_replace($data,[$field=>'14,5678']))->assertSessionHasErrors($field);
        }
        $this->put(route('products.update',$product),array_replace($data,['buy_price'=>'0,001']))->assertSessionHasNoErrors();
        $this->assertSame('0.001',$product->fresh()->buy_price);
    }

    public function test_document_prices_quantities_and_tax_round_trip(): void
    {
        $this->actingAs(User::factory()->create(['role'=>'Admin','status'=>'Active']));
        foreach (['quotations'=>[Quotation::class,'quotation_date'],'invoices'=>[Invoice::class,'invoice_date']] as $route=>[$class,$date]) {
            $data=['customer_name'=>'Decimal',$date=>'2026-09-15','currency'=>'USD','status'=>'Draft','tax_percent'=>'11,567', 'items'=>[['item_name'=>'Chemical','unit'=>'kg','qty'=>'14,567','unit_price'=>'14,56']]];
            $this->post(route($route.'.store'),$data)->assertSessionHasNoErrors();
            $model=$class::sole();
            $this->get(route($route.'.edit',$model))->assertJsonPath('tax_percent','11.567')->assertJsonPath('items.0.qty','14.567')->assertJsonPath('items.0.unit_price','14.560');
            $data['items'][0]['unit_price']='20,567';
            $this->put(route($route.'.update',$model),$data)->assertSessionHasNoErrors();
            $this->assertSame('20.567',$model->fresh()->items->first()->unit_price);
            $data['tax_percent']='11,5678';
            $this->put(route($route.'.update',$model),$data)->assertSessionHasErrors('tax_percent');
            $data['tax_percent']='101';
            $this->put(route($route.'.update',$model),$data)->assertSessionHasErrors('tax_percent');
        }
    }

    public function test_target_probability_rating_and_customer_quantities(): void
    {
        $admin=User::factory()->create(['role'=>'Admin','status'=>'Active']);
        $this->actingAs($admin);
        $user=['name'=>'Decimal','email'=>'decimal@example.test','password'=>'password','password_confirmation'=>'password','role'=>'Sales Executive','status'=>'Active','target'=>'1.000,567'];
        $this->post(route('users.store'),$user)->assertSessionHasNoErrors();
        $created=User::where('email',$user['email'])->firstOrFail();
        $this->assertSame('1000.567',$created->target);
        $this->put(route('users.update',$created),['target'=>'14,56'])->assertSessionHasNoErrors();
        $this->assertSame('14.560',$created->fresh()->target);
        $this->put(route('users.update',$created),['target'=>'14,5678'])->assertSessionHasErrors('target');

        $lead=['company_name'=>'Decimal lead','pic_name'=>'PIC','user_id'=>$admin->id,'probability'=>'14,567', 'products'=>[['product_name'=>'Chemical','qty'=>'14,56','unit'=>'kg']]];
        $this->post(route('leads.store'),$lead)->assertSessionHasNoErrors();
        $model=Lead::where('company_name','Decimal lead')->firstOrFail();
        $this->assertSame('14.567',$model->probability);
        $this->assertEquals(14.56,$model->products->first()->qty);
        $lead['probability']='20,123';
        $this->put(route('leads.update',$model),$lead)->assertSessionHasNoErrors();
        $this->assertSame('20.123',$model->fresh()->probability);
        $lead['probability']='100,001';
        $this->put(route('leads.update',$model),$lead)->assertSessionHasErrors('probability');

        $supplier=['supplier_name'=>'Decimal supplier','pic_name'=>'PIC','phone'=>'08123','source_type'=>'Local','status'=>'Active','relationship_status'=>'Potential','rating'=>'4,567'];
        $this->post(route('suppliers.store'),$supplier)->assertSessionHasNoErrors();
        $model=Supplier::sole();
        $this->assertSame('4.567',$model->rating);
        $supplier['rating']='3,456';
        $this->put(route('suppliers.update',$model),$supplier)->assertSessionHasNoErrors();
        $this->assertSame('3.456',$model->fresh()->rating);
        $supplier['rating']='5,001';
        $this->put(route('suppliers.update',$model),$supplier)->assertSessionHasErrors('rating');

        $customer=['company_name'=>'Decimal customer','pic_name'=>'PIC','phone'=>'08123','user_id'=>$admin->id,'products_list'=>[['product_name'=>'Chemical','unit'=>'kg','qty'=>'14,567']]];
        $this->post(route('customers.store'),$customer)->assertSessionHasNoErrors();
        $model=Customer::where('company_name','Decimal customer')->firstOrFail();
        $this->assertEquals(14.567,$model->productItems->first()->qty);
        $customer['products_list'][0]['qty']='20,123';
        $customer['products_submitted']=1;
        $this->put(route('customers.update',$model),$customer)->assertSessionHasNoErrors();
        $this->assertEquals(20.123,$model->fresh()->productItems->first()->qty);
    }
}
