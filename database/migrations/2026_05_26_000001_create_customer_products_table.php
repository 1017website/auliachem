<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel customer_products — field disamakan dengan supplier_products
        // (product_name, unit, description). Menggantikan field string `products`
        // sebagai sumber data kebutuhan produk customer.
        Schema::create('customer_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('product_name');
            $table->string('unit')->default('ton');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Migrasikan data lama dari kolom string `customers.products` (jika ada)
        // ke tabel relasi baru, agar data existing tidak hilang.
        if (Schema::hasColumn('customers', 'products')) {
            $customers = \Illuminate\Support\Facades\DB::table('customers')
                ->whereNotNull('products')
                ->where('products', '!=', '')
                ->get(['id', 'products']);

            foreach ($customers as $cust) {
                $items = array_filter(array_map('trim', explode(',', (string) $cust->products)));
                foreach ($items as $item) {
                    // Format lama bisa "Nama (unit)" — pecah jadi name + unit
                    $name = $item;
                    $unit = 'ton';
                    if (preg_match('/^(.*)\s*\((.+)\)\s*$/', $item, $m)) {
                        $name = trim($m[1]);
                        $unit = trim($m[2]);
                    }
                    if ($name === '') {
                        continue;
                    }
                    \Illuminate\Support\Facades\DB::table('customer_products')->insert([
                        'customer_id'  => $cust->id,
                        'product_name' => $name,
                        'unit'         => $unit !== '' ? $unit : 'ton',
                        'description'  => null,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_products');
    }
};
