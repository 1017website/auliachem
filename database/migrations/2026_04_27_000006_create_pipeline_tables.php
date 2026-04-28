<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop sales_activities (replaced by pipeline concept)
        Schema::dropIfExists('sales_activities');

        // One pipeline record per customer (the "active" editable record)
        Schema::create('customer_pipelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('stage', ['identifying','approaching','following_up','closing','maintaining'])
                  ->default('identifying');
            $table->enum('contact_type', ['phone','visit','whatsapp','email','other'])->nullable();
            $table->date('contact_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        // Auto-saved history log (immutable, append-only)
        Schema::create('pipeline_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->enum('stage', ['identifying','approaching','following_up','closing','maintaining']);
            $table->enum('contact_type', ['phone','visit','whatsapp','email','other'])->nullable();
            $table->date('contact_date')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pipeline_logs');
        Schema::dropIfExists('customer_pipelines');
    }
};