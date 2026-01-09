-- scripts/criar_banco.sql
-- Criação do banco e das tabelas necessárias para rodar local

-- Ajuste o nome do banco se necessário
CREATE DATABASE IF NOT EXISTS juridico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE juridico;

-- Tabelas do sistema de autenticação/licenças
CREATE TABLE IF NOT EXISTS usuarios_sistema (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) UNIQUE NOT NULL,
  senha VARCHAR(255) NULL,
  token_senha VARCHAR(64) NULL,
  token_expiry DATETIME NULL,
  ultimo_login DATETIME NULL,
  criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_token (token_senha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS licencas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL,
  produto_id VARCHAR(32) NOT NULL,
  produto_nome VARCHAR(255) NULL,
  status_licenca ENUM('ativa','inativa') DEFAULT 'ativa',
  data_expiracao DATE NULL,
  criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_produto (produto_id),
  INDEX idx_status (status_licenca),
  INDEX idx_expira (data_expiracao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS logs_emails_enviados (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL,
  tipo ENUM('pos_compra', 'manual', 'recuperacao') DEFAULT 'pos_compra',
  produto_nome VARCHAR(255) NULL,
  enviado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_tipo (tipo),
  INDEX idx_enviado (enviado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabelas da aplicação
CREATE TABLE IF NOT EXISTS clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id VARCHAR(100) NOT NULL,
  tipo ENUM('pf', 'pj') DEFAULT 'pf',
  nome VARCHAR(200) NOT NULL,
  cpf_cnpj VARCHAR(18),
  email VARCHAR(150),
  telefone VARCHAR(20),
  celular VARCHAR(20),
  whatsapp VARCHAR(20),
  cep VARCHAR(9),
  endereco VARCHAR(255),
  numero VARCHAR(10),
  complemento VARCHAR(100),
  bairro VARCHAR(100),
  cidade VARCHAR(100),
  estado VARCHAR(2),
  status ENUM('ativo', 'inativo') DEFAULT 'ativo',
  observacoes TEXT,
  data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_usuario (usuario_id),
  INDEX idx_nome (nome),
  INDEX idx_cpf_cnpj (cpf_cnpj)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS processos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id VARCHAR(100) NOT NULL,
  cliente_id INT,
  numero_processo VARCHAR(255) NOT NULL,
  tribunal VARCHAR(100) NOT NULL,
  vara VARCHAR(255),
  tipo_acao VARCHAR(150),
  parte_contraria VARCHAR(255),
  valor_causa DECIMAL(15,2),
  status ENUM('em_andamento', 'suspenso', 'arquivado') DEFAULT 'em_andamento',
  observacoes TEXT,
  data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
  INDEX idx_usuario (usuario_id),
  INDEX idx_cliente (cliente_id),
  INDEX idx_numero (numero_processo),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS eventos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  processo_id INT NOT NULL,
  descricao VARCHAR(255) NOT NULL,
  data_inicial DATE NOT NULL,
  prazo_dias INT NOT NULL,
  tipo_contagem ENUM('uteis', 'corridos') DEFAULT 'uteis',
  metodologia ENUM('exclui_inicio', 'inclui_inicio') DEFAULT 'exclui_inicio',
  data_final DATE NOT NULL,
  status ENUM('pendente', 'cumprido', 'perdido') DEFAULT 'pendente',
  ordem INT DEFAULT 0,
  data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (processo_id) REFERENCES processos(id) ON DELETE CASCADE,
  INDEX idx_processo (processo_id),
  INDEX idx_data_final (data_final),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS honorarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id VARCHAR(100) NOT NULL,
  cliente_id INT NOT NULL,
  processo_id INT,
  descricao VARCHAR(255),
  tipo ENUM('fixo', 'parcelado', 'exito') DEFAULT 'fixo',
  valor_total DECIMAL(15,2) NOT NULL,
  numero_parcelas INT DEFAULT 1,
  valor_parcela DECIMAL(15,2),
  data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
  FOREIGN KEY (processo_id) REFERENCES processos(id) ON DELETE SET NULL,
  INDEX idx_usuario (usuario_id),
  INDEX idx_cliente (cliente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS parcelas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  honorario_id INT NOT NULL,
  numero_parcela INT NOT NULL,
  valor DECIMAL(15,2) NOT NULL,
  data_vencimento DATE NOT NULL,
  data_pagamento DATE,
  status ENUM('pendente', 'pago', 'vencido') DEFAULT 'pendente',
  observacoes TEXT,
  data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (honorario_id) REFERENCES honorarios(id) ON DELETE CASCADE,
  INDEX idx_honorario (honorario_id),
  INDEX idx_vencimento (data_vencimento),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS despesas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id VARCHAR(100) NOT NULL,
  processo_id INT,
  descricao VARCHAR(255) NOT NULL,
  categoria VARCHAR(100),
  valor DECIMAL(15,2) NOT NULL,
  data_vencimento DATE NOT NULL,
  data_pagamento DATE,
  status ENUM('pendente', 'pago') DEFAULT 'pendente',
  observacoes TEXT,
  data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (processo_id) REFERENCES processos(id) ON DELETE SET NULL,
  INDEX idx_usuario (usuario_id),
  INDEX idx_vencimento (data_vencimento),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuário administrador padrão (insira a senha via script PHP)
INSERT INTO licencas (email, produto_id, produto_nome, status_licenca)
VALUES ('admin@local.test', '5776734', 'Precifex Jurídico', 'ativa')
ON DUPLICATE KEY UPDATE status_licenca='ativa', atualizado_em=NOW();

-- Cria um registro do admin sem senha (será definida pelo script PHP)
INSERT INTO usuarios_sistema (email, criado_em)
VALUES ('admin@local.test', NOW())
ON DUPLICATE KEY UPDATE atualizado_em=NOW();
