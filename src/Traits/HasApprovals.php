<?php

namespace bitoliveira\Approval\Traits;

use bitoliveira\Approval\Events\ApprovalRequested;
use bitoliveira\Approval\Models\Approval;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasApprovals
{
    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function requestApproval(string $action, array $data, int $userId, ?array $levels = null): Approval
    {
        $approval = $this->approvals()->create([
            'action' => $action,
            'data' => $data,
            'levels' => $levels,
            'current_level' => 1,
            'status' => 'pending',
            'requested_by' => $userId,
        ]);

        event(new ApprovalRequested($approval));

        return $approval;
    }
}
