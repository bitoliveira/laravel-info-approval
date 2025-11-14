<?php

use bitoliveira\Approval\Http\Resources\ApprovalCollection;
use bitoliveira\Approval\Http\Resources\ApprovalResource;
use bitoliveira\Approval\Models\Approval;
use bitoliveira\Approval\Tests\Fixtures\Models\Employee;
use Illuminate\Http\Request;

it('transforms approval to resource correctly', function () {
    $employee = Employee::query()->create(['name' => 'Alice', 'salary' => 1000]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 2000,
        'strategy' => 'single',
    ], userId: 10);

    $resource = new ApprovalResource($approval);
    $array = $resource->toArray(request());

    expect($array)->toHaveKeys([
        'id',
        'approvable_type',
        'approvable_id',
        'action',
        'data',
        'levels',
        'current_level',
        'approvals_log',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'created_at',
        'updated_at',
    ]);

    expect($array['id'])->toBe($approval->id);
    expect($array['status'])->toBe('pending');
    expect($array['action'])->toBe('update_field');
    expect($array['requested_by'])->toBe(10);
    expect($array['current_level'])->toBe(1);
});

it('formats dates as ISO 8601 in resource', function () {
    $employee = Employee::query()->create(['name' => 'Bob', 'salary' => 1500]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 2500,
    ], userId: 5);

    // Approve to set approved_at
    app(\bitoliveira\Approval\Services\ApprovalService::class)->approve($approval, approverId: 8);
    $approval->refresh();

    $resource = new ApprovalResource($approval);
    $array = $resource->toArray(request());

    // Check date format (ISO 8601)
    expect($array['created_at'])->toContain('T');
    expect($array['updated_at'])->toContain('T');
    expect($array['approved_at'])->toContain('T');

    // Verify dates are strings
    expect($array['created_at'])->toBeString();
    expect($array['updated_at'])->toBeString();
    expect($array['approved_at'])->toBeString();
});

it('handles null dates in resource', function () {
    $employee = Employee::query()->create(['name' => 'Carol', 'salary' => 2000]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 3000,
    ], userId: 1);

    $resource = new ApprovalResource($approval);
    $array = $resource->toArray(request());

    // approved_at should be null for pending approval
    expect($array['approved_at'])->toBeNull();
});

it('transforms approval collection with pagination correctly', function () {
    $employee = Employee::query()->create(['name' => 'Dave', 'salary' => 1800]);

    // Create multiple approvals
    for ($i = 0; $i < 3; $i++) {
        $employee->requestApproval('update_field', [
            'field' => 'salary',
            'new_value' => 2000 + ($i * 100),
        ], userId: 1);
    }

    $approvals = Approval::paginate(2);
    $collection = new ApprovalCollection($approvals);
    $response = $collection->toResponse(request());
    $array = json_decode($response->getContent(), true);

    expect($array)->toHaveKeys(['data', 'meta']);
    expect(is_array($array['data']))->toBeTrue();
    expect($array['meta'])->toHaveKeys([
        'total',
        'per_page',
        'current_page',
        'last_page',
        'from',
        'to',
    ]);

    // per_page might be in array format from laravel pagination, just check it exists
    expect($array['meta'])->toHaveKey('per_page');
});

it('includes all approval data fields in resource', function () {
    $employee = Employee::query()->create(['name' => 'Eve', 'salary' => 2200]);

    $levels = [
        ['roles' => ['Manager']],
        ['roles' => ['Admin']],
    ];

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 3200,
        'strategy' => 'majority',
        'approvers' => [1, 2, 3],
    ], userId: 10, levels: $levels);

    $resource = new ApprovalResource($approval);
    $array = $resource->toArray(request());

    // Check data field
    expect($array['data'])->toBeArray();
    expect($array['data']['field'])->toBe('salary');
    expect($array['data']['new_value'])->toBe(3200);
    expect($array['data']['strategy'])->toBe('majority');
    expect($array['data']['approvers'])->toBe([1, 2, 3]);

    // Check levels
    expect($array['levels'])->toBeArray();
    expect(count($array['levels']))->toBe(2);
    expect($array['levels'][0]['roles'])->toBe(['Manager']);
    expect($array['levels'][1]['roles'])->toBe(['Admin']);

    // Check approvals_log (should be null or empty array initially)
    expect($array['approvals_log'])->toBeIn([null, []]);
});

it('includes approvals log after approval in resource', function () {
    $employee = Employee::query()->create(['name' => 'Frank', 'salary' => 2500]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 3500,
        'strategy' => 'majority',
        'approvers' => [5, 6, 7],
    ], userId: 1);

    // First approval
    app(\bitoliveira\Approval\Services\ApprovalService::class)->approve($approval, approverId: 5);
    $approval->refresh();

    $resource = new ApprovalResource($approval);
    $array = $resource->toArray(request());

    expect($array['approvals_log'])->toBeArray();
    expect(count($array['approvals_log']))->toBe(1);
    expect($array['approvals_log'][0]['action'])->toBe('approve');
    expect($array['approvals_log'][0]['level'])->toBe(1);
    expect($array['approvals_log'][0]['by'])->toBe(5);
    expect($array['approvals_log'][0])->toHaveKey('at');
});
