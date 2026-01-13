-- MySQL dump 10.13  Distrib 8.0.44, for Linux (x86_64)
--
-- Host: localhost    Database: adv
-- ------------------------------------------------------
-- Server version	8.0.44-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('pf','pj') COLLATE utf8mb4_unicode_ci DEFAULT 'pf',
  `nome` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cpf_cnpj` varchar(18) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `celular` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cep` varchar(9) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `complemento` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bairro` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cidade` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('ativo','inativo') COLLATE utf8mb4_unicode_ci DEFAULT 'ativo',
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_nome` (`nome`),
  KEY `idx_cpf_cnpj` (`cpf_cnpj`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES (1,'admin','pf','Rodrigo Administrador',NULL,'rodrigoexer2@gmail.com',NULL,'62998579084','62998579084',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'ativo',NULL,'2026-01-12 10:42:42','2026-01-12 10:42:42'),(2,'416ec8bf5be5c47a7d7f42a65a97097f','pf','Ana Olivia','026.529.331-99','anaoliveira@gmail.com','(62) 98592-1140','(62) 98592-1140',NULL,'74961-070','Rua 5','18','Quadra 18 Lote 08','Jardim Tiradentes','Aparecida de Goiânia','GO','inativo','Cliente novo','2026-01-12 10:50:57','2026-01-12 18:20:04'),(3,'416ec8bf5be5c47a7d7f42a65a97097f','pf','CONCEICAO CANDIDA ROCHA','788.132.205-53','jlucasop@hotmail.com','','(71) 99370-3770',NULL,'','','','','','','','ativo','Indicação de fulano de tal','2026-01-12 18:18:28','2026-01-12 18:36:55');
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `despesas`
--

DROP TABLE IF EXISTS `despesas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `despesas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `processo_id` int DEFAULT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categoria` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valor` decimal(15,2) NOT NULL,
  `data_vencimento` date NOT NULL,
  `data_pagamento` date DEFAULT NULL,
  `status` enum('pendente','pago') COLLATE utf8mb4_unicode_ci DEFAULT 'pendente',
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `processo_id` (`processo_id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_vencimento` (`data_vencimento`),
  KEY `idx_status` (`status`),
  CONSTRAINT `despesas_ibfk_1` FOREIGN KEY (`processo_id`) REFERENCES `processos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `despesas`
--

