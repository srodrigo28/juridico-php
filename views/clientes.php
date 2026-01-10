<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="page-title">👤 Gestão de Clientes</h2>
        <p class="text-muted">Cadastre e gerencie seus clientes</p>
    </div>
    <div class="col-md-4 text-end">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoCliente">
            <i class="bi bi-plus-circle"></i> Novo Cliente
        </button>
    </div>
</div>

<?php
// Total de clientes para badge
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE usuario_id = ?");
$stmtCount->execute([$_SESSION['user_id']]);
$total_clientes = (int)$stmtCount->fetchColumn();
?>

<!-- Lista de Clientes -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Lista de Clientes</h5>
        <span class="badge bg-primary">Total: <?= $total_clientes ?></span>
    </div>
    <div class="card-body">
        <!-- Filtros -->
        <div class="row mb-3">
            <div class="col-md-4">
                <input type="text" class="form-control" id="buscarCliente" placeholder="Buscar por nome ou CPF/CNPJ...">
            </div>
            <div class="col-md-3">
                <select class="form-select" id="filtroTipo">
                    <option value="">Todos os tipos</option>
                    <option value="pf">Pessoa Física</option>
                    <option value="pj">Pessoa Jurídica</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="filtroStatus">
                    <option value="">Todos os status</option>
                    <option value="ativo">Ativo</option>
                    <option value="inativo">Inativo</option>
                </select>
            </div>
        </div>
        
        <?php
        $stmt = $pdo->prepare("
            SELECT * FROM clientes 
            WHERE usuario_id = ? 
            ORDER BY nome ASC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $clientes = $stmt->fetchAll();
        
        if (empty($clientes)):
        ?>
            <div class="text-center py-5">
                <i class="bi bi-people" style="font-size: 4rem; color: #ccc;"></i>
                <p class="text-muted mt-3">Nenhum cliente cadastrado ainda</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoCliente">
                    Cadastrar Primeiro Cliente
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="tabelaClientes">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>CPF/CNPJ</th>
                            <th>Contato</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $cliente): ?>
                        <tr data-cliente-id="<?= $cliente['id'] ?>">
                            <td>
                                <strong><?= sanitizar($cliente['nome']) ?></strong>
                            </td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?= $cliente['tipo'] === 'pf' ? 'Pessoa Física' : 'Pessoa Jurídica' ?>
                                </span>
                            </td>
                            <td><?= $cliente['cpf_cnpj'] ? sanitizar(formatarCpfCnpj($cliente['cpf_cnpj'])) : 'N/A' ?></td>
                            <td>
                                <?php if ($cliente['email']): ?>
                                    <i class="bi bi-envelope"></i> <?= sanitizar($cliente['email']) ?><br>
                                <?php endif; ?>
                                <?php if ($cliente['celular']): ?>
                                    <i class="bi bi-phone"></i> <?= sanitizar($cliente['celular']) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= obterClasseStatus($cliente['status']) ?>">
                                    <?= ucfirst($cliente['status']) ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info" onclick="visualizarCliente(<?= $cliente['id'] ?>)" title="Visualizar">
                                    <i class="bi bi-eye"></i>
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

<!-- Modal Novo Cliente -->
<div class="modal fade" id="modalNovoCliente" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNovoCliente">
                    <input type="hidden" name="action" value="cadastrar_cliente">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Tipo de Pessoa *</label>
                            <select class="form-select" name="tipo" id="tipoCliente" required>
                                <option value="pf">Pessoa Física</option>
                                <option value="pj">Pessoa Jurídica</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status *</label>
                            <select class="form-select" name="status" required>
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Nome Completo / Razão Social *</label>
                            <input type="text" class="form-control" name="nome" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">CPF / CNPJ</label>
                            <input type="text" class="form-control" name="cpf_cnpj" id="cpfCnpj">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">E-mail</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Telefone</label>
                            <input type="text" class="form-control" name="telefone">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Celular/WhatsApp</label>
                            <input type="text" class="form-control" name="celular">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">CEP</label>
                            <input type="text" class="form-control" name="cep" id="cep">
                        </div>
                        <div class="col-md-7">
                            <label class="form-label">Endereço</label>
                            <input type="text" class="form-control" name="endereco">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Número</label>
                            <input type="text" class="form-control" name="numero">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Complemento</label>
                            <input type="text" class="form-control" name="complemento">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bairro</label>
                            <input type="text" class="form-control" name="bairro">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Cidade</label>
                            <input type="text" class="form-control" name="cidade">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">UF</label>
                            <input type="text" class="form-control" name="estado" maxlength="2">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="salvarCliente()">Salvar Cliente</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Visualizar Cliente -->
