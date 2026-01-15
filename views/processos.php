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
                        <tr data-proc-id="<?= $proc['id'] ?>" class="proc-row">
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
                                <button type="button" class="btn btn-sm btn-primary" title="Adicionar movimentação"
                                        data-bs-toggle="modal" data-bs-target="#modalNovaMovimentacao"
                                        data-proc-id="<?= $proc['id'] ?>" data-proc-numero="<?= addslashes($proc['numero_processo']) ?>">
                                    <i class="bi bi-plus-lg"></i>
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
                <button type="button" class="btn btn-outline-secondary" id="btnAbrirMovimentacoes">
                    <i class="bi bi-list-task"></i> Movimentações
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
                <form id="formNovoProcesso" enctype="multipart/form-data">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Número do Processo *</label>
                            <input type="text" class="form-control" name="numero_processo" required>
                            <div class="invalid-feedback">Número do processo é obrigatório</div>
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
                            <div class="invalid-feedback">Selecione o tribunal</div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Cliente</label>
                            <div class="input-group mb-2">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" id="clienteSearchNovo" data-cliente-search data-target-hidden="#clienteIdNovo" data-suggestions="#clienteSugestoesNovo" placeholder="Buscar cliente por nome">
                                <div class="invalid-feedback">Selecione um cliente válido</div>
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

                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="form-label">Uploads de Arquivos</label>
                            <div id="uploadsContainer">
                                <div class="input-group mb-2 upload-group">
                                    <input type="text" class="form-control" name="upload_titulo[]" placeholder="Título do arquivo" required>
                                        <div class="invalid-feedback">Informe um título para o arquivo</div>
                                        <input type="file" class="form-control" name="uploads[]" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx" required>
                                        <div class="invalid-feedback">Selecione um arquivo válido</div>
                                    <button type="button" class="btn btn-outline-danger" onclick="this.parentNode.remove()">Remover</button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="adicionarUpload()">
                                <i class="bi bi-plus"></i> Adicionar outro arquivo
                            </button>
                            <div class="form-text">Tipos permitidos: pdf, png, jpg, doc, docx, xls, xlsx</div>
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
                <button type="button" id="btnSalvarNovoProcesso" class="btn btn-primary">Salvar Processo</button>
            </div>
        </div>
    </div>
</div>


<?php include __DIR__ . '/eventos.php'; ?>

<script>
let contadorEventos = 0;

function adicionarUpload() {
    const container = document.getElementById('uploadsContainer');
    if (!container) return;
    const div = document.createElement('div');
    div.className = 'input-group mb-2 upload-group';
    div.innerHTML = `
        <input type="text" class="form-control" name="upload_titulo[]" placeholder="Título do arquivo" required>
        <input type="file" class="form-control" name="uploads[]" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx" required>
        <button type="button" class="btn btn-outline-danger" onclick="this.parentNode.remove()">Remover</button>
    `;
    container.appendChild(div);
}


// --- Garantir que salvarNovoEvento está no escopo global ---


