# Laravel Info Approval

Pacote inicial para adicionar fluxos de aprovação a modelos Eloquent no Laravel.

Estado: rascunho inicial (MVP). Esta é apenas a base do package, pronta para ser instalada e evoluída.

## Instalação

1. Adicione o repositório/depêndencia ao seu `composer.json` do projeto ou publique no Packagist.
2. Instale via Composer:

```bash
composer require bitoliveira/laravel-info-approval
```

O pacote usa auto-discovery.

Opcionalmente, você pode publicar a config:

```bash
php artisan vendor:publish --tag=approval-config
```

E publicar as migrations (também são carregadas automaticamente se você preferir não publicar):

```bash
php artisan vendor:publish --tag=approval-migrations
```

## Configuração

Arquivo de configuração publicado: `config/approval.php`.

Opções principais:
- `enabled`: ativa/desativa o fluxo globalmente.
- `default_strategy`: 'single' | 'majority' | 'unanimous'.
- `majority_threshold`: número mínimo para maioria (se nulo, calculado pelo total de aprovadores).

## Estrutura base (inclusa no pacote)

- Migration `approvals` com as colunas: morphs approvable, action, data (json), status (pending|approved|rejected), requested_by, approved_by, timestamps.
- Model: `bitoliveira\Approval\Models\Approval`.
- Trait: `bitoliveira\Approval\Traits\HasApprovals`.
- Service: `bitoliveira\Approval\Services\ApprovalService` (approve/reject/executeAction).

## Exemplo de uso

```php
use bitoliveira\Approval\Traits\HasApprovals;
use bitoliveira\Approval\Services\ApprovalService;
use bitoliveira\Approval\Models\Approval;

class Employee extends Model {
    use HasApprovals;
}

$employee = Employee::find(1);

// Propor alteração de salário
$approval = $employee->requestApproval('update_field', [
    'field' => 'salary',
    'new_value' => 2500,
], auth()->id());

// Aprovar via serviço
$service = app(ApprovalService::class);
$service->approve($approval, auth()->id());
```

### Teste de exemplo (PHPUnit)

```php
public function test_salary_update_requires_approval()
{
    $employee = Employee::factory()->create(['salary' => 1000]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => 2000,
    ], 1);

    $this->assertEquals('pending', $approval->status);
    $this->assertEquals(1000, $employee->fresh()->salary);

    app(\bitoliveira\Approval\Services\ApprovalService::class)->approve($approval, 2);

    $this->assertEquals('approved', $approval->fresh()->status);
    $this->assertEquals(2000, $employee->fresh()->salary);
}
```

## API (para integração móvel)

O pacote expõe endpoints de API para gestão de aprovações, prontos para consumo por apps móveis.

**Documentação completa**: Consulte [API.md](./API.md) para documentação detalhada com exemplos de requests/responses.

Rotas (prefixo configurável, por predefinição: `approvals`):
- GET /approvals — lista as aprovações (pode filtrar por `status`, `approvable_type`, `approvable_id`).
- GET /approvals/{id} — mostra uma aprovação.
- POST /approvals/{id}/approve — aprova a solicitação. Body JSON: `{ "approver_id": 123 }`.
- POST /approvals/{id}/reject — rejeita a solicitação. Body JSON: `{ "approver_id": 123 }`.

Configuração:
- Em `config/approval.php` pode ajustar:
  - `api.prefix`: prefixo das rotas (default: `approvals`).
  - `api.middleware`: middleware aplicado às rotas (default: `["api", "auth:sanctum"]`). **Autenticação é obrigatória por segurança.**

**Segurança**:
- Autenticação via `auth:sanctum` é obrigatória por padrão.
- O `approver_id` DEVE corresponder ao ID do utilizador autenticado (validação automática).
- Validação de permissões por nível é respeitada automaticamente (via roles).
- Proteção contra aprovações duplicadas pelo mesmo utilizador.

## Estratégias de Aprovação

O pacote suporta três estratégias de aprovação:

### 1. Single (padrão)
Uma única aprovação é suficiente.

```php
$approval = $employee->requestApproval('update_field', [
    'field' => 'salary',
    'new_value' => 2500,
    'strategy' => 'single',
], userId: 1);
```

### 2. Majority
Requer aprovação da maioria dos aprovadores listados (mais de 50%).

```php
$approval = $employee->requestApproval('update_field', [
    'field' => 'salary',
    'new_value' => 2500,
    'strategy' => 'majority',
    'approvers' => [1, 2, 3], // Precisa de 2 aprovações (maioria de 3)
], userId: 10);
```

### 3. Unanimous
Requer aprovação de TODOS os aprovadores listados.

```php
$approval = $employee->requestApproval('update_field', [
    'field' => 'salary',
    'new_value' => 2500,
    'strategy' => 'unanimous',
    'approvers' => [1, 2], // Ambos precisam aprovar
], userId: 10);
```

## Eventos

O pacote dispara eventos que podem ser usados para notificações:

- `ApprovalRequested`: Quando uma aprovação é criada
- `ApprovalApproved`: Quando uma aprovação é finalmente aprovada
- `ApprovalRejected`: Quando uma aprovação é rejeitada
- `ApprovalLevelAdvanced`: Quando avança para o próximo nível

```php
use bitoliveira\Approval\Events\ApprovalRequested;

Event::listen(ApprovalRequested::class, function ($event) {
    // Enviar notificação aos aprovadores
    $approval = $event->approval;
});
```

## Próximos passos

- Condicionais entre tabelas (ex.: só permitir create se um campo noutro modelo estiver preenchido – validação no `requestApproval`).

## Níveis de Aprovação por Role (novo)

O pacote suporta múltiplos níveis de aprovação, podendo definir que cada nível é aprovado por um ou mais perfis (roles) de utilizador. Quando um nível não define roles (array vazio), qualquer utilizador pode aprovar esse nível.

Exemplo:

```php
$levels = [
    ['roles' => ['Manager', 'Finance']], // nível 1: precisa de Manager OU Finance
    ['roles' => ['Admin']],              // nível 2: precisa de Admin
];

$approval = $employee->requestApproval('update_field', [
    'field' => 'salary',
    'new_value' => 2500,
], userId: auth()->id(), levels: $levels);

// Aprovações
app(\bitoliveira\Approval\Services\ApprovalService::class)->approve($approval, approverId: $managerId); // avança para nível 2
app(\bitoliveira\Approval\Services\ApprovalService::class)->approve($approval, approverId: $adminId);   // aprova e aplica a ação
```

Notas:
- As roles são verificadas, quando disponíveis, usando o modelo de utilizador configurado (`approval.users_model`). O pacote tenta usar `getRoleNames()` (compatível com spatie/laravel-permission). Se não encontrar roles, apenas níveis sem restrições (roles vazias) poderão ser aprovados por qualquer utilizador.
- O histórico de decisões é guardado em `approvals_log`.
- Campos novos na tabela: `levels` (json), `current_level` (int), `approvals_log` (json).

## Segurança

Para comunicar vulnerabilidades, consulte o documento [SECURITY.md](./SECURITY.md). Siga as instruções de divulgação coordenada e reporte por e‑mail (ou advisory privado no GitHub, se aplicável).

## Licença
MIT
