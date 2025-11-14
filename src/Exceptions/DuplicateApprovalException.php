<?php

namespace bitoliveira\Approval\Exceptions;

/**
 * Thrown when a user attempts to approve the same request multiple times
 */
class DuplicateApprovalException extends ApprovalException
{
    public function __construct(string $message = 'Utilizador já aprovou este pedido.')
    {
        parent::__construct($message, 422);
    }
}
