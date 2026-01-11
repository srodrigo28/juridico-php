<?php
/**
 * Rota sugerida (não ligada no menu):
 * - Via index: index.php?aba=novo_usuario
 *   (Adicione 'novo_usuario' à lista de abas válidas no index.php)
 * - Acesso direto: views/novo_usuario.php (esta view define a constante necessária)
 */

if (!defined('SISTEMA_MEMBROS')) {
    define('SISTEMA_MEMBROS', true);
}

require_once __DIR__ . '/../sistemas/config.php';
require_once __DIR__ . '/../config/database.php';

$sessStarted = function_exists('session_status') ? (session_status() === PHP_SESSION_ACTIVE) : false;
if (!$sessStarted) { @session_start(); }

$pdo = conectarBanco();
criarTabelas($pdo);

// Garantir tabela de licenças (caso ainda não tenha sido criada pelo sistema)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS licencas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        produto_id VARCHAR(32) NOT NULL,
        produto_nome VARCHAR(255) NULL,
        status_licenca ENUM('ativa','inativa') DEFAULT 'ativa',
        data_expiracao DATE NULL,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_produto (produto_id),
        INDEX idx_status (status_licenca),
        INDEX idx_expira (data_expiracao)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Throwable $e) { /* silencioso */ }

$alertMsg = '';
$alertType = 'info';

