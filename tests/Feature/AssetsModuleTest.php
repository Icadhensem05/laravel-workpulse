<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetsModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_assign_asset(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);

        $assetId = $this->actingAs($admin)
            ->postJson('/app-api/assets', [
                'asset_code' => 'AST-0001',
                'name' => 'Dell Latitude',
                'category' => 'Laptop',
                'serial_no' => 'SN-123',
            ])
            ->assertCreated()
            ->json('row.id');

        $this->actingAs($admin)
            ->postJson("/app-api/assets/{$assetId}/status", [
                'status' => 'assigned',
                'assigned_to_user_id' => $employee->id,
                'remarks' => 'Issued to employee',
            ])
            ->assertOk()
            ->assertJsonPath('row.status', 'assigned');

        $this->actingAs($admin)
            ->getJson('/app-api/assets')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'rows');
    }
}
