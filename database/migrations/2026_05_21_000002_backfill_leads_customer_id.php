<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill customer_id di leads yang masih NULL
        // Match berdasarkan company_name (case-insensitive)
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("
                UPDATE leads
                SET customer_id = (
                    SELECT customers.id FROM customers
                    WHERE LOWER(TRIM(customers.company_name)) = LOWER(TRIM(leads.company_name))
                    LIMIT 1
                )
                WHERE customer_id IS NULL
                  AND EXISTS (
                    SELECT 1 FROM customers
                    WHERE LOWER(TRIM(customers.company_name)) = LOWER(TRIM(leads.company_name))
                  )
            ");
        } else {
            DB::statement("
                UPDATE leads l
                JOIN customers c ON LOWER(TRIM(l.company_name)) = LOWER(TRIM(c.company_name))
                SET l.customer_id = c.id
                WHERE l.customer_id IS NULL
            ");
        }
    }

    public function down(): void {}
};
