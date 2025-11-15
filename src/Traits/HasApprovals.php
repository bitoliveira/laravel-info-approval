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
        // Capture old data from the current model state
        $oldData = $this->getAttributes();

        // Filter data to only include fields that are different
        $changedData = [];
        foreach ($data as $key => $value) {
            // If the key exists in old data and the value is different, or if it's a new field
            if (!array_key_exists($key, $oldData) || $oldData[$key] !== $value) {
                $changedData[$key] = $value;
            }
        }

        $approval = $this->approvals()->create([
            'action' => $action,
            'old_data' => $oldData,
            'data' => $changedData,
            'levels' => $levels,
            'current_level' => 1,
            'status' => 'pending',
            'requested_by' => $userId,
        ]);

        event(new ApprovalRequested($approval));

        return $approval;
    }
}
