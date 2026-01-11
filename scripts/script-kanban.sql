-- Seed Kanban Cards
-- Ajuste o email abaixo para o usuário que verá as tarefas
SET @user_email = 'admin@local.test';
-- SET @user_email = 'admin@local.test';

-- Opcional: cria o perfil do usuário se não existir (usa parte local do email como usuario_id)
INSERT INTO usuarios_perfil (usuario_id, email, nome)
SELECT SUBSTRING_INDEX(@user_email, '@', 1), @user_email, 'Usuário Seed'
WHERE NOT EXISTS (SELECT 1 FROM usuarios_perfil WHERE email = @user_email);

-- Cria a tabela kanban_cards (se ainda não existir)
CREATE TABLE IF NOT EXISTS kanban_cards (
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

-- Opcional: adiciona FK para usuarios_perfil(email) (execute apenas se desejar a integridade por email)
-- ALTER TABLE kanban_cards
--   ADD CONSTRAINT fk_kanban_user_email
--   FOREIGN KEY (user_email) REFERENCES usuarios_perfil(email)
--   ON DELETE CASCADE ON UPDATE CASCADE;

-- Limpeza opcional para evitar duplicações em múltiplas execuções deste seed
-- Exclui apenas tarefas com este email e títulos conhecidos
DELETE FROM kanban_cards
WHERE user_email = @user_email
  AND titulo IN (
    'Definir escopo inicial',
    'Criar layout base',
    'Implementar arrastar e soltar',
    'Configurar páginas e menu',
    'Adicionar contadores por coluna',
    'Integrar backend AJAX'
  );

-- Inserções de exemplo (datas relativas à data atual do contexto: 2026-01-11)
INSERT INTO kanban_cards (user_email, titulo, descricao, prioridade, coluna, data_prevista, criado_em) VALUES
(@user_email, 'Definir escopo inicial', 'Mapear requisitos do Kanban, colunas e prioridades.', 'alta', 'tarefas', '2026-01-15', '2026-01-10 09:00:00'),
(@user_email, 'Criar layout base', 'Estruturar colunas, cabeçalhos e cartões com Bootstrap.', 'media', 'tarefas', '2026-01-16', '2026-01-10 14:30:00'),
(@user_email, 'Implementar arrastar e soltar', 'Habilitar DnD nativo, ordenação por prioridade e data.', 'media', 'doing', '2026-01-17', '2026-01-11 10:00:00'),
(@user_email, 'Configurar páginas e menu', 'Adicionar link Kanban no header e no index.', 'baixa', 'done', '2026-01-14', '2026-01-09 16:00:00'),
(@user_email, 'Adicionar contadores por coluna', 'Badges com cores e atualização dinâmica.', 'baixa', 'tarefas', '2026-01-18', '2026-01-11 11:15:00'),
(@user_email, 'Integrar backend AJAX', 'CRUD completo: listar, criar, editar, mover, excluir.', 'alta', 'doing', '2026-01-19', '2026-01-11 12:00:00');
