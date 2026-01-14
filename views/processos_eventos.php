<?php
// Prazos Urgentes (obtidos via helper)
require_once __DIR__ . '/../includes/processos_helper.php';
$prazos_urgentes = getPrazosUrgentes($pdo, $_SESSION['user_id'], 14);

if (!empty($prazos_urgentes)):
?>
<div class="card">
    <div class="card-header bg-warning text-white">
        <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Prazos Urgentes (Próximos 14 dias)</h5>
    </div>
    <div class="card-body">
        <?php foreach ($prazos_urgentes as $prazo): 
            $hoje = new DateTime();
            $data_prazo = new DateTime($prazo['data_final']);
            $diff = $hoje->diff($data_prazo);
            $dias_restantes = ($data_prazo < $hoje) ? -$diff->days : $diff->days;
        ?>
        <div class="evento-item <?= $dias_restantes < 0 ? 'evento-vencido' : ($dias_restantes <= 3 ? 'evento-urgente' : '') ?>">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <h6 class="mb-1">
                        <?= sanitizar($prazo['descricao']) ?>
                        <?php if ($dias_restantes < 0): ?>
                            <span class="badge bg-danger ms-2">VENCIDO</span>
                        <?php elseif ($dias_restantes == 0): ?>
                            <span class="badge bg-danger ms-2">VENCE HOJE</span>
                        <?php elseif ($dias_restantes <= 3): ?>
                            <span class="badge bg-warning ms-2"><?= $dias_restantes ?> dia(s)</span>
                        <?php else: ?>
                            <span class="badge bg-info ms-2"><?= $dias_restantes ?> dia(s)</span>
                        <?php endif; ?>
                    </h6>
                    <p class="mb-1 small text-muted">
                        <strong>Processo:</strong> <?= sanitizar($prazo['numero_processo']) ?>
                        <?php if ($prazo['cliente_nome']): ?>
                            | <strong>Cliente:</strong> <?= sanitizar($prazo['cliente_nome']) ?>
                        <?php endif; ?>
                    </p>
                    <p class="mb-0 small">
                        <strong>Data Inicial:</strong> <?= formatarData($prazo['data_inicial']) ?> |
                        <strong>Prazo:</strong> <?= $prazo['prazo_dias'] ?> dias <?= $prazo['tipo_contagem'] ?> |
                        <strong>Data Final:</strong> <?= formatarData($prazo['data_final']) ?>
                    </p>
                </div>
                <div class="ms-3">
                    <button class="btn btn-sm btn-success" onclick="marcarComoCumprido(<?= $prazo['id'] ?>)">
                        <i class="bi bi-check"></i> Cumprido
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
