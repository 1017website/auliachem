<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name');
            $table->text('customer_address')->nullable();
            $table->string('customer_phone')->nullable();
            $table->date('quotation_date');
            $table->date('valid_until')->nullable();
            $table->string('currency', 10)->default('IDR');
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->string('status', 30)->default('Draft');
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['quotation_date', 'status']);
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->string('item_name');
            $table->text('description')->nullable();
            $table->string('unit', 50)->default('Kg');
            $table->decimal('qty', 15, 3)->default(0);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name');
            $table->text('customer_address')->nullable();
            $table->string('customer_phone')->nullable();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('currency', 10)->default('IDR');
            $table->decimal('tax_percent', 5, 2)->default(11);
            $table->string('status', 30)->default('Draft');
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->text('bank_details')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['invoice_date', 'status']);
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('item_name');
            $table->text('description')->nullable();
            $table->string('unit', 50)->default('Kg');
            $table->decimal('qty', 15, 3)->default(0);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->timestamps();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->text('delivery_address')->nullable()->after('notes');
            $table->text('special_instructions')->nullable()->after('delivery_address');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_address', 'special_instructions']);
        });

        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
    }
};
