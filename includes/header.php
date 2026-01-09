<?php
// Detectar se está no buscador.php ou no index.php
$is_buscador = (basename($_SERVER['PHP_SELF']) === 'buscador.php');
$base_url = $is_buscador ? 'index.php' : '';
?>
<!-- Header -->
<header class="header">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-3">
                <h1 class="logo">⚖️ Precifex Jurídico</h1>
            </div>
            <div class="col-md-6">
                <!-- Menu de Navegação -->
                <nav class="nav-tabs-custom">
                    <a href="<?= $base_url ?>?aba=dashboard" class="nav-link <?= $aba_ativa === 'dashboard' ? 'active' : '' ?>">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a href="<?= $base_url ?>?aba=clientes" class="nav-link <?= $aba_ativa === 'clientes' ? 'active' : '' ?>">
                        <i class="bi bi-people"></i> Clientes
                    </a>
                    <a href="<?= $base_url ?>?aba=processos" class="nav-link <?= $aba_ativa === 'processos' ? 'active' : '' ?>">
                        <i class="bi bi-briefcase"></i> Processos
                    </a>
                    <a href="buscador.php" class="nav-link <?= $aba_ativa === 'buscador' ? 'active' : '' ?>">
                        <i class="bi bi-search"></i> Buscador
                    </a>
                    <a href="<?= $base_url ?>?aba=financeiro" class="nav-link <?= $aba_ativa === 'financeiro' ? 'active' : '' ?>">
                        <i class="bi bi-currency-dollar"></i> Financeiro
                    </a>
                    <a href="<?= $base_url ?>?aba=calculadoras" class="nav-link <?= $aba_ativa === 'calculadoras' ? 'active' : '' ?>">
                        <i class="bi bi-calculator"></i> Calculadoras
                    </a>
                </nav>
            </div>
            <div class="col-md-3 text-end">
                <div class="user-info">
                    <span class="user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuário') ?></span>
                    <a href="sistemas/logout.php" class="btn btn-sm btn-outline-danger ms-2">
                        <i class="bi bi-box-arrow-right"></i> Sair
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>