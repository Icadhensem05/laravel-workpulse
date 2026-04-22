<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claim_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained()->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->foreignId('uploaded_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('claim_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained()->cascadeOnDelete();
            $table->string('payment_reference', 120)->nullable();
            $table->date('payment_date')->nullable();
            $table->string('payment_method', 120)->nullable();
            $table->decimal('payment_amount', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->foreignId('recorded_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_payments');
        Schema::dropIfExists('claim_attachments');
    }
};
