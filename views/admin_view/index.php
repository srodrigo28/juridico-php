<?php
// admin_view/index.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /www/v2/login.php');
    exit;
}

// Verifica se a senha foi enviada e está correta
$senhaCorreta = false;
if (isset($_POST['senha'])) {
    if ($_POST['senha'] === '123123') {
        $senhaCorreta = true;
    } else {
        $erro = 'Senha incorreta!';
    }
}

// Ações de seed e reset
$mensagem = '';
if ($senhaCorreta && isset($_POST['acao'])) {
    if ($_POST['acao'] === 'seed_10') {
        // TODO: lógica para gerar 10 dados
        $mensagem = '10 dados gerados!';
    } elseif ($_POST['acao'] === 'seed_20') {
        // TODO: lógica para gerar 20 dados
        $mensagem = '20 dados gerados!';
    } elseif ($_POST['acao'] === 'seed_30') {
        // TODO: lógica para gerar 30 dados
        $mensagem = '30 dados gerados!';
    } elseif ($_POST['acao'] === 'reset') {
        // TODO: lógica para zerar dados do usuário
        $mensagem = 'Dados do usuário zerados!';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Admin View</title>
    <link rel="stylesheet" href="/www/v2/public/css/style.css">
    <style>
        .admin-btn { margin: 0.5rem 0; }
        .mensagem { color: green; margin-top: 1rem; }
        .erro { color: red; }
    </style>
</head>
<body>
    <h2>Administração do Usuário Atual</h2>
    <?php if (!$senhaCorreta): ?>
        <form method="post">
            <label>Digite a senha de admin:</label><br>
            <input type="password" name="senha" autofocus required>
            <button type="submit">Entrar</button>
            <?php if (isset($erro)): ?><div class="erro"><?= $erro ?></div><?php endif; ?>
        </form>
    <?php else: ?>
        <form method="post">
            <input type="hidden" name="senha" value="123123">
            <button class="admin-btn" name="acao" value="seed_10">Gerar 10 dados</button><br>
            <button class="admin-btn" name="acao" value="seed_20">Gerar 20 dados</button><br>
            <button class="admin-btn" name="acao" value="seed_30">Gerar 30 dados</button><br>
            <button class="admin-btn" name="acao" value="reset" style="color:red;">Zerar dados do usuário</button>
        </form>
        <?php if ($mensagem): ?><div class="mensagem"><?= $mensagem ?></div><?php endif; ?>
    <?php endif; ?>
</body>
</html>
