<?php

use bitoliveira\Approval\Models\Approval;
use bitoliveira\Approval\Services\ApprovalService;
use bitoliveira\Approval\Tests\Fixtures\Models\Employee;

it('can filter approvals by status using scopes', function () {
    $employee = Employee::query()->create(['name' => 'Alice', 'salary' => 1000]);

    // Create pending approval
    $pending = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 2000,
    ], userId: 1);

    // Create approved approval
    $approved = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 2500,
    ], userId: 1);
    app(ApprovalService::class)->approve($approved, approverId: 2);

    // Create rejected approval
    $rejected = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 3000,
    ], userId: 1);
    app(ApprovalService::class)->reject($rejected, approverId: 3);

    // Test scopes
    expect(Approval::pending()->count())->toBe(1);
    expect(Approval::approved()->count())->toBe(1);
    expect(Approval::rejected()->count())->toBe(1);
});

it('can filter approvals by type using scope', function () {
    $employee1 = Employee::query()->create(['name' => 'Bob', 'salary' => 1500]);
    $employee2 = Employee::query()->create(['name' => 'Carol', 'salary' => 2000]);

    $employee1->requestApproval('update_field', ['field' => 'salary', 'new_value' => 2000], userId: 1);
    $employee2->requestApproval('update_field', ['field' => 'salary', 'new_value' => 2500], userId: 1);

    $count = Approval::forType(Employee::class)->count();
    expect($count)->toBeGreaterThanOrEqual(2);
});

it('can filter approvals by requester using scope', function () {
    $employee = Employee::query()->create(['name' => 'Dave', 'salary' => 1800]);

    $employee->requestApproval('update_field', ['field' => 'salary', 'new_value' => 2000], userId: 10);
    $employee->requestApproval('update_field', ['field' => 'salary', 'new_value' => 2200], userId: 10);
    $employee->requestApproval('update_field', ['field' => 'salary', 'new_value' => 2400], userId: 20);

    expect(Approval::requestedBy(10)->count())->toBeGreaterThanOrEqual(2);
    expect(Approval::requestedBy(20)->count())->toBeGreaterThanOrEqual(1);
});

it('can filter approvals by action using scope', function () {
    $employee = Employee::query()->create(['name' => 'Eve', 'salary' => 2000]);

    $employee->requestApproval('update_field', ['field' => 'salary', 'new_value' => 2500], userId: 1);
    $employee->requestApproval('delete', [], userId: 1);

    expect(Approval::action('update_field')->count())->toBeGreaterThanOrEqual(1);
    expect(Approval::action('delete')->count())->toBeGreaterThanOrEqual(1);
});

it('can chain scopes together', function () {
    $employee = Employee::query()->create(['name' => 'Frank', 'salary' => 2200]);

    $employee->requestApproval('update_field', ['field' => 'salary', 'new_value' => 2800], userId: 5);

    $result = Approval::pending()
        ->forType(Employee::class)
        ->requestedBy(5)
        ->action('update_field')
        ->first();

    expect($result)->not->toBeNull();
    expect($result->status)->toBe('pending');
});
