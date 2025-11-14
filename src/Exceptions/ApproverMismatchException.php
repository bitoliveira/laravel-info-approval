<?php

namespace bitoliveira\Approval\Exceptions;

/**
 * Thrown when the approver_id doesn't match the authenticated user
 */
class ApproverMismatchException extends ApprovalException
{
    public function __construct(string $message = 'O approver_id deve corresponder ao utilizador autenticado.')
    {
        parent::__construct($message, 403);
    }
}
