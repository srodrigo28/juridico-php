<?php
/** URL PATCH C:\xampp\htdocs\www\v2\views\processos\index.php
 * /processos/index.php
 * Tela principal do módulo de processos.
 * Mantém o PHP + HTML, e carrega JS/CSS externos.
 */

// =====================================================
// 1) DADOS PARA O FRONT (clientes e tribunais)
// =====================================================
$stmt_clientes = $pdo->prepare("SELECT id, nome FROM clientes WHERE usuario_id = ? AND status = 'ativo' ORDER BY nome");
$stmt_clientes->execute([$_SESSION['user_id']]);
$lista_clientes = $stmt_clientes->fetchAll();

$calculadora = new CalculadoraDatas($pdo);
$tribunais = $calculadora->obterTribunais();

// =====================================================
// 2) TOTAL DE PROCESSOS (badge)
// =====================================================
$stmt_total_proc = $pdo->prepare("SELECT COUNT(*) FROM processos WHERE usuario_id = ?");
$stmt_total_proc->execute([$_SESSION['user_id']]);
$total_processos = (int)$stmt_total_proc->fetchColumn();

// =====================================================
// 3) LISTA DE PROCESSOS (tabela)
// =====================================================
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

// =====================================================
// 4) PRAZOS URGENTES (card)
// =====================================================
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

/**
 * FIM /processos/index.php 
*/

?>


<!-- =====================================================
     Header da página
     ===================================================== -->
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

<!-- =====================================================
     Lista de Processos
     ===================================================== -->
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">
      Meus Processos <span class="text-muted" id="tituloContagemProc">(<?= $total_processos ?>)</span>
    </h5>

    <div class="d-flex align-items-center gap-2" style="min-width:280px">
      <input type="text" class="form-control form-control-sm" id="buscarProcessoCliente" placeholder="Filtrar por cliente...">
      <span class="badge bg-primary">Total: <?= $total_processos ?></span>
      <?php if (!empty($processos ?? [])): ?>
        <span class="badge bg-secondary" id="badgeResultadosProc">Exibindo: <?= count($processos ?? []) ?></span>
      <?php endif; ?>
    </div>
  </div>

  <div class="card-body">
    <?php if (empty($processos)): ?>
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
                <?php if ((int)$proc['prazos_urgentes'] > 0): ?>
                  <span class="badge bg-danger ms-2"><?= (int)$proc['prazos_urgentes'] ?> urgente(s)</span>
                <?php endif; ?>
              </td>

              <td><?= sanitizar($proc['cliente_nome'] ?: 'N/A') ?></td>
              <td><?= sanitizar($proc['tribunal']) ?></td>
              <td><?= (int)$proc['total_prazos'] ?> prazo(s)</td>

              <td>
                <span class="badge bg-<?= obterClasseStatus($proc['status']) ?>">
                  <?= ucfirst(str_replace('_', ' ', $proc['status'])) ?>
                </span>
              </td>

              <td>
                <button type="button" class="btn btn-sm btn-info" onclick="visualizarProcesso(<?= (int)$proc['id'] ?>)" title="Visualizar">
                  <i class="bi bi-eye"></i>
                </button>

                <button type="button" class="btn btn-sm btn-primary" title="Adicionar movimentação"
                        data-bs-toggle="modal" data-bs-target="#modalNovaMovimentacao"
                        data-proc-id="<?= (int)$proc['id'] ?>" data-proc-numero="<?= addslashes($proc['numero_processo']) ?>">
                  <i class="bi bi-plus-lg"></i>
                </button>

                <?php
                  $wa_raw = $proc['cliente_celular'] ?? ($proc['cliente_whatsapp'] ?? ($proc['cliente_telefone'] ?? ''));
                  $wa_digits = preg_replace('/\D+/', '', $wa_raw);
                  if ($wa_digits && substr($wa_digits, 0, 2) !== '55') { $wa_digits = '55' . $wa_digits; }
                  $wa_text = urlencode('Olá, Precifex ADV traz informações sobre o processo ' . ($proc['numero_processo'] ?? ''));
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

<!-- =====================================================
     Prazos Urgentes
     ===================================================== -->
<?php if (!empty($prazos_urgentes)): ?>
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
              <strong>Prazo:</strong> <?= (int)$prazo['prazo_dias'] ?> dias <?= sanitizar($prazo['tipo_contagem']) ?> |
              <strong>Data Final:</strong> <?= formatarData($prazo['data_final']) ?>
            </p>
          </div>

          <div class="ms-3">
            <button class="btn btn-sm btn-success" onclick="marcarComoCumprido(<?= (int)$prazo['id'] ?>)">
              <i class="bi bi-check"></i> Cumprido
            </button>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- =====================================================
     Modal Visualizar/Editar Processo (mantém como está)
     ===================================================== -->
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
          <div id="detalhesProcesso"><!-- via AJAX --></div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-danger me-auto" id="btnExcluirProcesso" style="display:none"
                onclick="excluirProcesso(document.getElementById('processoIdEdicao').value)">
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