// Dados auxiliares no front (tribunais e clientes) para montar selects na edição
// Garantir strings JS válidas usando json_encode com escape de caracteres especiais
const TRIBUNAIS = <?= json_encode(array_merge(['NACIONAL'], array_map(function($t){return $t['abrangencia'];}, $tribunais)), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;
const CLIENTES_ATIVOS = <?= json_encode(array_map(function($cli){return ['id'=>(int)$cli['id'],'nome'=>$cli['nome']];}, $lista_clientes), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;

function adicionarEvento() {
    contadorEventos++;
    const id = `evento-${contadorEventos}`;
    const container = document.getElementById('eventosContainer');
    if (!container) return;
    const div = document.createElement('div');
    div.className = 'card mb-2';
    div.id = id;
    div.innerHTML = `
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-6">
                    <input type="text" class="form-control" name="eventos[${contadorEventos}][descricao]" placeholder="Descrição" required>
                    <div class="invalid-feedback">Descrição do evento é obrigatória</div>
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control" name="eventos[${contadorEventos}][data_inicial]" value="${getTodayISO()}" required>
                    <div class="invalid-feedback">Data inicial é obrigatória</div>
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control" name="eventos[${contadorEventos}][prazo_dias]" min="1" placeholder="Prazo" required>
                    <div class="invalid-feedback">Informe o prazo em dias (>=1)</div>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <select class="form-select" name="eventos[${contadorEventos}][tipo_contagem]">
                        <option value="uteis">Dias úteis</option>
                        <option value="corridos">Dias corridos</option>
                    </select>
                    <button type="button" class="btn btn-outline-danger" onclick="document.getElementById('${id}').remove()">Remover</button>
                </div>
            </div>
        </div>
    `;
    container.appendChild(div);
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
// Formata valor numérico do banco (ex: 3946.72 ou "3946.72") para formato BR (ex: "3.946,72")
function formatCurrencyFromDB(v){
    if (v == null || v === '') return '';
    // Converte para número se for string
    let num = typeof v === 'string' ? parseFloat(v) : v;
    if (isNaN(num)) return '';
    // Formata com 2 casas decimais, separador decimal = vírgula, separador milhar = ponto
    return num.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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

// Salvar novo processo (normaliza valor e datas de eventos)
window.salvarProcesso = async function(){
    const form = document.getElementById('formNovoProcesso');
    if (!form) return;
    // Primeiro, usar validação HTML5 nativa (atributos `required`, type, etc.)
    // Remove marcações anteriores
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    if (!form.checkValidity()) {
        // Mostrar mensagens nativas e destaque
        form.reportValidity();
        mostrarErro('Preencha os campos obrigatórios destacados.');
        // Marcar campos inválidos com classe visual e popular mensagens inline
        Array.from(form.elements).forEach(el => {
            if (el.willValidate && !el.checkValidity()) {
                el.classList.add('is-invalid');
                // localizar elemento .invalid-feedback associado (pode estar logo após ou após input-group)
                let feedback = null;
                if (el.closest('.input-group')) {
                    feedback = el.closest('.input-group').nextElementSibling;
                } else {
                    feedback = el.nextElementSibling;
                }
                if (feedback && feedback.classList && feedback.classList.contains('invalid-feedback')) {
                    feedback.textContent = el.validationMessage || 'Campo obrigatório';
                }
            } else {
                // remover marcação anterior
                el.classList.remove('is-invalid');
            }
        });
        return;
    }

    // Validação extra: campo número do processo e tribunal (redundante, mas garante)
    const numeroEl = form.querySelector('[name="numero_processo"]');
    const tribunalEl = form.querySelector('[name="tribunal"]');
    if (!numeroEl || !numeroEl.value.trim()) { mostrarErro('Número do processo é obrigatório'); numeroEl?.classList.add('is-invalid'); numeroEl?.focus(); return; }
    if (!tribunalEl || !tribunalEl.value.trim()) { mostrarErro('Tribunal é obrigatório'); tribunalEl?.classList.add('is-invalid'); tribunalEl?.focus(); return; }

    // Verificar eventos: ao menos 1 evento válido (descrição, data inicial, prazo)
    const eventoDescInputs = Array.from(form.querySelectorAll('[name*="[descricao]"]'))
        .filter(i => i.name.startsWith('eventos['));
    let temEventoValido = false;
    for (const descEl of eventoDescInputs) {
        const base = descEl.name.replace('[descricao]','');
        const dataEl = form.querySelector(`[name="${base}[data_inicial]"]`);
        const prazoEl = form.querySelector(`[name="${base}[prazo_dias]"]`);
        const descricaoVal = String(descEl.value||'').trim();
        const dataVal = dataEl ? String(dataEl.value||'').trim() : '';
        const prazoVal = prazoEl ? Number(prazoEl.value||0) : 0;
        if (descricaoVal && dataVal && prazoVal > 0) { temEventoValido = true; break; }
    }
    if (!temEventoValido) { mostrarErro('Adicione ao menos um evento/prazo válido (descrição, data inicial e prazo)'); return; }
    const fd = new FormData(form);
    // Normalizar valor da causa
    if (fd.has('valor_causa')){
        fd.set('valor_causa', normalizeCurrencyToEN(fd.get('valor_causa')));
    }
    // Converter datas de eventos (inputs type=date → enviar dd/mm/yyyy esperado pelo backend)
    const dateInputs = form.querySelectorAll('input[name^="eventos"][name$="[data_inicial]"]');
    dateInputs.forEach(inp => {
        const name = inp.name;
        const iso = inp.value || '';
        if (iso) fd.set(name, isoToBR(iso));
    });
    // Também converter any event data_final fields if present (nome like eventos[*][data_final])
    const dateFinals = form.querySelectorAll('input[name^="eventos"][name$="[data_final]"]');
    dateFinals.forEach(inp => {
        const name = inp.name;
        const iso = inp.value || '';
        if (iso) fd.set(name, isoToBR(iso));
    });

    fd.append('action','cadastrar_processo');
    fd.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
    
    // Prevenir double-submit
    const btn = document.getElementById('btnSalvarNovoProcesso');
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Salvando...'; }
    
    try {
        const r = await fetch('', { method: 'POST', body: fd });
        const j = await r.json();
        if (j.success){
            // Remover foco de qualquer elemento dentro do modal antes de fechar
            const modalEl = document.getElementById('modalNovoProcesso');
            if (modalEl) {
                // Remover foco do elemento ativo dentro do modal
                if (document.activeElement && modalEl.contains(document.activeElement)) {
                    document.activeElement.blur();
                }
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) {
                    // Esperar o modal fechar completamente antes de mostrar sucesso
                    modalEl.addEventListener('hidden.bs.modal', function onHidden(){
                        modalEl.removeEventListener('hidden.bs.modal', onHidden);
                        mostrarSucesso('Processo cadastrado!');
                        setTimeout(()=> location.reload(), 1000);
                    }, { once: true });
                    modalInstance.hide();
                } else {
                    mostrarSucesso('Processo cadastrado!');
                    setTimeout(()=> location.reload(), 1000);
                }
            } else {
                mostrarSucesso('Processo cadastrado!');
                setTimeout(()=> location.reload(), 1000);
            }
        } else { 
            if (btn) { btn.disabled = false; btn.innerHTML = 'Salvar Processo'; }
            // Mostrar erros específicos se disponíveis
            let errorMsg = j.error || j.message || 'Falha ao cadastrar processo';
            if (j.errors && typeof j.errors === 'object') {
                const firstError = Object.values(j.errors)[0];
                if (firstError) errorMsg = firstError;
            }
            mostrarErro(errorMsg); 
        }
    } catch(e){ 
        if (btn) { btn.disabled = false; btn.innerHTML = 'Salvar Processo'; }
        mostrarErro('Erro ao cadastrar processo'); 
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
    const novoEvtModalEl = document.getElementById('modalNovoEvento');
    if (novoEvtModalEl) {
        // Ajustar z-index do backdrop para modais empilhados
        novoEvtModalEl.addEventListener('show.bs.modal', function(e){
            // Garantir que este modal fique acima do modal pai
            setTimeout(() => {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                if (backdrops.length > 1) {
                    backdrops[backdrops.length - 1].style.zIndex = '1055';
                }
                novoEvtModalEl.style.zIndex = '1060';
            }, 10);
            
            const pid = document.getElementById('movProcessoId')?.value || '';
            const trib = document.getElementById('movTribunal')?.value || 'NACIONAL';
            const procIdEl = document.getElementById('novoEvtProcessoId');
            const tribEl = document.getElementById('novoEvtTribunal');
            if (procIdEl) procIdEl.value = pid;
            if (tribEl) tribEl.value = trib;
            const form = document.getElementById('formNovoEvento');
            if (form) {
                form.reset();
                const di = form.querySelector('input[name="data_inicial"]');
                if (di) di.value = getTodayISO();
                // Nota: data_final é type="date" (nativo), não precisa de máscara BR
            }
        });
        
        // Ao fechar o modal de Novo Evento, restaurar o foco no modal pai
        novoEvtModalEl.addEventListener('hidden.bs.modal', function(){
            const movModal = document.getElementById('modalNovaMovimentacao');
            if (movModal && movModal.classList.contains('show')) {
                document.body.classList.add('modal-open');
            }
        });
    }
    // Preparar modal Novo Processo: reset, adicionar primeiro evento e aplicar máscara de moeda
    const novoProcModal = document.getElementById('modalNovoProcesso');
    if (novoProcModal) {
        novoProcModal.addEventListener('show.bs.modal', function(){
            const form = document.getElementById('formNovoProcesso');
            if (form){
                form.reset();
                const eventosCont = document.getElementById('eventosContainer');
                if (eventosCont) {
                    eventosCont.innerHTML = '';
                    contadorEventos = 0;
                    adicionarEvento();
                }
                attachProcessMasks(form);
            }
        });
    }
    // Não abre aqui; o modal será aberto pelo Bootstrap via data attributes
});

// Popula o modal de movimentações com dados do processo
function popularModalMovimentacao(processoId, numeroProcesso){
    document.getElementById('movProcessoId').value = processoId;
    document.getElementById('movProcNumero').textContent = numeroProcesso ? `(${numeroProcesso})` : '';
    // Buscar tribunal do processo para cálculo preciso (para uso no modal de Novo Evento)
    (async () => {
        try {
            const fd = new FormData();
            fd.append('action','obter_processo');
            fd.append('processo_id', processoId);
            fd.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
            const r = await fetch('', { method:'POST', body: fd });
            const j = await r.json();
            if (j.success && j.processo) {
                document.getElementById('movTribunal').value = j.processo.tribunal || 'NACIONAL';
            } else {
                document.getElementById('movTribunal').value = 'NACIONAL';
            }
        } catch(e){
            document.getElementById('movTribunal').value = 'NACIONAL';
        }
    })();
    // Carregar eventos existentes
    carregarEventosProcesso(processoId);
}

// Abertura programática (compatibilidade)
function abrirModalMovimentacao(processoId, numeroProcesso){
    popularModalMovimentacao(processoId, numeroProcesso);
    const modal = new bootstrap.Modal(document.getElementById('modalNovaMovimentacao'));
    modal.show();
}

function initNovoEventoDatePicker(){ /* não utilizado com type=date */ }

function isoToBR(iso){
    // iso: yyyy-mm-dd → dd/mm/yyyy
    const parts = String(iso||'').split('-');
    if (parts.length !== 3) return '';
    return `${parts[2]}/${parts[1]}/${parts[0]}`;
}
function getTodayISO(){
    const d = new Date();
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth()+1).padStart(2,'0');
    const dd = String(d.getDate()).padStart(2,'0');
    return `${yyyy}-${mm}-${dd}`;
}
function brToISO(br){
    // br: dd/mm/yyyy → yyyy-mm-dd
    const v = String(br||'').trim();
    const m = v.match(/^([0-3]\d)\/([0-1]\d)\/(\d{4})$/);
    if (!m) return '';
    const dd = m[1], mm = m[2], yyyy = m[3];
    return `${yyyy}-${mm}-${dd}`;
}

function escapeHtml(s){
    return String(s||'').replace(/[&<>"]/g, function(m){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m]); });
}

function carregarEventosProcesso(processoId){
    const container = document.getElementById('movEventosContainer');
    if (!container) return;
    container.innerHTML = '<tr><td colspan="7" class="text-muted">Carregando eventos...</td></tr>';
    (async () => {
        try {
            const fd = new FormData();
            fd.append('action','obter_eventos_processo');
            fd.append('processo_id', processoId);
            const csrfEl = document.querySelector('[name="csrf_token"]');
            if (csrfEl) fd.append('csrf_token', csrfEl.value);
            const r = await fetch('', { method:'POST', body: fd });
            const j = await r.json();
            if (j.success){
                renderEventosLista(j.eventos||[]);
            } else {
                container.innerHTML = '<tr><td colspan="7" class="text-danger">Erro ao carregar eventos</td></tr>';
            }
        } catch(e){
            container.innerHTML = '<tr><td colspan="7" class="text-danger">Erro ao carregar eventos</td></tr>';
        }
    })();
}

function renderEventosLista(lista){
    const container = document.getElementById('movEventosContainer');
    if (!container) return;
    if (!lista.length){
        container.innerHTML = '<tr><td colspan="7" class="text-muted">Nenhum evento cadastrado</td></tr>';
        return;
    }
    container.innerHTML = lista.map(ev => {
        const statusClass = ev.status === 'pendente' ? 'bg-primary' : (ev.status === 'cumprido' ? 'bg-success' : 'bg-danger');
        const contagemLabel = ev.tipo_contagem === 'corridos' ? 'Corridos' : 'Úteis';
        const metodoLabel = ev.metodologia === 'inclui_inicio' ? 'Inclui início' : 'Exclui início';
        const toggleLabel = ev.status === 'pendente' ? 'Cumprir' : 'Pendente';
        const toggleNext = ev.status === 'pendente' ? 'cumprido' : 'pendente';
        return `
            <tr>
                <td>${escapeHtml(ev.descricao||'')}</td>
                <td>${ev.data_inicial||''}</td>
                <td>${ev.prazo_dias||0} dia(s)</td>
                <td>${contagemLabel}</td>
                <td>${metodoLabel}</td>
                <td>${ev.data_final||''}</td>
                <td>
                    <span class="badge ${statusClass}">${formatStatusLabel(ev.status||'pendente')}</span>
                    <div class="btn-group btn-group-sm ms-2" role="group">
                        <button type="button" class="btn btn-outline-secondary" title="Editar" onclick="abrirEditarEvento(${ev.id})"><i class="bi bi-pencil"></i></button>
                        <button type="button" class="btn btn-outline-success" title="${toggleLabel}" onclick="atualizarStatusEvento(${ev.id}, '${toggleNext}')"><i class="bi bi-check2"></i></button>
                        <button type="button" class="btn btn-outline-danger" title="Excluir" onclick="excluirEvento(${ev.id})"><i class="bi bi-trash"></i></button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function focusMovForm(){
    // Preencher ids ocultos antes de abrir
    document.getElementById('novoEvtProcessoId').value = document.getElementById('movProcessoId').value;
    document.getElementById('novoEvtTribunal').value = document.getElementById('movTribunal').value || 'NACIONAL';
    // reset e inicializar campos
    const form = document.getElementById('formNovoEvento');
    if (form) { 
        form.reset();
        const di = form.querySelector('input[name="data_inicial"]');
        if (di) di.value = getTodayISO();
        // Nota: data_final é type="date" (nativo), não precisa de máscara BR
    }
    // Abrir modal com opção para backdrop estático (evita fechamento acidental)
    const modalEl = document.getElementById('modalNovoEvento');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: true });
    modal.show();
}

function calcularDataFinalNovoEvento(){
    const form = document.getElementById('formNovoEvento');
    const fd = new FormData();
    fd.append('action','calcular_data');
    fd.append('data_inicial', isoToBR(form.data_inicial.value));
    fd.append('prazo_dias', form.prazo_dias.value);
    fd.append('tipo_contagem', form.tipo_contagem.value);
    fd.append('metodologia', form.metodologia.value);
    fd.append('tribunal', document.getElementById('novoEvtTribunal').value || 'NACIONAL');
    fd.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
    (async ()=>{
        try {
            const r = await fetch('', { method:'POST', body: fd });
            const j = await r.json();
            if (j.success) {
                // j.data_final já vem em dd/mm/aaaa; converter para yyyy-mm-dd para o input type=date
                document.getElementById('novoEvtDataFinal').value = brToISO(j.data_final);
            }
            else { mostrarErro(j.error||'Falha ao calcular'); }
        } catch(e){ mostrarErro('Erro ao calcular data'); }
    })();
}



function carregarResumoPrazos(processoId){
    (async ()=>{
        try {
            const fdr = new FormData();
            fdr.append('action','obter_resumo_processo');
            fdr.append('processo_id', processoId);
            fdr.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
            const rr = await fetch('', { method:'POST', body: fdr });
            const jr = await rr.json();
            if (jr.success && jr.resumo){
                const res = jr.resumo;
                const contEl = document.getElementById('movInfoPrazosResumo');
                if (contEl){
                    contEl.innerHTML = `
                        <span class=\"badge bg-light text-dark me-1\">Total: ${res.total}</span>
                        <span class=\"badge bg-primary me-1\">Pendentes: ${res.pendentes}</span>
                        <span class=\"badge bg-success me-1\">Cumpridos: ${res.cumpridos}</span>
                        <span class=\"badge bg-danger\">Urgentes: ${res.urgentes}</span>
                    `;
                }
                const proxEl = document.getElementById('movInfoProximo');
                if (proxEl){
                    const p = jr.proximo;
                    if (p){
                        const badgeClass = (p.dias_restantes < 0) ? 'bg-danger' : (p.dias_restantes <= 3 ? 'bg-warning text-dark' : 'bg-info');
                        const diaSemana = p.dia_semana ? ` (${p.dia_semana})` : '';
                        proxEl.innerHTML = `
                            <span class=\"small text-muted\">Próximo vencimento</span>
                            <div>
                                <span class=\"badge ${badgeClass} me-1\">${p.data_final}${diaSemana}</span>
                                <span class=\"small\">${escapeHtml(p.descricao || '')}</span>
                            </div>
                        `;
                    } else {
                        proxEl.innerHTML = `<span class=\"small text-muted\">Próximo vencimento</span><div>—</div>`;
                    }
                }
            }
        } catch(e){}
    })();
}