<div class="modal fade" id="modalVisualizarCliente" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes do Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formVisualizarEditarCliente">
                    <input type="hidden" name="action" value="atualizar_cliente">
                    <input type="hidden" name="cliente_id" id="clienteIdEdicao">
                    <div id="detalhesCliente">
                        <!-- Conteúdo carregado via AJAX: campos em modo somente leitura inicialmente -->
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger me-auto" id="btnExcluirCliente" style="display:none" onclick="excluirCliente(document.getElementById('clienteIdEdicao').value)">
                    <i class="bi bi-trash"></i> Excluir
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-outline-primary" id="btnEditarCliente" onclick="toggleEdicaoCliente(true)">
                    <i class="bi bi-pencil"></i> Editar
                </button>
                <button type="button" class="btn btn-primary" id="btnSalvarEdicao" style="display:none" onclick="salvarEdicaoCliente()">
                    <i class="bi bi-check2"></i> Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Busca em tempo real
document.getElementById('buscarCliente')?.addEventListener('input', function() {
    filtrarClientes();
});

document.getElementById('filtroTipo')?.addEventListener('change', function() {
    filtrarClientes();
});

document.getElementById('filtroStatus')?.addEventListener('change', function() {
    filtrarClientes();
});

function filtrarClientes() {
    const busca = (document.getElementById('buscarCliente').value || '').toLowerCase();
    const buscaDigits = busca.replace(/\D/g,'');
    const tipo = document.getElementById('filtroTipo').value;
    const status = document.getElementById('filtroStatus').value;
    const linhas = document.querySelectorAll('#tabelaClientes tbody tr');
    
    linhas.forEach(linha => {
        const texto = linha.textContent.toLowerCase();
        const tipoCol = linha.querySelector('td:nth-child(2)');
        const statusCol = linha.querySelector('td:nth-child(5) .badge');
        const tipoCliente = (tipoCol?.textContent || '').toLowerCase().includes('física') ? 'pf' : 'pj';
        const statusCliente = (statusCol?.textContent || '').trim().toLowerCase();
        const docCol = linha.querySelector('td:nth-child(3)');
        const docDigits = (docCol?.textContent || '').replace(/\D/g,'');
        
        let mostrar = true;
        
        if (busca && !texto.includes(busca)) {
            // Se busca for numérica, também comparar contra CPF/CNPJ sem máscara
            if (buscaDigits && !docDigits.includes(buscaDigits)) {
                mostrar = false;
            }
        }
        
        if (tipo && tipoCliente !== tipo) {
            mostrar = false;
        }
        
        if (status && statusCliente !== status) {
            mostrar = false;
        }
        
        linha.style.display = mostrar ? '' : 'none';
    });
}

// Utilitários de máscara
function onlyDigits(v){ return (v||'').replace(/\D/g,''); }
function maskCpfCnpj(v){
    const d = onlyDigits(v);
    if (d.length <= 11){
        return d
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    }
    return d
        .replace(/(\d{2})(\d)/, '$1.$2')
        .replace(/(\d{2})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1/$2')
        .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
}
function maskCEP(v){
    const d = onlyDigits(v).slice(0,8);
    if (d.length <= 5) return d;
    return d.slice(0,5) + '-' + d.slice(5);
}
function maskPhone(v){
    const d = onlyDigits(v).slice(0,11);
    if (d.length <= 10){
        return d
            .replace(/(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{4})(\d)/, '$1-$2');
    }
    return d
        .replace(/(\d{2})(\d)/, '($1) $2')
        .replace(/(\d{5})(\d{4})$/, '$1-$2');
}

function attachClienteMasks(formEl){
    if (!formEl) return;
    const inpCpf = formEl.querySelector('input[name="cpf_cnpj"]') || document.getElementById('cpfCnpj');
    const inpCEP = formEl.querySelector('input[name="cep"]') || document.getElementById('cep');
    const inpTel = formEl.querySelector('input[name="telefone"]');
    const inpCel = formEl.querySelector('input[name="celular"]');

    if (inpCpf){ inpCpf.addEventListener('input', e=> e.target.value = maskCpfCnpj(e.target.value)); }
    if (inpCEP){ 
        inpCEP.addEventListener('input', e=> e.target.value = maskCEP(e.target.value));
        inpCEP.addEventListener('blur', function(e){
            const cep = onlyDigits(e.target.value);
            if (cep.length === 8){
                fetch(`https://viacep.com.br/ws/${cep}/json/`)
                    .then(r=>r.json())
                    .then(data=>{
                        if (!data.erro){
                            const targetForm = formEl;
                            (targetForm.querySelector('[name="endereco"]')||document.querySelector('[name="endereco"]')).value = data.logradouro||'';
                            (targetForm.querySelector('[name="bairro"]')||document.querySelector('[name="bairro"]')).value = data.bairro||'';
                            (targetForm.querySelector('[name="cidade"]')||document.querySelector('[name="cidade"]')).value = data.localidade||'';
                            (targetForm.querySelector('[name="estado"]')||document.querySelector('[name="estado"]')).value = data.uf||'';
                        }
                    });
            }
        });
    }
    if (inpTel){ inpTel.addEventListener('input', e=> e.target.value = maskPhone(e.target.value)); }
    if (inpCel){ inpCel.addEventListener('input', e=> e.target.value = maskPhone(e.target.value)); }
}

// Aplicar máscaras no formulário Novo Cliente
attachClienteMasks(document.getElementById('formNovoCliente'));

async function salvarCliente() {
    const form = document.getElementById('formNovoCliente');
    const formData = new FormData(form);
    formData.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
    
    try {
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Cliente cadastrado com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + result.error);
        }
    } catch (error) {
        alert('Erro ao salvar cliente');
    }
}

