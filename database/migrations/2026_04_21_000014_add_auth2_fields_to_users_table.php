<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('auth_user_id')->nullable()->unique()->after('id');
            $table->string('status', 50)->default('active')->after('role');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['auth_user_id']);
            $table->dropColumn(['auth_user_id', 'status', 'last_login_at']);
        });
    }
};