async function abrirEditarEvento(eventoId){
    const fd = new FormData();
    fd.append('action','obter_evento');
    fd.append('evento_id', eventoId);
    fd.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
    try {
        const r = await fetch('', { method:'POST', body: fd });
        const j = await r.json();
        if (j.success){
            const ev = j.evento;
            const form = document.getElementById('formEditarEvento');
            document.getElementById('editarEvtId').value = ev.id;
            form.descricao.value = ev.descricao||'';
            // Converter datas BR (dd/mm/yyyy) para ISO (yyyy-mm-dd) para campos type="date"
            form.data_inicial.value = brToISO(ev.data_inicial||'');
            form.prazo_dias.value = ev.prazo_dias||'';
            form.tipo_contagem.value = ev.tipo_contagem||'uteis';
            form.metodologia.value = ev.metodologia||'exclui_inicio';
            form.data_final.value = brToISO(ev.data_final||'');
            // Campos type="date" não precisam de máscara
            const modal = new bootstrap.Modal(document.getElementById('modalEditarEvento'));
            modal.show();
        } else { mostrarErro(j.error||'Falha ao obter evento'); }
    } catch(e){ mostrarErro('Erro ao obter evento'); }
}

function calcularDataFinalEditarEvento(){
    const form = document.getElementById('formEditarEvento');
    const fd = new FormData();
    fd.append('action','calcular_data');
    // Campo type="date" retorna ISO; backend espera BR
    fd.append('data_inicial', isoToBR(form.data_inicial.value));
    fd.append('prazo_dias', form.prazo_dias.value);
    fd.append('tipo_contagem', form.tipo_contagem.value);
    fd.append('metodologia', form.metodologia.value);
    fd.append('tribunal', document.getElementById('movTribunal').value || 'NACIONAL');
    fd.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
    (async ()=>{
        try {
            const r = await fetch('', { method:'POST', body: fd });
            const j = await r.json();
            if (j.success) { 
                // j.data_final vem em BR; converter para ISO para input type="date"
                document.getElementById('editarEvtDataFinal').value = brToISO(j.data_final); 
            }
            else { mostrarErro(j.error||'Falha ao calcular'); }
        } catch(e){ mostrarErro('Erro ao calcular data'); }
    })();
}

