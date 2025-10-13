<?php

use bitoliveira\Approval\Events\ApprovalApproved;
use bitoliveira\Approval\Events\ApprovalLevelAdvanced;
use bitoliveira\Approval\Events\ApprovalRejected;
use bitoliveira\Approval\Events\ApprovalRequested;
use bitoliveira\Approval\Services\ApprovalService;
use bitoliveira\Approval\Tests\Fixtures\Models\Employee;
use Illuminate\Support\Facades\Event;

it('dispatches ApprovalRequested event when approval is created', function () {
    Event::fake();

    $employee = Employee::query()->create(['name' => 'Alice', 'salary' => 1000]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 2000,
    ], userId: 1);

    Event::assertDispatched(ApprovalRequested::class, function ($event) use ($approval) {
        return $event->approval->id === $approval->id;
    });
});

it('dispatches ApprovalApproved event when approval is finally approved', function () {
    Event::fake();

    $employee = Employee::query()->create(['name' => 'Bob', 'salary' => 1500]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 2500,
    ], userId: 1);

    app(ApprovalService::class)->approve($approval, approverId: 2);

    Event::assertDispatched(ApprovalApproved::class, function ($event) use ($approval) {
        return $event->approval->id === $approval->id && $event->approverId === 2;
    });
});

it('dispatches ApprovalRejected event when approval is rejected', function () {
    Event::fake();

    $employee = Employee::query()->create(['name' => 'Carol', 'salary' => 2000]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 3000,
    ], userId: 1);

    app(ApprovalService::class)->reject($approval, approverId: 3);

    Event::assertDispatched(ApprovalRejected::class, function ($event) use ($approval) {
        return $event->approval->id === $approval->id && $event->approverId === 3;
    });
});

it('dispatches ApprovalLevelAdvanced event when advancing to next level', function () {
    Event::fake();

    $employee = Employee::query()->create(['name' => 'Dave', 'salary' => 1800]);

    $levels = [
        ['roles' => []],
        ['roles' => []],
    ];

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 2800,
    ], userId: 1, levels: $levels);

    // First approval should advance level
    app(ApprovalService::class)->approve($approval, approverId: 4);

    Event::assertDispatched(ApprovalLevelAdvanced::class, function ($event) use ($approval) {
        return $event->approval->id === $approval->id
            && $event->previousLevel === 1
            && $event->newLevel === 2
            && $event->approverId === 4;
    });

    // Should NOT dispatch ApprovalApproved yet
    Event::assertNotDispatched(ApprovalApproved::class);
});

it('dispatches both ApprovalLevelAdvanced and ApprovalApproved in correct sequence', function () {
    Event::fake();

    $employee = Employee::query()->create(['name' => 'Eve', 'salary' => 2200]);

    $levels = [
        ['roles' => []],
        ['roles' => []],
    ];

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 3200,
    ], userId: 1, levels: $levels);

    // First approval
    app(ApprovalService::class)->approve($approval, approverId: 5);
    Event::assertDispatched(ApprovalLevelAdvanced::class);

    // Second (final) approval
    app(ApprovalService::class)->approve($approval, approverId: 6);
    Event::assertDispatched(ApprovalApproved::class);
});
