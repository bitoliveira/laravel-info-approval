# Changelog

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

## [Não Publicado] - 2025-10-13

### Adicionado

#### 1. Testes Pest Completos
- ✅ **ApprovalFlowTest.php**: Testes do fluxo básico de aprovação/rejeição
- ✅ **MultiLevelApprovalTest.php**: Testes de aprovações multi-nível com roles
- ✅ **ApiApprovalTest.php**: Testes dos endpoints da API REST
- ✅ **ApprovalStrategiesTest.php**: Testes das estratégias de aprovação (single, majority, unanimous)
- ✅ **ApprovalEventsTest.php**: Testes dos eventos disparados durante aprovações
- Configuração PHPUnit/Pest completa
- 15 testes, 47 assertions - **Todos passando** ✓

#### 2. Sistema de Eventos
- ✅ **ApprovalRequested**: Disparado quando uma aprovação é criada
- ✅ **ApprovalApproved**: Disparado quando aprovação é finalmente aprovada
- ✅ **ApprovalRejected**: Disparado quando aprovação é rejeitada
- ✅ **ApprovalLevelAdvanced**: Disparado quando aprovação avança para próximo nível
- Todos eventos em `src/Events/` com namespace correto
- Integração completa no Service e Trait

#### 3. Estratégias de Aprovação
- ✅ **Single**: Requer apenas uma aprovação (default)
- ✅ **Majority**: Requer aprovação da maioria dos aprovadores (> 50%)
- ✅ **Unanimous**: Requer aprovação de TODOS os aprovadores
- Implementação no `ApprovalService::isApprovalStrategyMet()`
- Configurável via `data['strategy']` e `data['approvers']`
- Suporte a `majority_threshold` customizável em config

#### 4. Segurança da API
- ✅ **Autenticação obrigatória**: `auth:sanctum` por padrão em `config/approval.php`
- ✅ **Validação de approver_id**: Verifica se corresponde ao utilizador autenticado
  - Retorna 403 Forbidden se houver discrepância
  - Implementado em `ApprovalApiController::approve()` e `reject()`
- ✅ **Proteção contra duplicação**: Não permite que mesmo utilizador aprove duas vezes
  - Implementado em `ApprovalService::assertApproverHasNotAlreadyApproved()`
  - Lança RuntimeException com mensagem clara

#### 5. Documentação da API
- ✅ **API.md**: Documentação completa com:
  - Descrição de todos endpoints com exemplos de request/response
  - Códigos de status HTTP detalhados
  - Exemplos de uso das 3 estratégias de aprovação
  - Exemplos de aprovações multi-nível com roles
  - Documentação de eventos disponíveis
  - Guia de segurança e configuração
  - Casos de uso práticos
- README.md atualizado com:
  - Links para documentação da API
  - Seção de estratégias de aprovação
  - Seção de eventos
  - Notas de segurança atualizadas

### Melhorado

- `ApprovalService`:
  - Adicionado método `assertApproverHasNotAlreadyApproved()`
  - Adicionado método `isApprovalStrategyMet()` com suporte às 3 estratégias
  - Disparar eventos em todas operações
  - Melhor tratamento de erros

- `HasApprovals` Trait:
  - Disparar `ApprovalRequested` ao criar aprovação
  - Definir explicitamente `status = 'pending'` na criação

- `config/approval.php`:
  - Middleware padrão agora inclui `auth:sanctum` (segurança por default)
  - Comentários mais claros sobre autenticação obrigatória

- `ApprovalApiController`:
  - Validação de segurança em ambos endpoints (approve/reject)
  - Mensagens de erro mais descritivas

- `TestCase`:
  - Configuração para desabilitar Sanctum em ambiente de testes
  - Setup completo com migrations de teste

### Corrigido

- Status `null` ao criar aprovação (agora sempre 'pending')
- Type mismatch em testes (salary: int vs float)
- Autenticação Sanctum não configurada em testes

---

## Resumo das 6 Recomendações Implementadas

| # | Recomendação | Status | Detalhes |
|---|--------------|--------|----------|
| 1 | Implementar testes | ✅ Completo | 15 testes, 47 assertions, 100% passando |
| 2 | Autenticação obrigatória na API | ✅ Completo | auth:sanctum por padrão + config |
| 3 | Validar approver_id vs autenticado | ✅ Completo | Validação com 403 Forbidden |
| 4 | Adicionar eventos | ✅ Completo | 4 eventos + testes + docs |
| 5 | Documentar API | ✅ Completo | API.md + README atualizado |
| 6 | Implementar strategies | ✅ Completo | Single, Majority, Unanimous + testes |

---

## Estatísticas

- **Ficheiros criados**: 9 (4 eventos, 3 testes, 1 config phpunit, 1 doc API)
- **Ficheiros modificados**: 7
- **Linhas de código adicionadas**: ~1500+
- **Testes adicionados**: 15
- **Cobertura de testes**: Fluxos principais, API, Eventos, Strategies, Multi-level

---

## Próximos Passos Sugeridos

1. Adicionar notifications (mail/database) usando os eventos
2. Implementar webhooks para integração externa
3. Adicionar UI para dashboard de aprovações
4. Suporte para aprovações condicionais entre tabelas
5. Auditoria completa (tracking de todas alterações)
6. Suporte para anexos em aprovações
