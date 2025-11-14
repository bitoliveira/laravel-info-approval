<?php

use bitoliveira\Approval\Models\Approval;
use bitoliveira\Approval\Tests\Fixtures\Models\Employee;

it('soft deletes an approval', function () {
    $employee = Employee::query()->create(['name' => 'Alice', 'salary' => 1000]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 2000,
    ], userId: 1);

    $approvalId = $approval->id;

    // Delete the approval
    $approval->delete();

    // Should not be found in regular query
    expect(Approval::find($approvalId))->toBeNull();

    // Should be found when including trashed
    $trashedApproval = Approval::withTrashed()->find($approvalId);
    expect($trashedApproval)->not->toBeNull();
    expect($trashedApproval->deleted_at)->not->toBeNull();
});

it('restores a soft deleted approval', function () {
    $employee = Employee::query()->create(['name' => 'Bob', 'salary' => 1500]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 2500,
    ], userId: 1);

    $approvalId = $approval->id;

    // Soft delete
    $approval->delete();
    expect(Approval::find($approvalId))->toBeNull();

    // Restore
    $trashedApproval = Approval::withTrashed()->find($approvalId);
    $trashedApproval->restore();

    // Should be found again
    $restoredApproval = Approval::find($approvalId);
    expect($restoredApproval)->not->toBeNull();
    expect($restoredApproval->deleted_at)->toBeNull();
});

it('force deletes an approval permanently', function () {
    $employee = Employee::query()->create(['name' => 'Carol', 'salary' => 2000]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 3000,
    ], userId: 1);

    $approvalId = $approval->id;

    // Force delete (permanent)
    $approval->forceDelete();

    // Should not be found even with trashed
    expect(Approval::withTrashed()->find($approvalId))->toBeNull();
});

it('queries only trashed approvals', function () {
    $employee = Employee::query()->create(['name' => 'Dave', 'salary' => 1800]);

    // Create and soft delete one approval
    $deleted = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 2000,
    ], userId: 1);
    $deleted->delete();

    // Create another active approval
    $active = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 2200,
    ], userId: 1);

    // Query only trashed
    $trashedApprovals = Approval::onlyTrashed()->get();

    expect($trashedApprovals->pluck('id')->contains($deleted->id))->toBeTrue();
    expect($trashedApprovals->pluck('id')->contains($active->id))->toBeFalse();
});

it('soft deleted approvals do not appear in scopes', function () {
    $employee = Employee::query()->create(['name' => 'Eve', 'salary' => 2200]);

    // Create pending approval
    $pending = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 2500,
    ], userId: 1);

    // Create and delete another pending approval
    $deleted = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 2800,
    ], userId: 1);
    $deleted->delete();

    // Query using scopes
    $pendingApprovals = Approval::pending()->get();

    expect($pendingApprovals->pluck('id')->contains($pending->id))->toBeTrue();
    expect($pendingApprovals->pluck('id')->contains($deleted->id))->toBeFalse();
});

it('preserves approval history when soft deleted', function () {
    $employee = Employee::query()->create(['name' => 'Frank', 'salary' => 2500]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 3500,
    ], userId: 10);

    // Approve it first
    app(\bitoliveira\Approval\Services\ApprovalService::class)->approve($approval, approverId: 20);
    $approval->refresh();

    // Record details before deletion
    $originalStatus = $approval->status;
    $originalLog = $approval->approvals_log;
    $originalApprovedBy = $approval->approved_by;

    // Soft delete
    $approval->delete();

    // Retrieve with trashed
    $trashedApproval = Approval::withTrashed()->find($approval->id);

    // History should be preserved
    expect($trashedApproval->status)->toBe($originalStatus);
    expect($trashedApproval->approvals_log)->toBe($originalLog);
    expect($trashedApproval->approved_by)->toBe($originalApprovedBy);
});

it('can use soft deletes with query scopes', function () {
    $employee = Employee::query()->create(['name' => 'Grace', 'salary' => 2800]);

    // Create multiple approvals
    $approval1 = $employee->requestApproval('update_field', ['field' => 'salary', 'new_value' => 3000], userId: 1);
    $approval2 = $employee->requestApproval('update_field', ['field' => 'salary', 'new_value' => 3200], userId: 1);
    $approval3 = $employee->requestApproval('delete', [], userId: 1);

    // Soft delete one
    $approval2->delete();

    // Query with scopes - should not include deleted
    $updateActions = Approval::action('update_field')->get();
    expect($updateActions->pluck('id')->contains($approval1->id))->toBeTrue();
    expect($updateActions->pluck('id')->contains($approval2->id))->toBeFalse();

    // Query with scopes including trashed
    $updateActionsWithTrashed = Approval::withTrashed()->action('update_field')->get();
    expect($updateActionsWithTrashed->pluck('id')->contains($approval1->id))->toBeTrue();
    expect($updateActionsWithTrashed->pluck('id')->contains($approval2->id))->toBeTrue();
});
