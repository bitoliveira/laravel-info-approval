<?php

namespace bitoliveira\Approval\Services;

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
            $approval->update([
                'approvals_log' => $log,
                'current_level' => $current + 1,
            ]);
            return;
        }

        // Último nível (ou não definido): aprovar
        $approval->update([
            'status' => 'approved',
            'approved_by' => $approverId,
            'approved_at' => Carbon::now(),
            'approvals_log' => $log,
        ]);

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
}
