# Quick Start - Laravel Info Approval

Guia rápido de 5 minutos para começar a usar o sistema de aprovações.

---

## ⚡ Instalação Rápida

```bash
composer require bitoliveira/laravel-info-approval
php artisan migrate
```

Pronto! O package está instalado e pronto para usar.

---

## 🚀 Uso Básico em 3 Passos

### 1️⃣ Adicionar a Trait ao Modelo

```php
use bitoliveira\Approval\Traits\HasApprovals;

class Employee extends Model
{
    use HasApprovals;
}
```

### 2️⃣ Criar uma Aprovação

```php
$employee = Employee::find(1);

$approval = $employee->requestApproval('update_field', [
    'field' => 'salary',
    'new_value' => 3500,
], userId: auth()->id());
```

### 3️⃣ Aprovar ou Rejeitar

```php
use bitoliveira\Approval\Services\ApprovalService;

$service = app(ApprovalService::class);

// Aprovar
$service->approve($approval, approverId: auth()->id());

// OU Rejeitar
$service->reject($approval, approverId: auth()->id());
```

---

## 🎯 Exemplos Comuns

### Atualizar Campo

```php
$employee->requestApproval('update_field', [
    'field' => 'salary',
    'new_value' => 3000,
], userId: 1);
```

### Deletar Registro

```php
$employee->requestApproval('delete', [], userId: 1);
```

### Criar Registro

```php
$employee->requestApproval('create', [
    'name' => 'John Doe',
    'salary' => 2500,
], userId: 1);
```

---

## 🔄 Estratégias de Aprovação

### Single (Padrão) - 1 aprovação

```php
'strategy' => 'single'
```

### Majority - Maioria aprova

```php
'strategy' => 'majority',
'approvers' => [1, 2, 3, 4] // Precisa de 2
```

### Unanimous - Todos aprovam

```php
'strategy' => 'unanimous',
'approvers' => [1, 2, 3] // Todos devem aprovar
```

---

## 🏢 Multi-Nível

```php
$levels = [
    ['roles' => ['Manager']],
    ['roles' => ['Director']],
];

$employee->requestApproval('update_field', [
    'field' => 'salary',
    'new_value' => 5000,
], userId: 1, levels: $levels);
```

---

## 🌐 API REST

### Listar Aprovações

```bash
GET /api/approvals?status=pending
Authorization: Bearer {token}
```

### Aprovar via API

```bash
POST /api/approvals/1/approve
Authorization: Bearer {token}
Content-Type: application/json

{
  "approver_id": 10
}
```

### Rejeitar via API

```bash
POST /api/approvals/1/reject
Authorization: Bearer {token}
Content-Type: application/json

{
  "approver_id": 10
}
```

---

## 🔍 Query Scopes

```php
use bitoliveira\Approval\Models\Approval;

// Pendentes
Approval::pending()->get();

// Aprovadas
Approval::approved()->get();

// Por tipo
Approval::forType(Employee::class)->get();

// Por usuário
Approval::requestedBy(auth()->id())->get();

// Combinar
Approval::pending()
    ->forType(Employee::class)
    ->requestedBy(auth()->id())
    ->recent()
    ->get();
```

---

## 📢 Eventos

```php
use bitoliveira\Approval\Events\ApprovalRequested;
use bitoliveira\Approval\Events\ApprovalApproved;

Event::listen(ApprovalRequested::class, function ($event) {
    // Notificar aprovadores
});

Event::listen(ApprovalApproved::class, function ($event) {
    // Notificar solicitante
});
```

---

## 🗑️ Soft Deletes

```php
// Deletar
$approval->delete();

// Incluir deletados
Approval::withTrashed()->find(1);

// Restaurar
$approval->restore();
```

---

## 📖 Mais Informações

- **Guia Completo:** [USAGE.md](./USAGE.md)
- **Documentação API:** [API.md](./API.md)
- **Testes:** Ver diretório `tests/`

---

## 💡 Exemplo Completo

```php
// 1. Criar aprovação
$approval = $employee->requestApproval('update_field', [
    'field' => 'salary',
    'new_value' => 4000,
    'strategy' => 'majority',
    'approvers' => [10, 11, 12],
], userId: auth()->id());

// 2. Primeira aprovação
app(ApprovalService::class)->approve($approval, approverId: 10);
// Status: pending (precisa de mais 1)

// 3. Segunda aprovação (maioria atingida)
app(ApprovalService::class)->approve($approval, approverId: 11);
// Status: approved ✅
// Salário atualizado para 4000!

// 4. Listar minhas aprovações
$myApprovals = Approval::requestedBy(auth()->id())
    ->recent()
    ->get();
```

---

## 🎉 Pronto!

Você já pode começar a usar aprovações no seu projeto Laravel!

Para recursos avançados, consulte [USAGE.md](./USAGE.md).
