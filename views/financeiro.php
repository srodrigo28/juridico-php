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
<div class="row g-3 align-items-stretch mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card stat-warning h-100">
            <div class="card-body">
                <h6 class="text-muted mb-2">A Receber</h6>
                <h3 class="mb-0"><?= formatarMoeda($stats_fin['valor_pendente']) ?></h3>
                <small class="text-muted"><?= $stats_fin['qtd_pendente'] ?> parcela(s)</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card stat-success h-100">
            <div class="card-body">
                <h6 class="text-muted mb-2">Recebido</h6>
                <h3 class="mb-0"><?= formatarMoeda($stats_fin['valor_recebido']) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card stat-danger h-100">
            <div class="card-body">
                <h6 class="text-muted mb-2">Vencido</h6>
                <h3 class="mb-0"><?= formatarMoeda($stats_fin['valor_vencido']) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card stat-info h-100">
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
                                <div class="d-flex gap-2 align-items-center">
                                    <button class="btn btn-sm btn-info" onclick="visualizarParcela(<?= $parc['id'] ?>)" title="Detalhes da parcela">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-secondary" onclick="visualizarHonorario(<?= $parc['honorario_id'] ?>)" title="Honorário">
                                        <i class="bi bi-receipt"></i>
                                    </button>
                                    <?php if ($status_calc !== 'pago'): ?>
                                    <button class="btn btn-sm btn-success" onclick="registrarPagamento(<?= $parc['id'] ?>)">
                                        <i class="bi bi-check"></i> Pagar
                                    </button>
                                    <?php else: ?>
                                    <span class="text-success"><i class="bi bi-check-circle"></i> Pago em <?= formatarData($parc['data_pagamento']) ?></span>
                                    <?php endif; ?>
                                </div>
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

<!-- Modal Unificado: Parcela/Honorário -->
<div class="modal fade" id="modalFinanceiro" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalFinanceiroTitulo">Detalhes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formFinanceiro">
                    <div id="finConteudo"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger me-auto" id="finBtnExcluir" onclick="excluirFinanceiro()" style="display:none">
                    <i class="bi bi-trash"></i> Excluir
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-outline-primary" id="finBtnEditar" onclick="toggleEdicaoFinanceiro(true)" style="display:none">
                    <i class="bi bi-pencil"></i> Editar
                </button>
                <button type="button" class="btn btn-primary" id="finBtnSalvar" onclick="salvarEdicaoFinanceiro()" style="display:none">
                    <i class="bi bi-check2"></i> Salvar
                </button>
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

// Utils
function maskCurrencyBR(value){
    const digits = String(value||'').replace(/\D/g,'');
    const n = (parseInt(digits||'0',10)/100).toFixed(2);
    const parts = n.split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return 'R$ ' + parts[0] + ',' + parts[1];
}

function attachParcelaMasks(formEl){
    const valorEl = formEl.querySelector('[name="valor"]');
    if (valorEl){
        valorEl.addEventListener('input', e=> e.target.value = maskCurrencyBR(e.target.value));
    }
    const statusEl = formEl.querySelector('[name="status"]');
    const dataPagEl = formEl.querySelector('[name="data_pagamento"]');
    function toggleDataPag(){
        if (!statusEl || !dataPagEl) return;
        const pago = statusEl.value === 'pago';
        dataPagEl.disabled = !pago;
        if (!pago) dataPagEl.value = '';
    }
    if (statusEl){ statusEl.addEventListener('change', toggleDataPag); toggleDataPag(); }
}

// Estado do modal unificado
const finState = { tipo: null, id: null, status: null };

async function visualizarParcela(id){
    return abrirModalFinanceiro('parcela', id);
}

async function visualizarHonorario(id){
    return abrirModalFinanceiro('honorario', id);
}

