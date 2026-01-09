# Relatório de Segurança e Boas Práticas

Este documento reúne recomendações para reforçar a segurança do projeto PHP e do MySQL, além de um script SQL para hardening das tabelas principais.

**Objetivo:** reduzir riscos (XSS, CSRF, brute force, exposed credentials), melhorar integridade de dados e preparar o ambiente para produção.

**Escopo:** referências aos arquivos atuais como `sistemas/config.php`, `sistemas/auth.php`, `includes/header.php`, e tabelas `usuarios_sistema`, `licencas`.

## Diagnóstico Atual (Resumo)
- **Sessões:** `session.cookie_httponly` ativado; `session.cookie_secure` agora condicional (bom). Falta `SameSite`.
- **Autenticação:** `password_hash(PASSWORD_ARGON2ID)` está em uso, mas com `SALT_SENHA` concatenado. `password_hash` já incorpora salt interno; considere usar um "pepper" secreto (HMAC) ao invés de um salt estático concatenado.
- **Tokens:** gerados com `random_bytes` (bom). Expiração aplicada a `token_senha` (bom).
- **SQL:** uso de PDO + prepared statements (bom). Índices podem ser aprimorados.
- **Logs:** arquivos em `sistemas/logs/`. Padronizar rotação e permissões.
- **SMTP:** credenciais estão no código. Move-las para variáveis de ambiente/arquivo seguro.
- **Headers de segurança:** não há CSP, XFO, XCTO, RP centralizados.
- **CSRF:** não há tokens de CSRF nas rotas/forms.
- **Rate limiting:** não há mecanismos contra brute-force no login.
- **HTTPS produção:** falta HSTS estrito.

## Recomendações PHP
- **Sessão:**
  - Ativar `session.cookie_samesite=Lax` (ou `Strict` para áreas sensíveis).
  - Regenerar ID da sessão após login (`session_regenerate_id(true)`).
  - Garantir `session.use_only_cookies=1` e desativar `url_rewriter.tags`.
- **Autenticação e senha:**
  - Remover concatenação de `SALT_SENHA` com a senha (opcional). Alternativa: aplicar um "pepper" com `hash_hmac('sha256', senha, SECRET_PEPPER)` antes do `password_hash` e manter o pepper fora do repositório.
  - Aplicar política de senha mínima (12+ caracteres) e `password_needs_rehash` quando o custo/algoritmo mudar.
- **CSRF:**
  - Criar `csrf_token` por sessão e validar em POSTs de login/alteração de senha.
- **XSS:**
  - Manter `htmlspecialchars` nas saídas e evitar interpolar HTML sem sanitização.
  - Definir uma **Content-Security-Policy** restritiva.
- **Headers de segurança (em `includes/header.php`):**
  - `Content-Security-Policy: default-src 'self'; img-src 'self' data:; script-src 'self'; style-src 'self' 'unsafe-inline'`
  - `X-Frame-Options: SAMEORIGIN`
  - `X-Content-Type-Options: nosniff`
  - `Referrer-Policy: no-referrer-when-downgrade`
  - `Strict-Transport-Security: max-age=31536000; includeSubDomains` (apenas em HTTPS produção)
- **Erros e logs:**
  - Em produção, `display_errors=0` e logging controlado; usar rotação e permissões 640.
- **Segredos e config:**
  - Carregar credenciais via `.env`/variáveis de ambiente e não em código.
- **Rate limiting:**
  - Contabilizar tentativas de login e bloquear temporariamente após N falhas.

## Melhorias MySQL
- **Charset/Collation:** `utf8mb4` e `utf8mb4_unicode_ci` em todas as tabelas.
- **Índices e chaves:**
  - `usuarios_sistema`: índices em `email`, `token_senha`, `token_expiry`; campos para `password_last_changed`, `password_attempts`, `locked_until`.
  - `licencas`: índices em `email`, `produto_id`, `status_licenca`, `data_expiracao`.
- **Integridade:**
  - Triggers para normalizar `email` (lowercase) em `INSERT/UPDATE`.
  - Evento diário para expirar licenças vencidas no banco (com `EVENT` scheduler).
- **Auditoria:**
  - Tabela `failed_logins` para monitorar tentativas e aplicar bloqueio.

## Script SQL de Hardening
O script abaixo é idempotente (verifica existência antes de alterar) e pode ser executado em MySQL 8+/MariaDB.

```
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
```

## Como Executar o Script
- Opção 1: via MySQL CLI

```bash
mysql -h <HOST> -u <USUARIO> -p clientes < scripts/seguranca.sql
```

- Opção 2: importar pelo phpMyAdmin (SQL) colando o conteúdo do script.

## Opções Pagas (Opcional)
- **WAF/CDN:** Cloudflare Pro para proteção DDoS, WAF e rate limiting.
- **Certificados/TLS:** certificados gerenciados ou EV/OV (se necessário).
- **Monitoramento/Logs:** serviços como Datadog/New Relic para observabilidade.
- **SMTP transacional:** serviços como SendGrid/Mailgun para melhor entregabilidade.

Cada item acima pode ser implementado conforme necessidade e orçamento, trazendo ganhos de segurança específicos.
