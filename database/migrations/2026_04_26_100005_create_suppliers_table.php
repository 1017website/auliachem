<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('pic_name');
            $table->string('phone', 50);
            $table->string('country', 100);
            $table->enum('type', ['potential', 'existing'])->default('potential');
            $table->enum('source', ['local', 'import']);
            $table->foreignId('principal_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_category_id')->constrained()->restrictOnDelete();
            $table->integer('lead_time_days')->nullable();
            $table->enum('currency', ['IDR', 'USD', 'EUR'])->default('IDR');
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
        Schema::dropIfExists('suppliers');
    }
};
