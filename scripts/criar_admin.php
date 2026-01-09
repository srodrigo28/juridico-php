<?php
// scripts/criar_admin.php
// Inicializa tabelas e cria usuário admin com senha definida

define('SISTEMA_MEMBROS', true);
require_once __DIR__ . '/../sistemas/config.php';
require_once __DIR__ . '/../sistemas/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/plain; charset=UTF-8');

try {
    // Conexões
    $pdoAuth = getDBConnection();
    $pdoApp = conectarBanco();

    if (!$pdoAuth || !$pdoApp) {
        throw new Exception('Falha na conexão com o banco.');
    }

    // Criar tabelas da aplicação
    criarTabelas($pdoApp);

    // Garantir tabela licencas (caso não esteja criada ainda)
    $pdoAuth->exec("CREATE TABLE IF NOT EXISTS licencas (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Parâmetros do admin
    $email = 'admin@local.test';
    $senhaPlana = 'Admin123!';
    $produtoId = '5776734';
    $produtoNome = 'Precifex Jurídico';

    // Inserir/atualizar licença ativa
    $stmt = $pdoAuth->prepare("INSERT INTO licencas (email, produto_id, produto_nome, status_licenca)
        VALUES (:email, :pid, :pnome, 'ativa')
        ON DUPLICATE KEY UPDATE status_licenca='ativa', produto_nome=:pnome, atualizado_em=NOW()");
    $stmt->execute([':email'=>$email, ':pid'=>$produtoId, ':pnome'=>$produtoNome]);

    // Definir senha do usuário admin
    $senhaHash = hashSenha($senhaPlana);
    $stmt2 = $pdoAuth->prepare("INSERT INTO usuarios_sistema (email, senha, criado_em)
        VALUES (:email, :senha, NOW())
        ON DUPLICATE KEY UPDATE senha=:senha, token_senha=NULL, token_expiry=NULL, atualizado_em=NOW()");
    $stmt2->execute([':email'=>$email, ':senha'=>$senhaHash]);

    echo "OK: Admin criado/atualizado.\n";
    echo "Login: $email\n";
    echo "Senha: $senhaPlana\n";
    echo "Licença ativa para produto $produtoId ($produtoNome).\n";
    echo "Acesse: " . LOGIN_URL . "\n";

} catch (Throwable $e) {
    http_response_code(500);
    echo 'Erro: ' . $e->getMessage() . "\n";
}
