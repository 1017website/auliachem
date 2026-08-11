<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_code')->unique();
            $table->string('product_name');
            $table->string('category')->nullable();
            $table->string('unit', 50)->default('Kg');
            $table->text('description')->nullable();
            $table->decimal('buy_price', 18, 2)->default(0);
            $table->decimal('sell_price', 18, 2)->default(0);
            $table->decimal('current_stock', 18, 3)->default(0);
            $table->decimal('minimum_stock', 18, 3)->default(0);
            $table->string('status', 20)->default('Active');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'category']);
            $table->index('product_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
