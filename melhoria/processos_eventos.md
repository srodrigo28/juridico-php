# Análise — Processos e Eventos

Data: 2026-01-15

## Arquivos principais identificados
- ajax/handler.php  — lógica CRUD e endpoints AJAX para `processos` e `eventos`.
- includes/processos_helper.php — funções auxiliares (validação, queries, resumos).
- views/processos.php — interface de cadastro/visualização e inclusão de eventos.
- scripts/criar_new_db.sql — esquema do banco (tabelas `processos`, `eventos`, `uploads`, chaves FK).

## Achados rápidos
- Estrutura DB: `processos` e `eventos` ligados por FK (`eventos.processo_id`) com `ON DELETE CASCADE` em alguns locais;
  uploads guardam `processo_id` e `evento_id`.
- Segurança básica: uso de `prepare()` com placeholders em quase todas as queries; validação CSRF no `handler.php` (hash_equals).
- Validação: existe `validar_processo_input()` que exige pelo menos um evento válido ao cadastrar processo — importante para UX/fluxo.
- Transações: `cadastrar_processo` utiliza transaction + commit/rollback (bom para consistência ao criar processos+eventos+uploads).
- Datas/prazos: cálculo centralizado via `CalculadoraDatas` (boa separação de responsabilidade).
- Uploads: validação por extensão e uso de `move_uploaded_file`; falta validação de MIME mais robusta e verificação de tamanho/corrupt.
- Frontend: `views/processos.php` contém markup e JS para adicionar eventos/uploads; formatos de data alternam entre `dd/mm/YYYY` e `YYYY-mm-dd` (date input), atenção à normalização.

## Problemas e riscos (prioridade alta)
- Validação de uploads: confiar apenas em extensão pode permitir arquivos maliciosos; validar MIME e limitar tamanho/paths é necessário.
- Conversão de valores numéricos (ex.: `valor_causa`) depende de replace simples — testar locais com diferentes locales/formatos.
- Erros silenciosos: várias operações suprimem exceções (try/catch vazio em algumas migrações) — pode mascarar problemas.
- Regras de autorização: a verificação de usuário é feita em muitas queries, mas revisar endpoints que usam joins/SELECTs para garantir consistência de ownership.

## Recomendações imediatas (curto prazo)
1. Reforçar segurança de uploads:
   - Validar MIME com `finfo_file()` além da extensão.
   - Aplicar limites de tamanho e renomear arquivos com IDs sem extensão confusa.
   - Armazenar uploads fora da raiz pública ou proteger via regras do servidor.
2. Normalização de datas/formatos:
   - Centralizar conversão de datas (entrada/saída) numa função util (evitar múltiplos formatos em handlers/views).
3. Validação e feedback UX:
   - Permitir cadastro de processo sem eventos (se for caso de uso válido) ou melhorar mensagem/UX atual que exige eventos.
4. Testes automatizados:
   - Adicionar testes unitários para `CalculadoraDatas` e testes de integração para endpoints críticos (`cadastrar_processo`, `cadastrar_evento`).

## Melhorias arquiteturais (médio prazo)
- Extrair lógica de negócio de `ajax/handler.php` para serviços/classes (ex.: `ProcessoService`, `EventoService`) para facilitar testes e manutenção.
- API: transformar endpoints em controlador REST organizado (rotas) ao invés de switch grande, melhorando legibilidade.
- Logging e monitoração de erros: adicionar logging estruturado para operações críticas (falhas em uploads, transações rollback).
- Políticas de soft-delete / auditoria: considerar manter histórico de eventos removidos para auditoria.

## Tarefas sugeridas imediatamente (próximo commit)
- [ ] Implementar validação MIME e limite de tamanho em uploads (arquivo: `ajax/handler.php` — trechos de upload processos/eventos).
- [ ] Centralizar parsing/formatacao de data (criar `includes/date_utils.php` ou similar).
- [ ] Adicionar testes mínimos para `CalculadoraDatas` (scripts de teste em `scripts/` ou `tests/`).
- [ ] Atualizar documentação interna (este arquivo) com exemplos de payloads e fluxo de criação de processo+eventos.

## Próximos passos que posso realizar agora (diga qual prefere)
- Implementar a validação de MIME e limites de tamanho nos handlers de upload.
- Refatorar `cadastrar_processo` para extrair criação de eventos para função separada e adicionar testes.
- Criar testes unitários para `CalculadoraDatas`.

---
Arquivo criado pelo assistente para iniciar a melhoria do módulo de `processos` e `eventos`.
