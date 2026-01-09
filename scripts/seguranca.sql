-- scripts/seguranca.sql

-- Charset padrão
SET NAMES utf8mb4;
SET collation_connection = 'utf8mb4_unicode_ci';

-- Garantir collation das tabelas principais (ajuste conforme necessário)
ALTER TABLE usuarios_sistema CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE licencas CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Adicionar colunas (se não existirem) em usuarios_sistema
SELECT COUNT(*) INTO @c1 FROM INFORMATION_SCHEMA.COLUMNS 
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios_sistema' AND COLUMN_NAME = 'password_last_changed';
SET @s1 = IF(@c1=0, 'ALTER TABLE usuarios_sistema ADD COLUMN password_last_changed DATETIME NULL AFTER senha;', 'SELECT 1');
PREPARE stmt1 FROM @s1; EXECUTE stmt1; DEALLOCATE PREPARE stmt1;

SELECT COUNT(*) INTO @c2 FROM INFORMATION_SCHEMA.COLUMNS 
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios_sistema' AND COLUMN_NAME = 'password_attempts';
SET @s2 = IF(@c2=0, 'ALTER TABLE usuarios_sistema ADD COLUMN password_attempts INT NOT NULL DEFAULT 0 AFTER password_last_changed;', 'SELECT 1');
PREPARE stmt2 FROM @s2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

SELECT COUNT(*) INTO @c3 FROM INFORMATION_SCHEMA.COLUMNS 
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios_sistema' AND COLUMN_NAME = 'locked_until';
SET @s3 = IF(@c3=0, 'ALTER TABLE usuarios_sistema ADD COLUMN locked_until DATETIME NULL AFTER password_attempts;', 'SELECT 1');
PREPARE stmt3 FROM @s3; EXECUTE stmt3; DEALLOCATE PREPARE stmt3;

-- Índices em usuarios_sistema
SELECT COUNT(*) INTO @i1 FROM INFORMATION_SCHEMA.STATISTICS 
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios_sistema' AND INDEX_NAME = 'idx_token_expiry';
SET @si1 = IF(@i1=0, 'ALTER TABLE usuarios_sistema ADD INDEX idx_token_expiry (token_expiry);', 'SELECT 1');
PREPARE st1 FROM @si1; EXECUTE st1; DEALLOCATE PREPARE st1;

-- Índices em licencas
SELECT COUNT(*) INTO @i2 FROM INFORMATION_SCHEMA.STATISTICS 
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'licencas' AND INDEX_NAME = 'idx_email';
SET @si2 = IF(@i2=0, 'ALTER TABLE licencas ADD INDEX idx_email (email);', 'SELECT 1');
PREPARE st2 FROM @si2; EXECUTE st2; DEALLOCATE PREPARE st2;

SELECT COUNT(*) INTO @i3 FROM INFORMATION_SCHEMA.STATISTICS 
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'licencas' AND INDEX_NAME = 'idx_produto';
SET @si3 = IF(@i3=0, 'ALTER TABLE licencas ADD INDEX idx_produto (produto_id);', 'SELECT 1');
PREPARE st3 FROM @si3; EXECUTE st3; DEALLOCATE PREPARE st3;

SELECT COUNT(*) INTO @i4 FROM INFORMATION_SCHEMA.STATISTICS 
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'licencas' AND INDEX_NAME = 'idx_status';
SET @si4 = IF(@i4=0, 'ALTER TABLE licencas ADD INDEX idx_status (status_licenca);', 'SELECT 1');
PREPARE st4 FROM @si4; EXECUTE st4; DEALLOCATE PREPARE st4;

SELECT COUNT(*) INTO @i5 FROM INFORMATION_SCHEMA.STATISTICS 
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'licencas' AND INDEX_NAME = 'idx_expiracao';
SET @si5 = IF(@i5=0, 'ALTER TABLE licencas ADD INDEX idx_expiracao (data_expiracao);', 'SELECT 1');
PREPARE st5 FROM @si5; EXECUTE st5; DEALLOCATE PREPARE st5;

-- Tabela de tentativas de login
CREATE TABLE IF NOT EXISTS failed_logins (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL,
  ip VARCHAR(45) NULL,
  ocorrida_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_data (ocorrida_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Triggers para normalizar email em usuarios_sistema
DROP TRIGGER IF EXISTS usuarios_sistema_email_lower_ins;
DELIMITER $$
CREATE TRIGGER usuarios_sistema_email_lower_ins
BEFORE INSERT ON usuarios_sistema FOR EACH ROW
BEGIN
  SET NEW.email = LOWER(NEW.email);
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS usuarios_sistema_email_lower_upd;
DELIMITER $$
CREATE TRIGGER usuarios_sistema_email_lower_upd
BEFORE UPDATE ON usuarios_sistema FOR EACH ROW
BEGIN
  SET NEW.email = LOWER(NEW.email);
END$$
DELIMITER ;

-- Evento diário para expirar licenças vencidas
CREATE EVENT IF NOT EXISTS licencas_expirar_diariamente
ON SCHEDULE EVERY 1 DAY
DO
  UPDATE licencas 
    SET status_licenca = 'inativa', atualizado_em = NOW()
  WHERE status_licenca = 'ativa' 
    AND data_expiracao IS NOT NULL 
    AND data_expiracao < CURDATE();

-- Observação: habilite o event scheduler se necessário
-- SET GLOBAL event_scheduler = ON;
