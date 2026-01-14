<?php
// Script de diagnóstico: testa a conexão com o banco usando getDBConnection()
// Coloque este arquivo em: scripts/test_db_connection.php e execute via CLI: php scripts/test_db_connection.php

require __DIR__ . '/../sistemas/config.php';

echo "Teste de conexão com o banco\n";

$pdo = getDBConnection();
if ($pdo === false) {
    echo "Falha ao criar conexão PDO. Verifique as configurações em sistemas/config.php e os logs.\n";
    exit(1);
}

try {
    $stmt = $pdo->query("SELECT VERSION() AS version");
    $row = $stmt->fetch();
    echo "Conectado ao servidor MySQL. Versão: " . ($row['version'] ?? 'desconhecida') . "\n";

    $stmt = $pdo->prepare("SHOW DATABASES LIKE :dbname");
    $stmt->execute([':dbname' => DB_NAME]);
    $found = $stmt->fetch();
    if ($found) {
        echo "Banco '" . DB_NAME . "' encontrado.\n";
    } else {
        echo "Banco '" . DB_NAME . "' NÃO encontrado.\n";
    }

    // Exibir usuário atual conectado (útil para checar permissões)
    $stmt = $pdo->query("SELECT USER() AS user");
    $u = $stmt->fetch();
    echo "Usuário conectado: " . ($u['user'] ?? 'desconhecido') . "\n";

} catch (PDOException $e) {
    echo "Erro na consulta/execução: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Teste concluído.\n";
