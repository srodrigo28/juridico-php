<?php
// Proteção contra acesso direto
if (!defined('SISTEMA_MEMBROS')) {
    die('Acesso negado');
}

// Configuração do banco de dados Gestão
/* Conexão externa
$DB_CONFIG = [
    'host' => '77.37.126.7',
    'port' => 3306,
    'database' => 'juridico',
    'username' => '',
    'password' => '',
    'charset' => 'utf8mb4'
];
*/
/* Conexão local */
$DB_CONFIG = [
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'juridico',
    'username' => '',
    'password' => '',
    'charset' => 'utf8mb4'
];
/**
 * Conectar ao banco de dados
 */
function conectarBanco() {
    global $DB_CONFIG;
    
    try {
        $dsn = "mysql:host={$DB_CONFIG['host']};port={$DB_CONFIG['port']};dbname={$DB_CONFIG['database']};charset={$DB_CONFIG['charset']}";
        $pdo = new PDO($dsn, $DB_CONFIG['username'], $DB_CONFIG['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        return $pdo;
    } catch (PDOException $e) {
        die("Erro de conexão com o banco de dados: " . $e->getMessage());
    }
}

/**
 * Criar tabelas se não existirem
 */
function criarTabelas($pdo) {
    try {
        // Tabela de clientes
        $pdo->exec("CREATE TABLE IF NOT EXISTS clientes (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Tabela de processos
        $pdo->exec("CREATE TABLE IF NOT EXISTS processos (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Tabela de eventos (prazos)
        $pdo->exec("CREATE TABLE IF NOT EXISTS eventos (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Tabela de honorários
        $pdo->exec("CREATE TABLE IF NOT EXISTS honorarios (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Tabela de parcelas (contas a receber)
        $pdo->exec("CREATE TABLE IF NOT EXISTS parcelas (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Tabela de despesas
        $pdo->exec("CREATE TABLE IF NOT EXISTS despesas (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
    } catch (PDOException $e) {
        // Silenciar erro se tabelas já existem
    }
}
