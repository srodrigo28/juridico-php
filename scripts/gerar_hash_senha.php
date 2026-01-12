<?php
/**
 * Gerar hash de senha compatível com o sistema
 */

// Mesmo SALT usado no sistema
define('SALT_SENHA', 'JLP_SISTEMAS_2025_SALT_HASH');

// Senha desejada
$senha = '123123';

// Gerar hash (mesmo método do sistema)
$hash = password_hash($senha . SALT_SENHA, PASSWORD_ARGON2ID);

echo "==========================================================\n";
echo "HASH DE SENHA GERADO\n";
echo "==========================================================\n\n";

echo "Senha original: {$senha}\n";
echo "SALT usado: " . SALT_SENHA . "\n\n";

echo "Hash gerado:\n";
echo $hash . "\n\n";

echo "==========================================================\n";
echo "SQL PARA ATUALIZAR:\n";
echo "==========================================================\n\n";

echo "UPDATE usuarios_sistema SET senha = '{$hash}' WHERE email = 'rodrigoexer2@gmail.com';\n\n";

echo "==========================================================\n";
echo "OU INSIRA NO criar_new_db.sql:\n";
echo "==========================================================\n\n";

echo "INSERT INTO usuarios_sistema (email, senha, criado_em) \n";
echo "VALUES ('rodrigoexer2@gmail.com', '{$hash}', NOW());\n\n";

// Testar se funciona
$teste = password_verify($senha . SALT_SENHA, $hash);
echo "Teste de verificação: " . ($teste ? "✅ OK" : "❌ FALHOU") . "\n";
