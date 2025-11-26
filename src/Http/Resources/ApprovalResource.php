<?php

namespace bitoliveira\Approval\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'approvable_type' => $this->approvable_type,
            'approvable_id' => $this->approvable_id,
            'action' => $this->action,
            'data' => $this->data,
            'old_data' => $this->old_data,
            'levels' => $this->levels,
            'current_level' => $this->current_level,
            'approvals_log' => $this->approvals_log,
            'status' => $this->status,
            'requested_by' => $this->requested_by,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
