</script>
<!-- Importa JS principal do sistema -->
<script src="/www/v2/public/js/app.js"></script>
<?php
// Detectar se está no buscador.php ou no index.php
$is_buscador = (basename($_SERVER['PHP_SELF']) === 'buscador.php');
$base_url = $is_buscador ? 'index.php' : '';
// Mock/Plano do cliente (teste): derivar nome do plano pelas licenças ativas
$licencas_ativas = $_SESSION['user_licencas'] ?? [];
$user_name = $_SESSION['user_name'] ?? 'Usuário';
$user_email = $_SESSION['user_email'] ?? '';
$plano_nome = (is_array($licencas_ativas) && count($licencas_ativas) > 1) ? 'Jurídico Premium' : 'Jurídico Pro';
$beneficios_plano = (strpos($plano_nome, 'Premium') !== false)
    ? ['Gestão Financeira avançada', 'Clientes e Processos', 'Buscador TJ', 'Calculadoras Pro', 'Relatórios avançados', 'Suporte prioritário']
    : ['Gestão Financeira', 'Clientes e Processos', 'Buscador TJ', 'Calculadoras'];
// Conteúdo HTML para popover dos benefícios
$beneficios_html = '<div style="min-width:260px"><strong>'.$plano_nome."</strong><ul style=\"margin:8px 0 0; padding-left:18px;\">".
    implode('', array_map(function($b){ return '<li>'.htmlspecialchars($b).'</li>'; }, $beneficios_plano)).
    '</ul></div>';
?>
<!-- Header -->
<header class="header">
    <div class="container-fluid ps-3 ps-md-4 pe-1 pe-md-0 position-relative">
        <div class="header-bar container">
            <div class="header-start d-flex align-items-center">
                <!-- Botão de menu (agora visível em todos os tamanhos) -->
                <button type="button" class="btn btn-sm btn-outline-light mobile-menu-button drawer-toggle me-2 d-inline-flex" aria-controls="mobileDrawer" aria-expanded="false" aria-label="Abrir menu" title="Menu">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="logo" id="adminLogo" style="cursor:pointer;">⚖️ Precifex ADV</h1>
            </div>
            <div class="header-center">
                <!-- Menu superior removido: navegação concentrada apenas no menu lateral -->
            </div>
            <div class="header-end d-flex align-items-center justify-content-end">
                <div class="user-info">
                    <!-- Notificações (apenas desktop) -->
                    <button type="button" class="btn btn-sm btn-outline-light d-inline-flex position-relative" title="Notificações">
                        <i class="bi bi-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">7</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Drawer Mobile (abre da esquerda) -->
<div class="drawer-backdrop" id="drawerBackdrop" hidden></div>
<aside id="mobileDrawer" class="mobile-drawer" aria-hidden="true" tabindex="-1">
    <div class="mobile-drawer-header">
        <h2 class="mobile-drawer-title">Menu</h2>
        <button type="button" class="btn btn-sm btn-outline-secondary drawer-close" aria-label="Fechar menu">
            <i class="bi bi-x"></i>
        </button>
    </div>
    <div class="mobile-drawer-user">
        <div class="user-avatar" aria-hidden="true"><?= strtoupper(mb_substr($user_name, 0, 1)) ?></div>
        <div class="user-meta">
            <div class="user-meta-name"><?= htmlspecialchars($user_name) ?></div>
            <?php if (!empty($user_email)): ?>
            <div class="user-meta-email"><?= htmlspecialchars($user_email) ?></div>
            <?php endif; ?>
            <div class="user-meta-actions">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="abrirPerfilUsuario()"><i class="bi bi-person"></i> Perfil</button>
            </div>
        </div>
    </div>
    <nav class="mobile-drawer-nav">
        <div class="mobile-drawer-section">Navegação</div>
        <a href="<?= $base_url ?>?aba=dashboard" class="mobile-drawer-link <?= $aba_ativa === 'dashboard' ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="left" title="Dashboard"><i class="bi bi-speedometer2"></i> <span class="link-label">Dashboard</span></a>
        <a href="<?= $base_url ?>?aba=clientes" class="mobile-drawer-link <?= $aba_ativa === 'clientes' ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="left" title="Clientes"><i class="bi bi-people"></i> <span class="link-label">Clientes</span></a>
        <a href="<?= $base_url ?>?aba=processos" class="mobile-drawer-link <?= $aba_ativa === 'processos' ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="left" title="Processos"><i class="bi bi-briefcase"></i> <span class="link-label">Processos</span></a>
        <a href="<?= $base_url ?>?aba=kanban" class="mobile-drawer-link <?= $aba_ativa === 'kanban' ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="left" title="Tarefas"><i class="bi bi-kanban"></i> <span class="link-label">Tarefas</span></a>
        <a href="buscador.php" class="mobile-drawer-link <?= $aba_ativa === 'buscador' ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="left" title="Buscador"><i class="bi bi-search"></i> <span class="link-label">Buscador</span></a>
        <a href="<?= $base_url ?>?aba=financeiro" class="mobile-drawer-link <?= $aba_ativa === 'financeiro' ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="left" title="Financeiro"><i class="bi bi-currency-dollar"></i> <span class="link-label">Financeiro</span></a>
        <a href="<?= $base_url ?>?aba=calculadoras" class="mobile-drawer-link <?= $aba_ativa === 'calculadoras' ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="left" title="Calculadoras"><i class="bi bi-calculator"></i> <span class="link-label">Calculadoras</span></a>
    </nav>
    <div class="mobile-drawer-footer">
        <a href="#" class="mobile-drawer-link plan" data-bs-toggle="tooltip" data-bs-placement="left" title="Plano: <?= htmlspecialchars($plano_nome) ?>">
            <i class="bi bi-stars"></i> <span class="link-label">Plano: <?= htmlspecialchars($plano_nome) ?></span>
        </a>
        <a href="sistemas/logout.php" class="mobile-drawer-link logout" data-bs-toggle="tooltip" data-bs-placement="left" title="Sair"><i class="bi bi-box-arrow-right"></i> <span class="link-label">Sair</span></a>
    </div>
    <!-- Para foco inicial acessível -->
    <span class="sr-only" aria-hidden="true"></span>
    
