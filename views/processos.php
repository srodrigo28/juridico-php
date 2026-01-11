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
<?php
// Total de processos para badge
$stmt_total_proc = $pdo->prepare("SELECT COUNT(*) FROM processos WHERE usuario_id = ?");
$stmt_total_proc->execute([$_SESSION['user_id']]);
$total_processos = (int)$stmt_total_proc->fetchColumn();
?>
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Meus Processos <span class="text-muted" id="tituloContagemProc">(<?= $total_processos ?>)</span></h5>
        <div class="d-flex align-items-center gap-2" style="min-width:280px">
            <input type="text" class="form-control form-control-sm" id="buscarProcessoCliente" placeholder="Filtrar por cliente...">
            <span class="badge bg-primary">Total: <?= $total_processos ?></span>
            <?php if (!empty($processos ?? [])): ?>
            <span class="badge bg-secondary" id="badgeResultadosProc">Exibindo: <?= count($processos ?? []) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <?php
        $stmt = $pdo->prepare("
            SELECT p.*, c.nome as cliente_nome,
                   MAX(c.celular) as cliente_celular,
                   MAX(c.whatsapp) as cliente_whatsapp,
                   MAX(c.telefone) as cliente_telefone,
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
                <table class="table table-hover" id="tabelaProcessos">
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
                                <button type="button" class="btn btn-sm btn-info" onclick="visualizarProcesso(<?= $proc['id'] ?>)" title="Visualizar">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <?php 
                                    $wa_raw = $proc['cliente_celular'] ?? ($proc['cliente_whatsapp'] ?? ($proc['cliente_telefone'] ?? ''));
                                    $wa_digits = preg_replace('/\D+/', '', $wa_raw);
                                    if ($wa_digits && substr($wa_digits,0,2) !== '55') { $wa_digits = '55'.$wa_digits; }
                                    $wa_text = urlencode('Olá, Precifex ADV traz informações sobre o processo '.($proc['numero_processo'] ?? '')); 
                                ?>
                                <?php if (!empty($wa_digits)): ?>
                                <a href="https://wa.me/<?= $wa_digits ?>?text=<?= $wa_text ?>" target="_blank" class="btn btn-sm btn-success" title="Chamar no WhatsApp">
                                    <i class="bi bi-whatsapp"></i>
                                </a>
                                <?php else: ?>
                                <button type="button" class="btn btn-sm btn-secondary" disabled title="WhatsApp indisponível">
                                    <i class="bi bi-whatsapp"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div id="noResultsProc" class="text-muted mt-2" style="display:none">Nenhum processo encontrado</div>
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

<!-- Modal Visualizar/Editar Processo -->
<div class="modal fade" id="modalVisualizarProcesso" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes do Processo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formVisualizarEditarProcesso">
                    <input type="hidden" name="action" value="atualizar_processo">
                    <input type="hidden" name="processo_id" id="processoIdEdicao">
                    <div id="detalhesProcesso">
                        <!-- Conteúdo via AJAX -->
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger me-auto" id="btnExcluirProcesso" style="display:none" onclick="excluirProcesso(document.getElementById('processoIdEdicao').value)">
                    <i class="bi bi-trash"></i> Excluir
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-outline-primary" id="btnEditarProcesso" onclick="toggleEdicaoProcesso(true)">
                    <i class="bi bi-pencil"></i> Editar
                </button>
                <button type="button" class="btn btn-primary" id="btnSalvarProcesso" style="display:none" onclick="salvarEdicaoProcesso()">
                    <i class="bi bi-check2"></i> Salvar
                </button>
            </div>
        </div>
    </div>
</div>

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
                            <div class="input-group mb-2">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" id="clienteSearchNovo" data-cliente-search data-target-hidden="#clienteIdNovo" data-suggestions="#clienteSugestoesNovo" placeholder="Buscar cliente por nome">
                            </div>
                            <div class="form-text"><span data-cliente-search-status>Digite para filtrar clientes</span></div>
                            <input type="hidden" name="cliente_id" id="clienteIdNovo" value="">
                            <div class="cliente-suggestions" id="clienteSugestoesNovo"></div>
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
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Valor da Causa</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="text" class="form-control" name="valor_causa" placeholder="0,00">
                            </div>
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

// Dados auxiliares no front (tribunais e clientes) para montar selects na edição
const TRIBUNAIS = ['NACIONAL', <?php foreach ($tribunais as $t) { echo "'" . $t['abrangencia'] . "',"; } ?>];
const CLIENTES_ATIVOS = [
    <?php foreach ($lista_clientes as $cli) { 
        echo '{id:'.(int)$cli['id'].',nome:"'.addslashes($cli['nome']).'"},';
    } ?>
];

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
    // normalizar valor da causa para formato numérico com ponto
    if (formData.has('valor_causa')){
        formData.set('valor_causa', normalizeCurrencyToEN(formData.get('valor_causa')));
    }
    
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

// Máscara para Valor da Causa (formato brasileiro 1.234,56)
function __onlyDigits(v){ return (v||'').replace(/\D/g,''); }
function maskCurrencyBR(v){
    let d = __onlyDigits(v);
    if (!d) return '';
    if (d.length === 1) d = '0' + d;
    const cents = d.slice(-2);
    let ints = d.slice(0, -2);
    ints = ints.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return (ints || '0') + ',' + cents;
}
// Converte "1.234,56" para "1234.56" para envio ao backend
function normalizeCurrencyToEN(v){
    if (v == null) return '';
    let s = String(v).trim();
    if (!s) return '';
    s = s.replace(/[^0-9.,-]/g, '');
    s = s.replace(/\./g, '').replace(/,/g, '.');
    return s;
}
function attachProcessMasks(formEl){
    if (!formEl) return;
    const valorInput = formEl.querySelector('input[name="valor_causa"]');
    if (valorInput){
        valorInput.addEventListener('input', (e)=>{
            e.target.value = maskCurrencyBR(e.target.value);
            e.target.selectionStart = e.target.selectionEnd = e.target.value.length;
        });
    }
}

function getClienteNome(id){
    const c = CLIENTES_ATIVOS.find(x => String(x.id) === String(id));
    return c ? c.nome : '';
}

// Busca e filtro de clientes por nome nos selects
function initClienteSearch(context){
    if (!context) return;
    const inputs = context.querySelectorAll('[data-cliente-search]');
    inputs.forEach(inp => {
        const hiddenSel = inp.getAttribute('data-target-hidden');
        const suggSel = inp.getAttribute('data-suggestions');
        const hidden = hiddenSel ? context.querySelector(hiddenSel) : context.querySelector('input[name="cliente_id"]');
        const sugg = suggSel ? context.querySelector(suggSel) : null;
        const statusEl = inp.closest('.col-md-6, .col-md-4')?.querySelector('[data-cliente-search-status]')
            || context.querySelector('[data-cliente-search-status]');
        if (!hidden) return;
        function renderSuggestions(list){
            if (!sugg) return;
            if (!list.length){
                sugg.innerHTML = '<div class="cliente-suggestion disabled">Nenhum cliente encontrado</div>';
                return;
            }
            sugg.innerHTML = list.map(c => `
                <button type="button" class="cliente-suggestion" data-id="${c.id}" data-nome="${c.nome}">
                    <i class="bi bi-person"></i> ${c.nome}
                </button>
            `).join('');
            sugg.querySelectorAll('.cliente-suggestion').forEach(btn => {
                btn.addEventListener('click', () => {
                    hidden.value = btn.getAttribute('data-id');
                    inp.value = btn.getAttribute('data-nome');
                    if (statusEl) statusEl.textContent = `Selecionado: ${btn.getAttribute('data-nome')}`;
                    if (sugg) sugg.innerHTML = '';
                });
            });
        }
        inp.addEventListener('input', () => {
            hidden.value = '';
            const q = inp.value.trim().toLowerCase();
            const filtered = CLIENTES_ATIVOS.filter(c => c.nome.toLowerCase().includes(q));
            if (statusEl){
                statusEl.textContent = filtered.length ? `${filtered.length} resultado(s)` : 'Nenhum cliente encontrado';
            }
            renderSuggestions(filtered);
        });
        inp.addEventListener('focus', () => {
            if (inp.value.trim() && sugg){
                const filtered = CLIENTES_ATIVOS.filter(c => c.nome.toLowerCase().includes(inp.value.trim().toLowerCase()));
                renderSuggestions(filtered);
            }
        });
        document.addEventListener('click', (e) => {
            if (!sugg) return;
            if (!sugg.contains(e.target) && e.target !== inp){
                sugg.innerHTML = '';
            }
        });
    });
}

// Adicionar primeiro evento automaticamente
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('modalNovoProcesso')) {
        adicionarEvento();
        attachProcessMasks(document.getElementById('formNovoProcesso'));
        initClienteSearch(document.getElementById('formNovoProcesso'));
    }
    const buscarProcInput = document.getElementById('buscarProcessoCliente');
    if (buscarProcInput){
        buscarProcInput.addEventListener('input', filtrarProcessosPorCliente);
    }
});

