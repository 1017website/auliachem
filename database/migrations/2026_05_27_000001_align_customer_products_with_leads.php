<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Revisi: field produk customer disamakan dengan lead_products
        // (product_name, qty, unit) => tambah `qty`, buang `description`.
        Schema::table('customer_products', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_products', 'qty')) {
                $table->decimal('qty', 15, 3)->default(0)->after('product_name');
            }
        });

        Schema::table('customer_products', function (Blueprint $table) {
            if (Schema::hasColumn('customer_products', 'description')) {
                $table->dropColumn('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_products', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_products', 'description')) {
                $table->text('description')->nullable()->after('unit');
            }
        });

        Schema::table('customer_products', function (Blueprint $table) {
            if (Schema::hasColumn('customer_products', 'qty')) {
                $table->dropColumn('qty');
            }
        });
    }
};
