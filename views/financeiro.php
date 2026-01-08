<?php
// Buscar estatísticas financeiras
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN status = 'pendente' THEN valor ELSE 0 END), 0) as valor_pendente,
        COALESCE(SUM(CASE WHEN status = 'pago' THEN valor ELSE 0 END), 0) as valor_recebido,
        COALESCE(SUM(CASE WHEN status = 'pendente' AND data_vencimento < CURDATE() THEN valor ELSE 0 END), 0) as valor_vencido,
        COUNT(CASE WHEN status = 'pendente' THEN 1 END) as qtd_pendente
    FROM parcelas par
    INNER JOIN honorarios h ON par.honorario_id = h.id
    WHERE h.usuario_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$stats_fin = $stmt->fetch();

// Buscar lista de clientes para select
$stmt_clientes = $pdo->prepare("SELECT id, nome FROM clientes WHERE usuario_id = ? AND status = 'ativo' ORDER BY nome");
$stmt_clientes->execute([$_SESSION['user_id']]);
$lista_clientes = $stmt_clientes->fetchAll();
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="page-title">💰 Gestão Financeira</h2>
        <p class="text-muted">Controle de honorários e contas a receber</p>
    </div>
    <div class="col-md-4 text-end">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoHonorario">
            <i class="bi bi-plus-circle"></i> Novo Honorário
        </button>
    </div>
</div>

