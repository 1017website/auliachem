<?php

namespace Tests\Feature;

use App\Models\DeletionRequest;
use App\Models\Activity;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeletionRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_creates_request_instead_of_deleting_data(): void
    {
        $manager = $this->makeUser(User::ROLE_SALES_MANAGER);
        $lead = $this->makeLead($manager);

        $this->actingAs($manager)
            ->post(route('deletion-requests.store'), [
                'module' => 'leads',
                'model_id' => $lead->id,
                'reason' => 'Data duplikat',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('deletion_requests', [
            'module' => 'leads',
            'model_id' => $lead->id,
            'status' => 'pending',
            'requested_by' => $manager->id,
        ]);
        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'deleted_at' => null]);
    }

    public function test_non_admin_cannot_bypass_request_flow_with_direct_delete(): void
    {
        $manager = $this->makeUser(User::ROLE_SALES_MANAGER);
        $lead = $this->makeLead($manager);

        $this->actingAs($manager)
            ->delete(route('leads.destroy', $lead))
            ->assertForbidden();

        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'deleted_at' => null]);
    }

    public function test_administrator_can_approve_and_execute_request(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $manager = $this->makeUser(User::ROLE_SALES_MANAGER);
        $lead = $this->makeLead($manager);
        $deletionRequest = DeletionRequest::requestFor('leads', $lead, $manager->id, 'Tidak valid');

        $this->actingAs($admin)
            ->post(route('deletion-requests.approve', $deletionRequest), [
                'review_note' => 'Disetujui',
            ])
            ->assertRedirect();

        $this->assertSoftDeleted('leads', ['id' => $lead->id]);
        $this->assertDatabaseHas('deletion_requests', [
            'id' => $deletionRequest->id,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_developer_has_admin_rights_and_is_hidden_from_user_master(): void
    {
        $developer = $this->makeUser(User::ROLE_DEVELOPER);
        $visibleUser = $this->makeUser(User::ROLE_SALES_EXECUTIVE);

        $response = $this->actingAs($developer)->get(route('users.index'));

        $response->assertOk();
        $listedUsers = $response->viewData('users')->getCollection();

        $this->assertFalse($listedUsers->contains('id', $developer->id));
        $this->assertTrue($listedUsers->contains('id', $visibleUser->id));
    }

    public function test_developer_deletes_immediately_without_creating_queue(): void
    {
        $developer = $this->makeUser(User::ROLE_DEVELOPER);
        $lead = $this->makeLead($developer);

        $this->actingAs($developer)
            ->post(route('deletion-requests.store'), [
                'module' => 'leads',
                'model_id' => $lead->id,
            ])
            ->assertRedirect();

        $this->assertSoftDeleted('leads', ['id' => $lead->id]);
        $this->assertDatabaseMissing('deletion_requests', [
            'module' => 'leads',
            'model_id' => $lead->id,
            'status' => 'pending',
        ]);
    }

    public function test_all_pages_with_delete_controls_render_successfully(): void
    {
        $manager = $this->makeUser(User::ROLE_SALES_MANAGER);
        $lead = $this->makeLead($manager);
        $lead->pics()->create(['pic_name' => 'PIC Lead']);
        $lead->products()->create(['product_name' => 'Produk Lead', 'qty' => 1, 'unit' => 'kg']);

        $customer = Customer::create([
            'company_name' => 'Customer ' . Str::random(8),
            'pic_name' => 'PIC Customer',
            'phone' => '0800000000',
            'status' => 'Existing',
            'user_id' => $manager->id,
        ]);
        $customer->pics()->create(['pic_name' => 'PIC Tambahan']);

        $supplier = Supplier::create([
            'supplier_name' => 'Supplier ' . Str::random(8),
            'pic_name' => 'PIC Supplier',
            'phone' => '0800000001',
            'source_type' => 'Local',
            'status' => 'Active',
            'relationship_status' => 'Existing',
        ]);
        $supplier->pics()->create(['pic_name' => 'PIC Supplier Tambahan']);
        $supplier->products()->create(['product_name' => 'Produk Supplier', 'unit' => 'kg']);

        PurchaseOrder::create([
            'po_number' => 'TEST-PO-' . Str::upper(Str::random(10)),
            'customer_id' => $customer->id,
            'supplier_id' => $supplier->id,
            'lead_id' => $lead->id,
            'user_id' => $manager->id,
            'currency' => 'IDR',
            'status' => 'In Progress',
            'order_date' => today(),
        ]);

        Activity::create([
            'user_id' => $manager->id,
            'sales_user_id' => $manager->id,
            'type' => 'Call',
            'subject' => 'Task Test',
            'activity_at' => now(),
            'status' => 'Planned',
        ]);

        $this->actingAs($manager);

        $this->get(route('leads.index'))->assertOk();
        $this->get(route('leads.show', $lead))->assertOk();
        $this->get(route('customers.index', ['selected_id' => $customer->id]))->assertOk();
        $this->get(route('suppliers.index', ['selected_id' => $supplier->id]))->assertOk();
        $this->get(route('purchase-orders.index'))->assertOk();
        $this->get(route('tasks.index'))->assertOk();
    }

    private function makeUser(string $role): User
    {
        return User::create([
            'name' => $role . ' Test',
            'email' => Str::uuid() . '@example.test',
            'password' => 'password',
            'role' => $role,
            'status' => 'Active',
            'target' => 0,
        ]);
    }

    private function makeLead(User $owner): Lead
    {
        return Lead::create([
            'lead_code' => 'TEST-' . Str::upper(Str::random(12)),
            'company_name' => 'Test Company ' . Str::random(8),
            'pic_name' => 'Test PIC',
            'pipeline_stage' => 'Identifying',
            'user_id' => $owner->id,
        ]);
    }
}