<!-- =====================================================
     Modal Novo Processo (COMPLETO)
     IMPORTANTÍSSIMO: não usar só modal-body
     ===================================================== -->
<div class="modal fade" id="modalNovoProcesso" tabindex="-1" aria-labelledby="modalNovoProcessoLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="modalNovoProcessoLabel">Novo Processo com Prazos</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>

      <div class="modal-body">
        <form id="formNovoProcesso" enctype="multipart/form-data">

          <ul class="nav nav-tabs" id="tabsNovoProcesso" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="tab-dados-tab" data-bs-toggle="tab" data-bs-target="#tab-dados" type="button" role="tab">
                <i class="bi bi-file-earmark-text"></i> Dados
              </button>
            </li>

            <li class="nav-item" role="presentation">
              <button class="nav-link" id="tab-uploads-tab" data-bs-toggle="tab" data-bs-target="#tab-uploads" type="button" role="tab">
                <i class="bi bi-paperclip"></i> Uploads
              </button>
            </li>

            <li class="nav-item" role="presentation">
              <button class="nav-link" id="tab-fin-tab" data-bs-toggle="tab" data-bs-target="#tab-fin" type="button" role="tab">
                <i class="bi bi-cash-coin"></i> Financeiro
              </button>
            </li>
          </ul>

          <div class="tab-content pt-3">

            <!-- TAB Dados -->
            <div class="tab-pane fade show active" id="tab-dados" role="tabpanel" aria-labelledby="tab-dados-tab">
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
                    <input type="text" class="form-control" id="clienteSearchNovo"
                           data-cliente-search data-target-hidden="#clienteIdNovo"
                           data-suggestions="#clienteSugestoesNovo"
                           placeholder="Buscar cliente por nome">
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
            </div>

            <!-- TAB Uploads -->
            <div class="tab-pane fade" id="tab-uploads" role="tabpanel" aria-labelledby="tab-uploads-tab">
              <div class="row mb-3">
                <div class="col-12">
                  <label class="form-label">Uploads de Arquivos</label>

                  <div id="uploadsContainer">
                    <div class="input-group mb-2 upload-group">
                      <input type="text" class="form-control" name="upload_titulo[]" placeholder="Título do arquivo" required>
                      <input type="file" class="form-control upload-file" name="uploads[]"
                        accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx" multiple>
                      <button type="button" class="btn btn-outline-danger" onclick="this.parentNode.remove()">Remover</button>
                    </div>
                  </div>

                  <button type="button" class="btn btn-sm btn-outline-primary" onclick="adicionarUpload()">
                    <i class="bi bi-plus"></i> Adicionar outro arquivo
                  </button>

                  <div class="form-text">Tipos permitidos: pdf, png, jpg, doc, docx, xls, xlsx</div>
                </div>
              </div>
            </div>

            <!-- TAB Financeiro -->
            <div class="tab-pane fade" id="tab-fin" role="tabpanel" aria-labelledby="tab-fin-tab">
              <div class="alert alert-light border d-flex align-items-center gap-2">
                <i class="bi bi-info-circle"></i>
                <div class="small text-muted m-0">Campos do financeiro (UI). Depois validamos e salvamos no banco.</div>
              </div>

              <div class="row mb-3">
                <div class="col-md-4">
                  <label class="form-label">Valor da Causa</label>
                  <div class="input-group">
                    <span class="input-group-text">R$</span>
                    <input type="text" class="form-control" name="valor_causa" placeholder="0,00">
                  </div>
                </div>

                <div class="col-md-4">
                  <label class="form-label">Valor do Contrato (Honorários)</label>
                  <div class="input-group">
                    <span class="input-group-text">R$</span>
                    <input type="text" class="form-control" name="fin_valor_total" id="finValorTotal" placeholder="0,00">
                  </div>
                </div>

                <div class="col-md-4">
                  <label class="form-label">Tipo de Pagamento</label>
                  <select class="form-select" name="fin_tipo_pagamento" id="finTipoPagamento">
                    <option value="avista">À vista</option>
                    <option value="entrada">Com entrada</option>
                    <option value="parcelado">Parcelado</option>
                  </select>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-md-4">
                  <label class="form-label">Data do Primeiro Vencimento</label>
                  <input type="date" class="form-control" name="fin_primeiro_vencimento" id="finPrimeiroVencimento">
                </div>

                <div class="col-md-4" id="finResumoParcelaWrap" style="display:none;">
                  <label class="form-label">Valor por Parcela (calculado)</label>
                  <div class="input-group">
                    <span class="input-group-text">R$</span>
                    <input type="text" class="form-control" id="finValorParcelaCalculado" readonly>
                  </div>
                  <div class="form-text">Calculado automaticamente com base nos valores informados.</div>
                </div>

                <div class="col-md-4">
                  <label class="form-label">Resumo</label>
                  <div class="border rounded p-2 bg-light" id="finResumoBox" style="min-height:42px;">
                    <span class="text-muted small">Informe os valores para ver o resumo.</span>
                  </div>
                </div>
              </div>

              <!-- Entrada (só aparece no modo "entrada") -->
              <div class="row mb-3" id="finBlocoEntrada" style="display:none;">
                <div class="col-md-4">
                  <label class="form-label">Valor de Entrada</label>
                  <div class="input-group">
                    <span class="input-group-text">R$</span>
                    <input type="text" class="form-control" name="fin_valor_entrada" id="finValorEntrada" placeholder="0,00">
                  </div>
                  <div class="form-text">A entrada será abatida do valor total antes de parcelar.</div>
                </div>
                <div class="col-md-8">
                  <div class="form-text mt-2">Use “Com entrada” quando parte é paga agora e o restante depois.</div>
                </div>
              </div>

              <!-- Parcelas (aparece em "entrada" e "parcelado") -->
              <div class="row mb-3" id="finBlocoParcelas" style="display:none;">
                <div class="col-md-4">
                  <label class="form-label">Número de Parcelas</label>
                  <select class="form-select" name="fin_num_parcelas" id="finNumParcelas">
                    <?php for ($i=1; $i<=10; $i++): ?>
                      <option value="<?= $i ?>"><?= $i ?>x</option>
                    <?php endfor; ?>
                  </select>
                </div>

                <div class="col-md-4">
                  <label class="form-label">Dia do Vencimento (mensal)</label>
                  <select class="form-select" name="fin_dia_vencimento" id="finDiaVencimento">
                    <option value="">(automático pela data)</option>
                    <?php for ($d=1; $d<=28; $d++): ?>
                      <option value="<?= $d ?>"><?= $d ?></option>
                    <?php endfor; ?>
                  </select>
                  <div class="form-text">Até 28 evita problema em fevereiro.</div>
                </div>

                <div class="col-md-4">
                  <label class="form-label">Observação Financeira</label>
                  <input type="text" class="form-control" name="fin_obs" placeholder="Ex.: via Pix, boleto, etc.">
                </div>
              </div>
            </div>


          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" id="btnSalvarNovoProcesso" class="btn btn-primary">Salvar Processo</button>
      </div>

    </div>
  </div>
