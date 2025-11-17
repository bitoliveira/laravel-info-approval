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

it('can update multiple fields at once on approval', function () {
    // Arrange: create an employee with initial data
    $employee = Employee::query()->create([
        'name' => 'Charlie',
        'salary' => 2000,
        'asycuda_code' => 'OLD123',
        'asycuda_status' => 'pending',
    ]);

    // Act: request approval to update multiple fields
    $approval = $employee->requestApproval('update', [
        'name' => 'Charles Updated',
        'salary' => 3500,
        'asycuda_code' => 'NEW456',
        'asycuda_status' => 'approved',
    ], userId: 1);

    // Assert: approval is pending and employee data unchanged
    expect($approval->status)->toBe('pending');
    $employee->refresh();
    expect($employee->name)->toBe('Charlie');
    expect($employee->salary)->toBe(2000.00);
    expect($employee->asycuda_code)->toBe('OLD123');
    expect($employee->asycuda_status)->toBe('pending');

    // Approve via service
    app(ApprovalService::class)->approve($approval, approverId: 2);

    // Assert: approval approved and all fields updated
    $approval->refresh();
    expect($approval->status)->toBe('approved');

    $employee->refresh();
    expect($employee->name)->toBe('Charles Updated');
    expect($employee->salary)->toBe(3500.00);
    expect($employee->asycuda_code)->toBe('NEW456');
    expect($employee->asycuda_status)->toBe('approved');
});