async function salvarEdicaoEvento(){
    const form = document.getElementById('formEditarEvento');
    if (!form.descricao.value.trim() || !form.data_inicial.value.trim() || !form.prazo_dias.value.trim()){
        mostrarErro('Preencha descrição, data inicial e prazo.');
        return;
    }
    const fd = new FormData(form);
    // Converter datas de ISO (do input type="date") para BR (backend espera dd/mm/yyyy)
    fd.set('data_inicial', isoToBR(form.data_inicial.value));
    if (form.data_final.value) {
        fd.set('data_final', isoToBR(form.data_final.value));
    }
    fd.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
    try {
        const r = await fetch('', { method:'POST', body: fd });
        const j = await r.json();
        if (j.success){
            mostrarSucesso('Evento atualizado!');
            const pid = document.getElementById('movProcessoId').value;
            carregarEventosProcesso(pid);
            carregarResumoPrazos(pid);
            bootstrap.Modal.getInstance(document.getElementById('modalEditarEvento')).hide();
        } else { mostrarErro(j.error||'Falha ao salvar'); }
    } catch(e){ mostrarErro('Erro ao salvar'); }
}

async function atualizarStatusEvento(eventoId, status){
    const fd = new FormData();
    fd.append('action','atualizar_status_evento');
    fd.append('evento_id', eventoId);
    fd.append('status', status);
    fd.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
    try {
        const r = await fetch('', { method:'POST', body: fd });
        const j = await r.json();
        if (j.success){
            const pid = document.getElementById('movProcessoId').value;
            carregarEventosProcesso(pid);
            carregarResumoPrazos(pid);
        } else { mostrarErro(j.error||'Falha ao atualizar'); }
    } catch(e){ mostrarErro('Erro ao atualizar'); }
}

