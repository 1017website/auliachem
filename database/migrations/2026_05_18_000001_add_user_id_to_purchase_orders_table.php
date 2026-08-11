<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_orders', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->after('lead_id');
            }
        });

        // Backfill: ambil user_id dari lead jika ada
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('UPDATE purchase_orders
                SET user_id = (SELECT leads.user_id FROM leads WHERE leads.id = purchase_orders.lead_id)
                WHERE user_id IS NULL AND lead_id IS NOT NULL');
        } else {
            DB::statement('UPDATE purchase_orders po
                JOIN leads l ON l.id = po.lead_id
                SET po.user_id = l.user_id
                WHERE po.user_id IS NULL AND po.lead_id IS NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