</aside>

<!-- Modal Perfil do Usuário -->
<div class="modal fade" id="modalPerfilUsuario" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header align-items-center">
                <h5 class="modal-title d-flex align-items-center gap-2">Perfil do Usuário
                    <span class="badge bg-primary">Plano: <?= htmlspecialchars($plano_nome) ?></span>
                    <span class="plan-icons d-inline-flex align-items-center gap-2">
                        <i class="bi bi-shield-check text-success" title="Segurança ativada"></i>
                        <i class="bi bi-stars text-warning" title="Benefícios do plano" data-bs-toggle="popover" data-bs-html="true" data-bs-content='<?= $beneficios_html ?>'></i>
                    </span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formPerfilUsuario">
                    <input type="hidden" name="action" value="atualizar_usuario">
                    <!-- Nome primeiro -->
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" class="form-control" name="nome" id="perfilNome" value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>" disabled>
                    </div>
                    <!-- Telefone e Email na mesma linha -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Telefone</label>
                            <input type="text" class="form-control" name="telefone" id="perfilTelefone" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="perfilEmail" value="<?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>" disabled>
                        </div>
                    </div>
                    <!-- OAB e Escritório na mesma linha -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">OAB</label>
                            <input type="text" class="form-control" name="oab" id="perfilOab" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Escritório</label>
                            <input type="text" class="form-control" name="escritorio" id="perfilEscritorio" disabled>
                        </div>
                    </div>
                    <!-- CEP e Endereço na mesma linha -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">CEP</label>
                            <input type="text" class="form-control" name="cep" id="perfilCep" placeholder="00000-000" disabled>
                            <small class="text-muted">Preencha o CEP para autocompletar Cidade e UF.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Endereço</label>
                            <input type="text" class="form-control" name="endereco" id="perfilEndereco" disabled>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Cidade</label>
                            <input type="text" class="form-control" name="cidade" id="perfilCidade" disabled>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estado (UF)</label>
                            <select class="form-select" name="estado" id="perfilEstado" disabled>
                                <option value="">Selecionar</option>
                                <option value="AC">AC</option>
                                <option value="AL">AL</option>
                                <option value="AP">AP</option>
                                <option value="AM">AM</option>
                                <option value="BA">BA</option>
                                <option value="CE">CE</option>
                                <option value="DF">DF</option>
                                <option value="ES">ES</option>
                                <option value="GO">GO</option>
                                <option value="MA">MA</option>
                                <option value="MT">MT</option>
                                <option value="MS">MS</option>
                                <option value="MG">MG</option>
                                <option value="PA">PA</option>
                                <option value="PB">PB</option>
                                <option value="PR">PR</option>
                                <option value="PE">PE</option>
                                <option value="PI">PI</option>
                                <option value="RJ">RJ</option>
                                <option value="RN">RN</option>
                                <option value="RS">RS</option>
                                <option value="RO">RO</option>
                                <option value="RR">RR</option>
                                <option value="SC">SC</option>
                                <option value="SP">SP</option>
                                <option value="SE">SE</option>
                                <option value="TO">TO</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-outline-primary" id="perfilBtnEditar" onclick="toggleEdicaoPerfil(true)"><i class="bi bi-pencil"></i> Editar</button>
                <button type="button" class="btn btn-primary" id="perfilBtnSalvar" onclick="salvarPerfilUsuario()" style="display:none"><i class="bi bi-check2"></i> Salvar</button>
            </div>
        </div>
    </div>
</div>

