<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Bikin tabel po_items
        Schema::create('po_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('product_category_id')->constrained('product_categories');
            $table->string('product_name'); // Nama produk spesifik (misal: "Methanol 99%")
            $table->decimal('quantity', 12, 2);
            $table->string('unit', 20)->default('kg'); // kg, liter, drum, dll
            $table->decimal('unit_price', 15, 2);   // harga jual per unit
            $table->decimal('unit_cost', 15, 2);    // COGS per unit
            $table->decimal('subtotal', 15, 2);     // qty * unit_price
            $table->decimal('subtotal_cogs', 15, 2); // qty * unit_cost
            $table->decimal('subtotal_gross_profit', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['purchase_order_id']);
        });

        // 2. Tambah kolom yang diperlukan ke purchase_orders, ubah yang lama jadi nullable/computed
        Schema::table('purchase_orders', function (Blueprint $table) {
            // total_amount, cogs, gross_profit sekarang dihitung dari items
            // Tapi tetap disimpan di parent untuk performa query analisa
            // Hanya buat product_category_id jadi nullable (jadi optional di header)
            $table->foreignId('product_category_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('product_category_id')->nullable(false)->change();
        });
        Schema::dropIfExists('po_items');
    }
};
