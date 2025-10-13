<?php

namespace bitoliveira\Approval\Services;

use bitoliveira\Approval\Events\ApprovalApproved;
use bitoliveira\Approval\Events\ApprovalLevelAdvanced;
use bitoliveira\Approval\Events\ApprovalRejected;
use bitoliveira\Approval\Models\Approval;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class  ApprovalService
{
    public function approve(Approval $approval, int $approverId): void
    {
        if ($approval->status !== 'pending') {
            return; // nada a fazer
        }

        // Verificar se o utilizador pode aprovar o nível atual
        $this->assertUserCanActOnCurrentLevel($approval, $approverId);

        // Verificar se não é aprovação duplicada
        $this->assertApproverHasNotAlreadyApproved($approval, $approverId);

        // Obter estratégia de aprovação
        $strategy = $approval->data['strategy'] ?? config('approval.default_strategy', 'single');

        // Registar no log
        $log = $approval->approvals_log ?? [];
        $log[] = [
            'action' => 'approve',
            'level' => $approval->current_level ?? 1,
            'by' => $approverId,
            'at' => Carbon::now()->toISOString(),
        ];

        $levels = $approval->levels ?? [];
        $totalLevels = is_array($levels) ? count($levels) : 0;
        $current = $approval->current_level ?? 1;

        // Se houver mais níveis, avançar sem executar a ação
        if ($totalLevels > 0 && $current < $totalLevels) {
            $previousLevel = $current;
            $approval->update([
                'approvals_log' => $log,
                'current_level' => $current + 1,
            ]);
            event(new ApprovalLevelAdvanced($approval, $previousLevel, $current + 1, $approverId));
            return;
        }

        // Verificar se a estratégia foi cumprida
        if (!$this->isApprovalStrategyMet($approval, $log, $strategy)) {
            // Ainda não atingiu o critério da estratégia
            $approval->update([
                'approvals_log' => $log,
            ]);
            return;
        }

        // Último nível (ou não definido) e estratégia cumprida: aprovar
        $approval->update([
            'status' => 'approved',
            'approved_by' => $approverId,
            'approved_at' => Carbon::now(),
            'approvals_log' => $log,
        ]);

        event(new ApprovalApproved($approval, $approverId));
        $this->executeAction($approval);
    }

    public function reject(Approval $approval, int $approverId): void
    {
        if ($approval->status !== 'pending') {
            return;
        }

        $this->assertUserCanActOnCurrentLevel($approval, $approverId);

        $log = $approval->approvals_log ?? [];
        $log[] = [
            'action' => 'reject',
            'level' => $approval->current_level ?? 1,
            'by' => $approverId,
            'at' => Carbon::now()->toISOString(),
        ];

        $approval->update([
            'status' => 'rejected',
            'approved_by' => $approverId,
            'approved_at' => Carbon::now(),
            'approvals_log' => $log,
        ]);

        event(new ApprovalRejected($approval, $approverId));
    }

    protected function executeAction(Approval $approval): void
    {
        $model = $approval->approvable;

        switch ($approval->action) {
            case 'update_field':
                $field = $approval->data['field'] ?? null;
                $value = $approval->data['new_value'] ?? null;
                if ($field !== null) {
                    $model->update([$field => $value]);
                }
                break;

            case 'delete':
                $model->delete();
                break;

            case 'create':
                $modelClass = $approval->approvable_type;
                if (class_exists($modelClass)) {
                    $modelClass::create($approval->data ?? []);
                }
                break;
        }
    }

    /**
     * Verifica se o utilizador tem alguma das roles exigidas para o nível atual.
     * Se o nível não tiver roles definidas, qualquer utilizador pode aprovar/rejeitar.
     */
    protected function assertUserCanActOnCurrentLevel(Approval $approval, int $userId): void
    {
        $levels = $approval->levels ?? [];
        $current = ($approval->current_level ?? 1) - 1; // índice 0-based
        $requiredRoles = [];
        if (is_array($levels) && isset($levels[$current]) && is_array($levels[$current])) {
            $requiredRoles = Arr::get($levels[$current], 'roles', []) ?? [];
        }

        // Sem restrições de role -> permitido
        if (empty($requiredRoles)) {
            return;
        }

        $userRoles = $this->getUserRoleNames($userId);
        $allowed = !empty(array_intersect($requiredRoles, $userRoles));

        if (!$allowed) {
            throw new \RuntimeException('Utilizador não autorizado a aprovar este nível.');
        }
    }

    /**
     * Tenta obter as roles do utilizador com base na config.
     * Compatível com spatie/laravel-permission (getRoleNames()).
     */
    protected function getUserRoleNames(int $userId): array
    {
        try {
            $userModelClass = config('approval.users_model');
            if (!is_string($userModelClass) || !class_exists($userModelClass)) {
                return [];
            }
            /** @var \Illuminate\Database\Eloquent\Model|null $user */
            $user = $userModelClass::query()->find($userId);
            if (!$user) {
                return [];
            }
            if (method_exists($user, 'getRoleNames')) {
                return $user->getRoleNames()->toArray();
            }
            if (property_exists($user, 'roles') && $user->roles) {
                // tentativa genérica
                return collect($user->roles)->pluck('name')->filter()->values()->all();
            }
        } catch (\Throwable $e) {
            // Falha silenciosa: sem roles -> validação ficará permissiva apenas se nível não exigir roles
            return [];
        }
        return [];
    }

    /**
     * Verifica se o aprovador já aprovou este pedido.
     */
    protected function assertApproverHasNotAlreadyApproved(Approval $approval, int $approverId): void
    {
        $log = $approval->approvals_log ?? [];
        foreach ($log as $entry) {
            if ($entry['action'] === 'approve' && $entry['by'] === $approverId) {
                throw new \RuntimeException('Utilizador já aprovou este pedido.');
            }
        }
    }

    /**
     * Verifica se a estratégia de aprovação foi cumprida.
     */
    protected function isApprovalStrategyMet(Approval $approval, array $log, string $strategy): bool
    {
        // Contar aprovações no nível atual
        $currentLevel = $approval->current_level ?? 1;
        $approvalsInCurrentLevel = collect($log)->filter(function ($entry) use ($currentLevel) {
            return $entry['action'] === 'approve' && $entry['level'] === $currentLevel;
        })->count();

        switch ($strategy) {
            case 'single':
                return $approvalsInCurrentLevel >= 1;

            case 'majority':
                $approvers = $approval->data['approvers'] ?? [];
                $totalApprovers = is_array($approvers) ? count($approvers) : 1;
                $threshold = config('approval.majority_threshold') ?? (int)ceil($totalApprovers / 2);
                return $approvalsInCurrentLevel >= $threshold;

            case 'unanimous':
                $approvers = $approval->data['approvers'] ?? [];
                $totalApprovers = is_array($approvers) ? count($approvers) : 1;
                return $approvalsInCurrentLevel >= $totalApprovers;

            default:
                return $approvalsInCurrentLevel >= 1;
        }
    }
}
