<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom product ke customers
        Schema::table('customers', function (Blueprint $table) {
            $table->text('products')->nullable()->after('notes'); // JSON list produk
        });

        // 2. Tabel lead_products (product interest per row)
        Schema::create('lead_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->string('product_name');
            $table->decimal('qty', 15, 3)->default(0);
            $table->string('unit')->default('ton');
            $table->timestamps();
        });

        // 3. Tabel supplier_products
        Schema::create('supplier_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('product_name');
            $table->string('unit')->default('ton');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 4. Tabel customer_pics (multi PIC)
        Schema::create('customer_pics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('pic_name');
            $table->string('pic_position')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        // 5. Tabel lead_pics (multi PIC untuk leads)
        Schema::create('lead_pics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->string('pic_name');
            $table->string('pic_position')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_pics');
        Schema::dropIfExists('customer_pics');
        Schema::dropIfExists('supplier_products');
        Schema::dropIfExists('lead_products');
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('products');
        });
    }
};
