<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_team_and_link_member(): void
    {
        $lead = User::factory()->create(['role' => 'employee']);
        $member = User::factory()->create(['role' => 'employee']);

        $teamId = $this->actingAs($lead)
            ->postJson('/app-api/team', [
                'name' => 'ICT Operations',
                'description' => 'Internal support team',
            ])
            ->assertCreated()
            ->json('row.id');

        $this->actingAs($lead)
            ->postJson('/app-api/team/link', [
                'team_id' => $teamId,
                'user_id' => $member->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($lead)
            ->getJson('/app-api/team/member-options?team_id='.$teamId)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($lead)
            ->getJson('/app-api/team/my')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'rows');
    }
}
