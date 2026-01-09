<?php
// Página de Login
define('SISTEMA_MEMBROS', true);
require_once __DIR__ . '/sistemas/config.php';
require_once __DIR__ . '/sistemas/auth.php';

// Se já estiver logado, ir para o dashboard
if (estaLogado()) {
    redirecionarPara(DASHBOARD_URL);
}

// Gerar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = gerarToken();
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    $csrf = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
        $erro = 'Falha de verificação CSRF.';
    } else {
        $resultado = fazerLogin($email, $senha);
        if ($resultado['success']) {
            redirecionarPara(DASHBOARD_URL);
        } else {
            $erro = $resultado['message'] ?? 'Falha no login.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Precifex Jurídico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow-sm">
                    <div class="card-header text-center">
                        <h4 class="mb-0">⚖️ Precifex Jurídico</h4>
                        <small class="text-muted">Área de Login</small>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($erro)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?= htmlspecialchars($erro) ?>
                            </div>
                        <?php endif; ?>
                        <form method="post" action="">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="senha" class="form-label">Senha</label>
                                <input type="password" class="form-control" id="senha" name="senha" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Entrar</button>
                            </div>
                        </form>
                    </div>
                </div>
                <p class="text-center mt-3 text-muted">&copy; <?= date('Y') ?> Precifex</p>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
