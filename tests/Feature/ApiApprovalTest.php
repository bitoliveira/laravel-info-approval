<?php

use bitoliveira\Approval\Models\Approval;
use bitoliveira\Approval\Tests\Fixtures\Models\Employee;
use Illuminate\Support\Facades\Route;

it('approves an approval request via API and executes the action', function () {
    // Arrange: create an employee and a pending approval to update a field
    $employee = Employee::query()->create(['name' => 'John', 'salary' => 500]);

    /** @var Approval $approval */
    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 750,
    ], userId: 1, levels: null);

    // Act: call the approve endpoint
    $response = $this->postJson('/approvals/' . $approval->id . '/approve', [
        'approver_id' => 1,
    ]);

    // Assert: response and DB state
    $response->assertOk();
    $response->assertJsonPath('approval.status', 'approved');

    // Refresh models to check updates
    $approval->refresh();
    $employee->refresh();

    expect($approval->status)->toBe('approved');
    expect((float)$employee->salary)->toBe(750.0);
});

it('rejects an approval request via API', function () {
    $employee = Employee::query()->create(['name' => 'Mary', 'salary' => 900]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 1000,
    ], userId: 2, levels: null);

    $response = $this->postJson('/approvals/' . $approval->id . '/reject', [
        'approver_id' => 3,
    ]);

    $response->assertOk();
    $response->assertJsonPath('approval.status', 'rejected');

    $approval->refresh();
    $employee->refresh();

    expect($approval->status)->toBe('rejected');
    // ensure no change applied on reject
    expect((float)$employee->salary)->toBe(900.0);
});
