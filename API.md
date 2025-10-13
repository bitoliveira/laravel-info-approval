# API Documentation - Laravel Info Approval

## Visão Geral

A API do pacote Laravel Info Approval expõe endpoints REST para gestão de aprovações, prontos para consumo por apps móveis e outras aplicações.

**Prefixo padrão**: `/approvals` (configurável em `config/approval.php`)

**Autenticação**: Obrigatória via `auth:sanctum` (configurável)

---

## Endpoints

### 1. Listar Aprovações

**GET** `/approvals`

Lista todas as aprovações com filtros opcionais.

#### Query Parameters

| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `status` | string | Filtrar por status: `pending`, `approved`, `rejected` |
| `approvable_type` | string | Filtrar por tipo de modelo (ex: `App\Models\Employee`) |
| `approvable_id` | integer | Filtrar por ID do modelo |

#### Exemplo de Request

```bash
GET /approvals?status=pending&approvable_type=App\Models\Employee
Authorization: Bearer {token}
```

#### Exemplo de Response (200 OK)

```json
{
  "data": [
    {
      "id": 1,
      "approvable_type": "App\\Models\\Employee",
      "approvable_id": 5,
      "action": "update_field",
      "data": {
        "field": "salary",
        "new_value": 2500,
        "strategy": "single"
      },
      "levels": null,
      "current_level": 1,
      "approvals_log": [],
      "status": "pending",
      "requested_by": 10,
      "approved_by": null,
      "approved_at": null,
      "created_at": "2025-09-20T10:30:00.000000Z",
      "updated_at": "2025-09-20T10:30:00.000000Z"
    }
  ],
  "links": {
    "first": "http://localhost/approvals?page=1",
    "last": "http://localhost/approvals?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "per_page": 15,
    "to": 1,
    "total": 1
  }
}
```

---

### 2. Obter Detalhes de uma Aprovação

**GET** `/approvals/{id}`

Retorna os detalhes de uma aprovação específica.

#### Exemplo de Request

```bash
GET /approvals/1
Authorization: Bearer {token}
```

#### Exemplo de Response (200 OK)

```json
{
  "id": 1,
  "approvable_type": "App\\Models\\Employee",
  "approvable_id": 5,
  "action": "update_field",
  "data": {
    "field": "salary",
    "new_value": 2500,
    "strategy": "majority",
    "approvers": [1, 2, 3]
  },
  "levels": [
    {"roles": ["Manager"]},
    {"roles": ["Admin"]}
  ],
  "current_level": 1,
  "approvals_log": [
    {
      "action": "approve",
      "level": 1,
      "by": 1,
      "at": "2025-09-20T11:00:00.000000Z"
    }
  ],
  "status": "pending",
  "requested_by": 10,
  "approved_by": null,
  "approved_at": null,
  "created_at": "2025-09-20T10:30:00.000000Z",
  "updated_at": "2025-09-20T11:00:00.000000Z"
}
```

---

### 3. Aprovar uma Solicitação

**POST** `/approvals/{id}/approve`

Aprova uma solicitação de aprovação.

#### Request Body

```json
{
  "approver_id": 1
}
```

**Validações**:
- `approver_id` é obrigatório
- `approver_id` deve corresponder ao ID do utilizador autenticado
- O utilizador deve ter as roles necessárias para o nível atual (se aplicável)
- O utilizador não pode aprovar a mesma solicitação duas vezes

#### Exemplo de Request

```bash
POST /approvals/1/approve
Authorization: Bearer {token}
Content-Type: application/json

{
  "approver_id": 1
}
```

#### Exemplo de Response - Aprovação Final (200 OK)

```json
{
  "message": "Approval updated successfully.",
  "approval": {
    "id": 1,
    "approvable_type": "App\\Models\\Employee",
    "approvable_id": 5,
    "action": "update_field",
    "data": {
      "field": "salary",
      "new_value": 2500,
      "strategy": "single"
    },
    "levels": null,
    "current_level": 1,
    "approvals_log": [
      {
        "action": "approve",
        "level": 1,
        "by": 1,
        "at": "2025-09-20T11:00:00.000000Z"
      }
    ],
    "status": "approved",
    "requested_by": 10,
    "approved_by": 1,
    "approved_at": "2025-09-20T11:00:00.000000Z",
    "created_at": "2025-09-20T10:30:00.000000Z",
    "updated_at": "2025-09-20T11:00:00.000000Z"
  }
}
```

