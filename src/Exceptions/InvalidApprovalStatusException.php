<?php

namespace bitoliveira\Approval\Exceptions;

/**
 * Thrown when attempting to modify an approval that is not in pending status
 */
class InvalidApprovalStatusException extends ApprovalException
{
    public function __construct(string $message = 'Esta aprovação não pode ser modificada.')
    {
        parent::__construct($message, 422);
    }
}