</div>


<?php
// =====================================================
// 5) Modais/trechos de eventos (seu include existente)
// =====================================================
include __DIR__ . '/eventos.php';
?>

<!-- =====================================================
     Dados PHP -> JS (um bloco pequeno e seguro)
     O resto fica no /processos/index.js
     ===================================================== -->
<script>
  window.ProcessosData = {
    TRIBUNAIS: <?= json_encode(array_merge(['NACIONAL'], array_map(fn($t) => $t['abrangencia'], $tribunais)), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>,
    CLIENTES_ATIVOS: <?= json_encode(array_map(fn($c) => ['id' => (int)$c['id'], 'nome' => $c['nome']], $lista_clientes), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>
  };
</script>

<!-- =====================================================
     CSS / JS do módulo Processos
     Caminhos estáveis para qualquer aba
     ===================================================== -->
<?php
$BASE = '/www/v2';
?>

<link rel="stylesheet" href="<?= $BASE ?>/views/processos/styles.css?v=<?= time() ?>">

<script src="http://localhost/www/v2/views/processos/index.js"></script>

<script src="<?= $BASE ?>/views/processos/js/core.js?v=<?= time() ?>"></script>
<script src="<?= $BASE ?>/views/processos/js/financeiro.js?v=<?= time() ?>"></script>
<script src="<?= $BASE ?>/views/processos/js/uploads.js?v=<?= time() ?>"></script>
<script src="<?= $BASE ?>/views/processos/js/tabela.js?v=<?= time() ?>"></script>
<script src="<?= $BASE ?>/views/processos/js/modal.js?v=<?= time() ?>"></script>