# Resumo do Projeto — 12 Jan 2026

Este documento apresenta um índice resumido e observações rápidas do projeto localizado em `v2`.

## Resumo rápido
- Aplicação PHP monolítica para gestão jurídica (clientes, processos, prazos, financeiro).
- Frontend com Bootstrap + JS (assets em `public/`).

## Estrutura principal
- `index.php` — entrada do sistema; controla abas/views.
- `login.php` — formulário e autenticação.
- `buscador.php` — buscador processual que carrega buscadores por tribunal.
- `ajax/handler.php` — endpoints AJAX para CRUD (clientes, processos, eventos, financeiro).
- `views/` — páginas parciais carregadas por `index.php`.
- `includes/` — funções utilitárias e classes (ex.: `CalculadoraDatas.php`, `functions.php`, `header.php`).
- `sistemas/` — configuração, autenticação, licenças e integração PHPMailer.
- `config/` — `database.php` com conexão e criação de tabelas.
- `public/` — `css/` e `js/` (assets públicos).
- `buscadores/` — adaptadores por tribunal usados por `buscador.php`.

## Principais arquivos (resumo)
- `index.php`: inicia sessão, protege página (`protegerPagina`), gera token CSRF e inclui views.
- `login.php`: valida CSRF e chama `fazerLogin()` de `sistemas/auth.php`.
- `sistemas/config.php`: configuração central (DB, DEBUG_MODE, sessão, helpers de log/DB singleton).
- `sistemas/auth.php`: lógica de autenticação, validação de licenças (`licencas`), criação/validação de tokens e envio de emails via PHPMailer.
- `config/database.php`: outra camada de conexão (`conectarBanco`) e `criarTabelas()` para esquemas principais.
- `includes/functions.php`: utilitários (formatação de data/moeda, validação CPF/CNPJ, sanitização, etc.).
- `includes/CalculadoraDatas.php`: cálculo de prazos úteis/corridos considerando feriados (consulta a DB externa).
- `ajax/handler.php`: todas as ações AJAX; verifica CSRF e permissões por `usuario_id` na sessão.
- `includes/header.php`: header, drawer mobile e modal de perfil (contém scripts para profile/CEP).
- `public/js/app.js`: máscaras, validações, debounce, UI helpers e inicialização do drawer.

## Riscos e pontos de atenção
1. Segredos hardcoded: credenciais de BD e SMTP em `sistemas/config.php`, `config/database.php`, e `sistemas/auth.php`.
2. Criação automática de tabelas em runtime (`criarTabelas`, `criarTabelaUsuarios`) — útil em dev, arriscado em produção.
3. Logs e arquivos (`logs/`, `webhook_licencas.log`) podem armazenar dados sensíveis; revisar permissões/rotina de limpeza.
4. Supressão de erros com `@` em alguns pontos dificulta diagnóstico.
5. Dois mecanismos de conexão (singleton `getDBConnection()` e `conectarBanco()`) — considerar consolidar.

## Recomendações rápidas
- Remover/rotacionar segredos e passar via variáveis de ambiente (ex.: `.env`, `$_ENV`).
- Substituir execução automática de migrações por um sistema de migrations (Flyway, Phinx, manual SQL controlado).
- Auditar logs e definir regras de retenção/permissões.
- Consolidar funções de conexão ao BD.
- Executar scan por strings sensíveis (senhas, chaves, tokens) e revisar uso do PHPMailer em produção.

## Próximos passos (opções)
- Gerar relatório detalhado por arquivo (linhas, responsabilidades, riscos específicos).
- Fazer scan automático por segredos no repositório.
- Criar checklist de hardening (config de sessão, headers de segurança, CSP, X-Frame, etc.).

---
Arquivo gerado automaticamente em 2026-01-12 para revisão rápida.
