<?php

namespace bitoliveira\Approval\Contracts;

use bitoliveira\Approval\Models\Approval;

/**
 * Interface for approval service implementations
 */
interface ApprovalServiceInterface
{
    /**
     * Approve an approval request
     *
     * @param Approval $approval
     * @param int $approverId
     * @return void
     * @throws \bitoliveira\Approval\Exceptions\UnauthorizedApproverException
     * @throws \bitoliveira\Approval\Exceptions\DuplicateApprovalException
     * @throws \bitoliveira\Approval\Exceptions\InvalidApprovalStatusException
     */
    public function approve(Approval $approval, int $approverId): void;

    /**
     * Reject an approval request
     *
     * @param Approval $approval
     * @param int $approverId
     * @return void
     * @throws \bitoliveira\Approval\Exceptions\UnauthorizedApproverException
     * @throws \bitoliveira\Approval\Exceptions\InvalidApprovalStatusException
     */
    public function reject(Approval $approval, int $approverId): void;
}