async function excluirEvento(eventoId){
    if (!confirm('Excluir este evento?')) return;
    const fd = new FormData();
    fd.append('action','excluir_evento');
    fd.append('evento_id', eventoId);
    fd.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
    try {
        const r = await fetch('', { method:'POST', body: fd });
        const j = await r.json();
        if (j.success){
            const pid = document.getElementById('movProcessoId').value;
            carregarEventosProcesso(pid);
            carregarResumoPrazos(pid);
        } else { mostrarErro(j.error||'Falha ao excluir'); }
    } catch(e){ mostrarErro('Erro ao excluir'); }
}

async function calcularDataFinalMovimentacao(){
    const form = document.getElementById('formNovaMovimentacao');
    const fd = new FormData();
    fd.append('action','calcular_data');
    fd.append('data_inicial', form.data_inicial.value);
    fd.append('prazo_dias', form.prazo_dias.value);
    fd.append('tipo_contagem', form.tipo_contagem.value);
    fd.append('metodologia', form.metodologia.value);
    // Usar tribunal do processo
    fd.append('tribunal', document.getElementById('movTribunal').value || 'NACIONAL');
    fd.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
    try{
        const r = await fetch('', { method:'POST', body: fd });
        const j = await r.json();
        if (j.success){
            document.getElementById('movDataFinal').value = j.data_final;
        } else {
            mostrarErro(j.error||'Falha ao calcular data final');
        }
    } catch(e){
        mostrarErro('Erro ao calcular data final');
    }
}

