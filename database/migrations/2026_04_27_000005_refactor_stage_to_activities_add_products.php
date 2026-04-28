<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove stage from customers (skip if already done)
        if (Schema::hasColumn('customers', 'stage')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('stage');
            });
        }

        // Add stage to sales_activities (skip if already done)
        if (!Schema::hasColumn('sales_activities', 'stage')) {
            Schema::table('sales_activities', function (Blueprint $table) {
                $table->enum('stage', ['identifying','approaching','following_up','closing','maintaining'])
                      ->after('customer_id');
            });
        }

        // Pivot: customer preferred products
        if (!Schema::hasTable('customer_product_categories')) {
            Schema::create('customer_product_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_category_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['customer_id', 'product_category_id'], 'cpc_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_product_categories');

        if (Schema::hasColumn('sales_activities', 'stage')) {
            Schema::table('sales_activities', function (Blueprint $table) {
                $table->dropColumn('stage');
            });
        }

        if (!Schema::hasColumn('customers', 'stage')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->enum('stage', ['identifying','approaching','following_up','closing','maintaining'])
                      ->default('identifying')->after('type');
            });
        }
    }
};