function gerarUsuarioId(string $email): string {
    $local = strtolower(substr($email, 0, strpos($email, '@') !== false ? strpos($email, '@') : strlen($email)));
    $slug = preg_replace('/[^a-z0-9]+/i', '_', $local);
    $slug = trim($slug, '_');
    if ($slug === '') { $slug = 'usuario'; }
    if (strlen($slug) > 100) { $slug = substr($slug, 0, 100); }
    return $slug;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? 'create';
    $email = trim($_POST['email'] ?? '');
    $nome = trim($_POST['nome'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $cep = preg_replace('/\D+/', '', $_POST['cep'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $estado = strtoupper(trim($_POST['estado'] ?? ''));
    $idUsuario = (int)($_POST['id_usuario'] ?? 0);

    // Normalização de texto para Cidade e Endereço (title case)
    function _normalizeTitleCase($s){
        $s = trim($s ?? '');
        if ($s === '') return '';
        if (function_exists('mb_convert_case')) { return mb_convert_case($s, MB_CASE_TITLE, 'UTF-8'); }
        return ucwords(strtolower($s));
    }
    $cidade = _normalizeTitleCase($cidade);
    $endereco = _normalizeTitleCase($endereco);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $alertMsg = 'Email inválido.';
        $alertType = 'danger';
    } else {
        try {
            if ($acao === 'create') {
                // Validação obrigatória para criação
                $licProd = $_POST['lic_produto_id'] ?? '';
                $licDur = $_POST['lic_duracao'] ?? '';
                $cepValido = (strlen($cep) === 8);
                $estadoValido = (strlen($estado) === 2);
                if ($nome === '' || $senha === '' || $telefone === '' || !$cepValido || $endereco === '' || $cidade === '' || !$estadoValido || $licProd === '' || $licDur === '') {
                    throw new Exception('Preencha todos os campos obrigatórios corretamente.');
                }
                $usuarioId = gerarUsuarioId($email);
                $check = $pdo->prepare('SELECT 1 FROM usuarios_perfil WHERE usuario_id = ?');
                $base = $usuarioId;
                $suffix = 1;
                while (true) {
                    $check->execute([$usuarioId]);
                    if (!$check->fetch()) break;
                    $usuarioId = $base . '_' . $suffix;
                    $suffix++;
                    if (strlen($usuarioId) > 100) { $usuarioId = substr($usuarioId, 0, 100); }
                }
                $exists = $pdo->prepare('SELECT 1 FROM usuarios_perfil WHERE email = ?');
                $exists->execute([$email]);
                if ($exists->fetch()) {
                    $alertMsg = 'Já existe um perfil com este email.';
                    $alertType = 'warning';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO usuarios_perfil (usuario_id, email, nome, telefone, cep, endereco, cidade, estado) VALUES (?,?,?,?,?,?,?,?)');
                    $stmt->execute([$usuarioId, $email, $nome !== '' ? $nome : null, $telefone !== '' ? $telefone : null, $cep !== '' ? $cep : null, $endereco !== '' ? $endereco : null, $cidade !== '' ? $cidade : null, $estado !== '' ? $estado : null]);
                    if ($senha !== '') {
                        $senhaHash = hashSenha($senha);
                        $stmt2 = $pdo->prepare("INSERT INTO usuarios_sistema (email, senha, criado_em) VALUES (?,?, NOW()) ON DUPLICATE KEY UPDATE senha=VALUES(senha), atualizado_em=NOW(), token_senha=NULL, token_expiry=NULL");
                        $stmt2->execute([$email, $senhaHash]);
                    }
                    // Criar licença ativa automaticamente para o produto 'Precifex Jurídico' (ID 5776734)
                    try {
                        $produtoId = $_POST['lic_produto_id'] ?? '5776734';
                        $produtoNome = ($produtoId === '4737273') ? 'Pesquisa de Preços' : 'Precifex Jurídico';
                        // Campo de expiração manual removido do modal; calcular via duração
                        $licDuracao = $_POST['lic_duracao'] ?? '';
                        $expCalc = null;
                        if ($licDuracao === '7d') { $expCalc = date('Y-m-d', strtotime('+7 days')); }
                        elseif ($licDuracao === '30d') { $expCalc = date('Y-m-d', strtotime('+30 days')); }
                        elseif ($licDuracao === '1y') { $expCalc = date('Y-m-d', strtotime('+1 year')); }
                        $expFinal = $expCalc ?: null;
                        $chkLic = $pdo->prepare('SELECT 1 FROM licencas WHERE email=? AND produto_id=?');
                        $chkLic->execute([$email, $produtoId]);
                        if (!$chkLic->fetch()) {
                            $insLic = $pdo->prepare('INSERT INTO licencas (email, produto_id, produto_nome, status_licenca, data_expiracao) VALUES (?,?,?,?,?)');
                            $insLic->execute([$email, $produtoId, $produtoNome, 'ativa', $expFinal]);
                        } else {
                            $updLic = $pdo->prepare("UPDATE licencas SET status_licenca='ativa', atualizado_em=NOW() WHERE email=? AND produto_id=?");
                            $updLic->execute([$email, $produtoId]);
                        }
                    } catch (Throwable $e) { /* se falhar, segue cadastro */ }
                    $alertMsg = 'Usuário cadastrado com sucesso.';
                    $alertType = 'success';
                }
            } elseif ($acao === 'update') {
                // Validação obrigatória para edição (exceto senha)
                $licProd = $_POST['lic_produto_id'] ?? '';
                $licDur = $_POST['lic_duracao'] ?? '';
                $licStatus = $_POST['lic_status'] ?? '';
                $cepValido = (strlen($cep) === 8);
                $estadoValido = (strlen($estado) === 2);
                if ($nome === '' || $telefone === '' || !$cepValido || $endereco === '' || $cidade === '' || !$estadoValido || $licProd === '' || $licDur === '' || !in_array($licStatus, ['ativa','inativa'])) {
                    throw new Exception('Preencha todos os campos obrigatórios corretamente.');
                }
                if ($idUsuario <= 0) { throw new Exception('ID inválido'); }
                $stmt = $pdo->prepare('UPDATE usuarios_perfil SET nome=?, telefone=?, cep=?, endereco=?, cidade=?, estado=? WHERE id=? AND email=?');
                $stmt->execute([$nome !== '' ? $nome : null, $telefone !== '' ? $telefone : null, $cep !== '' ? $cep : null, $endereco !== '' ? $endereco : null, $cidade !== '' ? $cidade : null, $estado !== '' ? $estado : null, $idUsuario, $email]);
                if ($senha !== '') {
                    $senhaHash = hashSenha($senha);
                    $stmt2 = $pdo->prepare("INSERT INTO usuarios_sistema (email, senha, criado_em) VALUES (?,?, NOW()) ON DUPLICATE KEY UPDATE senha=VALUES(senha), atualizado_em=NOW(), token_senha=NULL, token_expiry=NULL");
                    $stmt2->execute([$email, $senhaHash]);
                }
                // Atualizar licença do produto padrão
                $licProdutoId = $_POST['lic_produto_id'] ?? '5776734';
                $licStatus = $_POST['lic_status'] ?? 'ativa';
                // Campo de expiração manual removido do modal; calcular via duração
                $licDuracao = $_POST['lic_duracao'] ?? '';
                $expCalc = null;
                if ($licDuracao === '7d') { $expCalc = date('Y-m-d', strtotime('+7 days')); }
                elseif ($licDuracao === '30d') { $expCalc = date('Y-m-d', strtotime('+30 days')); }
                elseif ($licDuracao === '1y') { $expCalc = date('Y-m-d', strtotime('+1 year')); }
                $expFinal = $expCalc ?: null;
                if (!in_array($licStatus, ['ativa','inativa'])) { $licStatus = 'ativa'; }
                try {
                    $chkLic = $pdo->prepare('SELECT id FROM licencas WHERE email=? AND produto_id=?');
                    $chkLic->execute([$email, $licProdutoId]);
                    $produtoNome = 'Precifex Jurídico';
                    if ($chkLic->fetch()) {
                        $upd = $pdo->prepare('UPDATE licencas SET status_licenca=?, data_expiracao=?, atualizado_em=NOW() WHERE email=? AND produto_id=?');
                        $upd->execute([$licStatus, $expFinal, $email, $licProdutoId]);
                    } else {
                        $ins = $pdo->prepare('INSERT INTO licencas (email, produto_id, produto_nome, status_licenca, data_expiracao) VALUES (?,?,?,?,?)');
                        $ins->execute([$email, $licProdutoId, $produtoNome, $licStatus, $expFinal]);
                    }
                } catch (Throwable $e) { /* silencioso */ }
                $alertMsg = 'Usuário atualizado com sucesso.';
                $alertType = 'success';
            } elseif ($acao === 'delete') {
                if ($idUsuario <= 0) { throw new Exception('ID inválido'); }
                $emailDel = $email;
                $stmt = $pdo->prepare('DELETE FROM usuarios_perfil WHERE id=? AND email=?');
                $stmt->execute([$idUsuario, $emailDel]);
                $stmt2 = $pdo->prepare('DELETE FROM usuarios_sistema WHERE email=?');
                $stmt2->execute([$emailDel]);
                // Opcional: remover licenças do usuário
                try { $stmt3 = $pdo->prepare('DELETE FROM licencas WHERE email=?'); $stmt3->execute([$emailDel]); } catch (Throwable $e) {}
                $alertMsg = 'Usuário excluído com sucesso.';
                $alertType = 'success';
            }
        } catch (Throwable $e) {
            $alertMsg = 'Erro: ' . $e->getMessage();
            $alertType = 'danger';
        }
    }
}

// Listagem de usuários
$usuarios = [];
try {
    $q = $pdo->query('SELECT id, usuario_id, email, nome, telefone, cep, endereco, cidade, estado, atualizado_em FROM usuarios_perfil ORDER BY atualizado_em DESC, id DESC');
    $usuarios = $q->fetchAll();
} catch (Throwable $e) {
    $alertMsg = $alertMsg ?: ('Erro ao listar usuários: ' . $e->getMessage());
    $alertType = $alertMsg ? $alertType : 'danger';
}
?>

<div class="py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="mb-0">Admin: Novo Usuário & Lista</h2>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small">Ferramenta de teste sem link no menu</span>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoUsuario">
                <i class="bi bi-plus-lg"></i> Novo
            </button>
        </div>
    </div>

    <?php if ($alertMsg): ?>
        <div class="alert alert-<?php echo htmlspecialchars($alertType); ?>"><?php echo htmlspecialchars($alertMsg); ?></div>
    <?php endif; ?>

    <style>
    /* Tooltip theme matching badge status colors */
    .tooltip-success { --bs-tooltip-bg: var(--bs-success); --bs-tooltip-color: #fff; }
    .tooltip-secondary { --bs-tooltip-bg: var(--bs-secondary); --bs-tooltip-color: #fff; }
    </style>

    <!-- Modal: Novo Usuário -->
    <div class="modal fade" id="modalNovoUsuario" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Cadastrar novo usuário</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
          </div>
          <form method="post" autocomplete="off">
            <div class="modal-body">
                <input type="hidden" name="acao" value="create" />
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="exemplo@dominio" required />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nome</label>
                        <input type="text" name="nome" class="form-control" placeholder="Nome do usuário" required />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Telefone</label>
                        <input type="tel" name="telefone" id="novoTelefone" class="form-control" placeholder="(00) 00000-0000" required pattern="^\(\d{2}\)\s?\d{4,5}-\d{4}$" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">CEP</label>
                        <input type="text" name="cep" class="form-control" id="novoCEP" placeholder="00000-000" maxlength="9" required pattern="^\d{5}-\d{3}$" />
                        <div class="form-text">Informe o CEP para preencher endereço automaticamente.</div>
                        <div class="small text-danger d-none" id="novoCEPMsg">CEP não encontrado.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Endereço</label>
                        <input type="text" name="endereco" id="novoEndereco" class="form-control" placeholder="Rua/Avenida, número e complemento" required maxlength="255" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cidade</label>
                        <input type="text" name="cidade" id="novoCidade" class="form-control" placeholder="Cidade" required maxlength="100" />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Estado</label>
                        <input type="text" name="estado" id="novoEstado" class="form-control" placeholder="UF" maxlength="2" required pattern="^[A-Za-z]{2}$" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Senha</label>
                        <input type="password" name="senha" class="form-control" placeholder="Defina uma senha" required />
                        <div class="form-text">Para login funcionar, é necessário senha e licença ativa.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Produto da Licença</label>
                        <select name="lic_produto_id" class="form-select" required>
                            <option value="5776734" selected>Precifex Jurídico</option>
                            <option value="4737273">Pesquisa de Preços</option>
                        </select>
                        <div class="form-text">Licença será criada automaticamente como ativa.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Duração da Licença</label>
                        <select name="lic_duracao" class="form-select" required>
                            <option value="" selected>Selecione...</option>
                            <option value="7d">7 dias</option>
                            <option value="30d">30 dias</option>
                            <option value="1y">1 ano</option>
                        </select>
                        <div class="form-text">Ao escolher, a expiração é calculada automaticamente.</div>
                        <div class="form-text" id="createLicExpPreview">Expiração: —</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary"><i class="bi bi-person-plus"></i> Cadastrar</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light fw-bold d-flex justify-content-between align-items-center">
            <span>Usuários cadastrados</span>
            <div class="d-flex align-items-center gap-2">
                <input id="searchUsuario" type="text" class="form-control form-control-sm" placeholder="Pesquisar por email ou nome" style="width: 280px;" />
                <span class="badge bg-secondary"><?php echo count($usuarios); ?></span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive user-list-scroll" style="max-height:60vh; overflow-y:auto; overflow-x:hidden;">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Nome</th>
                            <th>Atualizado em</th>
                            <th>Expiração</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuarios)): ?>
                            <tr><td colspan="6" class="text-center text-muted">Nenhum usuário cadastrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $u): ?>
                                <tr>
                                    <td class="col-email"><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td class="col-nome"><?php echo htmlspecialchars($u['nome'] ?? ''); ?></td>
                                    <td><?php $upd = $u['atualizado_em'] ?? ''; echo $upd ? htmlspecialchars(date('d/m/Y', strtotime($upd))) : '—'; ?></td>
                                    <?php
                                        // Buscar licença atual do produto padrão (Precifex Jurídico)
                                        $licStatus = '';
                                        $licExp = '';
                                        try {
                                            $stLic = $pdo->prepare('SELECT status_licenca, data_expiracao FROM licencas WHERE email=? AND produto_id=? LIMIT 1');
                                            $stLic->execute([ $u['email'], '5776734' ]);
                                            $licRow = $stLic->fetch();
                                            if ($licRow) { $licStatus = $licRow['status_licenca'] ?? ''; $licExp = $licRow['data_expiracao'] ?? ''; }
                                        } catch (Throwable $e) { /* silencioso */ }
                                        $badgeClass = 'bg-secondary';
                                        if ($licStatus === 'ativa') { $badgeClass = 'bg-success'; }
                                        elseif ($licStatus === 'inativa') { $badgeClass = 'bg-secondary'; }
                                    ?>
                                    <td><?php echo $licExp ? htmlspecialchars(date('d/m/Y', strtotime($licExp))) : '—'; ?></td>
                                    <td>
                                        <span class="badge <?php echo $badgeClass; ?>"
                                              data-bs-toggle="tooltip"
                                              data-bs-title="<?php echo $licStatus ? htmlspecialchars($licStatus) : '—'; ?>"
                                              data-tooltip-class="<?php echo ($badgeClass === 'bg-success' ? 'tooltip-success' : 'tooltip-secondary'); ?>">
                                            <?php echo $licStatus ? htmlspecialchars($licStatus) : '—'; ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" data-bs-target="#modalEditarUsuario"
                                            data-id="<?php echo (int)$u['id']; ?>"
                                            data-email="<?php echo htmlspecialchars($u['email']); ?>"
                                            data-nome="<?php echo htmlspecialchars($u['nome'] ?? ''); ?>"
                                            data-lic-status="<?php echo htmlspecialchars($licStatus); ?>"
                                            data-lic-expiracao="<?php echo htmlspecialchars($licExp); ?>"
                                            data-telefone="<?php echo htmlspecialchars($u['telefone'] ?? ''); ?>"
                                            data-cep="<?php echo htmlspecialchars($u['cep'] ?? ''); ?>"
                                            data-endereco="<?php echo htmlspecialchars($u['endereco'] ?? ''); ?>"
                                            data-cidade="<?php echo htmlspecialchars($u['cidade'] ?? ''); ?>"
                                            data-estado="<?php echo htmlspecialchars($u['estado'] ?? ''); ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="acao" value="delete" />
                                            <input type="hidden" name="id_usuario" value="<?php echo (int)$u['id']; ?>" />
                                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($u['email']); ?>" />
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Excluir este usuário? Isso removerá seus dados associados.');">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Editar Usuário -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Editar Usuário</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <form method="post" autocomplete="off">
        <div class="modal-body">
            <input type="hidden" name="acao" value="update" />
            <input type="hidden" name="id_usuario" id="editIdUsuario" />
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" id="editEmail" class="form-control" readonly />
                    <div class="form-text">Email é imutável para manter vínculos no sistema.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nome</label>
                    <input type="text" name="nome" id="editNome" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nova Senha</label>
                    <input type="password" name="senha" id="editSenha" class="form-control" placeholder="Definir nova senha" required />
                    <div class="form-text">senha atualiza o acesso de login.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Telefone</label>
                    <input type="tel" name="telefone" id="editTelefone" class="form-control" placeholder="(00) 00000-0000" required pattern="^\(\d{2}\)\s?\d{4,5}-\d{4}$" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status da Licença</label>
                    <select name="lic_status" id="editLicStatus" class="form-select" required>
                        <option value="ativa">Ativa</option>
                        <option value="inativa">Inativa</option>
                    </select>
                </div>
                <div class="col-md-4 order-md-7">
                    <label class="form-label">CEP</label>
                    <input type="text" name="cep" id="editCEP" class="form-control" placeholder="00000-000" maxlength="9" required pattern="^\d{5}-\d{3}$" />
                    <div class="form-text text-danger d-none" id="editCEPMsg">CEP não encontrado.</div>
                </div>
                <div class="col-md-4 order-md-9">
                    <label class="form-label">Endereço</label>
                    <input type="text" name="endereco" id="editEndereco" class="form-control" placeholder="Rua/Avenida, número e complemento" required maxlength="255" />
                </div>
                <div class="col-md-4 order-md-8">
                    <label class="form-label">Cidade</label>
                    <input type="text" name="cidade" id="editCidade" class="form-control" placeholder="Cidade" required maxlength="100" />
                </div>
                <div class="col-md-4 order-md-6">
                    <label class="form-label">Estado</label>
                    <input type="text" name="estado" id="editEstado" class="form-control" placeholder="UF" maxlength="2" required pattern="^[A-Za-z]{2}$" />
                </div>
                <div class="col-md-4 order-md-5">
                    <label class="form-label">Produto da Licença</label>
                    <select name="lic_produto_id" id="editLicProdutoId" class="form-select" required>
                        <option value="5776734" selected>Precifex Jurídico</option>
                        <option value="4737273">Pesquisa de Preços</option>
                    </select>
                </div>
                <div class="col-md-4 order-md-4">
                    <label class="form-label">Duração da Licença</label>
                    <select name="lic_duracao" id="editLicDuracao" class="form-select" required>
                        <option value="" selected>Selecione...</option>
                        <option value="7d">7 dias</option>
                        <option value="30d">30 dias</option>
                        <option value="1y">1 ano</option>
                    </select>
                    <div class="form-text" id="editLicExpPreview">Expiração: —</div>
                </div>
                
            </div>
            <div class="alert alert-warning mt-3">Para login, além da senha, é necessário licença ativa para o email.</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
      </form>
    </div>
  </div>
  </div>

<script>
document.getElementById('modalEditarUsuario')?.addEventListener('show.bs.modal', function (event) {
  const button = event.relatedTarget;
  const id = button?.getAttribute('data-id') || '';
  const email = button?.getAttribute('data-email') || '';
  const nome = button?.getAttribute('data-nome') || '';
    const licStatus = button?.getAttribute('data-lic-status') || '';
    const licExp = button?.getAttribute('data-lic-expiracao') || '';
        const telefone = button?.getAttribute('data-telefone') || '';
        const cep = button?.getAttribute('data-cep') || '';
        const endereco = button?.getAttribute('data-endereco') || '';
        const cidade = button?.getAttribute('data-cidade') || '';
        const estado = button?.getAttribute('data-estado') || '';
  document.getElementById('editIdUsuario').value = id;
  document.getElementById('editEmail').value = email;
  document.getElementById('editNome').value = nome;
  document.getElementById('editSenha').value = '';
        document.getElementById('editTelefone').value = telefone;
        document.getElementById('editCEP').value = cep;
        document.getElementById('editEndereco').value = endereco;
        document.getElementById('editCidade').value = cidade;
        document.getElementById('editEstado').value = estado;
    // Licença
    document.getElementById('editLicStatus').value = licStatus || 'ativa';
        const prevEl = document.getElementById('editLicExpPreview');
        if(prevEl){ prevEl.textContent = 'Expiração: ' + (licExp ? new Date(licExp).toLocaleDateString('pt-BR') : '—'); }
        // CEP ViaCEP no editar
        const cepInput = document.getElementById('editCEP');
        const msgEl = document.getElementById('editCEPMsg');
        const enderecoEl = document.getElementById('editEndereco');
        const cidadeEl = document.getElementById('editCidade');
        const estadoEl = document.getElementById('editEstado');
        function limpaEndereco(){ enderecoEl.value=''; cidadeEl.value=''; estadoEl.value=''; }
        function buscarCEP(cepStr){
                const c = (cepStr||'').replace(/\D/g,'');
                if(c.length !== 8){ msgEl?.classList.add('d-none'); return; }
                fetch('https://viacep.com.br/ws/'+c+'/json/')
                        .then(r=>r.json())
                        .then(data=>{
                                if(data.erro){ msgEl?.classList.remove('d-none'); limpaEndereco(); return; }
                                msgEl?.classList.add('d-none');
                                enderecoEl.value = [data.logradouro, data.bairro].filter(Boolean).join(', ');
                                cidadeEl.value = data.localidade || '';
                                estadoEl.value = (data.uf || '').toUpperCase();
                        })
                        .catch(()=>{ msgEl?.classList.remove('d-none'); limpaEndereco(); });
        }
        cepInput?.addEventListener('blur', ()=>buscarCEP(cepInput.value));
});

// Preview de expiração calculada
function _computeExpDate(duration){
    const now = new Date();
    const d = new Date(now);
    if(duration === '7d'){ d.setDate(d.getDate()+7); }
    else if(duration === '30d'){ d.setDate(d.getDate()+30); }
    else if(duration === '1y'){ d.setFullYear(d.getFullYear()+1); }
    else { return '—'; }
    return d.toLocaleDateString('pt-BR');
}

document.getElementById('modalNovoUsuario')?.addEventListener('show.bs.modal', function(){
    // Resetar o formulário para iniciar limpo
    const form = this.querySelector('form');
    form?.reset();

    const sel = this.querySelector('select[name="lic_duracao"]');
    const prev = this.querySelector('#createLicExpPreview');
    const refresh = ()=>{ if(prev) prev.textContent = 'Expiração: ' + _computeExpDate(sel?.value||''); };
    sel?.addEventListener('change', refresh);
    refresh();
    // CEP auto-preenchimento via ViaCEP
    const cepInput = document.getElementById('novoCEP');
    const msgEl = document.getElementById('novoCEPMsg');
    const enderecoEl = document.getElementById('novoEndereco');
    const cidadeEl = document.getElementById('novoCidade');
    const estadoEl = document.getElementById('novoEstado');
    const telEl = document.getElementById('novoTelefone');
    // Máscaras
    function maskPhone(el){
        let v = (el.value||'').replace(/\D/g,'').slice(0,11);
        if(v.length <= 10){
            el.value = v.replace(/(\d{2})(\d{4})(\d{0,4})/, function(_,a,b,c){ return '('+a+') '+b+(c?('-'+c):''); });
        } else {
            el.value = v.replace(/(\d{2})(\d{5})(\d{0,4})/, function(_,a,b,c){ return '('+a+') '+b+(c?('-'+c):''); });
        }
    }
    function maskCEP(el){
        let v = (el.value||'').replace(/\D/g,'').slice(0,8);
        el.value = v.replace(/(\d{5})(\d{0,3})/, function(_,a,b){ return b? a+'-'+b : a; });
    }
    function maskUF(el){ el.value = (el.value||'').toUpperCase().slice(0,2).replace(/[^A-Z]/g,''); }
    function toTitleCasePTBR(str){
        const small = new Set(['de','da','do','das','dos','e']);
        return String(str||'')
            .toLowerCase()
            .split(/\s+/)
            .map((w,i)=>{
                return (i>0 && small.has(w)) ? w : w.split('-').map(p=>p.charAt(0).toUpperCase()+p.slice(1)).join('-');
            })
            .join(' ');
    }
    // Evitar múltiplos binds ao reabrir modal
    if (!this.dataset.bound) {
        telEl?.addEventListener('input', ()=>maskPhone(telEl));
        cepInput?.addEventListener('input', ()=>maskCEP(cepInput));
        estadoEl?.addEventListener('input', ()=>maskUF(estadoEl));
        // Title-case em blur
        cidadeEl?.addEventListener('blur', function(){ this.value = toTitleCasePTBR(this.value); });
        enderecoEl?.addEventListener('blur', function(){ this.value = toTitleCasePTBR(this.value); });
        // CEP busca
        cepInput?.addEventListener('blur', ()=>buscarCEP(cepInput.value));
        this.dataset.bound = '1';
    }

    // Limpar mensagens e campos de endereço ao abrir
    msgEl?.classList.add('d-none');
    function limpaEndereco(){ enderecoEl.value=''; cidadeEl.value=''; estadoEl.value=''; }
    limpaEndereco();

    function buscarCEP(cep){
        const c = (cep||'').replace(/\D/g,'');
        if(c.length !== 8){ limpaEndereco(); return; }
        fetch('https://viacep.com.br/ws/'+c+'/json/')
            .then(r=>r.json())
            .then(data=>{
                if(data.erro){ msgEl?.classList.remove('d-none'); limpaEndereco(); return; }
                msgEl?.classList.add('d-none');
                enderecoEl.value = [data.logradouro, data.bairro].filter(Boolean).join(', ');
                cidadeEl.value = data.localidade || '';
                estadoEl.value = (data.uf || '').toUpperCase();
            })
            .catch(()=>{ msgEl?.classList.remove('d-none'); limpaEndereco(); });
    }
});

document.getElementById('modalEditarUsuario')?.addEventListener('shown.bs.modal', function(){
    const sel = document.getElementById('editLicDuracao');
    const prev = document.getElementById('editLicExpPreview');
    const refresh = ()=>{ if(prev) prev.textContent = 'Expiração: ' + _computeExpDate(sel?.value||''); };
    sel?.addEventListener('change', refresh);
    // Máscaras
    const telEl = document.getElementById('editTelefone');
    const cepEl = document.getElementById('editCEP');
    const ufEl = document.getElementById('editEstado');
    function maskPhone(el){
        let v = (el.value||'').replace(/\D/g,'').slice(0,11);
        if(v.length <= 10){
            el.value = v.replace(/(\d{2})(\d{4})(\d{0,4})/, function(_,a,b,c){ return '('+a+') '+b+(c?('-'+c):''); });
        } else {
            el.value = v.replace(/(\d{2})(\d{5})(\d{0,4})/, function(_,a,b,c){ return '('+a+') '+b+(c?('-'+c):''); });
        }
    }
    function maskCEP(el){
        let v = (el.value||'').replace(/\D/g,'').slice(0,8);
        el.value = v.replace(/(\d{5})(\d{0,3})/, function(_,a,b){ return b? a+'-'+b : a; });
    }
    function maskUF(el){ el.value = (el.value||'').toUpperCase().slice(0,2).replace(/[^A-Z]/g,''); }
    function toTitleCasePTBR(str){
        const small = new Set(['de','da','do','das','dos','e']);
        return String(str||'')
            .toLowerCase()
            .split(/\s+/)
            .map((w,i)=>{
                return (i>0 && small.has(w)) ? w : w.split('-').map(p=>p.charAt(0).toUpperCase()+p.slice(1)).join('-');
            })
            .join(' ');
    }
    telEl?.addEventListener('input', ()=>maskPhone(telEl));
    cepEl?.addEventListener('input', ()=>maskCEP(cepEl));
    ufEl?.addEventListener('input', ()=>maskUF(ufEl));
    // Title-case em blur
    document.getElementById('editCidade')?.addEventListener('blur', function(){ this.value = toTitleCasePTBR(this.value); });
    document.getElementById('editEndereco')?.addEventListener('blur', function(){ this.value = toTitleCasePTBR(this.value); });
});

// Busca cliente-side por email e nome
document.getElementById('searchUsuario')?.addEventListener('input', function(){
    const term = (this.value || '').trim().toLowerCase();
    const rows = document.querySelectorAll('table tbody tr');
    rows.forEach(row => {
        const email = (row.querySelector('.col-email')?.textContent || '').toLowerCase();
        const nome = (row.querySelector('.col-nome')?.textContent || '').toLowerCase();
        const match = !term || email.includes(term) || nome.includes(term);
        row.style.display = match ? '' : 'none';
    });
});

// Inicializar tooltips com classes customizadas
document.addEventListener('DOMContentLoaded', function(){
    const triggers = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    triggers.forEach(el => {
        const cls = el.getAttribute('data-tooltip-class') || '';
        try { new bootstrap.Tooltip(el, { customClass: cls, placement: 'top' }); } catch (e) {}
    });
});
</script>
