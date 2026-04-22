<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_fetch_profile_payload(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Muhammad',
            'last_name' => 'Irsyad',
            'email' => 'profile@example.com',
            'role' => 'employee',
            'department' => 'ICT',
        ]);

        $response = $this->actingAs($user)->getJson('/app-api/profile');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.email', 'profile@example.com')
            ->assertJsonPath('user.department', 'ICT');
    }

    public function test_authenticated_user_can_update_profile(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Old',
            'last_name' => 'Name',
            'email' => 'old@example.com',
        ]);

        $response = $this->actingAs($user)->putJson('/app-api/profile', [
            'first_name' => 'New',
            'last_name' => 'Name',
            'email' => 'new@example.com',
            'employee_code' => 'WES-0200',
            'job_title' => 'Executive',
            'department' => 'Operations',
            'cost_center' => 'OPS',
            'base' => 'Subang',
            'phone' => '0199999999',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.email', 'new@example.com')
            ->assertJsonPath('user.job_title', 'Executive');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'new@example.com',
            'department' => 'Operations',
        ]);
    }

    public function test_authenticated_user_can_update_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->actingAs($user)->putJson('/app-api/profile/password', [
            'current' => 'Password123!',
            'next' => 'Password456!',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('Password456!', $user->fresh()->password));
    }

    public function test_authenticated_user_can_upload_profile_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/app-api/profile/photo', [
            'photo' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $path = $response->json('path');
        Storage::disk('public')->assertExists($path);
    }
}
