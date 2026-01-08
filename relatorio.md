# Relatório de Análise do Projeto - Precifex Jurídico

**Data:** 07/01/2026  
**Versão:** 1.0 Beta  
**Analista:** Antigravity AI

---

## 📋 Sumário Executivo

O **Precifex Jurídico** é um sistema de gestão completo para escritórios de advocacia, desenvolvido em PHP com arquitetura MVC simplificada. O sistema oferece funcionalidades de gestão de clientes, processos, prazos processuais, financeiro e um buscador processual integrado com o TJGO (Tribunal de Justiça de Goiás) via Projudi.

### Principais Características
- ✅ Sistema multi-usuário com autenticação
- ✅ Gestão completa de clientes e processos
- ✅ Calculadora de prazos processuais com feriados
- ✅ Controle financeiro (honorários e parcelas)
- ✅ Buscador processual automatizado (TJGO Projudi)
- ✅ Interface responsiva e moderna
- ✅ Proteção CSRF e segurança de sessão

---

## 🏗️ Arquitetura do Sistema

### Estrutura de Diretórios

```
juridico/
├── ajax/
│   └── handler.php              # Processamento de requisições AJAX
├── buscadores/
│   └── tjgo_projudi.php         # Integração com TJGO Projudi
├── config/
│   └── database.php             # Configuração e conexão com banco de dados
├── includes/
│   ├── CalculadoraDatas.php     # Classe para cálculo de prazos
│   ├── functions.php            # Funções auxiliares do sistema
│   └── header.php               # Cabeçalho/navegação compartilhado
├── public/
│   ├── css/
│   │   └── style.css            # Estilos principais
│   └── js/
│       └── app.js               # JavaScript global
├── views/
│   ├── calculadoras.php         # Interface de calculadoras
│   ├── clientes.php             # Gestão de clientes
│   ├── dashboard.php            # Painel principal
│   ├── financeiro.php           # Controle financeiro
│   └── processos.php            # Gestão de processos
├── buscador.php                 # Página standalone do buscador
├── index.php                    # Ponto de entrada principal
└── favicon.ico
```

### Padrão de Arquitetura

O sistema utiliza uma **arquitetura MVC simplificada**:

- **Model**: Acesso a dados via PDO com prepared statements
- **View**: Arquivos PHP em `/views/` com HTML/PHP misto
- **Controller**: Lógica em `index.php`, `buscador.php` e `ajax/handler.php`

---

## 🗄️ Banco de Dados

### Configuração

- **Host**: 77.37.126.7:3306
- **Database**: `juridico`
- **Charset**: UTF-8 (utf8mb4)
- **Engine**: InnoDB com suporte a transações

### Esquema de Tabelas

#### 1. **clientes**
Armazena informações dos clientes do escritório.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | INT AUTO_INCREMENT | Chave primária |
| usuario_id | VARCHAR(100) | ID do usuário proprietário |
| tipo | ENUM('pf', 'pj') | Pessoa física ou jurídica |
| nome | VARCHAR(200) | Nome completo |
| cpf_cnpj | VARCHAR(18) | CPF ou CNPJ |
| email | VARCHAR(150) | E-mail de contato |
| telefone, celular, whatsapp | VARCHAR(20) | Contatos |
| cep, endereco, numero, complemento, bairro, cidade, estado | VARCHAR | Endereço completo |
| status | ENUM('ativo', 'inativo') | Status do cliente |
| observacoes | TEXT | Observações gerais |
| data_criacao, data_atualizacao | TIMESTAMP | Auditoria |

**Índices**: usuario_id, nome, cpf_cnpj

