<?php

namespace bitoliveira\Approval\Tests\Fixtures\Models;

use bitoliveira\Approval\Models\Approval;
use bitoliveira\Approval\Traits\HasApprovals;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Employee extends Model
{
    use HasApprovals;

    protected $guarded = [];

    protected $casts = [
        'salary' => 'float',
    ];

    /**
     * Example of a specific custom action handler.
     * Method name format: executeApprovalAction{ActionNameInPascalCase}
     * For action 'update-asycuda', method name is 'executeApprovalActionUpdateAsycuda'
     */
    public function executeApprovalActionUpdateAsycuda(Approval $approval): void
    {
        // Custom logic for update-asycuda action
        $asycudaCode = $approval->data['asycuda_code'] ?? null;
        $asycudaStatus = $approval->data['asycuda_status'] ?? null;

        Log::info('Executing update-asycuda action', [
            'employee_id' => $this->id,
            'asycuda_code' => $asycudaCode,
            'asycuda_status' => $asycudaStatus,
        ]);

        // Update the employee with ASYCUDA data
        $this->update([
            'asycuda_code' => $asycudaCode,
            'asycuda_status' => $asycudaStatus,
        ]);
    }

}
