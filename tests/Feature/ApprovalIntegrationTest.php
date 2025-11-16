<?php

use bitoliveira\Approval\Events\ApprovalApproved;
use bitoliveira\Approval\Events\ApprovalLevelAdvanced;
use bitoliveira\Approval\Events\ApprovalRequested;
use bitoliveira\Approval\Models\Approval;
use bitoliveira\Approval\Services\ApprovalService;
use bitoliveira\Approval\Tests\Fixtures\Models\Employee;
use Illuminate\Support\Facades\Event;

it('completes a full approval workflow with events', function () {
    Event::fake();

    $employee = Employee::query()->create(['name' => 'Alice', 'salary' => 1000]);

    // Step 1: Request approval
    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 2000,
    ], userId: 10);

    Event::assertDispatched(ApprovalRequested::class);
    expect($approval->status)->toBe('pending');
    expect($employee->fresh()->salary)->toBe(1000.00);

    // Step 2: Approve
    app(ApprovalService::class)->approve($approval, approverId: 20);

    Event::assertDispatched(ApprovalApproved::class);
    $approval->refresh();
    $employee->refresh();

    expect($approval->status)->toBe('approved');
    expect($approval->approved_by)->toBe(20);
    expect($approval->approved_at)->not->toBeNull();
    expect((float)$employee->salary)->toBe(2000.00);
});

it('handles multi-level approval workflow with role restrictions', function () {
    Event::fake();

    $employee = Employee::query()->create(['name' => 'Bob', 'salary' => 1500]);

    $levels = [
        ['roles' => []], // Level 1: Any user
        ['roles' => []], // Level 2: Any user
    ];

    // Step 1: Create multi-level approval
    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 3000,
    ], userId: 1, levels: $levels);

    expect($approval->current_level)->toBe(1);
    expect($approval->status)->toBe('pending');

    // Step 2: First level approval
    app(ApprovalService::class)->approve($approval, approverId: 5);
    $approval->refresh();

    Event::assertDispatched(ApprovalLevelAdvanced::class);
    expect($approval->current_level)->toBe(2);
    expect($approval->status)->toBe('pending');
    expect((float)$employee->fresh()->salary)->toBe(1500.00); // Not applied yet

    // Step 3: Second level (final) approval
    app(ApprovalService::class)->approve($approval, approverId: 6);
    $approval->refresh();

    Event::assertDispatched(ApprovalApproved::class);
    expect($approval->status)->toBe('approved');
    expect((float)$employee->fresh()->salary)->toBe(3000.00); // Now applied
});

it('handles majority strategy workflow correctly', function () {
    $employee = Employee::query()->create(['name' => 'Carol', 'salary' => 2000]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 3500,
        'strategy' => 'majority',
        'approvers' => [10, 11, 12, 13], // 4 approvers, needs 2 for majority
    ], userId: 1);

    // First approval - still pending
    app(ApprovalService::class)->approve($approval, approverId: 10);
    $approval->refresh();
    expect($approval->status)->toBe('pending');
    expect(count($approval->approvals_log))->toBe(1);

    // Second approval - majority reached, should approve
    app(ApprovalService::class)->approve($approval, approverId: 11);
    $approval->refresh();

    expect($approval->status)->toBe('approved');
    expect(count($approval->approvals_log))->toBe(2);
    expect((float)$employee->fresh()->salary)->toBe(3500.00);
});

it('handles unanimous strategy workflow correctly', function () {
    $employee = Employee::query()->create(['name' => 'Dave', 'salary' => 2500]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 4000,
        'strategy' => 'unanimous',
        'approvers' => [20, 21, 22], // All 3 must approve
    ], userId: 1);

    // First approval - still pending
    app(ApprovalService::class)->approve($approval, approverId: 20);
    $approval->refresh();
    expect($approval->status)->toBe('pending');

    // Second approval - still pending
    app(ApprovalService::class)->approve($approval, approverId: 21);
    $approval->refresh();
    expect($approval->status)->toBe('pending');

    // Third approval - unanimous reached, should approve
    app(ApprovalService::class)->approve($approval, approverId: 22);
    $approval->refresh();

    expect($approval->status)->toBe('approved');
    expect(count($approval->approvals_log))->toBe(3);
    expect((float)$employee->fresh()->salary)->toBe(4000.00);
});