#### Exemplo de Response - Avançou para Próximo Nível (200 OK)

```json
{
  "message": "Approval updated successfully.",
  "approval": {
    "id": 1,
    "status": "pending",
    "current_level": 2,
    "approvals_log": [
      {
        "action": "approve",
        "level": 1,
        "by": 1,
        "at": "2025-09-20T11:00:00.000000Z"
      }
    ]
  }
}
```

#### Exemplo de Response - Erro (403 Forbidden)

```json
{
  "message": "O approver_id deve corresponder ao utilizador autenticado."
}
```

#### Exemplo de Response - Erro de Permissão (422 Unprocessable Entity)

```json
{
  "message": "Utilizador não autorizado a aprovar este nível."
}
```

#### Exemplo de Response - Aprovação Duplicada (422 Unprocessable Entity)

```json
{
  "message": "Utilizador já aprovou este pedido."
}
```

---

### 4. Rejeitar uma Solicitação

**POST** `/approvals/{id}/reject`

Rejeita uma solicitação de aprovação.

#### Request Body

```json
{
  "approver_id": 1
}
```

**Validações**:
- `approver_id` é obrigatório
- `approver_id` deve corresponder ao ID do utilizador autenticado
- O utilizador deve ter as roles necessárias para o nível atual (se aplicável)

#### Exemplo de Request

```bash
POST /approvals/1/reject
Authorization: Bearer {token}
Content-Type: application/json

{
  "approver_id": 1
}
```

#### Exemplo de Response (200 OK)

```json
{
  "message": "Approval updated successfully.",
  "approval": {
    "id": 1,
    "approvable_type": "App\\Models\\Employee",
    "approvable_id": 5,
    "action": "update_field",
    "data": {
      "field": "salary",
      "new_value": 2500,
      "strategy": "single"
    },
    "levels": null,
    "current_level": 1,
    "approvals_log": [
      {
        "action": "reject",
        "level": 1,
        "by": 1,
        "at": "2025-09-20T11:00:00.000000Z"
      }
    ],
    "status": "rejected",
    "requested_by": 10,
    "approved_by": 1,
    "approved_at": "2025-09-20T11:00:00.000000Z",
    "created_at": "2025-09-20T10:30:00.000000Z",
    "updated_at": "2025-09-20T11:00:00.000000Z"
  }
}
```

---

## Estratégias de Aprovação

O pacote suporta três estratégias de aprovação configuráveis via campo `data.strategy`:

### 1. Single (Padrão)

Requer apenas uma aprovação para completar.

```php
$approval = $employee->requestApproval('update_field', [
    'field' => 'salary',
    'new_value' => 2500,
    'strategy' => 'single',
], userId: 1);
```

### 2. Majority

Requer aprovação da maioria dos aprovadores listados.

```php
$approval = $employee->requestApproval('update_field', [
    'field' => 'salary',
    'new_value' => 2500,
    'strategy' => 'majority',
    'approvers' => [1, 2, 3], // 3 aprovadores, precisa de 2 (maioria)
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

---

## Níveis de Aprovação com Roles

O pacote suporta múltiplos níveis de aprovação com restrições por role:

```php
$levels = [
    ['roles' => ['Manager', 'Finance']], // Nível 1: Manager OU Finance
    ['roles' => ['Admin']],              // Nível 2: Admin
];

$approval = $employee->requestApproval('update_field', [
    'field' => 'salary',
    'new_value' => 2500,
], userId: 10, levels: $levels);
```

**Comportamento**:
1. Primeiro, um Manager ou Finance deve aprovar (nível 1)
2. Depois, um Admin deve aprovar (nível 2)
3. Apenas após ambos os níveis, a ação é executada

**Notas**:
- Se `roles` for array vazio `[]`, qualquer utilizador pode aprovar
- Compatível com `spatie/laravel-permission`

---

## Eventos Disponíveis

O pacote dispara os seguintes eventos:

### ApprovalRequested
Disparado quando uma aprovação é criada.

```php
use bitoliveira\Approval\Events\ApprovalRequested;

Event::listen(ApprovalRequested::class, function ($event) {
    $approval = $event->approval;
    // Enviar notificação aos aprovadores
});
```

### ApprovalApproved
Disparado quando uma aprovação é aprovada (finalmente).

```php
use bitoliveira\Approval\Events\ApprovalApproved;

