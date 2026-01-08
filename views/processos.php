<?php
// Buscar lista de clientes para o select
$stmt_clientes = $pdo->prepare("SELECT id, nome FROM clientes WHERE usuario_id = ? AND status = 'ativo' ORDER BY nome");
$stmt_clientes->execute([$_SESSION['user_id']]);
$lista_clientes = $stmt_clientes->fetchAll();

// Buscar tribunais
$calculadora = new CalculadoraDatas($pdo);
$tribunais = $calculadora->obterTribunais();
?>
<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="page-title">⚖️ Gestão de Processos</h2>
        <p class="text-muted">Cadastre processos e gerencie prazos processuais</p>
    </div>
    <div class="col-md-4 text-end">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoProcesso">
            <i class="bi bi-plus-circle"></i> Novo Processo
        </button>
    </div>
</div>

<!-- Lista de Processos -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Meus Processos</h5>
    </div>
    <div class="card-body">
        <?php
        $stmt = $pdo->prepare("
            SELECT p.*, c.nome as cliente_nome,
                   COUNT(e.id) as total_prazos,
                   SUM(CASE WHEN e.status = 'pendente' AND e.data_final <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as prazos_urgentes
            FROM processos p
            LEFT JOIN clientes c ON p.cliente_id = c.id
            LEFT JOIN eventos e ON p.id = e.processo_id
            WHERE p.usuario_id = ?
            GROUP BY p.id
            ORDER BY p.data_criacao DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $processos = $stmt->fetchAll();
        
        if (empty($processos)):
        ?>
            <div class="text-center py-5">
                <i class="bi bi-briefcase" style="font-size: 4rem; color: #ccc;"></i>
                <p class="text-muted mt-3">Nenhum processo cadastrado ainda</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoProcesso">
                    Cadastrar Primeiro Processo
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Cliente</th>
                            <th>Tribunal</th>
                            <th>Prazos</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($processos as $proc): ?>
                        <tr>
                            <td>
                                <strong><?= sanitizar($proc['numero_processo']) ?></strong>
                                <?php if ($proc['prazos_urgentes'] > 0): ?>
                                    <span class="badge bg-danger ms-2">
                                        <?= $proc['prazos_urgentes'] ?> urgente(s)
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?= sanitizar($proc['cliente_nome'] ?: 'N/A') ?></td>
                            <td><?= sanitizar($proc['tribunal']) ?></td>
                            <td><?= $proc['total_prazos'] ?> prazo(s)</td>
                            <td>
                                <span class="badge bg-<?= obterClasseStatus($proc['status']) ?>">
                                    <?= ucfirst(str_replace('_', ' ', $proc['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info" onclick="verPrazos(<?= $proc['id'] ?>)">
                                    <i class="bi bi-calendar"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="excluirProcesso(<?= $proc['id'] ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Prazos Urgentes -->
<?php
$stmt_prazos = $pdo->prepare("
    SELECT e.*, p.numero_processo, c.nome as cliente_nome
    FROM eventos e
    INNER JOIN processos p ON e.processo_id = p.id
    LEFT JOIN clientes c ON p.cliente_id = c.id
    WHERE p.usuario_id = ? 
    AND e.status = 'pendente'
    AND e.data_final <= DATE_ADD(CURDATE(), INTERVAL 14 DAY)
    ORDER BY e.data_final ASC
");
$stmt_prazos->execute([$_SESSION['user_id']]);
$prazos_urgentes = $stmt_prazos->fetchAll();

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

<!-- Modal Novo Processo -->
<div class="modal fade" id="modalNovoProcesso" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Processo com Prazos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNovoProcesso">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Número do Processo *</label>
                            <input type="text" class="form-control" name="numero_processo" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tribunal *</label>
                            <select class="form-select" name="tribunal" id="tribunalSelect" required>
                                <option value="">Selecione...</option>
                                <option value="NACIONAL">Nacional (Feriados Nacionais)</option>
                                <?php foreach ($tribunais as $trib): ?>
                                    <option value="<?= $trib['abrangencia'] ?>"><?= $trib['abrangencia'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Cliente</label>
                            <select class="form-select" name="cliente_id">
                                <option value="">Sem cliente vinculado</option>
                                <?php foreach ($lista_clientes as $cli): ?>
                                    <option value="<?= $cli['id'] ?>"><?= sanitizar($cli['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Vara</label>
                            <input type="text" class="form-control" name="vara">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Parte Contrária</label>
                            <input type="text" class="form-control" name="parte_contraria">
                        </div>
                    </div>
                    
                    <hr>
                    <h6>Eventos/Prazos do Processo</h6>
                    <div id="eventosContainer"></div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="adicionarEvento()">
                        <i class="bi bi-plus"></i> Adicionar Evento
                    </button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="salvarProcesso()">Salvar Processo</button>
            </div>
        </div>
    </div>
</div>

<script>
let contadorEventos = 0;

function adicionarEvento() {
    contadorEventos++;
    const html = `
        <div class="card mb-2" id="evento-${contadorEventos}">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Descrição</label>
                        <input type="text" class="form-control" name="eventos[${contadorEventos}][descricao]" placeholder="Ex: Contestação">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Data Inicial</label>
                        <input type="text" class="form-control" name="eventos[${contadorEventos}][data_inicial]" placeholder="dd/mm/aaaa">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Prazo (dias)</label>
                        <input type="number" class="form-control" name="eventos[${contadorEventos}][prazo_dias]" min="1">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Contagem</label>
                        <select class="form-select" name="eventos[${contadorEventos}][tipo_contagem]">
                            <option value="uteis">Úteis</option>
                            <option value="corridos">Corridos</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Metodologia</label>
                        <select class="form-select" name="eventos[${contadorEventos}][metodologia]">
                            <option value="exclui_inicio">Exclui início</option>
                            <option value="inclui_inicio">Inclui início</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-sm btn-danger" onclick="removerEvento(${contadorEventos})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.getElementById('eventosContainer').insertAdjacentHTML('beforeend', html);
}

function removerEvento(id) {
    document.getElementById(`evento-${id}`).remove();
}

async function salvarProcesso() {
    const form = document.getElementById('formNovoProcesso');
    const formData = new FormData(form);
    formData.append('action', 'cadastrar_processo');
    formData.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
    
    try {
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            mostrarSucesso('Processo cadastrado com sucesso!');
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarErro('Erro: ' + result.error);
        }
    } catch (error) {
        mostrarErro('Erro ao salvar processo');
    }
}

async function excluirProcesso(id) {
    if (!confirmar('Tem certeza que deseja excluir este processo e todos os seus prazos?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'excluir_processo');
    formData.append('processo_id', id);
    formData.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
    
    try {
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            mostrarSucesso('Processo excluído com sucesso!');
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarErro('Erro: ' + result.error);
        }
    } catch (error) {
        mostrarErro('Erro ao excluir processo');
    }
}

async function marcarComoCumprido(eventoId) {
    const formData = new FormData();
    formData.append('action', 'atualizar_status_evento');
    formData.append('evento_id', eventoId);
    formData.append('status', 'cumprido');
    formData.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
    
    try {
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            mostrarSucesso('Prazo marcado como cumprido!');
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarErro('Erro: ' + result.error);
        }
    } catch (error) {
        mostrarErro('Erro ao atualizar prazo');
    }
}

// Adicionar primeiro evento automaticamente
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('modalNovoProcesso')) {
        adicionarEvento();
    }
});
</script>
