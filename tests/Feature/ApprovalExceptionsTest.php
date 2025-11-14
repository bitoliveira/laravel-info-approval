<?php

use bitoliveira\Approval\Exceptions\DuplicateApprovalException;
use bitoliveira\Approval\Exceptions\InvalidApprovalStatusException;
use bitoliveira\Approval\Exceptions\UnauthorizedApproverException;
use bitoliveira\Approval\Services\ApprovalService;
use bitoliveira\Approval\Tests\Fixtures\Models\Employee;

it('throws InvalidApprovalStatusException when trying to approve an already approved request', function () {
    $employee = Employee::query()->create(['name' => 'Alice', 'salary' => 1000]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 2000,
    ], userId: 1);

    // Approve once
    app(ApprovalService::class)->approve($approval, approverId: 2);

    // Try to approve again - should throw exception
    expect(fn() => app(ApprovalService::class)->approve($approval, approverId: 3))
        ->toThrow(InvalidApprovalStatusException::class, 'Esta aprovação não pode ser modificada pois já foi approved.');
});

it('throws InvalidApprovalStatusException when trying to approve an already rejected request', function () {
    $employee = Employee::query()->create(['name' => 'Bob', 'salary' => 1500]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 2500,
    ], userId: 1);

    // Reject first
    app(ApprovalService::class)->reject($approval, approverId: 5);

    // Try to approve rejected - should throw exception
    expect(fn() => app(ApprovalService::class)->approve($approval, approverId: 6))
        ->toThrow(InvalidApprovalStatusException::class, 'Esta aprovação não pode ser modificada pois já foi rejected.');
});

it('throws InvalidApprovalStatusException when trying to reject an already approved request', function () {
    $employee = Employee::query()->create(['name' => 'Carol', 'salary' => 2000]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 3000,
    ], userId: 1);

    // Approve first
    app(ApprovalService::class)->approve($approval, approverId: 7);

    // Try to reject approved - should throw exception
    expect(fn() => app(ApprovalService::class)->reject($approval, approverId: 8))
        ->toThrow(InvalidApprovalStatusException::class, 'Esta aprovação não pode ser modificada pois já foi approved.');
});

it('throws DuplicateApprovalException when same approver tries to approve twice', function () {
    $employee = Employee::query()->create(['name' => 'Dave', 'salary' => 1800]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 2800,
        'strategy' => 'majority',
        'approvers' => [10, 11, 12],
    ], userId: 1);

    // First approval
    app(ApprovalService::class)->approve($approval, approverId: 10);

    // Same approver tries again - should throw exception
    expect(fn() => app(ApprovalService::class)->approve($approval, approverId: 10))
        ->toThrow(DuplicateApprovalException::class, 'Utilizador já aprovou este pedido.');
});

it('throws UnauthorizedApproverException when user does not have required role', function () {
    $employee = Employee::query()->create(['name' => 'Eve', 'salary' => 2200]);

    $levels = [
        ['roles' => ['Manager', 'Admin']], // User must have Manager or Admin role
    ];

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 3200,
    ], userId: 1, levels: $levels);

    // Try to approve without proper role - should throw exception
    // User ID 99 won't have any roles in test environment
    expect(fn() => app(ApprovalService::class)->approve($approval, approverId: 99))
        ->toThrow(UnauthorizedApproverException::class, 'Utilizador não autorizado a aprovar este nível.');
});

it('allows approval when level has empty roles array', function () {
    $employee = Employee::query()->create(['name' => 'Frank', 'salary' => 2500]);

    $levels = [
        ['roles' => []], // No role restrictions
    ];

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 3500,
    ], userId: 1, levels: $levels);

    // Should not throw - any user can approve
    app(ApprovalService::class)->approve($approval, approverId: 100);

    expect($approval->fresh()->status)->toBe('approved');
});

it('validates exception codes are correct', function () {
    expect((new UnauthorizedApproverException())->getCode())->toBe(403);
    expect((new DuplicateApprovalException())->getCode())->toBe(422);
    expect((new InvalidApprovalStatusException())->getCode())->toBe(422);
});
