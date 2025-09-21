<?php

namespace bitoliveira\Approval\Traits;

use bitoliveira\Approval\Models\Approval;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasApprovals
{
    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    /**
     * Cria um pedido de aprovação, com suporte a múltiplos níveis.
     *
     * Exemplo de $levels:
     * [
     *   ['roles' => ['Manager', 'Finance']],
     *   ['roles' => ['Admin']],
     * ]
     * Se omitir ou passar roles vazias num nível, qualquer utilizador poderá aprovar esse nível.
     */
    public function requestApproval(string $action, array $data, int $userId, ?array $levels = null): Approval
    {
        return $this->approvals()->create([
            'action' => $action,
            'data' => $data,
            'levels' => $levels,
            'current_level' => 1,
            'requested_by' => $userId,
        ]);
    }
}
