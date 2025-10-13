<?php

namespace bitoliveira\Approval\Events;

use bitoliveira\Approval\Models\Approval;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApprovalLevelAdvanced
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Approval $approval,
        public int $previousLevel,
        public int $newLevel,
        public int $approverId
    ) {}
}
