<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_activities', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->after('id');
            $table->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();
        });

        Schema::table('sales_activities', function (Blueprint $table) {
            $table->dropForeign(['sales_lead_id']);
            $table->dropColumn('sales_lead_id');
        });
    }

    public function down(): void
    {
        Schema::table('sales_activities', function (Blueprint $table) {
            $table->foreignId('sales_lead_id')->after('id')->constrained()->restrictOnDelete();
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });
    }
};