LOCK TABLES `despesas` WRITE;
/*!40000 ALTER TABLE `despesas` DISABLE KEYS */;
/*!40000 ALTER TABLE `despesas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `eventos`
--

DROP TABLE IF EXISTS `eventos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eventos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `processo_id` int NOT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_inicial` date NOT NULL,
  `prazo_dias` int NOT NULL,
  `tipo_contagem` enum('uteis','corridos') COLLATE utf8mb4_unicode_ci DEFAULT 'uteis',
  `metodologia` enum('exclui_inicio','inclui_inicio') COLLATE utf8mb4_unicode_ci DEFAULT 'exclui_inicio',
  `data_final` date NOT NULL,
  `status` enum('pendente','cumprido','perdido') COLLATE utf8mb4_unicode_ci DEFAULT 'pendente',
  `ordem` int DEFAULT '0',
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_processo` (`processo_id`),
  KEY `idx_data_final` (`data_final`),
  KEY `idx_status` (`status`),
  CONSTRAINT `eventos_ibfk_1` FOREIGN KEY (`processo_id`) REFERENCES `processos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `eventos`
--

LOCK TABLES `eventos` WRITE;
/*!40000 ALTER TABLE `eventos` DISABLE KEYS */;
/*!40000 ALTER TABLE `eventos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `honorarios`
--

DROP TABLE IF EXISTS `honorarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `honorarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cliente_id` int NOT NULL,
  `processo_id` int DEFAULT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo` enum('fixo','parcelado','exito') COLLATE utf8mb4_unicode_ci DEFAULT 'fixo',
  `valor_total` decimal(15,2) NOT NULL,
  `numero_parcelas` int DEFAULT '1',
  `valor_parcela` decimal(15,2) DEFAULT NULL,
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `processo_id` (`processo_id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_cliente` (`cliente_id`),
  CONSTRAINT `honorarios_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `honorarios_ibfk_2` FOREIGN KEY (`processo_id`) REFERENCES `processos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `honorarios`
--

LOCK TABLES `honorarios` WRITE;
/*!40000 ALTER TABLE `honorarios` DISABLE KEYS */;
/*!40000 ALTER TABLE `honorarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kanban_cards`
--

DROP TABLE IF EXISTS `kanban_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kanban_cards` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `prioridade` enum('alta','media','baixa') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'media',
  `coluna` enum('tarefas','doing','done') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tarefas',
  `data_prevista` date DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_kanban_user_email` (`user_email`),
  CONSTRAINT `fk_kanban_email` FOREIGN KEY (`user_email`) REFERENCES `usuarios_perfil` (`email`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kanban_cards`
--

LOCK TABLES `kanban_cards` WRITE;
/*!40000 ALTER TABLE `kanban_cards` DISABLE KEYS */;
INSERT INTO `kanban_cards` VALUES (1,'rodrigoexer2@gmail.com','Definir escopo inicial','Mapear requisitos do Kanban','alta','tarefas','2026-01-15','2026-01-10 09:00:00'),(2,'rodrigoexer2@gmail.com','Criar layout base','Estruturar colunas com Bootstrap','media','tarefas','2026-01-16','2026-01-10 14:30:00'),(4,'rodrigoexer2@gmail.com','Configurar páginas e menu','Adicionar link Kanban no header','baixa','tarefas','2026-01-14','2026-01-09 16:00:00'),(6,'rodrigoexer2@gmail.com','Integrar backend AJAX','CRUD completo de cards','alta','doing','2026-01-19','2026-01-11 12:00:00'),(7,'rodrigoexer2@gmail.com','Audiência','Audiência virtual do processo 5316834-61.2025.8.09.0051 às 14h00','alta','done','2026-01-14','2026-01-12 19:15:19');
/*!40000 ALTER TABLE `kanban_cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `licencas`
--

DROP TABLE IF EXISTS `licencas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `licencas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `produto_id` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `produto_nome` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_licenca` enum('ativa','inativa') COLLATE utf8mb4_unicode_ci DEFAULT 'ativa',
  `data_expiracao` date DEFAULT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_produto` (`produto_id`),
  KEY `idx_status` (`status_licenca`),
  KEY `idx_expira` (`data_expiracao`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `licencas`
--

LOCK TABLES `licencas` WRITE;
/*!40000 ALTER TABLE `licencas` DISABLE KEYS */;
INSERT INTO `licencas` VALUES (1,'rodrigoexer2@gmail.com','5776734','Precifex Jurídico','ativa','2027-12-31','2026-01-12 10:42:42','2026-01-12 10:42:42');
/*!40000 ALTER TABLE `licencas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs_emails_enviados`
--

DROP TABLE IF EXISTS `logs_emails_enviados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs_emails_enviados` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('pos_compra','manual','recuperacao') COLLATE utf8mb4_unicode_ci DEFAULT 'pos_compra',
  `produto_nome` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enviado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_enviado` (`enviado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs_emails_enviados`
--

LOCK TABLES `logs_emails_enviados` WRITE;
/*!40000 ALTER TABLE `logs_emails_enviados` DISABLE KEYS */;
/*!40000 ALTER TABLE `logs_emails_enviados` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parcelas`
--

DROP TABLE IF EXISTS `parcelas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parcelas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `honorario_id` int NOT NULL,
  `numero_parcela` int NOT NULL,
  `valor` decimal(15,2) NOT NULL,
  `data_vencimento` date NOT NULL,
  `data_pagamento` date DEFAULT NULL,
  `status` enum('pendente','pago','vencido') COLLATE utf8mb4_unicode_ci DEFAULT 'pendente',
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_honorario` (`honorario_id`),
  KEY `idx_vencimento` (`data_vencimento`),
  KEY `idx_status` (`status`),
  CONSTRAINT `parcelas_ibfk_1` FOREIGN KEY (`honorario_id`) REFERENCES `honorarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parcelas`
--

LOCK TABLES `parcelas` WRITE;
/*!40000 ALTER TABLE `parcelas` DISABLE KEYS */;
/*!40000 ALTER TABLE `parcelas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `processos`
--

DROP TABLE IF EXISTS `processos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `processos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cliente_id` int DEFAULT NULL,
  `numero_processo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tribunal` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vara` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_acao` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parte_contraria` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valor_causa` decimal(15,2) DEFAULT NULL,
  `status` enum('em_andamento','suspenso','arquivado') COLLATE utf8mb4_unicode_ci DEFAULT 'em_andamento',
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_numero` (`numero_processo`),
  KEY `idx_status` (`status`),
  CONSTRAINT `processos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `processos`
--

LOCK TABLES `processos` WRITE;
/*!40000 ALTER TABLE `processos` DISABLE KEYS */;
/*!40000 ALTER TABLE `processos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios_perfil`
--

DROP TABLE IF EXISTS `usuarios_perfil`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios_perfil` (
  `usuario_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`usuario_id`),
  UNIQUE KEY `uq_usuarios_perfil_email` (`email`),
  KEY `idx_usuarios_perfil_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios_perfil`
--

LOCK TABLES `usuarios_perfil` WRITE;
/*!40000 ALTER TABLE `usuarios_perfil` DISABLE KEYS */;
INSERT INTO `usuarios_perfil` VALUES ('admin','rodrigoexer2@gmail.com','Rodrigo Administrador','2026-01-12 10:42:42','2026-01-12 10:42:42');
/*!40000 ALTER TABLE `usuarios_perfil` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios_sistema`
--

DROP TABLE IF EXISTS `usuarios_sistema`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios_sistema` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token_senha` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `ultimo_login` datetime DEFAULT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_token` (`token_senha`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios_sistema`
--

LOCK TABLES `usuarios_sistema` WRITE;
/*!40000 ALTER TABLE `usuarios_sistema` DISABLE KEYS */;
INSERT INTO `usuarios_sistema` VALUES (1,'rodrigoexer2@gmail.com','$argon2id$v=19$m=65536,t=4,p=1$U1hneFJHeWdKQy9OcXNJTw$UpgxEWEgyelDa8q+5pg1ZrVpMjmccDA3cdfypZYfdsk',NULL,NULL,'2026-01-12 23:46:58','2026-01-12 10:42:42','2026-01-12 23:46:58');
/*!40000 ALTER TABLE `usuarios_sistema` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-12 23:49:33