function statusBadgeClass(status){
    switch (status) {
        case 'em_andamento': return 'bg-primary';
        case 'suspenso': return 'bg-warning text-dark';
        case 'arquivado': return 'bg-secondary';
        default: return 'bg-secondary';
    }
}
function formatStatusLabel(status){
    return String(status||'').replace(/_/g,' ').replace(/(^|\s)\S/g, s=>s.toUpperCase()) || 'Em Andamento';
}

async function salvarMovimentacao(){
    const form = document.getElementById('formNovaMovimentacao');
    // validações básicas
    if (!form.descricao.value.trim() || !form.data_inicial.value.trim() || !form.prazo_dias.value.trim()){
        mostrarErro('Preencha descrição, data inicial e prazo.');
        return;
    }
    const fd = new FormData(form);
    fd.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
    try{
        const r = await fetch('', { method:'POST', body: fd });
        const j = await r.json();
        if (j.success){
            mostrarSucesso('Movimentação adicionada!');
            setTimeout(()=> location.reload(), 1200);
        } else {
            mostrarErro(j.error||'Falha ao salvar movimentação');
        }
    } catch(e){
        mostrarErro('Erro ao salvar movimentação');
    }
}

// Máscara simples para datas no formato dd/mm/aaaa
function attachDateMaskBR(input){
    input.addEventListener('input', (e) => {
        let v = e.target.value.replace(/\D/g, '').slice(0,8);
        const parts = [];
        if (v.length >= 2) { parts.push(v.slice(0,2)); }
        if (v.length >= 4) { parts.push(v.slice(2,4)); }
        if (v.length > 4) { parts.push(v.slice(4)); }
        e.target.value = parts.join('/');
    });
}

