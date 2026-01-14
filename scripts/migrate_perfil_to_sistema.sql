-- Migration: copiar campos de usuarios_perfil para usuarios_sistema
-- Execute após atualizar o schema (criar_new_db.sql) em ambiente de teste primeiro.

START TRANSACTION;

-- 1) Inserir linhas em usuarios_sistema para emails que ainda não existem
INSERT INTO usuarios_sistema (email, usuario_id, nome, telefone, cep, endereco, cidade, estado, criado_em, atualizado_em)
SELECT up.email, up.usuario_id, up.nome, up.telefone, up.cep, up.endereco, up.cidade, up.estado, NOW(), NOW()
FROM usuarios_perfil up
LEFT JOIN usuarios_sistema us ON us.email = up.email
WHERE us.email IS NULL;

-- 2) Atualizar linhas existentes em usuarios_sistema com dados de usuarios_perfil quando estiverem ausentes
UPDATE usuarios_sistema us
JOIN usuarios_perfil up ON up.email = us.email
SET
    us.usuario_id = COALESCE(us.usuario_id, up.usuario_id),
    us.nome = COALESCE(us.nome, up.nome),
    us.telefone = COALESCE(us.telefone, up.telefone),
    us.cep = COALESCE(us.cep, up.cep),
    us.endereco = COALESCE(us.endereco, up.endereco),
    us.cidade = COALESCE(us.cidade, up.cidade),
    us.estado = COALESCE(us.estado, up.estado),
    us.atualizado_em = NOW();

COMMIT;

-- Observação:
-- - Este script não altera senhas, tokens ou licenças.
-- - Risco: se existirem emails duplicados ou divergências, revise antes de executar.
