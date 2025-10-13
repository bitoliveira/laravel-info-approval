<?php

use bitoliveira\Approval\Models\Approval;
use bitoliveira\Approval\Services\ApprovalService;
use bitoliveira\Approval\Tests\Fixtures\Models\Employee;

it('approves with single strategy (default)', function () {
    $employee = Employee::query()->create(['name' => 'Alice', 'salary' => 1000]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 2000,
        'strategy' => 'single',
    ], userId: 1);

    app(ApprovalService::class)->approve($approval, approverId: 2);

    $approval->refresh();
    expect($approval->status)->toBe('approved');
    expect($employee->fresh()->salary)->toBe(2000.00);
});

it('approves with majority strategy', function () {
    $employee = Employee::query()->create(['name' => 'Bob', 'salary' => 1500]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 2500,
        'strategy' => 'majority',
        'approvers' => [1, 2, 3], // 3 approvers, needs 2
    ], userId: 10);

    expect($approval->status)->toBe('pending');

    // First approval - still pending
    app(ApprovalService::class)->approve($approval, approverId: 1);
    $approval->refresh();
    expect($approval->status)->toBe('pending');
    expect($employee->fresh()->salary)->toBe(1500.00);

    // Second approval - majority reached, should approve
    app(ApprovalService::class)->approve($approval, approverId: 2);
    $approval->refresh();
    expect($approval->status)->toBe('approved');
    expect($employee->fresh()->salary)->toBe(2500.00);
});

it('approves with unanimous strategy', function () {
    $employee = Employee::query()->create(['name' => 'Carol', 'salary' => 2000]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 3000,
        'strategy' => 'unanimous',
        'approvers' => [1, 2],
    ], userId: 10);

    expect($approval->status)->toBe('pending');

    // First approval - still pending
    app(ApprovalService::class)->approve($approval, approverId: 1);
    $approval->refresh();
    expect($approval->status)->toBe('pending');
    expect($employee->fresh()->salary)->toBe(2000.00);

    // Second (and final) approval - unanimous reached
    app(ApprovalService::class)->approve($approval, approverId: 2);
    $approval->refresh();
    expect($approval->status)->toBe('approved');
    expect($employee->fresh()->salary)->toBe(3000.00);
});

it('rejects with majority strategy when any approver rejects', function () {
    $employee = Employee::query()->create(['name' => 'Dave', 'salary' => 2500]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 3500,
        'strategy' => 'majority',
        'approvers' => [1, 2, 3],
    ], userId: 10);

    // One rejects - should immediately reject
    app(ApprovalService::class)->reject($approval, approverId: 1);
    $approval->refresh();

    expect($approval->status)->toBe('rejected');
    expect($employee->fresh()->salary)->toBe(2500.00);
});

it('prevents duplicate approvals from same approver', function () {
    $employee = Employee::query()->create(['name' => 'Eve', 'salary' => 1800]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 2800,
        'strategy' => 'majority',
        'approvers' => [1, 2, 3],
    ], userId: 10);

    // First approval
    app(ApprovalService::class)->approve($approval, approverId: 1);
    $approval->refresh();

    // Same approver tries again - should throw exception
    expect(fn() => app(ApprovalService::class)->approve($approval, approverId: 1))
        ->toThrow(\RuntimeException::class, 'Utilizador já aprovou este pedido.');
});