it('handles rejection workflow and prevents further actions', function () {
    Event::fake();

    $employee = Employee::query()->create(['name' => 'Eve', 'salary' => 3000]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 4500,
    ], userId: 1);

    // Reject the approval
    app(ApprovalService::class)->reject($approval, approverId: 50);
    $approval->refresh();

    Event::assertDispatched(\bitoliveira\Approval\Events\ApprovalRejected::class);
    expect($approval->status)->toBe('rejected');
    expect($approval->approved_by)->toBe(50);
    expect($approval->approved_at)->not->toBeNull();
    expect((float)$employee->fresh()->salary)->toBe(3000.00); // No change

    // Try to approve after rejection - should throw exception
    expect(fn() => app(ApprovalService::class)->approve($approval, approverId: 51))
        ->toThrow(\bitoliveira\Approval\Exceptions\InvalidApprovalStatusException::class);
});

it('integrates with API for complete workflow', function () {
    $employee = Employee::query()->create(['name' => 'Frank', 'salary' => 3500]);

    // Step 1: Create approval via code
    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 5000,
        'strategy' => 'majority',
        'approvers' => [30, 31, 32],
    ], userId: 1);

    // Step 2: List via API
    $listResponse = $this->getJson('/api/approval?status=pending');
    $listResponse->assertOk();
    expect($listResponse->json('data'))->toBeArray();

    // Step 3: Get details via API
    $detailsResponse = $this->getJson('/api/approval/' . $approval->id);
    $detailsResponse->assertOk();
    $detailsResponse->assertJsonPath('status', 'pending');

    // Step 4: Approve via API (first approval)
    $approve1Response = $this->postJson('/api/approval/' . $approval->id . '/approve', [
        'approver_id' => 30,
    ]);
    $approve1Response->assertOk();
    $approve1Response->assertJsonPath('approval.status', 'pending');

    // Step 5: Approve via API (second approval - majority reached)
    $approve2Response = $this->postJson('/api/approval/' . $approval->id . '/approve', [
        'approver_id' => 31,
    ]);
    $approve2Response->assertOk();
    $approve2Response->assertJsonPath('approval.status', 'approved');

    // Verify employee was updated
    $employee->refresh();
    expect((float)$employee->salary)->toBe(5000.00);
});

it('handles delete action approval workflow', function () {
    $employee = Employee::query()->create(['name' => 'Grace', 'salary' => 4000]);
    $employeeId = $employee->id;

    // Request delete approval
    $approval = $employee->requestApproval('delete', [], userId: 1);

    expect($approval->action)->toBe('delete');
    expect($approval->status)->toBe('pending');

    // Employee should still exist
    expect(Employee::find($employeeId))->not->toBeNull();

    // Approve deletion
    app(ApprovalService::class)->approve($approval, approverId: 60);
    $approval->refresh();

    expect($approval->status)->toBe('approved');

    // Employee should now be deleted
    expect(Employee::find($employeeId))->toBeNull();
});

it('chains scopes with other query methods', function () {
    $employee1 = Employee::query()->create(['name' => 'Helen', 'salary' => 4500]);
    $employee2 = Employee::query()->create(['name' => 'Ian', 'salary' => 5000]);

    // Create various approvals
    $approval1 = $employee1->requestApproval('update_field', ['field' => 'salary', 'new_value' => 5500], userId: 100);
    $approval2 = $employee1->requestApproval('delete', [], userId: 100);
    $approval3 = $employee2->requestApproval('update_field', ['field' => 'salary', 'new_value' => 6000], userId: 200);

    // Approve one
    app(ApprovalService::class)->approve($approval1, approverId: 101);

    // Complex query with multiple scopes
    $results = Approval::pending()
        ->requestedBy(100)
        ->forType(Employee::class)
        ->action('delete')
        ->recent()
        ->get();

    expect($results->count())->toBeGreaterThanOrEqual(1);
    expect($results->first()->id)->toBe($approval2->id);
});
