# Laravel Info Approval

Sistema completo de aprovações para modelos Eloquent no Laravel com suporte a múltiplos níveis, estratégias flexíveis e API REST.

[![Tests](https://img.shields.io/badge/tests-55%20passing-brightgreen)](./tests)
[![Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen)](./tests)
[![Laravel](https://img.shields.io/badge/laravel-11%7C12-red)](https://laravel.com)
[![PHP](https://img.shields.io/badge/php-8.2%2B-blue)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-blue)](./LICENSE)

---

## 🚀 Início Rápido

```bash
composer require bitoliveira/laravel-info-approval
php artisan migrate
```

```php
use bitoliveira\Approval\Traits\HasApprovals;

class Employee extends Model {
    use HasApprovals;
}

// Criar aprovação
$approval = $employee->requestApproval('update_field', [
    'field' => 'salary',
    'new_value' => 3500,
], userId: auth()->id());

// Aprovar
app(\bitoliveira\Approval\Services\ApprovalService::class)
    ->approve($approval, approverId: auth()->id());
```

**📚 [Guia Rápido de 5 Minutos](./QUICKSTART.md)** | **📖 [Documentação Completa](./USAGE.md)** | **🌐 [API REST](./API.md)**

---

## ✨ Funcionalidades

### 🎯 Core
- ✅ **Aprovações Polimórficas** - Qualquer modelo Eloquent pode ter aprovações
- ✅ **Três Estratégias** - Single, Majority, Unanimous
- ✅ **Multi-Nível** - Aprovações hierárquicas (Manager → Director → CEO)
- ✅ **Controle por Roles** - Integração com spatie/laravel-permission
- ✅ **Histórico Completo** - Log detalhado de todas as ações
- ✅ **Soft Deletes** - Preserva histórico mesmo após exclusão

### 🌐 API
- ✅ **REST API Completa** - Pronta para apps mobile
- ✅ **Autenticação Sanctum** - Segurança integrada
- ✅ **Validações Robustas** - Form Requests customizados
- ✅ **Resources Padronizados** - Responses formatados
- ✅ **Paginação** - Suporte nativo Laravel

### 🔍 Consultas
- ✅ **11 Query Scopes** - Filtros reutilizáveis e encadeáveis
- ✅ **Busca Avançada** - Por status, tipo, usuário, ação
- ✅ **Performance** - Queries otimizadas

### 📢 Eventos
- ✅ **4 Eventos** - ApprovalRequested, Approved, Rejected, LevelAdvanced
- ✅ **Notificações** - Integração fácil com Laravel Notifications
- ✅ **Observabilidade** - Auditoria completa

### 🛡️ Segurança
- ✅ **Exceções Customizadas** - Tratamento de erros específico
- ✅ **Validação de Permissões** - Automática por role
- ✅ **Proteção de Duplicação** - Não permite aprovar 2x
- ✅ **Auditoria** - Rastreamento completo de ações

---

## 📦 Instalação

### 1. Via Composer

```bash
composer require bitoliveira/laravel-info-approval
```

### 2. Publicar Arquivos (Opcional)

```bash
# Configuração
php artisan vendor:publish --tag=approval-config

# Migrations
php artisan vendor:publish --tag=approval-migrations
```

### 3. Migrar Banco de Dados

```bash
php artisan migrate
```

---

## 🎯 Uso Básico

### 1. Adicionar a Trait ao Modelo

```php
use bitoliveira\Approval\Traits\HasApprovals;

class Employee extends Model
{
    use HasApprovals;

    protected $fillable = ['name', 'salary', 'department'];
}
```

### 2. Criar Aprovação

```php
$employee = Employee::find(1);

$approval = $employee->requestApproval('update_field', [
    'field' => 'salary',
    'new_value' => 3500,
], userId: auth()->id());

// Status: pending
// Mudança NÃO aplicada ainda
```

### 3. Aprovar ou Rejeitar

```php
use bitoliveira\Approval\Services\ApprovalService;

$service = app(ApprovalService::class);

// Aprovar
$service->approve($approval, approverId: auth()->id());
// Agora o salário é 3500 ✅

// OU Rejeitar
$service->reject($approval, approverId: auth()->id());
```

---

## 🎨 Estratégias de Aprovação

### Single (Padrão)
Uma aprovação é suficiente:

```php
$approval = $employee->requestApproval('update_field', [
    'field' => 'salary',
    'new_value' => 3000,
    'strategy' => 'single',
], userId: 1);
```

### Majority
Maioria deve aprovar:

```php
$approval = $employee->requestApproval('update_field', [
    'field' => 'salary',
    'new_value' => 3500,
    'strategy' => 'majority',
    'approvers' => [1, 2, 3, 4], // Precisa de 2 (maioria)
], userId: 1);
```

### Unanimous
Todos devem aprovar:

```php
$approval = $employee->requestApproval('update_field', [
    'field' => 'salary',
    'new_value' => 4000,
    'strategy' => 'unanimous',
    'approvers' => [1, 2, 3], // Todos devem aprovar
], userId: 1);
```

---

## 🏢 Aprovações Multi-Nível

Crie fluxos hierárquicos de aprovação:

```php
$levels = [
    ['roles' => ['Manager']],   // Nível 1: Manager
    ['roles' => ['Director']],  // Nível 2: Director
    ['roles' => ['CEO']],        // Nível 3: CEO
];

$approval = $employee->requestApproval('update_field', [
    'field' => 'salary',
    'new_value' => 5000,
], userId: 1, levels: $levels);

// Aprovação em cascata
$service->approve($approval, approverId: $managerId);   // → Nível 2
$service->approve($approval, approverId: $directorId);  // → Nível 3
$service->approve($approval, approverId: $ceoId);       // ✅ Aprovado!
```

**Recursos:**
- ✅ Quantos níveis quiser
- ✅ Roles diferentes por nível
- ✅ Eventos a cada avanço de nível
- ✅ Log completo do processo

---

## 🌐 API REST

Endpoints prontos para apps mobile:

```bash
GET    /api/approvals              # Listar com filtros
GET    /api/approvals/{id}         # Detalhes
POST   /api/approvals/{id}/approve # Aprovar
POST   /api/approvals/{id}/reject  # Rejeitar
```

### Exemplo de Uso

```javascript
// Listar aprovações pendentes
const response = await fetch('/api/approvals?status=pending', {
  headers: { 'Authorization': `Bearer ${token}` }
});

// Aprovar
await fetch(`/api/approvals/${id}/approve`, {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({ approver_id: userId })
});
```

**📖 [Documentação Completa da API](./API.md)**

---

## 🔍 Query Scopes

11 scopes poderosos para consultas:

```php
use bitoliveira\Approval\Models\Approval;

// Por status
Approval::pending()->get();
Approval::approved()->get();
Approval::rejected()->get();

// Por tipo/modelo
Approval::forType(Employee::class)->get();
Approval::forModel($employee)->get();

// Por ação
Approval::action('update_field')->get();

// Por usuário
Approval::requestedBy(auth()->id())->get();
Approval::approvedBy(auth()->id())->get();

// Por nível
Approval::atLevel(2)->get();

// Combinar e paginar
Approval::pending()
    ->forType(Employee::class)
    ->requestedBy(auth()->id())
    ->recent()
    ->paginate(15);
```

---

## 📢 Eventos

4 eventos para integração:

```php
use bitoliveira\Approval\Events\{
    ApprovalRequested,
    ApprovalApproved,
    ApprovalRejected,
    ApprovalLevelAdvanced
};

// Quando aprovação é criada
Event::listen(ApprovalRequested::class, function ($event) {
    $approval = $event->approval;
    // Notificar aprovadores
});

// Quando é finalmente aprovada
Event::listen(ApprovalApproved::class, function ($event) {
    $approval = $event->approval;
    $approverId = $event->approverId;
    // Notificar solicitante
});

// Quando é rejeitada
Event::listen(ApprovalRejected::class, function ($event) {
    // Log da rejeição
});

// Quando avança de nível
Event::listen(ApprovalLevelAdvanced::class, function ($event) {
    $previousLevel = $event->previousLevel;
    $newLevel = $event->newLevel;
    // Notificar próximos aprovadores
});
```

---

## 🛡️ Segurança

### Exceções Customizadas

```php
use bitoliveira\Approval\Exceptions\{
    InvalidApprovalStatusException,    // 422
    DuplicateApprovalException,        // 422
    UnauthorizedApproverException,     // 403
    ApproverMismatchException          // 403
};

try {
    $service->approve($approval, approverId: auth()->id());
} catch (DuplicateApprovalException $e) {
    return response()->json(['error' => $e->getMessage()], 422);
}
```

### Validações Automáticas

- ✅ `approver_id` deve corresponder ao usuário autenticado
- ✅ Usuário deve ter role necessária para o nível
- ✅ Não pode aprovar a mesma solicitação duas vezes
- ✅ Não pode modificar aprovações já processadas

---

## 🗑️ Soft Deletes

Preserve histórico mesmo após exclusão:

```php
// Deletar suavemente
$approval->delete();

// Incluir deletados em queries
Approval::withTrashed()->find(1);
Approval::onlyTrashed()->get();

// Restaurar
$approval->restore();

// Deletar permanentemente
$approval->forceDelete();
```

---

## 📊 Testes

**55 testes** cobrindo 100% das funcionalidades:

```bash
composer test
```

Cobertura de testes:
- ✅ 7 testes de exceções
- ✅ 7 testes de validação API
- ✅ 6 testes de API Resources
- ✅ 5 testes de query scopes
- ✅ 7 testes de soft deletes
- ✅ 5 testes de estratégias
- ✅ 8 testes de integração completa
- ✅ 5 testes de eventos
- ✅ E mais...

**Total: 275 assertions passando** ✅

---

## 📚 Documentação

| Documento | Descrição |
|-----------|-----------|
| [QUICKSTART.md](./QUICKSTART.md) | Guia rápido de 5 minutos |
| [USAGE.md](./USAGE.md) | Documentação completa de uso |
| [API.md](./API.md) | Documentação da API REST |
| [SECURITY.md](./SECURITY.md) | Política de segurança |

---

## 🤝 Configuração

Arquivo `config/approval.php`:

```php
return [
    'users_model' => "\\App\\Models\\User",
    'roles_model' => "\\Spatie\\Permission\\Models\\Role",
    'enabled' => true,
    'default_strategy' => 'single',

    'api' => [
        'prefix' => 'approvals',
        'middleware' => ['api', 'auth:sanctum'],
    ],
];
```

---

## 💡 Exemplos de Uso Real

### Dashboard de Aprovações

```php
public function dashboard()
{
    return view('approvals.dashboard', [
        'pending' => Approval::pending()
            ->whereJsonContains('data->approvers', auth()->id())
            ->recent()
            ->paginate(10),

        'myRequests' => Approval::requestedBy(auth()->id())
            ->recent()
            ->paginate(10),
    ]);
}
```

### Integração Mobile (React Native)

```javascript
const approvalService = {
  async getPending(token) {
    const res = await fetch('/api/approvals?status=pending', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    return res.json();
  },

  async approve(id, userId, token) {
    return fetch(`/api/approvals/${id}/approve`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ approver_id: userId })
    });
  }
};
```

---

## 🎓 Requisitos

- PHP 8.2+
- Laravel 11.x ou 12.x
- MySQL, PostgreSQL, ou SQLite

---

## 🤝 Contribuir

Contribuições são bem-vindas! Por favor:

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

---

## 📝 Changelog

### v0.1.0 (Atual)

**Funcionalidades:**
- ✅ Sistema completo de aprovações
- ✅ Três estratégias (single, majority, unanimous)
- ✅ Aprovações multi-nível com roles
- ✅ API REST completa
- ✅ 11 Query Scopes
- ✅ 4 Eventos
- ✅ Soft Deletes
- ✅ Exceções customizadas
- ✅ Form Requests e Resources
- ✅ 55 testes (100% cobertura)

---

## 🐛 Problemas e Suporte

- **Issues:** https://github.com/bitoliveira/laravel-info-approval/issues
- **Segurança:** Veja [SECURITY.md](./SECURITY.md)

---

## 📄 Licença

Este projeto está sob a licença MIT. Veja [LICENSE](./LICENSE) para mais detalhes.

---

## 👤 Autor

**Bruno Oliveira**
- Email: bit.oliveira.dev@gmail.com
- GitHub: [@bitoliveira](https://github.com/bitoliveira)

---

## ⭐ Apoie o Projeto

Se este package foi útil para você, considere dar uma ⭐ no GitHub!

---

**Desenvolvido com ❤️ para a comunidade Laravel**
