# Relatório de Telas e Status de CRUD

Data: 2026-01-09

## Resumo
- Total de telas principais: 7

## Telas
- Dashboard: [views/dashboard.php](views/dashboard.php)
  - Tipo: Visão geral
  - CRUD: Não se aplica

- Clientes: [views/clientes.php](views/clientes.php)
  - Tipo: Gestão de clientes
  - CRUD: Create ✅, Read ✅, Update ❌, Delete ✅
  - Observações: Botões "visualizar" e "editar" exibem mensagem "Funcionalidade em desenvolvimento"; não há endpoint de atualização no [ajax/handler.php](ajax/handler.php).

- Processos: [views/processos.php](views/processos.php)
  - Tipo: Gestão de processos e prazos
  - CRUD: Create ✅, Read ✅, Update ❌, Delete ✅
  - Observações: Botão "ver prazos" chama `verPrazos()` que não está implementado no arquivo; não há endpoint de atualização de processo no [ajax/handler.php](ajax/handler.php). Há atualização de status de evento (`atualizar_status_evento`).

- Financeiro: [views/financeiro.php](views/financeiro.php)
  - Tipo: Honorários e Contas a Receber (Parcelas)
  - Honorários: Create ✅, Read parcial ❗, Update ❌, Delete ❌
  - Parcelas: Create (automático via honorários) ✅, Read ✅, Update parcial (registrar pagamento) ✅, Delete ❌
  - Observações: Falta listagem/edição/remoção de honorários; não há exclusão de parcelas.

- Calculadoras: [views/calculadoras.php](views/calculadoras.php)
  - Tipo: Links externos para ferramentas
  - CRUD: Não se aplica

- Buscador Processual: [buscador.php](buscador.php)
  - Tipo: Consulta de processos por tribunal
  - CRUD: Não se aplica
  - Observações: Funciona fora do roteamento do `index.php`. A aba `buscador` em [index.php](index.php) não possui view correspondente em `views/`.

- Login: [login.php](login.php)
  - Tipo: Autenticação
  - CRUD: Não se aplica

## Roteamento
- [index.php](index.php) utiliza `?aba=` para carregar arquivos em `views/`. Abas válidas: `dashboard`, `clientes`, `processos`, `buscador`, `financeiro`, `calculadoras`.
- Não existe [views/buscador.php](views/buscador.php); o header aponta diretamente para [buscador.php](buscador.php). Ao acessar `index.php?aba=buscador`, a mensagem será "Aba não encontrada". Sugestão: criar `views/buscador.php` com redirecionamento para `buscador.php` ou remover `buscador` de `abas_validas` em [index.php](index.php).

## Endpoints AJAX mapeados
- Clientes (em [ajax/handler.php](ajax/handler.php)):
  - `cadastrar_cliente` (Create)
  - `excluir_cliente` (Delete)
  - Pendentes: `atualizar_cliente` (Update), `obter_cliente` (Read detalhado)

- Processos (em [ajax/handler.php](ajax/handler.php)):
  - `cadastrar_processo` (Create)
  - `excluir_processo` (Delete)
  - `atualizar_status_evento` (Update de evento)
  - Pendentes: `atualizar_processo` (Update), listagem detalhada de eventos (`listar_eventos`) para modal/visualização

- Financeiro (em [ajax/handler.php](ajax/handler.php)):
  - `cadastrar_honorario` (Create)
  - `registrar_pagamento` (Update de parcela)
  - Pendentes: `excluir_honorario` (Delete), `atualizar_honorario` (Update), `excluir_parcela` (Delete)

## Conclusão
- Telas com CRUD completo: Nenhuma.
- Telas com CRUD parcial: Clientes, Processos, Financeiro.
- Próximos passos recomendados:
  - Implementar visualização detalhada e edição em Clientes e Processos (UI + endpoints `atualizar_*`).
  - Adicionar exclusão e edição de Honorários e, opcionalmente, exclusão de Parcelas.
  - Ajustar roteamento da aba `buscador` para evitar "Aba não encontrada" no `index.php`.
