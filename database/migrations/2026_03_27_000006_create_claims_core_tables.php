<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claim_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('requires_attachment')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->string('claim_no')->unique();
            $table->foreignId('employee_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('company_name')->default('Weststar Engineering');
            $table->string('employee_name');
            $table->string('employee_code')->nullable();
            $table->string('position_title')->nullable();
            $table->string('department')->nullable();
            $table->string('cost_center')->nullable();
            $table->string('claim_month', 7);
            $table->date('claim_date');
            $table->decimal('total_travelling', 12, 2)->default(0);
            $table->decimal('total_transportation', 12, 2)->default(0);
            $table->decimal('total_accommodation', 12, 2)->default(0);
            $table->decimal('total_travelling_allowance', 12, 2)->default(0);
            $table->decimal('total_entertainment', 12, 2)->default(0);
            $table->decimal('total_miscellaneous', 12, 2)->default(0);
            $table->decimal('advance_amount', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->decimal('balance_claim', 12, 2)->default(0);
            $table->text('employee_remarks')->nullable();
            $table->text('manager_remarks')->nullable();
            $table->text('finance_remarks')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('claim_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained('claims')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('claim_categories')->restrictOnDelete();
            $table->unsignedInteger('line_no')->default(1);
            $table->date('item_date');
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->string('purpose')->nullable();
            $table->string('receipt_no')->nullable();
            $table->string('invoice_no')->nullable();
            $table->string('hotel_name')->nullable();
            $table->string('description')->nullable();
            $table->decimal('distance_km', 10, 2)->default(0);
            $table->decimal('mileage_rate', 10, 4)->default(0);
            $table->decimal('mileage_amount', 12, 2)->default(0);
            $table->decimal('toll_amount', 12, 2)->default(0);
            $table->decimal('parking_amount', 12, 2)->default(0);
            $table->decimal('rate_amount', 12, 2)->default(0);
            $table->decimal('quantity_value', 10, 2)->default(1);
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('claim_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained('claims')->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('action_name');
            $table->foreignId('action_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action_role')->default('system');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        DB::table('claim_categories')->insert([
            ['code' => 'travelling', 'name' => 'Travelling', 'requires_attachment' => 0, 'display_order' => 10, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'transportation', 'name' => 'Transportation', 'requires_attachment' => 1, 'display_order' => 20, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'accommodation', 'name' => 'Accommodation', 'requires_attachment' => 1, 'display_order' => 30, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'travelling_allowance', 'name' => 'Travelling Allowance', 'requires_attachment' => 0, 'display_order' => 40, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'entertainment', 'name' => 'Entertainment / Refreshment', 'requires_attachment' => 1, 'display_order' => 50, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'miscellaneous', 'name' => 'Miscellaneous / Others', 'requires_attachment' => 1, 'display_order' => 60, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_status_logs');
        Schema::dropIfExists('claim_items');
        Schema::dropIfExists('claims');
        Schema::dropIfExists('claim_categories');
    }
};
