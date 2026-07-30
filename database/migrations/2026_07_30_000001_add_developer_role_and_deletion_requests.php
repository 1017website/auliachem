<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ubah enum menjadi string agar role sistem Developer dapat disimpan
        // dan penambahan role berikutnya tidak memerlukan ALTER ENUM.
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 100)->default('Sales Executive')->change();
        });

        Schema::create('deletion_requests', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->string('model_label')->nullable();
            $table->string('module');
            $table->string('status')->default('pending');
            $table->text('reason')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id', 'status'], 'deletion_target_status_index');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deletion_requests');

        // Kolom role sengaja tetap string. Mengembalikannya menjadi enum berisiko
        // merusak akun Developer yang mungkin sudah dibuat.
    }
};
