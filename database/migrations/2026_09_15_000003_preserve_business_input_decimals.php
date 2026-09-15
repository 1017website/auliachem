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
            foreach ($fields as $field) $this->changeDecimal($name, $field, 19, 0);
        }
        $this->changeDecimal('users', 'target', 22, 500000000);
        $this->changeDecimal('leads', 'probability', 6, 0);
        $this->changeDecimal('suppliers', 'rating', 5, 0);
        $this->changeDecimal('quotations', 'tax_percent', 6, 0);
        $this->changeDecimal('invoices', 'tax_percent', 6, 11);
    }

    private function changeDecimal(string $name, string $field, int $precision, int $default): void
    {
        $column = collect(Schema::getColumns($name))->firstWhere('name', $field);
        Schema::table($name, function (Blueprint $table) use ($field, $precision, $default, $column) {
            $table->decimal($field, $precision, 3)
                ->nullable($column['nullable'])
                ->default($default)->change();
        });
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