<!-- Cards Financeiros -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card stat-warning">
            <div class="card-body">
                <h6 class="text-muted mb-2">A Receber</h6>
                <h3 class="mb-0"><?= formatarMoeda($stats_fin['valor_pendente']) ?></h3>
                <small class="text-muted"><?= $stats_fin['qtd_pendente'] ?> parcela(s)</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card stat-success">
            <div class="card-body">
                <h6 class="text-muted mb-2">Recebido</h6>
                <h3 class="mb-0"><?= formatarMoeda($stats_fin['valor_recebido']) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card stat-danger">
            <div class="card-body">
                <h6 class="text-muted mb-2">Vencido</h6>
                <h3 class="mb-0"><?= formatarMoeda($stats_fin['valor_vencido']) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card stat-info">
            <div class="card-body">
                <h6 class="text-muted mb-2">Total</h6>
                <h3 class="mb-0"><?= formatarMoeda($stats_fin['valor_pendente'] + $stats_fin['valor_recebido']) ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Contas a Receber -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Contas a Receber</h5>
    </div>
    <div class="card-body">
        <!-- Filtros -->
        <div class="row mb-3">
            <div class="col-md-4">
                <select class="form-select" id="filtroStatusFin">
                    <option value="">Todos os status</option>
                    <option value="pendente">Pendente</option>
                    <option value="pago">Pago</option>
                    <option value="vencido">Vencido</option>
                </select>
            </div>
        </div>
        
        <?php
        $stmt = $pdo->prepare("
            SELECT par.*, c.nome as cliente_nome, h.descricao as honorario_descricao
            FROM parcelas par
            INNER JOIN honorarios h ON par.honorario_id = h.id
            LEFT JOIN clientes c ON h.cliente_id = c.id
            WHERE h.usuario_id = ?
            ORDER BY par.data_vencimento ASC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $parcelas = $stmt->fetchAll();
        
        if (empty($parcelas)):
        ?>
            <div class="text-center py-5">
                <i class="bi bi-cash-stack" style="font-size: 4rem; color: #ccc;"></i>
                <p class="text-muted mt-3">Nenhuma conta a receber cadastrada</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoHonorario">
                    Cadastrar Primeiro Honorário
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="tabelaParcelas">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Descrição</th>
                            <th>Parcela</th>
                            <th>Valor</th>
                            <th>Vencimento</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($parcelas as $parc): 
                            $status_calc = calcularStatusParcela($parc['data_vencimento'], $parc['data_pagamento']);
                        ?>
                        <tr data-status="<?= $status_calc ?>">
                            <td><?= sanitizar($parc['cliente_nome'] ?: 'N/A') ?></td>
                            <td><?= sanitizar($parc['honorario_descricao']) ?></td>
                            <td><?= $parc['numero_parcela'] ?></td>
                            <td><strong><?= formatarMoeda($parc['valor']) ?></strong></td>
                            <td><?= formatarData($parc['data_vencimento']) ?></td>
                            <td>
                                <span class="badge bg-<?= obterClasseStatus($status_calc) ?>">
                                    <?= ucfirst($status_calc) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($status_calc !== 'pago'): ?>
                                <button class="btn btn-sm btn-success" onclick="registrarPagamento(<?= $parc['id'] ?>)">
                                    <i class="bi bi-check"></i> Pagar
                                </button>
                                <?php else: ?>
                                <span class="text-success"><i class="bi bi-check-circle"></i> Pago em <?= formatarData($parc['data_pagamento']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Novo Honorário -->
<div class="modal fade" id="modalNovoHonorario" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Honorário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNovoHonorario">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Cliente *</label>
                            <select class="form-select" name="cliente_id" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($lista_clientes as $cli): ?>
                                    <option value="<?= $cli['id'] ?>"><?= sanitizar($cli['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Descrição</label>
                            <input type="text" class="form-control" name="descricao" placeholder="Ex: Processo Trabalhista">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Tipo *</label>
                            <select class="form-select" name="tipo" id="tipoHonorario" required>
                                <option value="fixo">Fixo</option>
                                <option value="parcelado">Parcelado</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Valor Total *</label>
                            <input type="text" class="form-control" name="valor_total" id="valorTotal" placeholder="R$ 0,00" required>
                        </div>
                        <div class="col-md-4" id="divParcelas" style="display: none;">
                            <label class="form-label">Número de Parcelas</label>
                            <input type="number" class="form-control" name="numero_parcelas" min="1" value="1">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Data Primeira Parcela *</label>
                        <input type="date" class="form-control" name="data_primeira_parcela" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="salvarHonorario()">Salvar Honorário</button>
            </div>
        </div>
    </div>
</div>

<script>
// Mostrar/ocultar campo de parcelas
document.getElementById('tipoHonorario')?.addEventListener('change', function() {
    const divParcelas = document.getElementById('divParcelas');
    if (this.value === 'parcelado') {
        divParcelas.style.display = 'block';
    } else {
        divParcelas.style.display = 'none';
    }
});

// Máscara de dinheiro
document.getElementById('valorTotal')?.addEventListener('input', function(e) {
    let valor = e.target.value.replace(/\D/g, '');
    valor = (parseInt(valor) / 100).toFixed(2);
    valor = valor.replace('.', ',');
    valor = valor.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    e.target.value = 'R$ ' + valor;
});

// Filtro de status
document.getElementById('filtroStatusFin')?.addEventListener('change', function() {
    const status = this.value;
    const linhas = document.querySelectorAll('#tabelaParcelas tbody tr');
    
    linhas.forEach(linha => {
        const statusLinha = linha.getAttribute('data-status');
        if (!status || statusLinha === status) {
            linha.style.display = '';
        } else {
            linha.style.display = 'none';
        }
    });
});

async function salvarHonorario() {
    const form = document.getElementById('formNovoHonorario');
    const formData = new FormData(form);
    formData.append('action', 'cadastrar_honorario');
    formData.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
    
    try {
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            mostrarSucesso('Honorário cadastrado com sucesso!');
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarErro('Erro: ' + result.error);
        }
    } catch (error) {
        mostrarErro('Erro ao salvar honorário');
    }
}

async function registrarPagamento(parcelaId) {
    if (!confirmar('Confirmar o recebimento desta parcela?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'registrar_pagamento');
    formData.append('parcela_id', parcelaId);
    formData.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
    
    try {
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            mostrarSucesso('Pagamento registrado com sucesso!');
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarErro('Erro: ' + result.error);
        }
    } catch (error) {
        mostrarErro('Erro ao registrar pagamento');
    }
}
</script>
