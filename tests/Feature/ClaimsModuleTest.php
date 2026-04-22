<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClaimsModuleTest extends TestCase
{
    use RefreshDatabase;

    private function claimPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'employee_code' => 'WES-0146',
            'position_title' => 'Internship',
            'department' => 'ICT',
            'cost_center' => 'KLHQ',
            'claim_month' => '2026-03',
            'claim_date' => '2026-03-27',
            'advance_amount' => 50,
            'employee_remarks' => 'Monthly claim draft.',
            'items' => [
                [
                    'category_code' => 'travelling',
                    'item_date' => '2026-03-27',
                    'from_location' => 'Office',
                    'to_location' => 'Site',
                    'purpose' => 'Inspection',
                    'distance_km' => 20,
                    'mileage_rate' => 0.5,
                    'toll_amount' => 3,
                    'parking_amount' => 2,
                ],
                [
                    'category_code' => 'transportation',
                    'item_date' => '2026-03-27',
                    'description' => 'Grab to meeting',
                    'amount' => 18,
                ],
            ],
        ], $overrides);
    }

    public function test_authenticated_user_can_create_and_list_claim_drafts(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Muhammad',
            'last_name' => 'Irsyad',
            'role' => 'employee',
        ]);

        $create = $this->actingAs($user)->postJson('/app-api/claims', $this->claimPayload());

        $create
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('claim.status', 'draft');

        $list = $this->actingAs($user)->getJson('/app-api/claims');

        $list
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'claims')
            ->assertJsonPath('summary.total', 1);
    }

    public function test_authenticated_user_can_view_and_update_own_draft_claim(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Muhammad',
            'last_name' => 'Irsyad',
            'role' => 'employee',
        ]);

        $claimId = $this->actingAs($user)->postJson('/app-api/claims', $this->claimPayload())
            ->json('claim.id');

        $detail = $this->actingAs($user)->getJson("/app-api/claims/{$claimId}");
        $detail
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'items');

        $update = $this->actingAs($user)->putJson("/app-api/claims/{$claimId}", $this->claimPayload([
            'advance_amount' => 25,
            'employee_remarks' => 'Updated draft claim.',
        ]));

        $update
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('claim.advance_amount', 25);
    }

    public function test_authenticated_user_can_submit_draft_claim(): void
    {
        $user = User::factory()->create([
            'role' => 'employee',
        ]);

        $claimId = $this->actingAs($user)->postJson('/app-api/claims', $this->claimPayload())
            ->json('claim.id');

        $submit = $this->actingAs($user)->postJson("/app-api/claims/{$claimId}/submit");

        $submit
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('claim.status', 'submitted');

        $this->assertDatabaseHas('claims', [
            'id' => $claimId,
            'status' => 'submitted',
        ]);
    }

    public function test_non_owner_cannot_view_employee_claim(): void
    {
        $owner = User::factory()->create(['role' => 'employee']);
        $other = User::factory()->create(['role' => 'employee']);

        $claimId = $this->actingAs($owner)->postJson('/app-api/claims', $this->claimPayload())
            ->json('claim.id');

        $this->actingAs($other)
            ->getJson("/app-api/claims/{$claimId}")
            ->assertForbidden();
    }

    public function test_admin_can_view_other_employee_claim(): void
    {
        $owner = User::factory()->create(['role' => 'employee']);
        $admin = User::factory()->create(['role' => 'admin']);

        $claimId = $this->actingAs($owner)->postJson('/app-api/claims', $this->claimPayload())
            ->json('claim.id');

        $this->actingAs($admin)
            ->getJson("/app-api/claims/{$claimId}")
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_employee_can_upload_and_delete_attachment_for_editable_claim(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'employee']);
        $claimId = $this->actingAs($user)->postJson('/app-api/claims', $this->claimPayload())
            ->json('claim.id');

        $upload = $this->actingAs($user)->postJson("/app-api/claims/{$claimId}/attachments", [
            'file' => UploadedFile::fake()->create('receipt.pdf', 120, 'application/pdf'),
        ]);

        $attachmentId = $upload->json('attachment.id');

        $upload
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('attachment.file_name', 'receipt.pdf');

        $this->assertDatabaseHas('claim_attachments', [
            'id' => $attachmentId,
            'claim_id' => $claimId,
        ]);

        $this->actingAs($user)
            ->deleteJson("/app-api/claims/{$claimId}/attachments/{$attachmentId}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('claim_attachments', [
            'id' => $attachmentId,
        ]);
    }

    public function test_admin_can_run_claim_workflow_until_paid(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $admin = User::factory()->create(['role' => 'admin']);

        $claimId = $this->actingAs($employee)->postJson('/app-api/claims', $this->claimPayload())
            ->json('claim.id');

        $this->actingAs($employee)
            ->postJson("/app-api/claims/{$claimId}/submit")
            ->assertOk()
            ->assertJsonPath('claim.status', 'submitted');

        $this->actingAs($admin)
            ->postJson("/app-api/claims/{$claimId}/action", [
                'action' => 'manager_approve',
                'remarks' => 'Reviewed by manager.',
            ])
            ->assertOk()
            ->assertJsonPath('claim.status', 'pending_finance_verification');

        $this->actingAs($admin)
            ->postJson("/app-api/claims/{$claimId}/action", [
                'action' => 'finance_approve',
                'remarks' => 'Verified by finance.',
            ])
            ->assertOk()
            ->assertJsonPath('claim.status', 'approved');

        $this->actingAs($admin)
            ->postJson("/app-api/claims/{$claimId}/action", [
                'action' => 'mark_paid',
                'payment_reference' => 'PAY-20260327-0001',
                'payment_method' => 'Bank Transfer',
                'payment_amount' => 123.45,
                'remarks' => 'Payment released.',
            ])
            ->assertOk()
            ->assertJsonPath('claim.status', 'paid');

        $this->assertDatabaseHas('claim_payments', [
            'claim_id' => $claimId,
            'payment_reference' => 'PAY-20260327-0001',
        ]);
    }

    public function test_admin_can_return_or_reject_submitted_claim(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $admin = User::factory()->create(['role' => 'admin']);

        $claimId = $this->actingAs($employee)->postJson('/app-api/claims', $this->claimPayload())
            ->json('claim.id');

        $this->actingAs($employee)->postJson("/app-api/claims/{$claimId}/submit")->assertOk();

        $this->actingAs($admin)
            ->postJson("/app-api/claims/{$claimId}/action", [
                'action' => 'manager_return',
                'remarks' => 'Please fix receipt details.',
            ])
            ->assertOk()
            ->assertJsonPath('claim.status', 'returned_for_amendment');

        $secondClaimId = $this->actingAs($employee)->postJson('/app-api/claims', $this->claimPayload([
            'employee_remarks' => 'Second claim.',
        ]))->json('claim.id');

        $this->actingAs($employee)->postJson("/app-api/claims/{$secondClaimId}/submit")->assertOk();

        $this->actingAs($admin)
            ->postJson("/app-api/claims/{$secondClaimId}/action", [
                'action' => 'manager_reject',
                'remarks' => 'Out of policy.',
            ])
            ->assertOk()
            ->assertJsonPath('claim.status', 'rejected');
    }
}