async function abrirModalFinanceiro(tipo, id){
    finState.tipo = tipo;
    finState.id = id;
    const fd = new FormData();
    fd.append('action', tipo === 'parcela' ? 'obter_parcela' : 'obter_honorario');
    fd.append(tipo === 'parcela' ? 'parcela_id' : 'honorario_id', id);
    fd.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
    try{
        const r = await fetch('', {method:'POST', body: fd});
        const res = await r.json();
        if (!res.success) throw new Error(res.error||'Falha ao obter dados');
        const conteudo = document.getElementById('finConteudo');
        const tituloEl = document.getElementById('modalFinanceiroTitulo');
        let html = '';
        if (tipo === 'parcela'){
            const p = res.parcela; finState.status = p.status;
            tituloEl.textContent = 'Detalhes da Parcela';
            html = `
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Cliente</label>
                    <input type="text" class="form-control" value="${(p.cliente_nome||'N/A')}" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Honorário</label>
                    <input type="text" class="form-control" value="${(p.honorario_descricao||'')}" disabled>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Parcela</label>
                    <input type="text" class="form-control" value="${p.numero_parcela}" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Valor</label>
                    <input type="text" class="form-control" name="valor" value="${maskCurrencyBR(p.valor)}" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Vencimento</label>
                    <input type="date" class="form-control" name="data_vencimento" value="${p.data_vencimento||''}" disabled>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status" disabled>
                        <option value="pendente" ${p.status==='pendente'?'selected':''}>Pendente</option>
                        <option value="pago" ${p.status==='pago'?'selected':''}>Pago</option>
                        <option value="vencido" ${p.status==='vencido'?'selected':''}>Vencido</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Data de Pagamento</label>
                    <input type="date" class="form-control" name="data_pagamento" value="${p.data_pagamento||''}" disabled>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Observações</label>
                <textarea class="form-control" name="observacoes" rows="3" disabled>${p.observacoes||''}</textarea>
            </div>`;
            conteudo.innerHTML = html;
            attachParcelaMasks(document.getElementById('formFinanceiro'));
            // Botões
            document.getElementById('finBtnEditar').style.display = '';
            document.getElementById('finBtnSalvar').style.display = 'none';
            document.getElementById('finBtnExcluir').style.display = (p.status==='pago') ? 'none' : '';
        } else {
            const h = res.honorario; const s = res.resumos || {qtd:0,soma:0,soma_paga:0};
            finState.status = null;
            tituloEl.textContent = 'Detalhes do Honorário';
            html = `
            <div class="mb-3">
                <label class="form-label">Cliente</label>
                <input type="text" class="form-control" value="${h.cliente_nome||'N/A'}" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">Descrição</label>
                <input type="text" class="form-control" name="descricao" value="${h.descricao||''}" disabled>
            </div>
            <div class="row mb-3">
                <div class="col-6">
                    <label class="form-label">Tipo</label>
                    <input type="text" class="form-control" value="${(h.tipo||'').charAt(0).toUpperCase()+ (h.tipo||'').slice(1)}" disabled>
                </div>
                <div class="col-6">
                    <label class="form-label">Qtd. Parcelas</label>
                    <input type="text" class="form-control" value="${h.numero_parcelas}" disabled>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-6">
                    <label class="form-label">Valor Total</label>
                    <input type="text" class="form-control" value="${maskCurrencyBR(h.valor_total)}" disabled>
                </div>
                <div class="col-6">
                    <label class="form-label">Valor Parcela</label>
                    <input type="text" class="form-control" value="${maskCurrencyBR(h.valor_parcela)}" disabled>
                </div>
            </div>
            <div class="row">
                <div class="col-4"><small class="text-muted">Parcelas: ${s.qtd}</small></div>
                <div class="col-4"><small class="text-muted">Total: ${maskCurrencyBR(s.soma)}</small></div>
                <div class="col-4"><small class="text-muted">Recebido: ${maskCurrencyBR(s.soma_paga)}</small></div>
            </div>`;
            conteudo.innerHTML = html;
            // Botões
            document.getElementById('finBtnEditar').style.display = '';
            document.getElementById('finBtnSalvar').style.display = 'none';
            document.getElementById('finBtnExcluir').style.display = '';
        }
        new bootstrap.Modal(document.getElementById('modalFinanceiro')).show();
    }catch(e){
        alert(e.message||'Erro ao abrir detalhes');
    }
}

function toggleEdicaoFinanceiro(ativar){
    const form = document.getElementById('formFinanceiro');
    form.querySelectorAll('input, select, textarea').forEach(el=>{
        // Campos sem name permanecem read-only
        if (!el.name) { el.disabled = true; return; }
        if (finState.tipo === 'honorario' && el.name !== 'descricao') { el.disabled = true; return; }
        if (finState.tipo === 'parcela' && ['valor','data_vencimento','status','data_pagamento','observacoes'].indexOf(el.name) === -1) { el.disabled = true; return; }
        el.disabled = !ativar;
    });
    document.getElementById('finBtnEditar').style.display = ativar ? 'none' : '';
    document.getElementById('finBtnSalvar').style.display = ativar ? '' : 'none';
}

async function salvarEdicaoFinanceiro(){
    const form = document.getElementById('formFinanceiro');
    const fd = new FormData(form);
    const csrf = document.querySelector('[name="csrf_token"]').value;
    fd.append('csrf_token', csrf);
    if (finState.tipo === 'parcela'){
        fd.set('action','atualizar_parcela');
        fd.append('parcela_id', finState.id);
    } else {
        fd.set('action','atualizar_honorario');
        fd.append('honorario_id', finState.id);
    }
    try{
        const r = await fetch('', {method:'POST', body: fd});
        const res = await r.json();
        if (!res.success) throw new Error(res.error||'Falha ao salvar');
        alert(finState.tipo==='parcela' ? 'Parcela atualizada com sucesso!' : 'Honorário atualizado com sucesso!');
        location.reload();
    }catch(e){
        alert(e.message||'Erro ao salvar');
    }
}

async function excluirFinanceiro(){
    if (finState.tipo === 'parcela'){
        if (!confirm('Tem certeza que deseja excluir esta parcela?')) return;
        if (finState.status === 'pago'){ alert('Não é possível excluir uma parcela já paga.'); return; }
    } else {
        if (!confirm('Tem certeza que deseja excluir este honorário? Todas as parcelas serão removidas.')) return;
    }
    const fd = new FormData();
    fd.append('action', finState.tipo === 'parcela' ? 'excluir_parcela' : 'excluir_honorario');
    fd.append(finState.tipo === 'parcela' ? 'parcela_id' : 'honorario_id', finState.id);
    fd.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
    try{
        const r = await fetch('', {method:'POST', body: fd});
        const res = await r.json();
        if (!res.success) throw new Error(res.error||'Falha ao excluir');
        alert(finState.tipo==='parcela' ? 'Parcela excluída com sucesso!' : 'Honorário excluído com sucesso!');
        location.reload();
    }catch(e){
        alert(e.message||'Erro ao excluir');
    }
}
</script>
