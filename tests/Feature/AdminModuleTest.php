<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_settings_and_approvals(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($admin)
            ->getJson('/app-api/admin/users')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($admin)
            ->postJson('/app-api/admin/approvals', [
                'rows' => [
                    ['module' => 'claims', 'setting_key' => 'level_1', 'setting_value' => 'manager'],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($admin)
            ->postJson('/app-api/admin/settings', [
                'settings' => [
                    'company_name' => 'Weststar Engineering',
                    'default_mileage_rate' => '0.60',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($admin)
            ->postJson('/app-api/admin/link-person', [
                'user_id' => $employee->id,
                'employee_code' => 'WES-0099',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($admin)->getJson('/app-api/admin/overview')->assertOk()->assertJsonPath('success', true);
        $this->actingAs($admin)->getJson('/app-api/admin/settings')->assertOk()->assertJsonPath('success', true);
        $this->actingAs($admin)->getJson('/app-api/admin/approvals')->assertOk()->assertJsonPath('success', true);
    }
}