function filtrarProcessosPorCliente(){
    const q = (document.getElementById('buscarProcessoCliente').value || '').toLowerCase().trim();
    const linhas = document.querySelectorAll('#tabelaProcessos tbody tr');
    let visiveis = 0;
    linhas.forEach(linha => {
        const clienteCol = linha.querySelector('td:nth-child(2)');
        const nomeCliente = (clienteCol?.textContent || '').toLowerCase();
        const mostrar = q ? nomeCliente.includes(q) : true;
        linha.style.display = mostrar ? '' : 'none';
        if (mostrar) visiveis++;
    });
    const badge = document.getElementById('badgeResultadosProc');
    if (badge) { badge.textContent = `Exibindo: ${visiveis}`; }
    const titulo = document.getElementById('tituloContagemProc');
    if (titulo) { titulo.textContent = `(${visiveis})`; }
    const noRes = document.getElementById('noResultsProc');
    if (noRes) { noRes.style.display = visiveis === 0 ? '' : 'none'; }
}

async function visualizarProcesso(id){
    const fd = new FormData();
    fd.append('action','obter_processo');
    fd.append('processo_id', id);
    fd.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
    try {
        const r = await fetch('', { method:'POST', body: fd });
        const j = await r.json();
        if (!j.success) throw new Error(j.error||'Falha ao obter processo');
        const p = j.processo;
        document.getElementById('processoIdEdicao').value = p.id;
        // Montar HTML com campos readonly inicialmente
        const clienteOptions = ['<option value="">Sem cliente vinculado</option>']
            .concat(CLIENTES_ATIVOS.map(c=>`<option value="${c.id}">${c.nome}</option>`)).join('');
        const tribunalOptions = TRIBUNAIS.map(t=>`<option value="${t}">${t}</option>`).join('');
        const html = `
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Número do Processo</label>
                    <input type="text" class="form-control" name="numero_processo" value="${p.numero_processo||''}" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tribunal</label>
                    <select class="form-select" name="tribunal" disabled>
                        ${tribunalOptions}
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Cliente</label>
                    <input type="text" class="form-control" name="cliente_readonly" value="${getClienteNome(p.cliente_id)||''}" disabled>
                    <input type="hidden" name="cliente_id" value="${p.cliente_id||''}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Vara</label>
                    <input type="text" class="form-control" name="vara" value="${p.vara||''}" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipo de Ação</label>
                    <input type="text" class="form-control" name="tipo_acao" value="${p.tipo_acao||''}" disabled>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Parte Contrária</label>
                    <input type="text" class="form-control" name="parte_contraria" value="${p.parte_contraria||''}" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Valor da Causa</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" class="form-control" name="valor_causa" value="${p.valor_causa||''}" disabled>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status" disabled>
                        <option value="em_andamento">Em andamento</option>
                        <option value="suspenso">Suspenso</option>
                        <option value="arquivado">Arquivado</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Observações</label>
                <textarea class="form-control" name="observacoes" rows="3" disabled>${p.observacoes||''}</textarea>
            </div>
        `;
        document.getElementById('detalhesProcesso').innerHTML = html;
        // Selecionar valores atuais em selects
        const form = document.getElementById('formVisualizarEditarProcesso');
        form.querySelector('[name="tribunal"]').value = p.tribunal||'';
        if (p.cliente_id) form.querySelector('[name="cliente_id"]').value = p.cliente_id;
        form.querySelector('[name="status"]').value = p.status||'em_andamento';
        // aplicar máscara no campo de valor e formatar valor inicial
        attachProcessMasks(form);
        const valorInicialEl = form.querySelector('[name="valor_causa"]');
        if (valorInicialEl) {
            valorInicialEl.value = maskCurrencyBR(valorInicialEl.value);
        }
        // Mostrar modal
        document.getElementById('btnEditarProcesso').style.display = '';
        document.getElementById('btnSalvarProcesso').style.display = 'none';
        document.getElementById('btnExcluirProcesso').style.display = '';
        const modal = new bootstrap.Modal(document.getElementById('modalVisualizarProcesso'));
        modal.show();
    } catch(e){
        mostrarErro(e.message||'Erro ao visualizar processo');
    }
}

