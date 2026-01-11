<?php
session_name('MEMBROS_SESSION');

// Proteção do sistema de membros
define('SISTEMA_MEMBROS', true);
require_once __DIR__ . '/sistemas/config.php';
require_once __DIR__ . '/sistemas/auth.php';

// Proteger a página - ID do produto Precifex Jurídico
protegerPagina('5776734');

// Configurar timezone
date_default_timezone_set('America/Sao_Paulo');
setlocale(LC_TIME, 'pt_BR.UTF-8', 'pt_BR', 'portuguese');

// Gerar token CSRF se não existir
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = gerarToken();
}

// Incluir configurações e classes
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/CalculadoraDatas.php';
require_once __DIR__ . '/includes/functions.php';

// Conectar ao banco
$pdo = conectarBanco();

// Criar tabelas se não existirem
criarTabelas($pdo);

// Determinar a aba ativa
$aba_ativa = $_GET['aba'] ?? 'dashboard';
$abas_validas = ['dashboard', 'clientes', 'processos', 'buscador', 'financeiro', 'calculadoras', 'kanban'];

if (!in_array($aba_ativa, $abas_validas)) {
    $aba_ativa = 'dashboard';
}

// Processar ações AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_once __DIR__ . '/ajax/handler.php';
    exit;
}

// Buscar dados para o dashboard
$stats = obterEstatisticas($pdo, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Precifex Jurídico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>
    <?php 
    $aba_ativa = $_GET['aba'] ?? 'dashboard';
    include __DIR__ . '/includes/header.php'; 
    ?>

    <!-- Container Principal -->
    <div class="container-fluid main-content">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <?php
        // Incluir o conteúdo da aba ativa
        $arquivo_aba = __DIR__ . "/views/{$aba_ativa}.php";
        if (file_exists($arquivo_aba)) {
            include $arquivo_aba;
        } else {
            echo "<div class='alert alert-danger'>Aba não encontrada.</div>";
        }
        ?>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?= date('Y') ?> Precifex Jurídico - Todos os direitos reservados</p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0">Versão 1.0 Beta | <a href="mailto:contato@precifex.com">Suporte</a></p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="public/js/app.js"></script>
</body>
</html>
