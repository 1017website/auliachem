<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove pipeline tables
        Schema::dropIfExists('pipeline_logs');
        Schema::dropIfExists('customer_pipelines');

        // Restore sales_activities (customer_id + stage)
        Schema::create('sales_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->enum('stage', ['identifying','approaching','following_up','closing','maintaining'])
                  ->default('identifying');
            $table->enum('type', ['phone','visit','whatsapp','email','other']);
            $table->date('activity_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_activities');
    }
};