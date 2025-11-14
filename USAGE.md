# Guia de Uso - Laravel Info Approval

Guia completo para implementar e usar o sistema de aprovações no seu projeto Laravel.

---

## 📋 Índice

1. [Instalação](#instalação)
2. [Configuração Básica](#configuração-básica)
3. [Preparar Seus Modelos](#preparar-seus-modelos)
4. [Criar Aprovações](#criar-aprovações)
5. [Aprovar/Rejeitar](#aprovarrejeitar)
6. [Estratégias de Aprovação](#estratégias-de-aprovação)
7. [Aprovações Multi-Nível](#aprovações-multi-nível)
8. [API REST](#api-rest)
9. [Eventos](#eventos)
10. [Query Scopes](#query-scopes)
11. [Soft Deletes](#soft-deletes)
12. [Exemplos Práticos](#exemplos-práticos)

---

## Instalação

### 1. Instalar via Composer

```bash
composer require bitoliveira/laravel-info-approval
```

### 2. Publicar Configuração (Opcional)

```bash
php artisan vendor:publish --tag=approval-config
```

### 3. Publicar Migrations (Opcional)

```bash
php artisan vendor:publish --tag=approval-migrations
```

### 4. Executar Migrations

```bash
php artisan migrate
```

---

## Configuração Básica

Edite o arquivo `config/approval.php`:

```php
return [
    // Modelo de usuários
    'users_model' => "\\App\\Models\\User",

    // Modelo de roles (spatie/laravel-permission)
    'roles_model' => "\\Spatie\\Permission\\Models\\Role",

    // Namespace dos modelos da aplicação
    'models_path' => "\\App\\Models",

    // Habilitar/desabilitar globalmente
    'enabled' => true,

    // Estratégia padrão: 'single', 'majority', 'unanimous'
    'default_strategy' => 'single',

    // Threshold para maioria (null = calculado automaticamente)
    'majority_threshold' => null,

    // Configurações da API
    'api' => [
        'prefix' => 'approvals',
        'middleware' => ['api', 'auth:sanctum'],
    ],
];
```

---

## Preparar Seus Modelos

### Adicionar a Trait `HasApprovals`

```php
<?php

namespace App\Models;

use bitoliveira\Approval\Traits\HasApprovals;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasApprovals;

    protected $fillable = ['name', 'salary', 'department'];
}
```

Pronto! Seu modelo agora suporta aprovações.

---

## Criar Aprovações

### Sintaxe Básica

```php
$approval = $model->requestApproval(
    action: 'nome_da_acao',
    data: ['dados' => 'necessarios'],
    userId: auth()->id(),
    levels: null // opcional
);
```

### Exemplo 1: Atualizar Campo

```php
$employee = Employee::find(1);

$approval = $employee->requestApproval(
    action: 'update_field',
    data: [
        'field' => 'salary',
        'new_value' => 3500,
    ],
    userId: auth()->id()
);

// Status: pending
// Mudança NÃO aplicada ainda
echo $employee->salary; // Valor antigo
```

### Exemplo 2: Deletar Registro

```php
$employee = Employee::find(1);

$approval = $employee->requestApproval(
    action: 'delete',
    data: [],
    userId: auth()->id()
);

// Registro ainda existe até aprovação
echo Employee::find(1)->name; // Funciona
```

### Exemplo 3: Criar Registro

```php
$approval = Employee::first()->requestApproval(
    action: 'create',
    data: [
        'name' => 'New Employee',
        'salary' => 2500,
        'department' => 'IT',
    ],
    userId: auth()->id()
);

// Novo registro NÃO criado até aprovação
```

---

## Aprovar/Rejeitar

### Usar o ApprovalService

```php
use bitoliveira\Approval\Services\ApprovalService;
use bitoliveira\Approval\Models\Approval;

$approval = Approval::find(1);
$service = app(ApprovalService::class);

// Aprovar
$service->approve($approval, approverId: auth()->id());

// OU Rejeitar
$service->reject($approval, approverId: auth()->id());
```

### Via API (ver seção API REST)

---

## Estratégias de Aprovação

### 1. Single (Padrão)

Requer apenas **1 aprovação**.

```php
$approval = $employee->requestApproval('update_field', [
    'field' => 'salary',
    'new_value' => 3000,
    'strategy' => 'single', // Opcional (é o padrão)
], userId: 1);

// Primeira aprovação = aprovado
app(ApprovalService::class)->approve($approval, approverId: 2);
// Status: approved ✅
```

### 2. Majority

Requer aprovação da **maioria** (>50%).

```php
$approval = $employee->requestApproval('update_field', [
    'field' => 'salary',
    'new_value' => 3500,
    'strategy' => 'majority',
    'approvers' => [10, 11, 12, 13], // 4 aprovadores
], userId: 1);

// Precisa de 2 aprovações (ceil(4/2) = 2)
app(ApprovalService::class)->approve($approval, approverId: 10);
// Status: pending ⏳

app(ApprovalService::class)->approve($approval, approverId: 11);
// Status: approved ✅ (maioria atingida)
```

### 3. Unanimous

Requer aprovação de **TODOS**.

```php
$approval = $employee->requestApproval('update_field', [
    'field' => 'salary',
    'new_value' => 4000,
    'strategy' => 'unanimous',
    'approvers' => [20, 21, 22], // Todos devem aprovar
], userId: 1);

app(ApprovalService::class)->approve($approval, approverId: 20);
// Status: pending ⏳

app(ApprovalService::class)->approve($approval, approverId: 21);
// Status: pending ⏳

app(ApprovalService::class)->approve($approval, approverId: 22);
// Status: approved ✅ (todos aprovaram)
```

---

## Aprovações Multi-Nível

### Conceito

Aprovações podem passar por **múltiplos níveis hierárquicos** antes de serem aplicadas.

### Estrutura de Níveis

```php
$levels = [
    ['roles' => ['Manager', 'Supervisor']], // Nível 1
    ['roles' => ['Director']],              // Nível 2
    ['roles' => ['CEO']],                   // Nível 3
];
```

### Exemplo Completo

```php
$levels = [
    ['roles' => ['Manager']],  // Nível 1
    ['roles' => ['Director']], // Nível 2
];

$approval = $employee->requestApproval('update_field', [
    'field' => 'salary',
    'new_value' => 5000,
], userId: 1, levels: $levels);

// Status: pending, current_level: 1

// Manager aprova (nível 1)
app(ApprovalService::class)->approve($approval, approverId: $managerId);
// Status: pending, current_level: 2 ⬆️
// Dispara evento: ApprovalLevelAdvanced

// Director aprova (nível 2 - final)
app(ApprovalService::class)->approve($approval, approverId: $directorId);
// Status: approved ✅
// Dispara evento: ApprovalApproved
// Ação EXECUTADA!
```

### Sem Restrições de Role

```php
$levels = [
    ['roles' => []], // Qualquer usuário pode aprovar
    ['roles' => []], // Qualquer usuário pode aprovar
];

$approval = $employee->requestApproval('update_field', [
    'field' => 'salary',
    'new_value' => 3000,
], userId: 1, levels: $levels);

// Qualquer usuário pode aprovar cada nível
app(ApprovalService::class)->approve($approval, approverId: 100); // Nível 1
app(ApprovalService::class)->approve($approval, approverId: 101); // Nível 2
```

### Combinar com Estratégias

Cada **nível** pode ter sua própria **estratégia**:

```php
$levels = [
    ['roles' => ['Manager'], 'strategy' => 'majority', 'approvers' => [10, 11, 12]],
    ['roles' => ['Admin'], 'strategy' => 'single'],
];

// Nível 1: precisa maioria dos managers
// Nível 2: precisa apenas 1 admin
```

---

## API REST

### Endpoints Disponíveis

Base URL: `https://seuapp.com/api/approvals`

#### 1. Listar Aprovações

```http
GET /approvals?status=pending&approvable_type=App\Models\Employee
Authorization: Bearer {token}
```

**Resposta:**
```json
{
  "data": [
    {
      "id": 1,
      "approvable_type": "App\\Models\\Employee",
      "approvable_id": 5,
      "action": "update_field",
      "data": { "field": "salary", "new_value": 3000 },
      "status": "pending",
      "created_at": "2025-10-29T10:00:00.000000Z"
    }
  ],
  "meta": {
    "total": 1,
    "per_page": 15,
    "current_page": 1
  }
}
```

#### 2. Obter Detalhes

```http
GET /approvals/1
Authorization: Bearer {token}
```

#### 3. Aprovar

```http
POST /approvals/1/approve
Authorization: Bearer {token}
Content-Type: application/json

{
  "approver_id": 10
}
```

**Validações:**
- ✅ `approver_id` deve ser do usuário autenticado
- ✅ Usuário deve ter role necessária (se aplicável)
- ✅ Não pode aprovar duas vezes

**Resposta:**
```json
{
  "message": "Approval updated successfully.",
  "approval": {
    "id": 1,
    "status": "approved",
    "approved_by": 10,
    "approved_at": "2025-10-29T11:00:00.000000Z"
  }
}
```

#### 4. Rejeitar

```http
POST /approvals/1/reject
Authorization: Bearer {token}
Content-Type: application/json

{
  "approver_id": 10
}
```

### Exemplo com Axios (JavaScript)

```javascript
// Listar aprovações pendentes
const response = await axios.get('/api/approvals', {
  params: { status: 'pending' },
  headers: { 'Authorization': `Bearer ${token}` }
});

// Aprovar
await axios.post(`/api/approvals/${approvalId}/approve`, {
  approver_id: userId
}, {
  headers: { 'Authorization': `Bearer ${token}` }
});
```

### Exemplo com Guzzle (PHP)

```php
use GuzzleHttp\Client;

$client = new Client(['base_uri' => 'https://seuapp.com/api/']);

// Listar
$response = $client->get('approvals', [
    'query' => ['status' => 'pending'],
    'headers' => ['Authorization' => "Bearer {$token}"]
]);

// Aprovar
$response = $client->post("approvals/{$approvalId}/approve", [
    'json' => ['approver_id' => $userId],
    'headers' => ['Authorization' => "Bearer {$token}"]
]);
```

---

## Eventos

O package dispara 4 eventos que você pode escutar:

### 1. ApprovalRequested

Disparado quando aprovação é **criada**.

```php
use bitoliveira\Approval\Events\ApprovalRequested;

Event::listen(ApprovalRequested::class, function ($event) {
    $approval = $event->approval;

    // Enviar notificação aos aprovadores
    Notification::send($approvers, new ApprovalPendingNotification($approval));
});
```

### 2. ApprovalApproved

Disparado quando aprovação é **finalmente aprovada**.

```php
use bitoliveira\Approval\Events\ApprovalApproved;

Event::listen(ApprovalApproved::class, function ($event) {
    $approval = $event->approval;
    $approverId = $event->approverId;

    // Notificar solicitante
    $requester = User::find($approval->requested_by);
    $requester->notify(new ApprovalApprovedNotification($approval));
});
```

### 3. ApprovalRejected

Disparado quando aprovação é **rejeitada**.

```php
use bitoliveira\Approval\Events\ApprovalRejected;

Event::listen(ApprovalRejected::class, function ($event) {
    $approval = $event->approval;
    $approverId = $event->approverId;

    // Notificar solicitante
    Log::info("Approval {$approval->id} rejected by user {$approverId}");
});
```

### 4. ApprovalLevelAdvanced

Disparado quando aprovação **avança de nível**.

```php
use bitoliveira\Approval\Events\ApprovalLevelAdvanced;

Event::listen(ApprovalLevelAdvanced::class, function ($event) {
    $approval = $event->approval;
    $previousLevel = $event->previousLevel;
    $newLevel = $event->newLevel;
    $approverId = $event->approverId;

    // Notificar aprovadores do próximo nível
    $nextLevelApprovers = getApproversForLevel($approval, $newLevel);
    Notification::send($nextLevelApprovers, new NewLevelNotification($approval));
});
```

### Registrar no EventServiceProvider

```php
// app/Providers/EventServiceProvider.php

protected $listen = [
    \bitoliveira\Approval\Events\ApprovalRequested::class => [
        \App\Listeners\SendApprovalNotification::class,
    ],
    \bitoliveira\Approval\Events\ApprovalApproved::class => [
        \App\Listeners\NotifyRequester::class,
    ],
];
```

---

## Query Scopes

O modelo `Approval` possui 11 scopes úteis:

### Scopes por Status

```php
use bitoliveira\Approval\Models\Approval;

// Apenas pendentes
$pending = Approval::pending()->get();

// Apenas aprovadas
$approved = Approval::approved()->get();

// Apenas rejeitadas
$rejected = Approval::rejected()->get();

// Por status específico
$approvals = Approval::status('pending')->get();
```

### Scopes por Tipo/Modelo

```php
// Por tipo de modelo
$employeeApprovals = Approval::forType(Employee::class)->get();

// Por modelo específico
$employee = Employee::find(1);
$approvals = Approval::forModel($employee)->get();
```

### Scopes por Ação

```php
// Apenas atualizações
$updates = Approval::action('update_field')->get();

// Apenas deleções
$deletes = Approval::action('delete')->get();
```

### Scopes por Usuário

```php
// Solicitadas por usuário
$myRequests = Approval::requestedBy(auth()->id())->get();

// Aprovadas por usuário
$myApprovals = Approval::approvedBy(auth()->id())->get();
```

### Scopes por Nível

```php
// Aprovações no nível 2
$level2 = Approval::atLevel(2)->get();
```

### Ordenação

```php
// Mais recentes primeiro
$recent = Approval::recent()->get();
```

### Encadeamento de Scopes

```php
// Combinar múltiplos scopes
$approvals = Approval::pending()
    ->forType(Employee::class)
    ->requestedBy(auth()->id())
    ->action('update_field')
    ->recent()
    ->get();
```

### Com Paginação

```php
$approvals = Approval::pending()
    ->forType(Employee::class)
    ->recent()
    ->paginate(15);
```

---

## Soft Deletes

Aprovações podem ser deletadas suavemente, preservando o histórico.

### Deletar Suavemente

```php
$approval = Approval::find(1);
$approval->delete(); // Soft delete

// Não aparece em queries normais
Approval::find(1); // null
```

### Incluir Deletados

```php
// Incluir deletados
$approval = Approval::withTrashed()->find(1);

// Apenas deletados
$deleted = Approval::onlyTrashed()->get();
```

### Restaurar

```php
$approval = Approval::withTrashed()->find(1);
$approval->restore();

// Agora disponível novamente
Approval::find(1); // Encontrado
```

### Deletar Permanentemente

```php
$approval = Approval::find(1);
$approval->forceDelete(); // Permanente

// Não existe mais em lugar nenhum
Approval::withTrashed()->find(1); // null
```

### Scopes com Soft Deletes

```php
// Pendentes incluindo deletados
$pending = Approval::withTrashed()->pending()->get();

// Aprovados apenas não deletados
$approved = Approval::approved()->get();
```

---

## Exemplos Práticos

### Exemplo 1: Aprovação de Aumento Salarial

```php
// Controller
public function requestSalaryIncrease(Request $request, Employee $employee)
{
    $request->validate([
        'new_salary' => 'required|numeric|min:0',
    ]);

    $approval = $employee->requestApproval('update_field', [
        'field' => 'salary',
        'new_value' => $request->new_salary,
        'strategy' => 'majority',
        'approvers' => [1, 2, 3], // IDs dos managers
    ], userId: auth()->id());

    return response()->json([
        'message' => 'Solicitação de aumento enviada para aprovação.',
        'approval_id' => $approval->id,
    ]);
}

// Aprovar
public function approveSalaryIncrease(Approval $approval)
{
    app(ApprovalService::class)->approve($approval, approverId: auth()->id());

    return response()->json([
        'message' => 'Aumento aprovado com sucesso!',
    ]);
}
```

### Exemplo 2: Aprovação Multi-Nível de Despesa

```php
public function requestExpense(Request $request)
{
    $amount = $request->input('amount');

    // Definir níveis baseado no valor
    $levels = $amount > 10000
        ? [
            ['roles' => ['Manager']],
            ['roles' => ['Director']],
            ['roles' => ['CFO']],
        ]
        : [
            ['roles' => ['Manager']],
        ];

    $expense = Expense::create($request->all());

    $approval = $expense->requestApproval('create',
        $request->all(),
        auth()->id(),
        $levels
    );

    return redirect()->route('expenses.show', $approval);
}
```

### Exemplo 3: Dashboard de Aprovações

```php
public function dashboard()
{
    $user = auth()->user();

    return view('approvals.dashboard', [
        // Minhas solicitações
        'myRequests' => Approval::requestedBy($user->id)
            ->recent()
            ->paginate(10),

        // Pendentes para eu aprovar
        'pendingForMe' => Approval::pending()
            ->whereJsonContains('data->approvers', $user->id)
            ->recent()
            ->paginate(10),

        // Aprovadas por mim
        'approvedByMe' => Approval::approved()
            ->approvedBy($user->id)
            ->recent()
            ->paginate(10),
    ]);
}
```

### Exemplo 4: Notificações com Eventos

```php
// app/Listeners/SendApprovalNotification.php

namespace App\Listeners;

use bitoliveira\Approval\Events\ApprovalRequested;
use App\Notifications\NewApprovalNotification;
use App\Models\User;

class SendApprovalNotification
{
    public function handle(ApprovalRequested $event)
    {
        $approval = $event->approval;
        $approvers = $approval->data['approvers'] ?? [];

        if (empty($approvers)) {
            return;
        }

        $users = User::whereIn('id', $approvers)->get();

        \Notification::send($users, new NewApprovalNotification($approval));
    }
}
```

### Exemplo 5: Middleware de Autorização

```php
// app/Http/Middleware/CanApprove.php

namespace App\Http\Middleware;

use bitoliveira\Approval\Models\Approval;
use Closure;

class CanApprove
{
    public function handle($request, Closure $next)
    {
        $approval = $request->route('approval');

        if (!$approval instanceof Approval) {
            return response()->json(['error' => 'Invalid approval'], 400);
        }

        $user = $request->user();
        $approvers = $approval->data['approvers'] ?? [];

        if (!in_array($user->id, $approvers)) {
            return response()->json([
                'error' => 'Você não está autorizado a aprovar esta solicitação.'
            ], 403);
        }

        return $next($request);
    }
}
```

### Exemplo 6: Integração com Aplicativo Mobile (React Native)

```javascript
// services/approvalService.js

import axios from 'axios';

const API_URL = 'https://api.seuapp.com/api';

export const approvalService = {
  // Listar aprovações pendentes
  async getPendingApprovals(token) {
    const response = await axios.get(`${API_URL}/approvals`, {
      params: { status: 'pending' },
      headers: { Authorization: `Bearer ${token}` }
    });
    return response.data;
  },

  // Aprovar
  async approve(approvalId, userId, token) {
    const response = await axios.post(
      `${API_URL}/approvals/${approvalId}/approve`,
      { approver_id: userId },
      { headers: { Authorization: `Bearer ${token}` }}
    );
    return response.data;
  },

  // Rejeitar
  async reject(approvalId, userId, token) {
    const response = await axios.post(
      `${API_URL}/approvals/${approvalId}/reject`,
      { approver_id: userId },
      { headers: { Authorization: `Bearer ${token}` }}
    );
    return response.data;
  }
};

// Uso no componente
import React, { useEffect, useState } from 'react';
import { approvalService } from './services/approvalService';

function ApprovalList() {
  const [approvals, setApprovals] = useState([]);
  const token = getAuthToken(); // Seu método de auth
  const userId = getUserId();

  useEffect(() => {
    loadApprovals();
  }, []);

  async function loadApprovals() {
    const data = await approvalService.getPendingApprovals(token);
    setApprovals(data.data);
  }

  async function handleApprove(approvalId) {
    await approvalService.approve(approvalId, userId, token);
    loadApprovals(); // Recarregar lista
  }

  return (
    <div>
      {approvals.map(approval => (
        <div key={approval.id}>
          <h3>{approval.action}</h3>
          <p>Status: {approval.status}</p>
          <button onClick={() => handleApprove(approval.id)}>
            Aprovar
          </button>
        </div>
      ))}
    </div>
  );
}
```

---

## Tratamento de Exceções

### Exceções Disponíveis

```php
use bitoliveira\Approval\Exceptions\InvalidApprovalStatusException;
use bitoliveira\Approval\Exceptions\DuplicateApprovalException;
use bitoliveira\Approval\Exceptions\UnauthorizedApproverException;
use bitoliveira\Approval\Exceptions\ApproverMismatchException;
```

### Capturar e Tratar

```php
use bitoliveira\Approval\Services\ApprovalService;
use bitoliveira\Approval\Exceptions\DuplicateApprovalException;
use bitoliveira\Approval\Exceptions\InvalidApprovalStatusException;

try {
    app(ApprovalService::class)->approve($approval, approverId: auth()->id());

    return response()->json(['message' => 'Aprovado com sucesso!']);

} catch (DuplicateApprovalException $e) {
    return response()->json([
        'error' => 'Você já aprovou esta solicitação.'
    ], 422);

} catch (InvalidApprovalStatusException $e) {
    return response()->json([
        'error' => 'Esta aprovação não pode ser modificada.'
    ], 422);

} catch (UnauthorizedApproverException $e) {
    return response()->json([
        'error' => 'Você não tem permissão para aprovar.'
    ], 403);
}
```

---

## Dicas e Boas Práticas

### ✅ Sempre use eventos para notificações

```php
// ❌ Não faça isso
$approval = $employee->requestApproval(...);
Mail::to($approvers)->send(new ApprovalEmail($approval));

// ✅ Faça isso
Event::listen(ApprovalRequested::class, SendApprovalNotification::class);
$approval = $employee->requestApproval(...);
// Evento dispara automaticamente
```

### ✅ Use scopes para queries complexas

```php
// ❌ Query manual
$approvals = Approval::where('status', 'pending')
    ->where('approvable_type', Employee::class)
    ->where('requested_by', auth()->id())
    ->orderBy('created_at', 'desc')
    ->get();

// ✅ Use scopes
$approvals = Approval::pending()
    ->forType(Employee::class)
    ->requestedBy(auth()->id())
    ->recent()
    ->get();
```

### ✅ Valide permissões antes de criar aprovação

```php
public function requestApproval(Request $request, Employee $employee)
{
    // Validar se usuário pode solicitar
    if (!auth()->user()->can('request-salary-change')) {
        abort(403, 'Sem permissão para solicitar mudança.');
    }

    $approval = $employee->requestApproval(...);
}
```

### ✅ Use soft deletes para auditoria

```php
// Manter histórico mesmo após exclusão
$approval->delete(); // Soft delete
// Histórico preservado para auditoria
```

---

## Troubleshooting

### Problema: Aprovação não está executando a ação

**Solução:** Verifique se:
1. Status está `approved`
2. Estratégia foi cumprida
3. Todos os níveis foram aprovados

### Problema: Erro "Utilizador já aprovou este pedido"

**Solução:** Mesmo usuário não pode aprovar duas vezes. Use `approvals_log` para verificar quem já aprovou.

### Problema: API retorna 403 Forbidden

**Solução:** Certifique-se que `approver_id` corresponde ao usuário autenticado.

### Problema: Eventos não estão sendo disparados

**Solução:** Verifique o `EventServiceProvider` e certifique-se que está registrado.

---

## Suporte

- **Documentação API:** [API.md](./API.md)
- **Segurança:** [SECURITY.md](./SECURITY.md)
- **Issues:** https://github.com/bitoliveira/laravel-info-approval/issues

---

## Licença

MIT License - Veja [LICENSE](./LICENSE) para mais detalhes.