Event::listen(ApprovalApproved::class, function ($event) {
    $approval = $event->approval;
    $approverId = $event->approverId;
    // Notificar o solicitante
});
```

### ApprovalRejected
Disparado quando uma aprovação é rejeitada.

```php
use bitoliveira\Approval\Events\ApprovalRejected;

Event::listen(ApprovalRejected::class, function ($event) {
    $approval = $event->approval;
    $approverId = $event->approverId;
    // Notificar o solicitante da rejeição
});
```

### ApprovalLevelAdvanced
Disparado quando uma aprovação avança para o próximo nível.

```php
use bitoliveira\Approval\Events\ApprovalLevelAdvanced;

Event::listen(ApprovalLevelAdvanced::class, function ($event) {
    $approval = $event->approval;
    $previousLevel = $event->previousLevel;
    $newLevel = $event->newLevel;
    $approverId = $event->approverId;
    // Notificar aprovadores do próximo nível
});
```

---

## Configuração

### Arquivo: `config/approval.php`

```php
return [
    'users_model' => "\\App\\Models\\User",
    'roles_model' => "\\Spatie\\Permission\\Models\\Role",
    'enabled' => true,
    'default_strategy' => 'single',
    'majority_threshold' => null, // null = calculado automaticamente (ceil(total/2))

    'api' => [
        'prefix' => 'approvals',
        'middleware' => ['api', 'auth:sanctum'], // Autenticação obrigatória
    ],
];
```

---

## Segurança

### Autenticação
- **Obrigatória** por padrão via `auth:sanctum`
- Configure o guard adequado em `config/approval.php`

### Validação de Utilizador
- O `approver_id` DEVE corresponder ao utilizador autenticado
- Retorna 403 Forbidden se houver discrepância

### Permissões por Role
- Valida automaticamente se o utilizador tem a role necessária
- Retorna 422 se o utilizador não tiver permissão

### Proteção contra Duplicação
- Não permite que o mesmo utilizador aprove duas vezes
- Retorna 422 com mensagem de erro

---

## Exemplos de Uso

### Exemplo 1: Aprovação Simples de Atualização de Salário

```php
// Backend: Criar pedido
$employee = Employee::find(5);
$approval = $employee->requestApproval('update_field', [
    'field' => 'salary',
    'new_value' => 2500,
    'strategy' => 'single',
], userId: auth()->id());

// Mobile App: Aprovar via API
POST /approvals/1/approve
{
  "approver_id": 1
}
```

### Exemplo 2: Aprovação com Maioria (3 aprovadores)

```php
// Backend: Criar pedido
$approval = $employee->requestApproval('update_field', [
    'field' => 'salary',
    'new_value' => 3500,
    'strategy' => 'majority',
    'approvers' => [1, 2, 3],
], userId: 10);

// Mobile: Primeira aprovação (ainda pending)
POST /approvals/1/approve
{"approver_id": 1}

// Mobile: Segunda aprovação (aprovado - maioria atingida)
POST /approvals/1/approve
{"approver_id": 2}
```

### Exemplo 3: Múltiplos Níveis com Roles

```php
// Backend
$levels = [
    ['roles' => ['Manager']],
    ['roles' => ['Admin']],
];

$approval = $employee->requestApproval('update_field', [
    'field' => 'salary',
    'new_value' => 5000,
], userId: 10, levels: $levels);

// Manager aprova (avança para nível 2)
POST /approvals/1/approve
{"approver_id": 5} // User 5 tem role "Manager"

// Admin aprova (aprovação final)
POST /approvals/1/approve
{"approver_id": 8} // User 8 tem role "Admin"
```

---

## Códigos de Status HTTP

| Código | Descrição |
|--------|-----------|
| `200 OK` | Operação bem-sucedida |
| `401 Unauthorized` | Token de autenticação inválido ou ausente |
| `403 Forbidden` | `approver_id` não corresponde ao utilizador autenticado |
| `404 Not Found` | Aprovação não encontrada |
| `422 Unprocessable Entity` | Erro de validação (permissões, duplicação, etc.) |

---

## Suporte

Para reportar problemas ou vulnerabilidades, consulte [SECURITY.md](./SECURITY.md).

## Licença

MIT
