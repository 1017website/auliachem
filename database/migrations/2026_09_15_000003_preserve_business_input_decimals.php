<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['products' => ['buy_price', 'sell_price'], 'quotation_items' => ['unit_price'],
            'invoice_items' => ['unit_price']] as $name => $fields) {
            Schema::table($name, function (Blueprint $table) use ($fields) {
                foreach ($fields as $field) $table->decimal($field, 19, 3)->default(0)->change();
            });
        }
        Schema::table('users', fn (Blueprint $table) => $table->decimal('target', 22, 3)->default(500000000)->change());
        Schema::table('leads', fn (Blueprint $table) => $table->decimal('probability', 6, 3)->default(0)->change());
        Schema::table('suppliers', fn (Blueprint $table) => $table->decimal('rating', 5, 3)->default(0)->change());
        Schema::table('quotations', fn (Blueprint $table) => $table->decimal('tax_percent', 6, 3)->default(0)->change());
        Schema::table('invoices', fn (Blueprint $table) => $table->decimal('tax_percent', 6, 3)->default(11)->change());
    }

    public function down(): void
    {
        foreach (['products' => ['buy_price', 'sell_price'], 'quotation_items' => ['unit_price'],
            'invoice_items' => ['unit_price']] as $name => $fields) {
            Schema::table($name, function (Blueprint $table) use ($fields) {
                foreach ($fields as $field) $table->decimal($field, 18, 2)->default(0)->change();
            });
        }
        Schema::table('users', fn (Blueprint $table) => $table->bigInteger('target')->default(500000000)->change());
        Schema::table('leads', fn (Blueprint $table) => $table->integer('probability')->default(0)->change());
        Schema::table('suppliers', fn (Blueprint $table) => $table->decimal('rating', 3, 1)->default(0)->change());
        Schema::table('quotations', fn (Blueprint $table) => $table->decimal('tax_percent', 5, 2)->default(0)->change());
        Schema::table('invoices', fn (Blueprint $table) => $table->decimal('tax_percent', 5, 2)->default(11)->change());
    }
};
