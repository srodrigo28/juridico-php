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

<!-- Lista de Clientes -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Lista de Clientes</h5>
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
                            <td><?= sanitizar($cliente['cpf_cnpj'] ?: 'N/A') ?></td>
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
                                <button type="button" class="btn btn-sm btn-info" onclick="visualizarCliente(<?= $cliente['id'] ?>)">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-primary" onclick="editarCliente(<?= $cliente['id'] ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="excluirCliente(<?= $cliente['id'] ?>)">
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
            <div class="modal-body" id="detalhesCliente">
                <!-- Conteúdo carregado via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
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
    const busca = document.getElementById('buscarCliente').value.toLowerCase();
    const tipo = document.getElementById('filtroTipo').value;
    const status = document.getElementById('filtroStatus').value;
    const linhas = document.querySelectorAll('#tabelaClientes tbody tr');
    
    linhas.forEach(linha => {
        const texto = linha.textContent.toLowerCase();
        const tipoCliente = linha.querySelector('td:nth-child(2)').textContent.includes('Física') ? 'pf' : 'pj';
        const statusCliente = linha.querySelector('td:nth-child(5) .badge').textContent.toLowerCase();
        
        let mostrar = true;
        
        if (busca && !texto.includes(busca)) {
            mostrar = false;
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

// Máscara de CPF/CNPJ
document.getElementById('cpfCnpj')?.addEventListener('input', function(e) {
    let valor = e.target.value.replace(/\D/g, '');
    
    if (valor.length <= 11) {
        // CPF
        valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
        valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
        valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    } else {
        // CNPJ
        valor = valor.replace(/^(\d{2})(\d)/, '$1.$2');
        valor = valor.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
        valor = valor.replace(/\.(\d{3})(\d)/, '.$1/$2');
        valor = valor.replace(/(\d{4})(\d)/, '$1-$2');
    }
    
    e.target.value = valor;
});

// Buscar CEP
document.getElementById('cep')?.addEventListener('blur', function(e) {
    const cep = e.target.value.replace(/\D/g, '');
    
    if (cep.length === 8) {
        fetch(`https://viacep.com.br/ws/${cep}/json/`)
            .then(response => response.json())
            .then(data => {
                if (!data.erro) {
                    document.querySelector('[name="endereco"]').value = data.logradouro;
                    document.querySelector('[name="bairro"]').value = data.bairro;
                    document.querySelector('[name="cidade"]').value = data.localidade;
                    document.querySelector('[name="estado"]').value = data.uf;
                }
            });
    }
});

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
    // Implementar visualização
    alert('Funcionalidade em desenvolvimento');
}

async function editarCliente(id) {
    // Implementar edição
    alert('Funcionalidade em desenvolvimento');
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
