<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date');
            $table->time('check_in_at')->nullable();
            $table->time('check_out_at')->nullable();
            $table->unsignedInteger('break_minutes')->default(0);
            $table->unsignedInteger('total_minutes')->default(0);
            $table->string('status', 50)->default('absent');
            $table->text('remarks')->nullable();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_entries');
    }
};
