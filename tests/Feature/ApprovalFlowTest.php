<?php

use bitoliveira\Approval\Models\Approval;
use bitoliveira\Approval\Services\ApprovalService;
use bitoliveira\Approval\Tests\Fixtures\Models\Employee;

it('creates a pending approval request and applies update on approval', function () {
    // Arrange: create an employee
    $employee = Employee::query()->create(['name' => 'Alice', 'salary' => 1000]);

    // Act: request approval to update salary
    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 2000,
    ], userId: 1);

    // Assert: approval is pending and employee salary unchanged
    expect($approval->status)->toBe('pending');
    expect($employee->fresh()->salary)->toBe(1000.00);

    // Approve via service
    app(ApprovalService::class)->approve($approval, approverId: 2);

    // Assert: approval approved and salary updated
    $approval->refresh();
    expect($approval->status)->toBe('approved');
    expect($employee->fresh()->salary)->toBe(2000.00);
});

it('can reject an approval request without side effects', function () {
    $employee = Employee::query()->create(['name' => 'Bob', 'salary' => 1500]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 3000,
    ], userId: 5);

    app(ApprovalService::class)->reject($approval, approverId: 9);

    $approval->refresh();

    expect($approval->status)->toBe('rejected');
    expect($approval->approved_by)->toBe(9);
    // salary unchanged
    expect($employee->fresh()->salary)->toBe(1500.00);
});
