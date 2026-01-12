-- Merged: criar_banco.sql + script-kanban.sql
-- Database: adv

DROP DATABASE IF EXISTS adv;
CREATE DATABASE adv CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE adv;

-- Tabela de perfil de usuários
CREATE TABLE usuarios_perfil (
  usuario_id VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL,
  nome VARCHAR(200) NULL,
  criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (usuario_id),
  UNIQUE KEY uq_usuarios_perfil_email (email),
  INDEX idx_usuarios_perfil_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabelas do sistema de autenticação/licenças
CREATE TABLE usuarios_sistema (
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

CREATE TABLE licencas (
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

CREATE TABLE logs_emails_enviados (
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
CREATE TABLE clientes (
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

CREATE TABLE processos (
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

CREATE TABLE eventos (
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

CREATE TABLE honorarios (
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

CREATE TABLE parcelas (
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

CREATE TABLE despesas (
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

-- Tabela Kanban
CREATE TABLE kanban_cards (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_email VARCHAR(255) NOT NULL,
  titulo VARCHAR(255) NOT NULL,
  descricao TEXT,
  prioridade ENUM('alta','media','baixa') NOT NULL DEFAULT 'media',
  coluna ENUM('tarefas','doing','done') NOT NULL DEFAULT 'tarefas',
  data_prevista DATE NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_kanban_user_email (user_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuário admin com senha hash ARGON2ID (compatível com o sistema)
-- Email: rodrigoexer2@gmail.com
-- Senha: 123123
-- Hash gerado com SALT_SENHA: JLP_SISTEMAS_2025_SALT_HASH
INSERT INTO usuarios_sistema (email, senha, criado_em) 
VALUES ('rodrigoexer2@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$U1hneFJHeWdKQy9OcXNJTw$UpgxEWEgyelDa8q+5pg1ZrVpMjmccDA3cdfypZYfdsk', NOW());

-- Licença admin ativa
INSERT INTO licencas (email, produto_id, produto_nome, status_licenca, data_expiracao)
VALUES ('rodrigoexer2@gmail.com', '5776734', 'Precifex Jurídico', 'ativa', '2027-12-31');

-- Perfil admin com telefone
INSERT INTO usuarios_perfil (usuario_id, email, nome)
VALUES ('admin', 'rodrigoexer2@gmail.com', 'Rodrigo Administrador');

-- Cliente padrão do admin (para ter um cliente base)
INSERT INTO clientes (usuario_id, tipo, nome, email, celular, whatsapp, status)
VALUES ('admin', 'pf', 'Rodrigo Administrador', 'rodrigoexer2@gmail.com', '62998579084', '62998579084', 'ativo');

-- Cards de exemplo para o novo usuário
INSERT INTO kanban_cards (user_email, titulo, descricao, prioridade, coluna, data_prevista, criado_em) VALUES
('rodrigoexer2@gmail.com', 'Definir escopo inicial', 'Mapear requisitos do Kanban', 'alta', 'tarefas', '2026-01-15', '2026-01-10 09:00:00'),
('rodrigoexer2@gmail.com', 'Criar layout base', 'Estruturar colunas com Bootstrap', 'media', 'tarefas', '2026-01-16', '2026-01-10 14:30:00'),
('rodrigoexer2@gmail.com', 'Implementar arrastar e soltar', 'Habilitar DnD nativo', 'media', 'doing', '2026-01-17', '2026-01-11 10:00:00'),
('rodrigoexer2@gmail.com', 'Configurar páginas e menu', 'Adicionar link Kanban no header', 'baixa', 'done', '2026-01-14', '2026-01-09 16:00:00'),
('rodrigoexer2@gmail.com', 'Adicionar contadores por coluna', 'Badges com cores dinâmicas', 'baixa', 'tarefas', '2026-01-18', '2026-01-11 11:15:00'),
('rodrigoexer2@gmail.com', 'Integrar backend AJAX', 'CRUD completo de cards', 'alta', 'doing', '2026-01-19', '2026-01-11 12:00:00');