#### 2. **processos**
Gerencia os processos jurídicos.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | INT AUTO_INCREMENT | Chave primária |
| usuario_id | VARCHAR(100) | ID do usuário proprietário |
| cliente_id | INT | FK para clientes |
| numero_processo | VARCHAR(255) | Número do processo |
| tribunal | VARCHAR(100) | Tribunal competente |
| vara | VARCHAR(255) | Vara judicial |
| tipo_acao | VARCHAR(150) | Tipo de ação |
| parte_contraria | VARCHAR(255) | Nome da parte contrária |
| valor_causa | DECIMAL(15,2) | Valor da causa |
| status | ENUM | em_andamento, suspenso, arquivado |
| observacoes | TEXT | Observações |
| data_criacao, data_atualizacao | TIMESTAMP | Auditoria |

**Índices**: usuario_id, cliente_id, numero_processo, status

#### 3. **eventos** (Prazos Processuais)
Controla prazos e eventos dos processos.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | INT AUTO_INCREMENT | Chave primária |
| processo_id | INT | FK para processos |
| descricao | VARCHAR(255) | Descrição do prazo |
| data_inicial | DATE | Data inicial |
| prazo_dias | INT | Quantidade de dias |
| tipo_contagem | ENUM('uteis', 'corridos') | Tipo de contagem |
| metodologia | ENUM | exclui_inicio, inclui_inicio |
| data_final | DATE | Data final calculada |
| status | ENUM | pendente, cumprido, perdido |
| ordem | INT | Ordem de exibição |

**Índices**: processo_id, data_final, status

#### 4. **honorarios**
Gerencia contratos de honorários.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | INT AUTO_INCREMENT | Chave primária |
| usuario_id | VARCHAR(100) | ID do usuário |
| cliente_id | INT | FK para clientes |
| processo_id | INT | FK para processos (opcional) |
| descricao | VARCHAR(255) | Descrição |
| tipo | ENUM | fixo, parcelado, exito |
| valor_total | DECIMAL(15,2) | Valor total |
| numero_parcelas | INT | Número de parcelas |
| valor_parcela | DECIMAL(15,2) | Valor por parcela |

**Índices**: usuario_id, cliente_id

#### 5. **parcelas**
Controla contas a receber.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | INT AUTO_INCREMENT | Chave primária |
| honorario_id | INT | FK para honorarios |
| numero_parcela | INT | Número da parcela |
| valor | DECIMAL(15,2) | Valor |
| data_vencimento | DATE | Data de vencimento |
| data_pagamento | DATE | Data de pagamento |
| status | ENUM | pendente, pago, vencido |
| observacoes | TEXT | Observações |

**Índices**: honorario_id, data_vencimento, status

#### 6. **despesas**
Controla contas a pagar.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | INT AUTO_INCREMENT | Chave primária |
| usuario_id | VARCHAR(100) | ID do usuário |
| processo_id | INT | FK para processos (opcional) |
| descricao | VARCHAR(255) | Descrição |
| categoria | VARCHAR(100) | Categoria da despesa |
| valor | DECIMAL(15,2) | Valor |
| data_vencimento | DATE | Data de vencimento |
| data_pagamento | DATE | Data de pagamento |
| status | ENUM | pendente, pago |

**Índices**: usuario_id, data_vencimento, status

---

## 🔐 Segurança

### Implementações de Segurança

1. **Autenticação e Sessão**
   - Sistema de membros integrado (`SISTEMA_MEMBROS`)
   - Proteção de páginas via `protegerPagina('5776734')`
   - Sessão nomeada: `MEMBROS_SESSION`

2. **Proteção CSRF**
   - Token CSRF gerado em cada sessão
   - Validação em todas as requisições POST
   - Uso de `hash_equals()` para comparação segura

3. **Banco de Dados**
   - Prepared statements em todas as queries
   - Validação de permissões por `usuario_id`
   - Transações para operações complexas

4. **Validação de Entrada**
   - Sanitização com `htmlspecialchars()`
   - Validação de CPF/CNPJ
   - Validação de tipos e formatos

5. **Proteção contra Acesso Direto**
   - Constante `SISTEMA_MEMBROS` em todos os includes
   - Verificação em arquivos sensíveis

---

## 🎨 Frontend

### Tecnologias