<script>
async function abrirPerfilUsuario(){
    try{
        const fd = new FormData();
        fd.append('action','obter_usuario');
        fd.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
        const r = await fetch('', {method:'POST', body: fd});
        const res = await r.json();
        if (!res.success) throw new Error(res.error||'Falha ao obter perfil');
        const u = res.usuario;
        document.getElementById('perfilEmail').value = u.email || '';
        document.getElementById('perfilNome').value = u.nome || '';
        document.getElementById('perfilTelefone').value = u.telefone || '';
        document.getElementById('perfilOab').value = u.oab || '';
        document.getElementById('perfilEscritorio').value = u.escritorio || '';
        document.getElementById('perfilCep').value = u.cep || '';
        document.getElementById('perfilEndereco').value = u.endereco || '';
        document.getElementById('perfilCidade').value = u.cidade || '';
        document.getElementById('perfilEstado').value = (u.estado||'').toUpperCase();
        // Resetar botões e estado
        document.querySelectorAll('#formPerfilUsuario input').forEach(el=> el.disabled = true);
        document.getElementById('perfilBtnEditar').style.display = '';
        document.getElementById('perfilBtnSalvar').style.display = 'none';
        const modalEl = document.getElementById('modalPerfilUsuario');
        new bootstrap.Modal(modalEl).show();
        // Inicializar popovers de benefícios (teste)
        modalEl.querySelectorAll('[data-bs-toggle="popover"]').forEach(el=>{
            new bootstrap.Popover(el, {html:true, trigger:'hover focus', placement:'auto'});
        });
    }catch(e){
        alert(e.message||'Erro ao abrir perfil');
    }
}

function toggleEdicaoPerfil(ativar){
    document.querySelectorAll('#formPerfilUsuario input').forEach(el=>{
        if (el.name === 'email') { el.disabled = true; return; }
        el.disabled = !ativar;
    });
    document.querySelectorAll('#formPerfilUsuario select').forEach(el=> el.disabled = !ativar);
    document.getElementById('perfilBtnEditar').style.display = ativar ? 'none' : '';
    document.getElementById('perfilBtnSalvar').style.display = ativar ? '' : 'none';
}

async function salvarPerfilUsuario(){
    const form = document.getElementById('formPerfilUsuario');
    const fd = new FormData(form);
    fd.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
    try{
        const r = await fetch('', {method:'POST', body: fd});
        const res = await r.json();
        if (!res.success) throw new Error(res.error||'Falha ao salvar perfil');
        // Atualizar nome exibido no header
        const novoNome = form.querySelector('[name="nome"]').value;
        if (novoNome){
            document.querySelector('.user-name').textContent = novoNome;
        }
        alert('Perfil atualizado com sucesso!');
        bootstrap.Modal.getInstance(document.getElementById('modalPerfilUsuario')).hide();
    }catch(e){
        alert(e.message||'Erro ao salvar perfil');
    }
}

// Máscara de telefone brasileiro (formato dinâmico)
function formatTelefone(v){
    const d = String(v||'').replace(/\D/g,'').slice(0,11);
    if (d.length <= 10){
        // (11) 1234-5678
        const p1 = d.slice(0,2);
        const p2 = d.slice(2,6);
        const p3 = d.slice(6,10);
        return [p1?`(${p1})`:'', p2?` ${p2}`:'', p3?`-${p3}`:''].join('');
    } else {
        // (11) 91234-5678
        const p1 = d.slice(0,2);
        const p2 = d.slice(2,7);
        const p3 = d.slice(7,11);
        return [p1?`(${p1})`:'', p2?` ${p2}`:'', p3?`-${p3}`:''].join('');
    }
}

// Aplicar máscara ao digitar
document.getElementById('perfilTelefone')?.addEventListener('input', (e)=>{
    e.target.value = formatTelefone(e.target.value);
});

// Máscara de CEP e autocompletar com ViaCEP
function formatCep(v){
    const d = String(v||'').replace(/\D/g,'').slice(0,8);
    if (d.length <= 5){
        return d;
    }
    return d.slice(0,5) + '-' + d.slice(5);
}

async function buscarViaCep(cep){
    const d = String(cep||'').replace(/\D/g,'');
    if (d.length !== 8) return;
    try{
        const r = await fetch(`https://viacep.com.br/ws/${d}/json/`);
        const data = await r.json();
        if (data.erro) return;
        // Preencher cidade, UF e endereço
        document.getElementById('perfilCidade').value = data.localidade || '';
        document.getElementById('perfilEstado').value = (data.uf||'').toUpperCase();
        const logradouro = data.logradouro || '';
        const bairro = data.bairro ? `, ${data.bairro}` : '';
        document.getElementById('perfilEndereco').value = (logradouro + bairro).trim();
    }catch(e){ /* ignorar erros */ }
}

document.getElementById('perfilCep')?.addEventListener('input', (e)=>{
    e.target.value = formatCep(e.target.value);
    buscarViaCep(e.target.value);
});
</script>