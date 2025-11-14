<?php

use bitoliveira\Approval\Tests\Fixtures\Models\Employee;

it('returns validation error when approver_id is missing', function () {
    $employee = Employee::query()->create(['name' => 'Alice', 'salary' => 1000]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 2000,
    ], userId: 1);

    $response = $this->postJson('/approvals/' . $approval->id . '/approve', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['approver_id']);
});

it('returns validation error when approver_id is not an integer', function () {
    $employee = Employee::query()->create(['name' => 'Bob', 'salary' => 1500]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 2500,
    ], userId: 1);

    $response = $this->postJson('/approvals/' . $approval->id . '/approve', [
        'approver_id' => 'not-an-integer',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['approver_id']);
});

it('lists approvals with pagination via API', function () {
    $employee = Employee::query()->create(['name' => 'Carol', 'salary' => 2000]);

    // Create multiple approvals
    for ($i = 0; $i < 5; $i++) {
        $employee->requestApproval('update_field', [
            'field' => 'salary',
            'new_value' => 2000 + ($i * 100),
        ], userId: 1);
    }

    $response = $this->getJson('/approvals');

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            '*' => [
                'id',
                'approvable_type',
                'approvable_id',
                'action',
                'data',
                'status',
                'created_at',
            ],
        ],
        'meta' => [
            'total',
            'per_page',
            'current_page',
            'last_page',
        ],
    ]);

    expect($response->json('data'))->toBeArray();
    expect(count($response->json('data')))->toBeGreaterThanOrEqual(5);
});

it('filters approvals by status via API', function () {
    $employee = Employee::query()->create(['name' => 'Dave', 'salary' => 1800]);

    // Create pending approval
    $pending = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 2000,
    ], userId: 1);

    // Create and approve another
    $approved = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 2200,
    ], userId: 1);
    app(\bitoliveira\Approval\Services\ApprovalService::class)->approve($approved, approverId: 2);

    // Filter by pending
    $response = $this->getJson('/approvals?status=pending');
    $response->assertOk();

    $pendingApprovals = collect($response->json('data'))->where('status', 'pending');
    expect($pendingApprovals->count())->toBeGreaterThan(0);

    // Filter by approved
    $response = $this->getJson('/approvals?status=approved');
    $response->assertOk();

    $approvedApprovals = collect($response->json('data'))->where('status', 'approved');
    expect($approvedApprovals->count())->toBeGreaterThan(0);
});

it('filters approvals by approvable_type via API', function () {
    $employee = Employee::query()->create(['name' => 'Eve', 'salary' => 2200]);

    $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 3000,
    ], userId: 1);

    $response = $this->getJson('/approvals?approvable_type=' . urlencode(Employee::class));

    $response->assertOk();
    expect($response->json('data'))->toBeArray();

    $filtered = collect($response->json('data'))->where('approvable_type', Employee::class);
    expect($filtered->count())->toBeGreaterThan(0);
});

it('shows single approval details via API', function () {
    $employee = Employee::query()->create(['name' => 'Frank', 'salary' => 2500]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 3500,
        'strategy' => 'single',
    ], userId: 1);

    $response = $this->getJson('/approvals/' . $approval->id);

    $response->assertOk();
    $response->assertJsonStructure([
        'id',
        'approvable_type',
        'approvable_id',
        'action',
        'data',
        'levels',
        'current_level',
        'approvals_log',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'created_at',
        'updated_at',
    ]);

    expect($response->json('id'))->toBe($approval->id);
    expect($response->json('status'))->toBe('pending');
});

it('returns proper error codes for exceptions via API', function () {
    $employee = Employee::query()->create(['name' => 'Grace', 'salary' => 2800]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 3800,
        'strategy' => 'majority',
        'approvers' => [10, 11, 12],
    ], userId: 1);

    // First approval
    $this->postJson('/approvals/' . $approval->id . '/approve', ['approver_id' => 10])
        ->assertOk();

    // Duplicate approval - should return 422
    $response = $this->postJson('/approvals/' . $approval->id . '/approve', ['approver_id' => 10]);

    $response->assertStatus(422);
    $response->assertJson([
        'message' => 'Utilizador já aprovou este pedido.',
    ]);
});
