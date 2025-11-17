<?php

use bitoliveira\Approval\Tests\Fixtures\Models\Employee;

it('executes custom approval action using specific method', function () {
    $employee = Employee::query()->create(['name' => 'John', 'salary' => 1000, 'asycuda_code' => '321YSA']);

    // Create an approval with custom action
    $approval = $employee->requestApproval('update-asycuda', [
        'asycuda_code' => 'ASY123',
        'asycuda_status' => 'exported',
    ], userId: 1);

    expect($approval->status)->toBe('pending')
        ->and($approval->action)->toBe('update-asycuda');

    // Approve the request
    app(\bitoliveira\Approval\Services\ApprovalService::class)->approve($approval, approverId: 1);

    $approval->refresh();
    expect($approval->status)->toBe('approved');

    $employee->refresh();
    expect($employee->asycuda_code)->toBe('ASY123')
        ->and($employee->asycuda_status)->toBe('exported');
});

it('logs warning for unknown approval action without handler', function () {
    $employee = Employee::query()->create(['name' => 'Jane', 'salary' => 1500]);

    // Create an approval with unknown custom action (no handler defined)
    $approval = $employee->requestApproval('unknown-custom-action', [
        'data' => 'some custom data',
    ], userId: 1);

    expect($approval->status)->toBe('pending');
    expect($approval->action)->toBe('unknown-custom-action');

    // Approve the request - should approve but log a warning
    app(\bitoliveira\Approval\Services\ApprovalService::class)->approve($approval, approverId: 1);

    $approval->refresh();
    expect($approval->status)->toBe('approved');
    // The approval is marked as approved, but the action won't be executed
});
