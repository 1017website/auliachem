<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Industry;
use App\Models\Principal;
use App\Models\ProductCategory;
use App\Models\PurchaseOrder;
use App\Models\SalesActivity;
use App\Models\SalesLead;
use App\Models\Supplier;
use App\Models\User;
use App\Observers\AssignmentObserver;
use App\Observers\AuditObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $auditModels = [
            User::class,
            Industry::class,
            ProductCategory::class,
            Principal::class,
            Customer::class,
            Supplier::class,
            SalesLead::class,
            SalesActivity::class,
            PurchaseOrder::class,
            Expense::class,
        ];

        foreach ($auditModels as $model) {
            $model::observe(AuditObserver::class);
        }

        Customer::observe(AssignmentObserver::class);
        SalesLead::observe(AssignmentObserver::class);
    }
}
