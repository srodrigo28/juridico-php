-- Migration: cria a tabela `uploads` se não existir
-- Execute em seu banco: mysql -u usuario -p nome_banco < add_uploads_table.sql

CREATE TABLE IF NOT EXISTS `uploads` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cliente_id` int DEFAULT NULL,
  `processo_id` int NOT NULL,
  `evento_id` int DEFAULT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `arquivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tamanho` int DEFAULT NULL,
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_uploads_processo` (`processo_id`),
  KEY `idx_uploads_evento` (`evento_id`),
  KEY `idx_uploads_cliente` (`cliente_id`),
  CONSTRAINT `uploads_fk_processo` FOREIGN KEY (`processo_id`) REFERENCES `processos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `uploads_fk_evento` FOREIGN KEY (`evento_id`) REFERENCES `eventos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `uploads_fk_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