- **Bootstrap 5.3.0**: Framework CSS responsivo
- **Bootstrap Icons 1.11.0**: Ícones
- **jQuery 3.7.0**: Manipulação DOM e AJAX
- **Chart.js 4.4.0**: Gráficos (preparado para uso)

### Design System

#### Paleta de Cores (CSS Variables)

```css
--primary-color: #2563eb    /* Azul principal */
--secondary-color: #64748b  /* Cinza secundário */
--success-color: #10b981    /* Verde sucesso */
--danger-color: #ef4444     /* Vermelho erro */
--warning-color: #f59e0b    /* Amarelo aviso */
--info-color: #3b82f6       /* Azul informação */
--light-bg: #f8fafc         /* Fundo claro */
--dark-text: #1e293b        /* Texto escuro */
--border-color: #e2e8f0     /* Bordas */
```

#### Componentes Principais

1. **Header com Navegação**
   - Gradiente azul (135deg, #1e40af → #2563eb)
   - Navegação por abas com estado ativo
   - Sticky no topo
   - Responsivo

2. **Cards Estatísticos**
   - Efeito hover com elevação
   - Ícones coloridos
   - Bordas laterais coloridas por categoria

3. **Formulários**
   - Bordas arredondadas (8px)
   - Focus state com sombra azul
   - Validação visual

4. **Tabelas**
   - Hover effect
   - Cabeçalhos com fundo claro
   - Responsivas

### Responsividade

- Breakpoint principal: 768px
- Navegação adaptável em mobile
- Cards empilhados em telas pequenas
- Tabelas com scroll horizontal

---

## ⚙️ Funcionalidades Principais

### 1. Dashboard

**Arquivo**: `views/dashboard.php`

**Estatísticas Exibidas**:
- Total de clientes ativos
- Total de processos em andamento
- Prazos próximos (7 dias)
- Valor a receber (contas pendentes)

**Alertas**:
- Contas vencidas (vermelho)
- Prazos urgentes (amarelo)

**Listas**:
- Prazos urgentes (próximos 7 dias)
- Próximos recebimentos
- Processos recentes

### 2. Gestão de Clientes

**Arquivo**: `views/clientes.php`

**Funcionalidades**:
- Cadastro de PF e PJ
- Validação de CPF/CNPJ
- Busca de CEP via ViaCEP
- Listagem com filtros
- Edição e exclusão

**Campos**:
- Dados pessoais (nome, CPF/CNPJ, contatos)
- Endereço completo
- Status (ativo/inativo)
- Observações

### 3. Gestão de Processos

**Arquivo**: `views/processos.php`

**Funcionalidades**:
- Cadastro de processos
- Vinculação com clientes
- Gestão de prazos processuais
- Calculadora de prazos integrada
- Listagem com filtros por status

**Dados do Processo**:
- Número do processo
- Tribunal e vara
- Tipo de ação
- Parte contrária
- Valor da causa
- Status (em andamento, suspenso, arquivado)

**Gestão de Prazos**:
- Múltiplos eventos por processo
- Cálculo automático de data final
- Tipos: dias úteis ou corridos
- Metodologias: inclui/exclui início
- Consideração de feriados nacionais e estaduais

### 4. Buscador Processual

**Arquivo**: `buscador.php`

**Integração**: TJGO Projudi

**Tipos de Busca**:
1. **Por Número de Processo**
   - Busca direta no sistema do tribunal
   - Extração de dados completos
   - Filtro de movimentações por termos

2. **Por Nome da Parte**
   - Lista processos encontrados
   - Exibe polo ativo/passivo
   - Data de distribuição
   - Busca detalhes sob demanda

**Dados Extraídos**:
- Número do processo
- Situação/Status
- Polo ativo (promovente)
- Polo passivo (promovido)
- Serventia
- Classe processual
- Assunto
- Valor da causa
- Fase processual
- Data de distribuição
- Movimentações completas

**Recursos**:
- Busca em lote (até 50 processos)
- Filtro de movimentações por palavras-chave
- Interface AJAX sem reload
- Tratamento de erros robusto
- Delay entre requisições (2s)

**Limitações Conhecidas**:
- Proteção anti-bot (Cloudflare Turnstile) pode bloquear busca por nome
- Apenas TJGO Projudi implementado (preparado para expansão)

### 5. Calculadora de Prazos

**Arquivo**: `includes/CalculadoraDatas.php`

**Classe**: `CalculadoraDatas`

**Funcionalidades**:
- Cálculo de dias úteis e corridos
- Metodologias: início incluso/excluso
- Integração com banco de feriados
- Suporte a feriados nacionais e estaduais
- Tribunais configuráveis

**Banco de Feriados**:
- Database separado: `calculadora`
- Host: 77.37.126.7:3306
- Tabela: `feriados` (data, descricao, abrangencia)

**Métodos Principais**:
```php
calcularDataFinal($dataInicial, $dias, $tipoContagem, $metodologia, $abrangencia)
obterTribunais()
```

### 6. Controle Financeiro

**Arquivo**: `views/financeiro.php`

**Funcionalidades**:
- Cadastro de honorários
- Tipos: fixo, parcelado, êxito
- Geração automática de parcelas
- Controle de recebimentos
- Registro de pagamentos
- Gestão de despesas

**Relatórios**:
- Contas a receber
- Contas vencidas
- Histórico de pagamentos
- Fluxo de caixa

---

## 🔄 Fluxo de Dados

### Requisições AJAX

**Handler**: `ajax/handler.php`

**Actions Disponíveis**:

1. **Clientes**
   - `cadastrar_cliente`
   - `excluir_cliente`

2. **Processos**
   - `cadastrar_processo` (com eventos)
   - `excluir_processo`
   - `atualizar_status_evento`

3. **Calculadora**
   - `calcular_data`

4. **Financeiro**
   - `cadastrar_honorario` (com parcelas)
   - `registrar_pagamento`

**Padrão de Resposta**:
```json
{
  "success": true|false,
  "message": "Mensagem de sucesso",
  "error": "Mensagem de erro",
  "data": {...}
}
```

### Integração com Tribunal

**Arquivo**: `buscadores/tjgo_projudi.php`

**Funções Principais**:
- `buscarProcessos($processos, $termos_busca, $tipo_busca)`
- `consultarProcessoPorNumero($numero_processo, $termos_busca)`
- `consultarProcessoPorNome($nome_parte, $termos_busca)`
- `extrairInformacoes($html, $numero_processo, $termos_busca)`
- `extrairListaProcessos($html, $termos_busca)`

**Tecnologia**:
- cURL para requisições HTTP
- DOMDocument e DOMXPath para parsing HTML
- Cookies para manutenção de sessão
- Headers customizados para simular navegador

**Tratamento de Erros**:
- Timeout de 30 segundos
- Verificação de código HTTP
- Detecção de proteção anti-bot
- Mensagens de erro descritivas

---

## 📊 Funções Auxiliares

**Arquivo**: `includes/functions.php`

### Estatísticas
- `obterEstatisticas($pdo, $usuario_id)`: Coleta dados para dashboard

### Formatação
- `formatarMoeda($valor)`: R$ 1.234,56
- `formatarData($data)`: dd/mm/YYYY
- `formatarDataHora($dataHora)`: dd/mm/YYYY HH:mm
- `formatarCPF($cpf)`: 123.456.789-01
- `formatarCNPJ($cnpj)`: 12.345.678/0001-90

### Validação
- `validarCPF($cpf)`: Validação completa com dígitos verificadores
- `validarCNPJ($cnpj)`: Validação simplificada

### Utilidades
- `sanitizar($string)`: htmlspecialchars + trim
- `calcularStatusParcela($data_vencimento, $data_pagamento)`
- `obterClasseStatus($status)`: Retorna classe CSS Bootstrap
- `obterCorPrazo($data_final)`: Retorna cor baseada em urgência
- `contarDiasUteis($data_inicial, $data_final)`
- `buscarNomeCliente($pdo, $cliente_id)`

---

## 🌐 JavaScript Global

**Arquivo**: `public/js/app.js`

### Funções Disponíveis

**Formatação**:
- `formatarMoeda(valor)`: Intl.NumberFormat pt-BR
- `formatarData(data)`: toLocaleDateString pt-BR

**Validação**:
- `validarCPF(cpf)`: Validação completa
- `validarCNPJ(cnpj)`: Validação simplificada
- `validarData(data)`: Formato dd/mm/aaaa

**Máscaras**:
- `mascaraTelefone(valor)`: (99) 9999-9999 ou (99) 99999-9999
- `mascaraCEP(valor)`: 99999-999

**Integração**:
- `buscarCEP(cep)`: Fetch ViaCEP API

**UI**:
- `mostrarSucesso(mensagem)`: Alert verde
- `mostrarErro(mensagem)`: Alert vermelho
- `confirmar(mensagem)`: Confirm dialog

**Utilidades**:
- `debounce(func, wait)`: Debounce para eventos

**Inicialização**:
- Máscaras automáticas em inputs tel
- Tooltips Bootstrap

---

## 🚀 Pontos Fortes

1. **Arquitetura Sólida**
   - Separação clara de responsabilidades
   - Código modular e reutilizável
   - Padrões de projeto bem aplicados

2. **Segurança**
   - Proteção CSRF implementada
   - Prepared statements em todas as queries
   - Validação de permissões por usuário
   - Sanitização de dados

3. **UX/UI**
   - Interface moderna e responsiva
   - Feedback visual consistente
   - Navegação intuitiva
   - Design system bem definido

4. **Funcionalidades Completas**
   - Gestão end-to-end de escritório
   - Automação de tarefas (cálculo de prazos, busca processual)
   - Controle financeiro robusto

5. **Escalabilidade**
   - Preparado para múltiplos tribunais
   - Estrutura de banco normalizada
   - Código preparado para expansão

---

## ⚠️ Pontos de Atenção

### 1. Credenciais Expostas

**Problema**: Credenciais de banco de dados hardcoded nos arquivos.

**Arquivos**:
- `config/database.php` (linhas 12-13)
- `includes/CalculadoraDatas.php` (linha 29)

**Recomendação**:
```php
// Usar variáveis de ambiente
$DB_CONFIG = [
    'host' => getenv('DB_HOST'),
    'username' => getenv('DB_USER'),
    'password' => getenv('DB_PASS'),
    'database' => getenv('DB_NAME')
];
```

### 2. Dependência Externa

**Problema**: Sistema de autenticação depende de arquivos externos:
- `../sistemas/config.php`
- `../sistemas/auth.php`

**Impacto**: Projeto não é standalone.

**Recomendação**: Documentar dependências ou integrar no projeto.

### 3. Buscador Processual

**Limitações**:
- Apenas TJGO Projudi implementado
- Vulnerável a mudanças no HTML do tribunal
- Proteção anti-bot pode bloquear buscas
- Sem cache de resultados

**Recomendações**:
- Implementar cache de buscas
- Sistema de retry com backoff
- Notificação quando tribunal muda estrutura
- Considerar APIs oficiais quando disponíveis

### 4. Tratamento de Erros

**Problema**: Alguns erros silenciosos em produção.

**Exemplo**: `database.php` linha 164
```php
} catch (PDOException $e) {
    // Silenciar erro se tabelas já existem
}
```

**Recomendação**: Log de erros mesmo quando silenciados.

### 5. Validações Frontend

**Problema**: Validações principalmente no backend.

**Impacto**: Experiência do usuário pode ser melhorada.

**Recomendação**: 
- Validação em tempo real no frontend
- Feedback imediato antes do submit

### 6. Performance

**Oportunidades**:
- Sem paginação em listagens
- Queries N+1 em alguns casos
- Sem cache de dados frequentes

**Recomendação**:
- Implementar paginação
- Otimizar queries com JOINs
- Cache de estatísticas do dashboard

### 7. Testes

**Problema**: Não há testes automatizados.

**Recomendação**:
- Testes unitários para funções críticas
- Testes de integração para AJAX
- Testes E2E para fluxos principais

---

## 📈 Recomendações de Melhoria

### Curto Prazo (1-2 semanas)

1. **Segurança**
   - [ ] Mover credenciais para variáveis de ambiente
   - [ ] Implementar rate limiting no buscador
   - [ ] Adicionar logs de auditoria

2. **UX**
   - [ ] Validações frontend em tempo real
   - [ ] Loading states mais claros
   - [ ] Confirmações antes de exclusões

3. **Performance**
   - [ ] Paginação em listagens
   - [ ] Índices adicionais no banco
   - [ ] Cache de estatísticas (5 min)

### Médio Prazo (1-2 meses)

1. **Funcionalidades**
   - [ ] Exportação de relatórios (PDF/Excel)
   - [ ] Notificações de prazos (email/push)
   - [ ] Anexos de documentos
   - [ ] Histórico de alterações

2. **Integrações**
   - [ ] Mais tribunais no buscador
   - [ ] API para integrações externas
   - [ ] Backup automático

3. **Qualidade**
   - [ ] Testes automatizados
   - [ ] CI/CD pipeline
   - [ ] Monitoramento de erros

### Longo Prazo (3-6 meses)

1. **Arquitetura**
   - [ ] Migração para framework moderno (Laravel/Symfony)
   - [ ] API REST completa
   - [ ] Frontend SPA (Vue/React)

2. **Recursos Avançados**
   - [ ] IA para análise de processos
   - [ ] Dashboard analytics avançado
   - [ ] App mobile nativo

3. **Escalabilidade**
   - [ ] Multi-tenancy
   - [ ] Microserviços
   - [ ] Cloud deployment

---

## 🔧 Requisitos Técnicos

### Servidor

- **PHP**: 7.4+ (recomendado 8.0+)
- **MySQL**: 5.7+ ou MariaDB 10.3+
- **Extensões PHP**:
  - PDO
  - pdo_mysql
  - curl
  - mbstring
  - xml
  - json

### Cliente

- **Navegadores**: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
- **JavaScript**: Habilitado
- **Cookies**: Habilitados

### Desenvolvimento

- **Ferramentas**:
  - Composer (para futuras dependências)
  - Git para versionamento
  - IDE com suporte PHP (VS Code, PhpStorm)

---

## 📝 Conclusão

O **Precifex Jurídico** é um sistema bem estruturado e funcional que atende às necessidades básicas e avançadas de um escritório de advocacia. A arquitetura é sólida, o código é limpo e organizado, e as funcionalidades são abrangentes.

### Destaques Positivos

✅ **Segurança**: Implementação robusta de proteções CSRF e SQL injection  
✅ **Funcionalidades**: Suite completa de gestão jurídica  
✅ **UX**: Interface moderna e responsiva  
✅ **Inovação**: Buscador processual automatizado  
✅ **Calculadora**: Sistema inteligente de prazos com feriados  

### Áreas de Melhoria

⚠️ **Credenciais**: Remover hardcoded credentials  
⚠️ **Testes**: Implementar testes automatizados  
⚠️ **Performance**: Otimizar queries e adicionar cache  
⚠️ **Documentação**: Expandir documentação técnica  

### Próximos Passos Sugeridos

1. Implementar as melhorias de curto prazo (segurança e UX)
2. Adicionar testes automatizados
3. Expandir buscador para outros tribunais
4. Desenvolver módulo de relatórios
5. Considerar migração para framework moderno

---

## 📞 Informações de Contato

**Suporte**: contato@precifex.com  
**Versão**: 1.0 Beta  
**Última Atualização**: 07/01/2026

---

**Relatório gerado por**: Antigravity AI  
**Metodologia**: Análise estática de código, revisão de arquitetura e boas práticas
