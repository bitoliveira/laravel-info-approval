<?php

namespace bitoliveira\Approval\Exceptions;

/**
 * Thrown when a user is not authorized to approve/reject at the current level
 */
class UnauthorizedApproverException extends ApprovalException
{
    public function __construct(string $message = 'Utilizador não autorizado a aprovar este nível.')
    {
        parent::__construct($message, 403);
    }
}
