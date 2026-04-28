<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('stage', ['identifying','approaching','following_up','closing','maintaining'])
                  ->default('identifying')->after('type');
            $table->enum('status', ['prospect','active','inactive'])
                  ->default('prospect')->after('stage');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['stage', 'status']);
        });
    }
};