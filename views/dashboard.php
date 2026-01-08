<div class="row mb-4">
    <div class="col-12">
        <h2 class="page-title">📊 Dashboard</h2>
        <p class="text-muted">Visão geral do seu escritório jurídico</p>
    </div>
</div>

<!-- Cards de Estatísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card stat-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Clientes Ativos</h6>
                        <h2 class="mb-0"><?= $stats['total_clientes'] ?></h2>
                    </div>
                    <div class="stat-icon">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stat-card stat-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Processos</h6>
                        <h2 class="mb-0"><?= $stats['total_processos'] ?></h2>
                    </div>
                    <div class="stat-icon">
                        <i class="bi bi-briefcase"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stat-card stat-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Prazos Próximos</h6>
                        <h2 class="mb-0"><?= $stats['prazos_proximos'] ?></h2>
                        <small class="text-muted">Próximos 7 dias</small>
                    </div>
                    <div class="stat-icon">
                        <i class="bi bi-alarm"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stat-card stat-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">A Receber</h6>
                        <h2 class="mb-0"><?= formatarMoeda($stats['valor_receber']) ?></h2>
                        <small class="text-muted"><?= $stats['contas_pendentes'] ?> parcela(s)</small>
                    </div>
                    <div class="stat-icon">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alertas -->
<?php if ($stats['contas_vencidas'] > 0): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <div>
                <strong>Atenção!</strong> Você tem <?= $stats['contas_vencidas'] ?> parcela(s) vencida(s) 
                no valor total de <?= formatarMoeda($stats['valor_vencido']) ?>.
                <a href="?aba=financeiro" class="alert-link ms-2">Ver detalhes</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($stats['prazos_proximos'] > 0): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-warning d-flex align-items-center" role="alert">
            <i class="bi bi-clock-fill me-2"></i>
            <div>
                <strong>Prazos urgentes!</strong> Você tem <?= $stats['prazos_proximos'] ?> prazo(s) 
                vencendo nos próximos 7 dias.
                <a href="?aba=processos" class="alert-link ms-2">Ver prazos</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Gráficos e Listas -->
<div class="row">
    <!-- Prazos Urgentes -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-alarm"></i> Prazos Urgentes (Próximos 7 dias)</h5>
            </div>
            <div class="card-body">
                <?php
                $stmt = $pdo->prepare("
                    SELECT e.*, p.numero_processo, c.nome as cliente_nome
                    FROM eventos e
                    INNER JOIN processos p ON e.processo_id = p.id
                    LEFT JOIN clientes c ON p.cliente_id = c.id
                    WHERE p.usuario_id = ? 
                    AND e.data_final <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                    AND e.status = 'pendente'
                    ORDER BY e.data_final ASC
                    LIMIT 5
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $prazos = $stmt->fetchAll();
                
                if (empty($prazos)):
                ?>
                    <p class="text-muted text-center mb-0">
                        <i class="bi bi-check-circle"></i> Nenhum prazo urgente no momento
                    </p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($prazos as $prazo): ?>
                        <div class="list-group-item px-0">
                            <div class="d-flex w-100 justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?= sanitizar($prazo['descricao']) ?></h6>
                                    <p class="mb-1 small text-muted">
                                        Processo: <?= sanitizar($prazo['numero_processo']) ?>
                                        <?php if ($prazo['cliente_nome']): ?>
                                            | Cliente: <?= sanitizar($prazo['cliente_nome']) ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <span class="badge bg-<?= obterCorPrazo($prazo['data_final']) ?>">
                                    <?= formatarData($prazo['data_final']) ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Contas a Receber -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-cash-stack"></i> Próximos Recebimentos</h5>
            </div>
            <div class="card-body">
                <?php
                $stmt = $pdo->prepare("
                    SELECT par.*, c.nome as cliente_nome
                    FROM parcelas par
                    INNER JOIN honorarios h ON par.honorario_id = h.id
                    LEFT JOIN clientes c ON h.cliente_id = c.id
                    WHERE h.usuario_id = ? 
                    AND par.status = 'pendente'
                    ORDER BY par.data_vencimento ASC
                    LIMIT 5
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $recebimentos = $stmt->fetchAll();
                
                if (empty($recebimentos)):
                ?>
                    <p class="text-muted text-center mb-0">
                        <i class="bi bi-inbox"></i> Nenhuma parcela pendente
                    </p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recebimentos as $rec): 
                            $status_parcela = calcularStatusParcela($rec['data_vencimento'], $rec['data_pagamento']);
                        ?>
                        <div class="list-group-item px-0">
                            <div class="d-flex w-100 justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?= formatarMoeda($rec['valor']) ?></h6>
                                    <p class="mb-0 small text-muted">
                                        <?= sanitizar($rec['cliente_nome']) ?>
                                        | Parcela <?= $rec['numero_parcela'] ?>
                                    </p>
                                </div>
                                <span class="badge bg-<?= obterClasseStatus($status_parcela) ?>">
                                    <?= formatarData($rec['data_vencimento']) ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Processos Recentes -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-briefcase"></i> Processos Recentes</h5>
                <a href="?aba=processos" class="btn btn-sm btn-primary">Ver todos</a>
            </div>
            <div class="card-body">
                <?php
                $stmt = $pdo->prepare("
                    SELECT p.*, c.nome as cliente_nome
                    FROM processos p
                    LEFT JOIN clientes c ON p.cliente_id = c.id
                    WHERE p.usuario_id = ?
                    ORDER BY p.data_criacao DESC
                    LIMIT 5
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $processos = $stmt->fetchAll();
                
                if (empty($processos)):
                ?>
                    <p class="text-muted text-center mb-0">
                        <i class="bi bi-inbox"></i> Nenhum processo cadastrado ainda
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Número do Processo</th>
                                    <th>Cliente</th>
                                    <th>Tribunal</th>
                                    <th>Status</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($processos as $proc): ?>
                                <tr>
                                    <td><?= sanitizar($proc['numero_processo']) ?></td>
                                    <td><?= sanitizar($proc['cliente_nome'] ?: 'N/A') ?></td>
                                    <td><?= sanitizar($proc['tribunal']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= obterClasseStatus($proc['status']) ?>">
                                            <?= ucfirst(str_replace('_', ' ', $proc['status'])) ?>
                                        </span>
                                    </td>
                                    <td><?= formatarData($proc['data_criacao']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
