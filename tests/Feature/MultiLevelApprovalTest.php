<?php

use bitoliveira\Approval\Models\Approval;
use bitoliveira\Approval\Services\ApprovalService;
use bitoliveira\Approval\Tests\Fixtures\Models\Employee;

it('handles multi-level approvals (no role restrictions)', function () {
    // Arrange
    $employee = Employee::query()->create(['name' => 'Carol', 'salary' => 1000]);

    // Two levels, both without role restrictions
    $levels = [
        ['roles' => []],
        ['roles' => []],
    ];

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 1800,
    ], userId: 10, levels: $levels);

    expect($approval->status)->toBe('pending');
    expect($approval->current_level)->toBe(1);

    // First approval -> should advance to level 2 and stay pending
    app(ApprovalService::class)->approve($approval, approverId: 20);
    $approval->refresh();

    expect($approval->status)->toBe('pending');
    expect($approval->current_level)->toBe(2);
    expect($employee->fresh()->salary)->toBe(1000.00);

    // Second (final) approval -> should approve and apply the change
    app(ApprovalService::class)->approve($approval, approverId: 21);
    $approval->refresh();

    expect($approval->status)->toBe('approved');
    expect($employee->fresh()->salary)->toBe(1800.00);

    // Log should have two entries
    expect(is_array($approval->approvals_log))->toBeTrue();
    expect(count($approval->approvals_log))->toBe(2);
});
