<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('employee_code')->nullable()->after('email');
            $table->string('role')->default('employee')->after('employee_code');
            $table->string('job_title')->nullable()->after('role');
            $table->string('department')->nullable()->after('job_title');
            $table->string('cost_center')->nullable()->after('department');
            $table->string('base')->nullable()->after('cost_center');
            $table->string('phone')->nullable()->after('base');
            $table->string('profile_photo')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name',
                'employee_code',
                'role',
                'job_title',
                'department',
                'cost_center',
                'base',
                'phone',
                'profile_photo',
            ]);
        });
    }
};
