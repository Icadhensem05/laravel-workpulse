<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->decimal('default_days', 8, 1)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('leave_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->decimal('allocated_days', 8, 1)->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'leave_type_id', 'year'], 'leave_allocations_user_type_year_unique');
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('part_day', 30)->default('full');
            $table->decimal('days_count', 8, 1)->default(0);
            $table->string('person_to_relief')->nullable();
            $table->text('reason')->nullable();
            $table->string('status', 50)->default('pending');
            $table->text('admin_comment')->nullable();
            $table->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });

        DB::table('leave_types')->insert([
            ['code' => 'annual', 'name' => 'Annual Leave', 'default_days' => 14, 'display_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'sick', 'name' => 'Sick Leave', 'default_days' => 14, 'display_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'emergency', 'name' => 'Emergency Leave', 'default_days' => 3, 'display_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'unpaid', 'name' => 'Unpaid Leave', 'default_days' => 0, 'display_order' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_allocations');
        Schema::dropIfExists('leave_types');
    }
};