async function visualizarCliente(id) {
    const formData = new FormData();
    formData.append('action', 'obter_cliente');
    formData.append('cliente_id', id);
    formData.append('csrf_token', document.querySelector('[name="csrf_token"]').value);

    try {
        const response = await fetch('', { method: 'POST', body: formData });
        const result = await response.json();
        if (!result.success) { throw new Error(result.error || 'Falha ao obter cliente'); }

        const c = result.cliente;
        document.getElementById('clienteIdEdicao').value = c.id;
        // Renderizar campos (read-only)
        const html = `
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Tipo de Pessoa</label>
                    <select class="form-select" name="tipo" disabled>
                        <option value="pf" ${c.tipo==='pf'?'selected':''}>Pessoa Física</option>
                        <option value="pj" ${c.tipo==='pj'?'selected':''}>Pessoa Jurídica</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status" disabled>
                        <option value="ativo" ${c.status==='ativo'?'selected':''}>Ativo</option>
                        <option value="inativo" ${c.status==='inativo'?'selected':''}>Inativo</option>
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-8">
                    <label class="form-label">Nome / Razão Social</label>
                    <input type="text" class="form-control" name="nome" value="${c.nome ?? ''}" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label">CPF / CNPJ</label>
                    <input type="text" class="form-control" name="cpf_cnpj" value="${maskCpfCnpj(c.cpf_cnpj ?? '')}" disabled>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">E-mail</label>
                    <input type="email" class="form-control" name="email" value="${c.email ?? ''}" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Telefone</label>
                    <input type="text" class="form-control" name="telefone" value="${c.telefone ?? ''}" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Celular/WhatsApp</label>
                    <input type="text" class="form-control" name="celular" value="${c.celular ?? ''}" disabled>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">CEP</label>
                    <input type="text" class="form-control" name="cep" value="${c.cep ?? ''}" disabled>
                </div>
                <div class="col-md-7">
                    <label class="form-label">Endereço</label>
                    <input type="text" class="form-control" name="endereco" value="${c.endereco ?? ''}" disabled>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Número</label>
                    <input type="text" class="form-control" name="numero" value="${c.numero ?? ''}" disabled>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Complemento</label>
                    <input type="text" class="form-control" name="complemento" value="${c.complemento ?? ''}" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Bairro</label>
                    <input type="text" class="form-control" name="bairro" value="${c.bairro ?? ''}" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cidade</label>
                    <input type="text" class="form-control" name="cidade" value="${c.cidade ?? ''}" disabled>
                </div>
                <div class="col-md-1">
                    <label class="form-label">UF</label>
                    <input type="text" class="form-control" name="estado" value="${c.estado ?? ''}" maxlength="2" disabled>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Observações</label>
                <textarea class="form-control" name="observacoes" rows="3" disabled>${c.observacoes ?? ''}</textarea>
            </div>
        `;
        document.getElementById('detalhesCliente').innerHTML = html;

        // Ajustar botões
        document.getElementById('btnEditarCliente').style.display = '';
        document.getElementById('btnSalvarEdicao').style.display = 'none';
        document.getElementById('btnExcluirCliente').style.display = 'none';

        const modal = new bootstrap.Modal(document.getElementById('modalVisualizarCliente'));
        // Aplicar máscaras nos campos do modal
        attachClienteMasks(document.getElementById('formVisualizarEditarCliente'));
        modal.show();
    } catch (error) {
        alert(error.message || 'Erro ao visualizar cliente');
    }
}

function toggleEdicaoCliente(ativar) {
    const form = document.getElementById('formVisualizarEditarCliente');
    form.querySelectorAll('input, select, textarea').forEach(el => {
        if (el.name === 'cliente_id' || el.name === 'action') return;
        el.disabled = !ativar;
    });
    document.getElementById('btnEditarCliente').style.display = ativar ? 'none' : '';
    document.getElementById('btnSalvarEdicao').style.display = ativar ? '' : 'none';
    document.getElementById('btnExcluirCliente').style.display = ativar ? '' : 'none';
}

async function salvarEdicaoCliente() {
    const form = document.getElementById('formVisualizarEditarCliente');
    const formData = new FormData(form);
    formData.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
    try {
        const response = await fetch('', { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) {
            alert('Cliente atualizado com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + (result.error || 'Falha ao atualizar'));
        }
    } catch (error) {
        alert('Erro ao salvar alterações');
    }
}

async function excluirCliente(id) {
    if (!confirm('Tem certeza que deseja excluir este cliente?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'excluir_cliente');
    formData.append('cliente_id', id);
    formData.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
    
    try {
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Cliente excluído com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + result.error);
        }
    } catch (error) {
        alert('Erro ao excluir cliente');
    }
}
</script>