function toggleEdicaoProcesso(ativar){
    const form = document.getElementById('formVisualizarEditarProcesso');
    form.querySelectorAll('input, select, textarea').forEach(el => {
        if (el.name === 'processo_id' || el.name === 'action' || el.name === 'cliente_readonly' || el.name === 'cliente_id') return;
        el.disabled = !ativar;
    });
    document.getElementById('btnEditarProcesso').style.display = ativar ? 'none' : '';
    document.getElementById('btnSalvarProcesso').style.display = ativar ? '' : 'none';
    document.getElementById('btnExcluirProcesso').style.display = ativar ? '' : 'none';
}

async function salvarEdicaoProcesso(){
    const form = document.getElementById('formVisualizarEditarProcesso');
    const fd = new FormData(form);
    fd.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
    // normalizar valor da causa para formato numérico com ponto
    if (fd.has('valor_causa')){
        fd.set('valor_causa', normalizeCurrencyToEN(fd.get('valor_causa')));
    }
    try {
        const r = await fetch('', { method:'POST', body: fd });
        const j = await r.json();
        if (j.success){
            mostrarSucesso('Processo atualizado com sucesso!');
            setTimeout(()=> location.reload(), 1200);
        } else {
            mostrarErro('Erro: ' + (j.error||'Falha ao atualizar'));
        }
    } catch(e){
        mostrarErro('Erro ao salvar alterações');
    }
}
</script>