function filtrarProcessos(){
    const q = (document.getElementById('buscarProcessoCliente')?.value || '').toLowerCase().trim();
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
// Vincular ao input de busca (se existir) e executar inicialmente
(function(){
    const inputBuscar = document.getElementById('buscarProcessoCliente');
    if (inputBuscar) {
        inputBuscar.addEventListener('input', filtrarProcessos);
    }
    // Executar uma vez ao carregar o script
    try{ filtrarProcessos(); } catch(e){}
})();

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
                    <input type="text" class="form-control" name="numero_processo" value="" disabled>
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
                    <input type="text" class="form-control" name="cliente_readonly" value="" disabled>
                    <input type="hidden" name="cliente_id" value="">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Vara</label>
                    <input type="text" class="form-control" name="vara" value="" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipo de Ação</label>
                    <input type="text" class="form-control" name="tipo_acao" value="" disabled>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Parte Contrária</label>
                    <input type="text" class="form-control" name="parte_contraria" value="" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Valor da Causa</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" class="form-control" name="valor_causa" value="" disabled>
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
                <textarea class="form-control" name="observacoes" rows="3" disabled></textarea>
            </div>
        `;
        document.getElementById('detalhesProcesso').innerHTML = html;
        // Selecionar valores atuais em selects e preencher campos com segurança (evita injeção de backticks)
        const form = document.getElementById('formVisualizarEditarProcesso');
        form.querySelector('[name="tribunal"]').value = p.tribunal||'';
        if (p.cliente_id) form.querySelector('[name="cliente_id"]').value = p.cliente_id;
        form.querySelector('[name="cliente_readonly"]').value = getClienteNome(p.cliente_id) || '';
        form.querySelector('[name="numero_processo"]').value = p.numero_processo || '';
        form.querySelector('[name="vara"]').value = p.vara || '';
        form.querySelector('[name="tipo_acao"]').value = p.tipo_acao || '';
        form.querySelector('[name="parte_contraria"]').value = p.parte_contraria || '';
        form.querySelector('[name="valor_causa"]').value = formatCurrencyFromDB(p.valor_causa);
        form.querySelector('[name="status"]').value = p.status||'em_andamento';
        form.querySelector('[name="observacoes"]').textContent = p.observacoes || '';
        // aplicar máscara no campo de valor para edição futura
        attachProcessMasks(form);
        // Configurar botão de Movimentações para abrir modal sobreposto
        const btnMov = document.getElementById('btnAbrirMovimentacoes');
        if (btnMov){
            btnMov.onclick = () => abrirModalMovimentacao(p.id, p.numero_processo||'');
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

async function salvarEdicaoProcesso() {
    const form = document.getElementById('formVisualizarEditarProcesso');
    const fd = new FormData(form);
    fd.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
    // normalizar valor da causa para formato numérico com ponto
    if (fd.has('valor_causa')) {
        fd.set('valor_causa', normalizeCurrencyToEN(fd.get('valor_causa')));
    }
    try {
        const r = await fetch('', { method: 'POST', body: fd });
        const j = await r.json();
        if (j.success) {
            mostrarSucesso('Processo atualizado com sucesso!');
            setTimeout(function () { location.reload(); }, 1200);
        } else {
            mostrarErro('Erro: ' + (j.error || 'Falha ao atualizar'));
        }
    } catch (e) {
        mostrarErro('Erro ao salvar alterações');
    }
}


// Definir apenas uma vez no escopo global
window.salvarNovoEvento = async function(){
    const form = document.getElementById('formNovoEvento');
    if (!form.descricao.value.trim() || !form.data_inicial.value.trim() || !form.prazo_dias.value.trim()){
        mostrarErro('Preencha descrição, data inicial e prazo.');
        return;
    }
    const fd = new FormData(form);
    // Converter datas ISO para BR antes de enviar
    fd.set('data_inicial', isoToBR(form.data_inicial.value));
    if (form.data_final.value) fd.set('data_final', isoToBR(form.data_final.value));
    fd.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
    try {
        const r = await fetch('', { method:'POST', body: fd });
        const j = await r.json();
        if (j.success){
            mostrarSucesso('Evento criado!');
            const pid = document.getElementById('movProcessoId').value;
            carregarEventosProcesso(pid);
            carregarResumoPrazos(pid);
            bootstrap.Modal.getInstance(document.getElementById('modalNovoEvento')).hide();
        } else { mostrarErro(j.error||'Falha ao salvar'); }
    } catch(e){ mostrarErro('Erro ao salvar'); }
}

// Attach click handler for Novo Processo save button (avoid inline onclick)
try {
    const btnSalvarNovo = document.getElementById('btnSalvarNovoProcesso');
    if (btnSalvarNovo) btnSalvarNovo.addEventListener('click', () => { if (typeof window.salvarProcesso === 'function') window.salvarProcesso(); });
} catch(e) { /* noop */ }

console.log('Script de processos carregado, salvarNovoEvento:', typeof window.salvarNovoEvento);
</script>