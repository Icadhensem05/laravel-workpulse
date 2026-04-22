<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 120)->unique();
            $table->text('setting_value')->nullable();
            $table->timestamps();
        });

        Schema::create('approval_settings', function (Blueprint $table) {
            $table->id();
            $table->string('module', 100);
            $table->string('setting_key', 120);
            $table->string('setting_value', 255)->nullable();
            $table->timestamps();
            $table->unique(['module', 'setting_key']);
        });

        DB::table('app_settings')->insert([
            ['setting_key' => 'company_name', 'setting_value' => 'Weststar Engineering', 'created_at' => now(), 'updated_at' => now()],
            ['setting_key' => 'default_mileage_rate', 'setting_value' => '0.50', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_settings');
        Schema::dropIfExists('app_settings');
    }
};
